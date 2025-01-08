<?php
/**
 * OpenSim class
 * 
 * This class is responsible for defining constants and loading all classes needed by all scripts.
 * 
 * Classes needed only by some scripts are handled by themselves.
 * 
 * @package magicoli/opensim-helpers
 */

class OpenSim {
    private static $tmp_dir;
    private static $user_notices = array();

    public function __construct() {
    }

    public function init() {
        $this->constants();
        $this->includes();
    }

    public function constants() {
        if( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', dirname( __FILE__ ) . '/' );
        }
        define( 'OSHELPERS', true );
        define( 'OSHELPERS_DIR', self::trailingslashit( dirname( __DIR__ ) ) );
        define( 'OSHELPERS_URL', ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]" );
    }

    public function includes() {
    }

    private static function set_version() {
        if( file_exists( OSHELPERS_DIR . '.version' ) ) {
            $version = file_get_contents( '.version' );
        }
        
        // Get version from .git/HEAD file.
        if( file_exists( OSHELPERS_DIR . '.git/HEAD' ) ) {
            $version .= ' (git ' . trim(preg_replace('%.*/%', '', file_get_contents( '.git/HEAD' ) ) ) . ')';
        }
        $this->version = $version;
        return $version;
    }

    public static function get_version() {
        if( isset( self::$version ) ) {
            return self::$version;
        }
        return self::set_version();
    }

    public static function get_temp_dir( $dir = false ) {
        if( isset( self::$tmp_dir ) ) {
            return self::$tmp_dir;
        }

        if ( ! empty( $dir ) && is_dir( $dir ) && is_writable( $dir ) ) {
            $dir = realpath( $dir );
        } else {
            $dirs = array(
                sys_get_temp_dir(),
                dirname( $_SERVER['DOCUMENT_ROOT'] )  . '/tmp',
                ini_get( 'upload_tmp_dir' ),
                '/var/tmp',
                '~/tmp',
            );
            foreach( $dirs as $key => $dir ) {
                if( is_dir( $dir ) && is_writable( $dir ) ) {
                    $dir = realpath( $dir );
                    break;
                }
            }
        }
        if ( ! $dir ) {
            throw new Error( 'No writable temporary directory found.' );
        }
        
        self::$tmp_dir = $dir;
        return $dir;
    }

    public static function parse_args( $args, $defaults ) {
        if( is_object( $args ) ) {
            $args = get_object_vars( $args );
        } elseif( is_array( $args ) ) {
            $args = $args;
        } else {
            parse_str( $args, $args );
        }
        return array_merge( $defaults, $args );
    }

	public static function connectionstring_to_array( $connectionstring ) {
		$parts = explode( ';', $connectionstring );
		$creds = array();
		foreach ( $parts as $part ) {
			$pair              = explode( '=', $part );
			$creds[ $pair[0] ] = $pair[1] ?? '';
		}
        if( preg_match( '/:[0-9]+$/', $creds['Data Source'] ) ) {
            $host = explode( ':', $creds['Data Source'] );
            $creds['Data Source'] = $host[0];
            $creds['Port'] = empty( $host[1] || $host[1] == 3306 ) ? null : $creds['Port'];
        }
        $result = array(
            'host' => $creds['Data Source'],
            'port' => $creds['Port'] ?? null,
            'name' => $creds['Database'],
            'user' => $creds['User ID'],
            'pass' => $creds['Password'],
        );
		return $result;
	}

    // Clone of WP trailingslashit function
    public static function trailingslashit( $value ) {
        return self::untrailingslashit( $value ) . '/';
    }

    // Clone of WP untrailingslashit function
    public static function untrailingslashit( $value ) {
        return rtrim( $value, '/\\' );
    }

    public static function notify_error( $e, $message = '', $type = 'warning' ) {
        if( is_string ( $e ) ) {
            $message = $e ?? _('Empty message');
            self::notify( $message, $type );
        } else if ( is_callable( $e, 'getTrace' ) ) {
            $trace = $e->getTrace();
            $message = empty( $message ) ? $trace[0]['function'] : '';
            $message = ( empty( $message ) ? '' : $message . ': ' ) . $e->getMessage();
        } else {
            self::notify( _( 'Unknown error ' ), $type );
        }
    }

    public static function notify( $message, $type = 'info' ) {
        $key = md5( $message ); // Make sure we don't have duplicates
        self::$user_notices[$key] = array(
            'message' => $message,
            'type' => $type,
        );
    }
    
    public static function get_notices() {
        $html = '';
        foreach( self::$user_notices as $key => $notice ) {
            $type = $notice['type'] ?? 'info';
            $html .= sprintf(
                '<div class="alert alert-%s">%s</div>',
                $type,
                $notice['message']
            );
        }
        return $html;
    }

    public static function validate_error_type( $type, $fallback = 'light' ) {
        $type = in_array( $type, array(
            'primary',
            'secondary',
            'success',
            'danger',
            'warning',
            'info',
            'light',
            'dark',
        ) ) ? $type : $fallback;
        return $type;
    }

    public static function validate_error ( $error, $type = 'light' ) {
        if( is_string( $error )) {
            $error = array( 'message', $error );
        }
        $error = self::parse_args( $error, array(
            'message' => _('Error'),
            'type' => $type,
        ));
        $error['type'] = self::validate_error_type( $error['type'], $type );
        return $error;
    }

    public static function error_html( $error, $type = null ) {
        $error = self::validate_error( $error, $type );
        $html = sprintf(
            '<div class="text-%s">%s</div>',
            $error['type'],
            $error['message'],
        );
        return $html;
    }
}

$OpenSim = new OpenSim();
$OpenSim->init();

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

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class OpenSim {
    private static $tmp_dir;
    private static $user_notices = array();
    private static $version;
    private static $scripts;

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
        define( 'OSHELPERS_URL', self::get_helpers_url() );
        // ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]" );

    }

    public function get_helpers_url() {
        $helpers_path = dirname( __DIR__ );
        $url_path = self::trailingslashit( str_replace( $_SERVER['DOCUMENT_ROOT'], '', $helpers_path ) );

        $parsed = array(
            'scheme' => isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http',
            'host' => $_SERVER['HTTP_HOST'],
        );
        $url = self::build_url( $parsed ) . ltrim( $url_path );

        return $url;
    }

    public function includes() {
    }

    private static function get_version() {
        if( self::$version ) {
            return self::$version;
        }
        if( file_exists( OSHELPERS_DIR . '.version' ) ) {
            $version = file_get_contents( '.version' );
        } else {
            $version = '0.0.0';
        }

        self::$version = $version;
        return $version;
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
        if( empty( $defaults ) ) {
            $defaults = array();
        }
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

    /**
     * Notify user of an error, log it and display it in the admin area
     * 
     * @param mixed $error (string) Error message or (Throwable) Exception
     * @param string $type Error severity: 'info', 'warning', 'danger'
     * @return void
     */
    public static function notify_error( $error, $type = 'warning' ) {
        if ( $error instanceof Throwable ) {
            $e = $error;
            $message = $e->getMessage();
            // $message = empty( $message ) ? ( $origin ?? '' ) : '';
            // $message = ( empty( $message ) ? '' : $message . ': ' ) . $e->getMessage();
            $trace = $e->getTrace();
            $origin = $trace[0];
        } else if( is_string ( $error ) ) {
            $message = $error;
        } else {
            error_log( 'Unidentified error type: ' . gettype( $error ) . ' ' . print_r( $e, true ) );
            $message = _( 'Unknown error, see log for details' );
        }

        self::notify( $message, $type );
        if( ! empty( $origin['class'] ) ) {
            $message = $origin['class'] . '::' . $origin['function'] . '(): ' . $message;
        } else {
            $message = $origin['function'] . '(): ' . $message;
        }
        error_log( '[' . strtoupper( $type ) . '] ' . $message );
    }

    public static function notify( $message, $type = 'info' ) {
        $key = md5( $key . $message ); // Make sure we don't have duplicates
        self::$user_notices[$key] = array(
            'message' => $message,
            'type' => $type,
        );
    }
    
    public static function get_notices() {
        $html = '';
        foreach( self::$user_notices as $key => $notice ) {
            $type = $notice['type'] ?? 'info';
            switch( $type ) {
                case 'task-checked':
                    $html .= sprintf(
                        '<div class="form-check %s">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" checked readonly>
                            <label class="form-check-label" for="flexCheckChecked">
                                %s
                            </label>
                        </div>',
                        $type,
                        $notice['message']
                    );
                    break;

                    default:
                    $html .= sprintf(
                        '<div class="alert alert-%s my-4">%s</div>',
                        $type,
                        $notice['message']
                    );
            }
        }
        return $html;
    }

    public static function validate_error_type( $type, $fallback = 'light' ) {
        $given = $type;
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

    /**
     * Log message to error_log, adding calling [CLASS] and function before message, and severity if given
     */
    public static function log( $message, $severity = none ) {
        $caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
        $caller = $caller[1];
        $class = $caller['class'] ?? '';
        $function = $caller['function'] ?? '';
        $message = sprintf(
            '[%s%s%s] %s%s',
            $class,
            empty( $class ) ? '' : '::',
            $function,
            empty( $severity ) ? '' : strtoupper( $severity ) . ' ',
            $message
        );
        // Add severity if given
        error_log( $message );
    }

    public static function callback_name_string( $callback ) {
        if( is_string( $callback ) ) {
            return $callback;
        }
        if( is_array( $callback ) && is_object( $callback[0] ) ) {
            $callback_name = get_class($callback[0]) . '::' . $callback[1];
            return $callback_name;
        }
        if( is_array( $callback ) ) {
            return $callback[0] . '::' . $callback[1];
        }
        if( is_object( $callback ) ) {
            return get_class( $callback );
        }
        return 'Unknown';
    }

    /**
     * Basic function to replace WP enqueue_script when not in WP environment.
     * Add the script to a private property that will be used with another method to output all scripts.
     * Use OSHELPERS_URL constant to build the URL unless it's already full.
     * Use self::get_version() to define the version of the script unless it is already defined.
     */
    public static function enqueue_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
        if( ! file_exists( $src ) ) {
            error_log( __FUNCTION__ . ' file not found: ' . $src );
            return false;
        }

        $handle = preg_match( '/^oshelpers-/', $handle ) ? $handle : 'oshelpers-' . $handle;

        self::$scripts = self::$scripts ?? array( 'head' => array(), 'footer' => array() );
        if( strpos( $src, '://' ) === false ) {
            $src = OSHELPERS_URL . ltrim( $src, '/' );
        }
        $src = self::add_query_args( $src, array( 'ver' => self::get_version() ) );
        $section = $in_footer ? 'footer' : 'head';
        self::$scripts[$section][$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver ?? self::get_version(),
            'in_footer' => $in_footer,
        );
    }

    public static function build_url( $parsed ) {
        if( empty( $parsed['host'] ) ) {
            $url = '';
        } else {
            $url = ( $parsed['scheme'] ?? 'https' ) . '://' . $parsed['host'];
        }
        $url .= $parsed['path'] ?? '';
        if( ! empty( $parsed['query'] ) ) {
            $url .= '?' . $parsed['query'];
        }
        return $url;
    }

    public static function add_query_args( $url, $args ) {
        $parsed = parse_url( $url );
        $query = $parsed['query'] ?? '';
        $query = self::parse_args( $query, array() );
        $query = array_merge( $query, $args );
        $query = http_build_query( $query );
        $parsed['query'] = $query;
        $url = self::build_url( $parsed );
        return $url;
    }

    public static function get_scripts( $section, $echo = false ) {
        if( ! isset( self::$scripts[$section] ) ) {
            return '';
        }
        $html = '';
        if(empty( self::$scripts[$section] ) ) {
            return '';
        }
        if( $section === 'head' ) {
            $template = '<link id="%s" rel="stylesheet" href="%s" type="text/css" %s>';
        } else {
            $template = '<script id="%s" src="%s" type="text/javascript"></script>';
        }

        $scripts = self::$scripts[$section];
        foreach( $scripts as $handle => $script ) {
            // error_log( 'Script: ' . print_r( $script, true ) );
            $html .= sprintf(
                $template,
                $handle,
                $script['src'],
                empty( $script['ver'] ) ? '' : 'version="' . $script['ver'] . '"'
            );
        }
        if( $echo ) {
            echo $html;
        }
        // error_log( $html );
        return $html;
    }
}

$OpenSim = new OpenSim();
$OpenSim->init();

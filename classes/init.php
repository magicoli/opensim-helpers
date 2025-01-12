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
    private static $version_slug;
    private static $scripts;
    private static $styles;
    private static $is_dev;

    public function __construct() {
        // Check if domain name starts with "dev." or usual wp debug constants are set
        self::$is_dev = ( strpos( $_SERVER['HTTP_HOST'], 'dev.' ) === 0 ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG );
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

    public function includes() {
        require_once( OSHELPERS_DIR . 'classes/class-locale.php' );
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

    public static function get_version( $sanitized = false ) {
        if( $sanitized && self::$version_slug ) {
            return self::$version_slug;
        } else if ( ! $sanitized && self::$version ) {
            return self::$version;
        }
        if( file_exists( OSHELPERS_DIR . '.version' ) ) {
            $version = file_get_contents( '.version' );
        } else {
            $version = '0.0.0';
        }
        if( file_exists( '.git/HEAD' ) ) {
            $hash = trim( file_get_contents( '.git/HEAD' ) );
            $hash = trim( preg_replace( '+.*[:/]+', '', $hash ) );
            if( !empty( $hash ) && file_exists( '.git/refs/heads/' . $hash ) ) {
                $hash = substr( file_get_contents( '.git/refs/heads/' . $hash ), 0, 7 ) . " ($hash)";
            } else {
                $hash = substr( $hash, 0, 7 );
                $hash .= ' (detached)';
            }

            $version .= empty( $hash ) ? ' git ' : ' git ' . $hash;
            self::$is_dev = ( empty( $hash ) ) ? self::$is_dev : true;
        }

        self::$version = $version;
        self::$version_slug = self::sanitize_slug( $version );
        if( $sanitized && self::$version_slug ) {
            return self::$version_slug;
        }
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
        // Initialize the prefix before error type check
        $prefix = '[' . strtoupper( $type ) . '] ';

        // Retrieve the calling method's information
        if ( $error instanceof Throwable ) {
            $message = $error->getMessage();
        } elseif( is_string($error) ) {
            $message = $error;
        } else {
            $message = _('Unknown error, see log for details');
            error_log( $prefix . 'Unidentified error type: ' . gettype( $error ) . ' ' . print_r( $error, true ) );
        }
        
        self::notify( $message, $type );

        $message = strip_tags( $message );
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if( ! empty( $trace[1] ) ) {
            $class = $trace[1]['class'] ?? '';
            $function = $trace[1]['function'] ?? '';
        }
        if( ! empty( trim ( $class . $function ) ) ) {
            $prefix .= '(' . ( empty( $class ) ? '' : $class . '::' ) . $function . ') ';
        }
        error_log( $prefix . $message );
    }

    public static function notify( $message, $type = 'info' ) {
        $key = md5( $type . $message ); // Make sure we don't have duplicates
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
        $handle = ( rtrim ( $handle, '-css' ) ) . '-js';

        self::$scripts = self::$scripts ?? array( 'head' => array(), 'footer' => array() );
        if( strpos( $src, '://' ) === false ) {
            $src = OSHELPERS_URL . ltrim( $src, '/' );
        }
        $ver = empty( $ver ) ? self::get_version( true ) : self::sanitize_slug( $ver );
        $src = self::add_query_args( $src, array( 'ver' => $ver ) );

        $section = $in_footer ? 'footer' : 'head';
        self::$scripts[$section][$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver ?? self::get_version( true ),
            'in_footer' => $in_footer,
        );
    }

    /**
     * Return or output the html for scripts in the head or footer
     * 
     * @param string $section 'head' or 'footer'
     * @param bool $echo Output the html if true, return it if false
     */
    public static function get_scripts( $section, $echo = false ) {
        if( ! isset( self::$scripts[$section] ) ) {
            return '';
        }
        $html = '';
        if(empty( self::$scripts[$section] ) ) {
            return '';
        }

        $template = '<script id="%s" src="%s" type="text/javascript"></script>';

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

    public static function enqueue_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
        if( ! file_exists( $src ) ) {
            error_log( __FUNCTION__ . ' file not found: ' . $src );
            return false;
        }

        $handle = preg_match( '/^oshelpers-/', $handle ) ? $handle : 'oshelpers-' . $handle;
        $handle = ( rtrim ( $handle, '-css' ) ) . '-css';

        self::$styles = self::$styles ?? array( 'head' => array(), 'footer' => array() );
        if( strpos( $src, '://' ) === false ) {
            $src = OSHELPERS_URL . ltrim( $src, '/' );
        }
        $ver = empty( $ver ) ? self::get_version( true ) : self::sanitize_slug( $ver );
        $src = self::add_query_args( $src, array( 'ver' => $ver ) );

        self::$styles['head'][$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
        );
    }

    public static function get_styles( $echo = false ) {
        if( ! isset( self::$styles['head'] ) ) {
            return '';
        }
        $html = '';
        if(empty( self::$styles['head'] ) ) {
            return '';
        }

        $template = '<link id="%s" rel="stylesheet" href="%s" type="text/css" media="%s">';

        $styles = self::$styles['head'];
        foreach( $styles as $handle => $style ) {
            $html .= sprintf(
                $template,
                $handle,
                $style['src'],
                $style['media'],
            );
        }
        if( $echo ) {
            echo $html;
        }
        return $html;
    }

    public static function sanitize_slug( $string ) {
        if( empty( $string ) ) {
            return false;
        }
        
        $slug = $string;
        try {
            $slug = transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();", $slug );
            $slug = preg_replace('/[-\s]+/', '-', $slug );
        } catch ( Exception $e ) {
            error_log( 'Error sanitizing slug: ' . $e->getMessage() );
            $slug = $string;
        }
            
        return $slug;
    }

    public static function validate_condition( $condition ) {
        if( is_callable( $condition ) ) {
            return $condition();
        }
        if( is_bool( $condition ) ) {
            return $condition;
        }
        switch( $condition ) {
            case 'logged_in':
                return self::is_logged_in();
            case 'logged_out':
                return self::is_logged_out();
            default:
                return false;
        }
    }

    /**
     * Get user preferred language from browser settings.
     * 
     * @param bool $long Full locale string if true (en_US), language code otherwise (en)
     * @return string
     */
    public static function user_locale( $long = true ) {
        return $long ? OpenSim_Locale::locale() : OpenSim_Locale::lang();
    }

    public static function user_lang( $long = false ) {
        return $long ? OpenSim_Locale::locale() : OpenSim_Locale::lang();
    }

    /**
     * Return the actual language of the content if localization is setup
     */
    public static function content_lang( $long = false ) {
        // When localization is setup, we will return user language
        // For now, we return english.
        $lang = 'en_US';
        
        // return self::user_locale( $long );
        return $long ? $lang : substr( $lang, 0, 2 );
    }

    public static function is_logged_in() {
        // WP is not loaded so constants like COOKIEHASH are not available.
        // Any cookie matching wordpress_logged_in or wordpress_logged_in_*
        // is considered a valid login cookie.
        // If logged_in, use first part of cookie value as user_id
        foreach( $_COOKIE as $key => $value ) {
            if( preg_match( '/^wordpress_logged_in/', $key ) ) {
                $parts = explode( '|', $value );
                $_SESSION['user_id'] = $parts[0];
                error_log( 'Logged in user: ' . $_SESSION['user_id'] );
                error_log( 'user locale ' . self::user_locale( true ) );
                // error_log( 'Cookies: ' . print_r( $_COOKIE, true ) );
                break;
            }
        }

        return isset( $_SESSION['user_id'] );
    }

    public static function is_logged_out() {
        return ! self::is_logged_in();
    }

    public static function get_user_id() {
        return $_SESSION['user_id'] ?? false;
    }
}

$OpenSim = new OpenSim();
$OpenSim->init();

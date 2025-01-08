<?php
/**
 * OpenSimulator Helpers installationn script
 * 
 * This script will scan Robust configuration file to get your grid settings and generate the helpers configuration file.
 * 
 * It is only needed to run this tool once, after that you delete this install.php file.
 * 
 * @package		magicoli/opensim-helpers
**/

require_once( __DIR__ . '/classes/init.php' );
require_once( __DIR__ . '/classes/class-page.php' );
require_once( __DIR__ . '/classes/class-ini.php' );

class OpenSim_Install extends OpenSim_Page {
    private $ini_path;
    private $config_file;
    private $user_notices = array();
    private $errors = array();
    private $forms = array();
    private $ini;

    public function __construct() {
        if( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', dirname( __FILE__ ) . '/' );
        }

        $this->page_title = _('Helpers Installation');
        $this->register_forms();
        $this->process_forms();
        $this->generate_php_config();

        $this->content = $this->render_content();
    }

    private function generate_php_config() {
        $template = 'includes/config.example.php';
        if (!file_exists($template)) {
            OpenSim::notify(_('Template file not found.'), 'error');
            return false;
        }

        try {
            $php_template = file_get_contents($template);
        } catch (Error $e) {
            OpenSim::notify_error($e, 'Error reading template file');
            return false;
        }
        $config = $this->config;
        $robust_db = OpenSim::connectionstring_to_array($config['DatabaseService']['ConnectionString']);

        $registrars = array(
            'DATA_SRV_W4OSDev' => "http://dev.w4os.org/helpers/register.php",
            'DATA_SRV_2do' => 'http://2do.directory/helpers/register.php',
            'DATA_SRV_MISearch' => 'http://metaverseink.com/cgi-bin/register.py',
        );

        $console = array(
            'ConsoleUser' => $config['Network']['ConsoleUser'],
            'ConsolePass' => $config['Network']['ConsolePass'],
            'ConsolePort' => $config['Network']['ConsolePort'],
            'numeric' => 123456789,
            'boolean_string' => 'true',
        );
        
        // Define mapping between config array keys and template constants
        $mapping = array(
            'OPENSIM_GRID_NAME'   => $config['Const']['BaseURL'],
            'OPENSIM_LOGIN_URI'   => $config['Const']['BaseURL'] . ':' . $config['Const']['PublicPort'],
            'OPENSIM_MAIL_SENDER' => "no-reply@" . parse_url($config['Const']['BaseURL'], PHP_URL_HOST),
            'ROBUST_DB'           => $robust_db,
            'OPENSIM_DB'          => true, // Changed from string to boolean
            'OPENSIM_DB_HOST'     => $robust_db['host'],
            'OPENSIM_DB_PORT'     => $robust_db['port'] ?? null,
            'OPENSIM_DB_NAME'     => $robust_db['name'],
            'OPENSIM_DB_USER'     => $robust_db['user'],
            'OPENSIM_DB_PASS'     => $robust_db['pass'],
            'SEARCH_REGISTRARS'   => $registrars,
            'ROBUST_CONSOLE'     => $console,
            'CURRENCY_NAME'       => $config['LoginService']['Currency'],
            'CURRENCY_HELPER_URL' => $config['GridInfoService']['economy'],

            // Add more mappings as needed
        );

        // Replace placeholders in the template
        foreach ($mapping as $constant => $value) {
            $pattern = "/define\(\s*'{$constant}'\s*,\s*(?:array\s*\([^;]*?\)|'[^']*'|\"[^\"]*\"|[^)]+)\s*\);/s";

            if (is_array($value)) {
                $exported = var_export($value, true);
                // Remove quotes for numeric and boolean strings if necessary
                $exported = preg_replace("/'([0-9]+)'/", '$1', $exported);
                $exported = str_replace("'true'", 'true', $exported);
                $exported = str_replace("'false'", 'false', $exported);
                $replacement = "define( '{$constant}', {$exported} );";
            } else if( $value === null ) {
                $exported = "NULL";
                $replacement = "define( '{$constant}', {$exported} );";
            } else if (is_bool($value)) {
                $bool = $value ? 'true' : 'false';
                $replacement = "define( '{$constant}', {$bool} );";
            } else if (is_numeric($value)) {
                $replacement = "define( '{$constant}', {$value} );";
            } else {
                $replacement = "define( '{$constant}', '" . addslashes($value) . "' );";
            }
            $php_template = preg_replace($pattern, $replacement, $php_template);
        }

        // Write the updated config to config.php
        $config_file = 'includes/config.php';
        try {
            file_put_contents($config_file, $php_template);
        } catch (Error $e) {
            OpenSim::notify_error($e, 'Error writing configuration file');
            return false;
        }

        OpenSim::notify(_('Configuration file generated successfully.'), 'success');
        return true;
    }

    private function process_forms() {
        if( empty( $_POST ) ) {
            return;
        }
        $form_id = $_POST['form_id'] ?? null;
        if( empty( $form_id ) ) {
            error_log( __FUNCTION__ . ' ERROR: Missing form ID.' );
            return false;
        }
        $form = $this->forms[$form_id] ?? null;
        if( empty( $form ) ) {
            error_log( __FUNCTION__ . ' ERROR: Form empty.' );
            return false;
        }

        $callback = 'process_form_' . $form_id;
        if( is_callable( [ $this, $callback ] ) ) {
            return call_user_func( [ $this, $callback ] );
        } else {
            error_log( 'callback ' . $callback . ' not found.' );
            return false;
        }
    }

    private function process_form_config() {
        $count = 0;

        if( empty( $this->ini_path ) ) {
            $this->errors['ini_path'] = _('Please provide a valid path to Robust configuration file.');
            $count++;
        }
        if( ! file_exists( $this->ini_path ) ) {
            $this->errors['ini_path'] = _('File not found.');
            $count++;
        }
        if( empty( $this->config_file ) ) {
            $this->errors['config_file'] = _('Please provide a valid path to the configuration file.');
            $count++;
        }
        if( file_exists( $this->config_file ) ) {
            $this->errors['config_file'] = _('Configuration file already exists. It will be overwritten.');
        }

        if( $count == 0 ) {
            return $this->process_ini();
        }
    }
    
    private function register_form( $form_id, $fields ) {
        if( empty( $form_id ) || empty( $fields ) ) {
            error_log( __FUNCTION__ . ' ERROR: Missing form ID or fields.' );
            return false;
        }
        $this->forms[$form_id] = $fields;
    }

    private function register_forms() {
        $this->ini_path = $_POST['ini_path'] ?? $_SESSION['ini_path'] ?? null;
        if( file_exists( $this->ini_path ) ) {
            $this->ini_path = realpath( $this->ini_path );
        }
        $this->config_file = $_POST['config_file'] ?? $_SESSION['config_file'] ?? 'includes/config.php';
        if( file_exists( $this->config_file ) ) {
            $this->config_file = realpath( $this->config_file );
            $this->errors['config_file'] = _('Configuration file already exists. It will be overwritten.');
        }
        $this->register_form( 'config', array(
            'ini_path' => array(
                'label' => _('Robust .ini file path'),
                'type' => 'text',
                'required' => true,
                'value' => $this->ini_path,
                'help' => _('The full path to Robust.HG.ini (in grid mode) or Robust.ini (standalone mode) on this server.'),
            ),
            'config_file' => array(
                'label' => _('Target configuration file'),
                'type' => 'text',
                'value' => $this->config_file,
                'readonly' => true,
                'disabled' => true,
                'help' => _('This file will be created or replaced with the settings found in the .ini file.'),
            ),
        ) );
    }

    public function get_form( $form_id ) {
        if( empty( $form_id ) ) {
            OpenSim::notify( sprintf( _('%s submitted without form ID.'), __FUNCTION__ ), 'error' );
            return null;
        }
        if( empty( $this->forms[$form_id] ) ) {
            OpenSim::notify( sprintf( _('Form %s not registered.'), $form_id ), 'error' );
            return null;
        }
        OpenSim::notify( sprintf( _('Form %s rendered.'), $form_id ), 'info' );
        return $this->build_form( $form_id );
    }

    public function build_form( $form_id ) {
        $fields = $this->forms[$form_id];
        
        $html = '';
        foreach ( $fields as $field => $data ) {
            if( ! empty( $this->errors[$field] ) ) {
                $data['help'] = '<div class="text-danger">' . $this->errors[$field] . '</div>' . $data['help'];
            }
            $add_attrs = '';
            $add_attrs .= isset( $data['readonly'] ) && $data['readonly'] ? ' readonly' : '';
            $add_attrs .= isset( $data['disabled'] ) && $data['disabled'] ? ' disabled' : '';
            $add_attrs .= isset( $data['required'] ) && $data['required'] ? ' required' : '';

            $html .= sprintf(
                '<div class="form-group py-1">
                    <label for="%s">%s</label>
                    <input type="%s" name="%s" class="form-control %s" value="%s" %s>
                    <small class="form-text text-muted">%s</small>
                </div>',
                $field,
                $data['label'],
                $data['type'],
                $field,
                $add_attrs,
                $data['value'],
                $add_attrs,
                $data['help']
            );
        }

        if( empty( $html )) {
            return null;
        }

        $submit = sprintf(
            '<input type="hidden" name="form_id" value="%s">'
            . '<div class="form-group py-4 text-end"><button type="submit" class="btn btn-primary">%s</button></div>',
            $form_id,
            _('Submit')
        );

        $html = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="bg-light p-4">' . $html . $submit . '</form>';
        return $html;
    }

    public function render_content() {
        $content = OpenSim::get_notices();
        $content .= $this->content ?? '';
        // $content = '<p>' . join( '</p><p>', array(
        //     _('This tool wil scan Robust configuration file to get your grid settings and generate helpers configuration files.'),
        //     _('It only needs to run once.'),
        //     _('When config is saved, you can (and should) delete install.php file.')
        // ) ) . '</p>';


        $content .= $this->get_form('config');

        return $content;
    }

    /**
     * Read the ini file and store config in an array.
     */
    public function process_ini() {
        $ini = new OpenSim_Ini( $this->ini_path );
        if ( ! $ini ) {
            OpenSim::notify( _('Error parsing file.'), 'error' );
            return false;
        }
        $this->config = $ini->get_config();
        if ( $this->config ) {
            OpenSim::notify( _('Ini parsed successfully.'), 'success' );
            return true;
        }
    }
}

$page = new OpenSim_Install();
$page_title = $page->get_page_title();
$content = $page->get_content();

// Last step is to load template to display the page.
require( 'templates/templates.php' );

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
require_once( __DIR__ . '/classes/class-form.php' );

class OpenSim_Install extends OpenSim_Page {
    private $ini_path;
    private $config_file;
    private $user_notices = array();
    private $errors = array();
    private $forms = array();
    private $ini;
    private $form;

    public function __construct() {
        if( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', dirname( __FILE__ ) . '/' );
        }

        $this->page_title = _('Helpers Installation');

        $this->register_form_installation();

        // $this->refresh_steps();

        // if( $next_step == 'config_robust' ) {
        $form = $this->form;

        if( ! $form ) {
            OpenSim::notify_error( 'Could not create form');
        } else {
            try {
                $values = $form->process();
            } catch (Error $e) {
                OpenSim::notify_error($e, 'Could not process form' );
            }

            if ( $values === false ) {
                OpenSim::notify_error('Form not validated');
                $this->content .= $form->render_form();
            } else if ( ! empty( $values ) ) {
                $result = $this->process_ini();
                if( ! $result ) {
                    OpenSim::notify_error('Could not process ini file');
                } else {
                    $result = $this->generate_php_config();
                    if( ! $result ) {
                        OpenSim::notify_error('Could not generate config file');
                    } else {
                        OpenSim::notify('Robust configuration completed', 'success');
                    }
                }
            }
        }

        $this->content = $this->render_content();
    }

    private function generate_php_config() {
        $template = 'includes/config.example.php';
        if ( ! file_exists( $template )) {
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
        if( empty( $config ) ) {
            OpenSim::notify_error(_('No configuration found.'), 'error');
            return false;
        }
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
            'CURRENCY_NAME'       => $config['LoginService']['Currency'] ?? 'L$',
            'CURRENCY_HELPER_URL' => $config['GridInfoService']['economy'] ?? '',

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
        $this->form->complete('config_robust');
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
        $form = $this->form ?? null;
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

    public function process_form_installation() {
        $form_id='installation';
        $form = $this->form ?? false;
        if( ! $form ) {
            error_log( __FUNCTION__ . ' form not set ' . print_r( $this->forms, true ) );  
            return false;
        }
        $values = $form->get_values();
        $errors = 0;
        if( ! empty( $values['ini_path'] ) ) {
            if( file_exists($values['ini_path']) ) {
                $this->ini_path = realpath( $values['ini_path'] );
            } else {
                $form->error('ini_path', _('File not found'), 'danger');
                $errors++;
            }
        } else {
            $form->error('ini_path', _('A file must be specified'));
            $errors++;
        }
        if( ! empty( $values['config_file'] ) ) {
            if( file_exists($values['config_file']) ) {
                $this->config_file = realpath( $values['config_file'] );
            } else {
                $form->error('config_file', _('File not found', 'danger'));
                $errors++;
            }
        } else {
            $form->error('config_file', _('A file must be specified'));
            $errors++;
        }

        return ( $errors > 0 ) ? false : true;
    }

    private function register_form( $form_id, $fields ) {
        if( empty( $form_id ) || empty( $fields ) ) {
            error_log( __FUNCTION__ . ' ERROR: Missing form ID or fields.' );
            return false;
        }
        $this->form = $fields;
    }

    private function register_form_installation() {
        $form_id = 'installation';

        $fields = array(
            'ini_path' => array(
                'label' => _('Robust .ini file path'),
                'type' => 'text',
                'required' => true,
                'value' => null,
                'placeholder' => '/opt/opensim/bin/Robust.HG.ini',
                'help' => _('The full path to Robust.HG.ini (in grid mode) or Robust.ini (standalone mode) on this server.'),
            ),
            'config_file' => array(
                'label' => _('Target configuration file'),
                'type' => 'text',
                'value' => $_POST['config_file'] ?? $this->config_file ?? 'includes/config.php',
                'placeholder' => 'includes/config.php',
                'readonly' => true,
                'disabled' => true,
                'help' => _('This file will be created or replaced with the settings found in the .ini file.'),
            ),
        );

        $steps = array(
            'config_robust' => _('Setup Robust'), // Get Robust.ini path and process it, skip for standalone grids
            'config_opensim' => _('Setup OpenSim'), // Get OpenSim.ini file and process it
            'config_others' => _('Get additional files'), // according to Robust/OpenSim settings, e.g. MoneyServer.ini, Gloebit.ini...
            'config_helpers' => _('Setup Helpers'), // additional settings specific to helpers, not in ini files, e.g. OSSEARCH_DB
            'validation' => _('Validation')
        );

        $form = OpenSim_Form::register(array(
            'form_id' => $form_id,
            'fields' => $fields,
            'callback' => [$this, 'process_form_installation'],
            'steps' => $steps,
        ));
        if( ! $form ) {
            error_log( __FUNCTION__ . ' form registration failed' );
            return false;
        }
        $this->form = $form;
        return $form;
    }

    public function render_form( $form_id = null ) {
        $form = $this->form ?? false;
        if ( $form ) {
            return $form->render_form();
        }
        // if( $this->form ) {
        //     return OpenSim_Form::render_form( $form_id );
        // }

        return false;
        // Probably obsolete
        // if( empty( $form_id ) ) {
        //     OpenSim::notify( sprintf( _('%s submitted without form ID.'), __FUNCTION__ ), 'error' );
        //     return null;
        // }
        // if( empty( $this->form ) ) {
        //     OpenSim::notify( sprintf( _('Form %s not registered.'), $form_id ), 'error' );
        //     return null;
        // }
        // OpenSim::notify( sprintf( _('Form %s rendered.'), $form_id ), 'info' );
        // return $this->build_form( $form_id );
    }

    public function render_content() {

        $content = OpenSim::get_notices();
        $content .= $this->form->render_progress();
        $content .= $this->form->render_form();
        $content .= ( $this->content ?? '' );

        return $content;
    }

    /**
     * Read the ini file and store config in an array.
     */
    public function process_ini() {
        try {
            $ini = new OpenSim_Ini( $this->ini_path );
        } catch (Error $e) {
            OpenSim::notify_error($e, 'Error creating ini object');
            return false;
        }
        if ( ! $ini ) {
            OpenSim::notify( _('Error parsing file.'), 'error' );
            return false;
        }
        $this->config = $ini->get_config();
        if ( $this->config ) {
            OpenSim::notify( _('Ini parsed successfully.'), 'success' );
            return true;
        } else {
            OpenSim::notify( _('Error parsing file.'), 'error' );
            return false;
        }
    }
}

$page = new OpenSim_Install();
$page_title = $page->get_page_title();
$content = $page->get_content();

// Last step is to load template to display the page.
require( 'templates/templates.php' );

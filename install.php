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
    private $completed; // Temporary value for debug
    private $form;

    public function __construct() {
        if( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', dirname( __FILE__ ) . '/' );
        }

        $this->page_title = _('Helpers Installation');

        $this->register_form_config_robust();

        $this->get_steps();

        // if( $next_step == 'config_robust' ) {
        $form = $this->forms['config_robust'];
        if( ! $form ) {
            OpenSim::notify_error( 'Could not create form');
        } else {
            try {
                $values = $form->process();
            } catch (Error $e) {
                OpenSim::notify_error($e, 'Could not process form' );
            }

            if ($values === null || $values === false) {
                $this->content .= $form->get_html();
            } else {
                OpenSim::notify('Form processed');
                // $this->generate_php_config();
            }
        }

        $this->content = $this->render_content();
    }

    /**
     * Use the value of $this->complete as last completed step, get the next step and 
     * build a navigation html.
     */
    private function get_steps() {
        $steps = array(
            'config_robust' => _('Configure Robust'), // Get Robust.ini path and process it, skip for standalone grids
            'config_opensim' => _('Configure OpenSim.ini'), // Get OpenSim.ini file and process it
            'config_others' => _('Get additional ini files'), // according to Robust/OpenSim settings, e.g. MoneyServer.ini, Gloebit.ini...
            'config_helpers' => _('Helpers config'), // additional settings specific to helpers, not in ini files, e.g. OSSEARCH_DB
            'validation' => _('Validation')
        );
        if( ! empty($_POST['form_id']) ) {
            $form_id = $_POST['form_id'];
            $form = $this->forms[$form_id];
            if( $form ) {
                error_log( __METHOD__ . ' processing form ' . $form_id );
                $form->process();
            } else {
                error_log( 'Form ' . $form_id . ' is not registered' );
            }
        }
        $current_step = array_search($this->completed, array_keys($steps));
        if( empty( $current_step )) {
            $next_step_key = key($steps);
            $next_step = $steps[$next_step_key];
        } else {
            $next_step_key = array_keys($steps)[$current_step + 1] ?? null;
            if( empty($steps[$next_step_key])) {
                $next_step_key='completed';
                $next_step = _('Completed');
            } else {
                $next_step = $steps[$next_step_key] ?? null;
            }
        }

        // TODO: build steps navigation and save in $this->step_navigation

        OpenSim::notify( "current_step $current_step Next step $next_step_key $next_step" );
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

    public function process_form_config_robust() {
        $form_id='config_robust';
        $form = $this->forms[$form_id] ?? false;
        if( ! $form ) {
            error_log( __FUNCTION__ . ' form not set' );
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
                
        error_log( '<pre>
        ' . print_r($values, true) . '
        $this->ini_path ' . ( $this->ini_path ?? 'not set') . '
        $this->config_file ' . ( $this->config_file ?? 'not set' ) . '
        </pre>' );

        // $this->ini_path = $values['ini_path'] ?? '';
        // $this->config_file = $values['config_file'] ?? '';
        

        return false;
    }

    private function register_form( $form_id, $fields ) {
        if( empty( $form_id ) || empty( $fields ) ) {
            error_log( __FUNCTION__ . ' ERROR: Missing form ID or fields.' );
            return false;
        }
        $this->forms[$form_id] = $fields;
    }

    private function register_form_config_robust() {
        $form_id = 'config_robust';
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
        $this->forms[$form_id] = OpenSim_Form::register(array(
            'form_id' => $form_id,
            'fields' => $fields,
            'callback' => [$this, 'process_form_config_robust']
        ));
        return $this->forms[$form_id];
    }

    public function get_html( $form_id = null ) {
        $form = $this->forms[$form_id] ?? false;
        if ( $form ) {
            return $form->get_html();
        }
        // if( $this->form ) {
        //     return OpenSim_Form::get_html( $form_id );
        // }

        return false;
        // Probably obsolete
        // if( empty( $form_id ) ) {
        //     OpenSim::notify( sprintf( _('%s submitted without form ID.'), __FUNCTION__ ), 'error' );
        //     return null;
        // }
        // if( empty( $this->forms[$form_id] ) ) {
        //     OpenSim::notify( sprintf( _('Form %s not registered.'), $form_id ), 'error' );
        //     return null;
        // }
        // OpenSim::notify( sprintf( _('Form %s rendered.'), $form_id ), 'info' );
        // return $this->build_form( $form_id );
    }

    public function render_content() {
        $content = OpenSim::get_notices() . ( $this->content ?? '' );
        // $content .= $this->forms_html;

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

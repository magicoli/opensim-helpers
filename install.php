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
    private $user_notices = array();
    private $errors = array();
    private $forms = array();
    private $form;

    public function __construct() {
        if( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', dirname( __FILE__ ) . '/' );
        }
        if( ! isset( $_SESSION['installation'] ) ) {
            $_SESSION['installation'] = array();
        }
        $this->handle_reset();

        $this->page_title = _('Helpers Installation');

        $this->register_form_installation();
        $form = $this->form;
        if( ! $form ) {
            OpenSim::notify_error( 'Could not create form');
        } else {
            
            $next_step_key = $form->get_next_step();
            $next_step_label = array_key_exists( $next_step_key, $form->steps ) ? $form->steps[$next_step_key]['label'] : false;

            if ( isset( $_POST['step_key'] ) && $_POST['step_key'] == $next_step_key && ! empty( $form->tasks ) ) {
                error_log( "Starting tasks of $next_step_key label $next_step_label" );
                $result = false;
                foreach( $form->tasks as $key => $task ) {
                    $callback_name = OpenSim::callback_name_string( $task['callback'] );
                    // error_log( 'starting task ' . $task['label'] );

                    try {
                        $result = call_user_func( $task['callback'] );
                        if( ! $result ) {
                            throw new Error( $task['error'] ?? 'Failed' );
                        }
                    } catch (Error $e) {
                        $result = false;
                        $message = $callback_name . '() ' . $e->getMessage();
                        error_log( $message );
                        OpenSim::notify_error( $e, $e->getMessage() );
                        break;
                    }
                    // if( ! $result ) {
                    //     $message = $callback_name . '() ' . ( $task['error'] ?? 'Failed' );
                    //     error_log( $message );
                    //     OpenSim::notify_error( $message );
                    //     break;
                    // }
                    $message = ( $task['label'] ?? $callback_name . '()' ) . ': ' . ( $task['success'] ?? 'Success' );
                    error_log( '[' . __CLASS__ . '] ' . $message );
                    OpenSim::notify( $message, 'task-checked' );
                }
                if( ! $result ) {
                    $message = $next_step_label . ': ' . ( $form->steps[$next_step_key]['error'] ?? 'Failed' );
                    OpenSim::notify_error( $message, 'danger' );
                } else if ( $result instanceof Error ) {
                    $message = $next_step_label . ': ' . $result->getMessage();
                    OpenSim::notify_error( $message, 'danger' );
                } else {
                    $message = $next_step_label . ': ' . ( $form->steps[$next_step_key]['success'] ?? 'Success' );
                    OpenSim::notify( $message, 'success' );
                    $form->complete( $next_step_key );
                }
            }
        }

        $this->content = $this->render_content();
    }

    private function robust_generate_config() {
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
        $config = $_SESSION['installation']['config'] ?? null;
        if( empty( $config ) ) {
            OpenSim::notify_error( __FUNCTION__ . '() ' . _('No configuration found.'), 'error');
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
        if( empty( $_SESSION['installation']['config_file'] ) ) {
            // Should not happen, it has been validated before
            $message = _( 'No config file specified, should be possible at this stage.' );
            error_log( 'ERROR ' . __FUNCTION__ . '() ' . $message );
            OpenSim::notify_error( __FUNCTION__ . '() ' . _('No config file specified, should not have occured.'), 'danger');
            return false;
        }
        $temp_config_file = $_SESSION['installation']['config_file'] . '.install.temp';

        try {
            $result = file_put_contents($temp_config_file, $php_template);
            if ( ! $result ) {
                throw new Error( sprintf(
                    _( 'Error writing temporary file, make sure the web server has read/write permissions to %s directory.'),
                    '<nobr><code>' . dirname( $temp_config_file ) . '/</code></nobr>'
                ) );
            }
        } catch (Error $e) {
            OpenSim::notify_error($e, $e->getMessage());
            return false;
        }
        // OpenSim::notify(_('Configuration file generated successfully.'), 'success');
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
            error_log( __FUNCTION__ . ' form not set' );
            return false;
        }

        $next_step_key = $form->get_next_step();
        $values = $form->get_values();
        $errors = 0;
        if( ! empty( $values['robust_ini_path'] ) ) {
            if( file_exists($values['robust_ini_path']) ) {
                $_SESSION['installation']['robust_ini_path'] = realpath( $values['robust_ini_path'] );
            } else {
                $form->task_error('robust_ini_path', _('File not found'), 'danger' );
                $errors++;
            }
        } else {
            $form->task_error('robust_ini_path', _('A file must be specified'));
            $errors++;
        }

        if( file_exists( $values['config_file'] ) ) {
            $_SESSION['installation']['config_file'] = realpath( $values['config_file'] );
            $form->task_error('config_file', _('File will be overwritten, any existing config wil be lost.'), 'warning' );
        } else {
            $_SESSION['installation']['config_file'] = $values['config_file'] ?? 'includes/config.php';
        }

        return ( $errors > 0 ) ? false : true;
    }

    private function register_form_installation() {
        $form_id = 'installation';

        $config_file = $_POST['config_file'] ?? $_SESSION['installation']['config_file'] ?? 'includes/config.php';
        $form = OpenSim_Form::register(array(
            'form_id' => $form_id,
            'multistep' => true,
            'success' => _('Robust configuration completed.'),
            'callback' => [$this, 'process_form_installation'],
            // 'steps' => $steps, // Steps can be defined here if all objects needed are available
            'fields' => array(
                'config_robust' => array(
                    'robust_ini_path' => array(
                        'label' => _('Robust config file path'),
                        'type' => 'text',
                        'required' => true,
                        'value' => null,
                        'placeholder' => '/opt/opensim/bin/Robust.HG.ini',
                        'help' => _('The full path to Robust.HG.ini (in grid mode) or Robust.ini (standalone mode) on this server.'),
                    ),
                    'config_file' => array(
                        'label' => _('Target configuration file'),
                        'type' => 'text',
                        'value' => $config_file,
                        'default' => $config_file,
                        'placeholder' => 'includes/config.php',
                        'readonly' => true,
                        // 'disabled' => true,
                        'help' => _('This file will be created or replaced with the settings found in the .ini file.'),
                    ),
                ),
                'config_opensim' => array(
                    'opensim_ini_path' => array(
                        'label' => _('OpenSim config file path'),
                        'type' => 'text',
                        'required' => true,
                        'value' => isset( $_SESSION['installation']['robust_ini_path']) ? dirname( $_SESSION['installation']['robust_ini_path'] ) . '/OpenSim.ini' : null,
                        'placeholder' => '/opt/opensim/bin/OpenSim.ini',
                        'help' => _('The full path to OpenSim.ini on this server.'),
                    ),
                ),
                'config_others' => array(),
                'config_helpers' =>array(),
                'validation' => array(),
            ),
        ));

        if( ! $form ) {
            error_log( __FUNCTION__ . ' form registration failed' );
            return false;
        }

        // As steps require the form to be registered, we need to register
        // them after the form is created.
        $steps = array(
            'config_robust' => array(
                'label' => _('Setup Robust'),
                'init' => [ $form, 'render_form' ],
                'description' => _('Give the path of your Robust configuration file.
                Robust.HG.ini for grid mode, Robust.ini for standalone mode.
                The file will be parsed and converted to a PHP configuration file.'),
                'success' => _('Configuration parsed and converted successfully.'),
                'tasks' => array(
                    array(
                        'label' => _('Process form'),
                        'callback' => [ $form, 'process' ],
                        'error' => _('Invalid submission.'),
                        'success' => _('Submission validated.'),
                    ),
                    array(
                        'label' => _('Process ini file'),
                        'callback' => [ $this, 'robust_process_ini' ],
                        'error' => _('Error parsing Robust ini file.'),
                        'success' => _('Robust ini parsed and converted.'),
                    ),
                    array(
                        'label' => _('Generate config'),
                        'callback' => [ $this, 'robust_generate_config' ],
                        'error' => _('Error generating PHP config file.'),
                        'success' => _('PHP config file generated.'),
                    ),
                )
            ),
            'config_opensim' => array(
                'label' => _('Setup OpenSim'),
                'description' => _('Get OpenSim.ini file and process it'),
            ),
            'config_others' => array(
                'label' => _('Get additional files'),
                'description' => _('Get additional files, e.g. MoneyServer.ini, Gloebit.ini...'),
            ),
            'config_helpers' => array(
                'label' => _('Setup Helpers'),
                'description' => _('Additional settings specific to helpers, not in ini files, e.g. OSSEARCH_DB'),
            ),
            'validation' => array(
                'label' => _('Validation'),
                'description' => _('Validate the configuration'),
            ),
        );
        $form->add_steps( $steps );

        // Get values prematurely to check if the config file exists
        $next_step_key = $form->get_next_step();
        $values = $form->get_values();
        if( file_exists( $values['config_file'] ) ) {
            $form->task_error('config_file', _('File will be overwritten, any existing config wil be lost.'), 'warning' );
        }

        $this->form = $form;
        return $form;
    }

    public function render_form( $form_id = null ) {
        $form = $this->form ?? false;
        if ( $form ) {
            return $form->render_form();
        }

        return false;
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
    public function robust_process_ini() {
        try {
            $ini = new OpenSim_Ini( $_SESSION['installation']['robust_ini_path'] );
        } catch (Error $e) {
            OpenSim::notify_error($e, 'Error creating ini object');
            return false;
        }
        if ( ! $ini ) {
            OpenSim::notify( _('Error parsing file.'), 'error' );
            return false;
        }

        $config = $ini->get_config();
        $_SESSION['installation']['config'] = $config;
        if ( ! $config ) {
            OpenSim::notify( _('Error parsing file.'), 'error' );
            return false;
        }
        return true;
    }

    /**
     * Handle the restart action by clearing the installation session and redirecting.
     */
    private function handle_reset() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['reset'])) {
                unset($_SESSION['installation']);
                OpenSim::notify(_('Installation session has been cleared. Restarting installation.'), 'success');
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }

    }
}

$page = new OpenSim_Install();
$page_title = $page->get_page_title();
$content = $page->get_content();

// Last step is to load template to display the page.
require( 'templates/templates.php' );

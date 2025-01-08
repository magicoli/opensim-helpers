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


class OpenSim_Page {
    protected $page_title;
    protected $content;

    public function __construct() {

    }

    public function get_page_title() {
        return $this->page_title;
    }

    public function get_content() {
        return $this->content;
    }
}

class OpenSim_Install extends OpenSim_Page {
    private $ini_path;
    private $config_file;
    private $user_notices = array();
    private $errors = array();
    private $forms = array();

    public function __construct() {
        if( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', dirname( __FILE__ ) . '/' );
        }

        $this->page_title = _('Helpers Installation');
        $this->register_forms();
        $this->process_forms();
        
        $this->content = $this->render_content();
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
            call_user_func( [ $this, $callback ] );
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

        if( empty( $this->errors ) ) {
            $this->process_ini();
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

    public function parse_args( $args, $defaults ) {
        if( is_object( $args ) ) {
            $args = get_object_vars( $args );
        } elseif( is_array( $args ) ) {
            $args = $args;
        } else {
            parse_str( $args, $args );
        }
        return array_merge( $defaults, $args );
    }

    public function user_notice( $message, $type = 'info' ) {
        $key = md5( $message ); // Make sure we don't have duplicates
        $this->user_notices[$key] = array(
            'message' => $message,
            'type' => $type,
        );
    }

    public function get_notices() {
        $html = '';
        foreach( $this->user_notices as $key => $notice ) {
            $type = $notice['type'] ?? 'info';
            $html .= sprintf(
                '<div class="alert alert-%s">%s</div>',
                $type,
                $notice['message']
            );
        }
        return $html;
    }

    public function get_form( $form_id ) {
        if( empty( $form_id ) ) {
            $this->user_notice( sprintf( _('%s submitted without form ID.'), __FUNCTION__ ), 'error' );
            return null;
        }
        if( empty( $this->forms[$form_id] ) ) {
            $this->user_notice( sprintf( _('Form %s not registered.'), $form_id ), 'error' );
            return null;
        }
        $this->user_notice( sprintf( _('Form %s rendered.'), $form_id ), 'info' );
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
        $content = '';
        // $content = '<p>' . join( '</p><p>', array(
        //     _('This tool wil scan Robust configuration file to get your grid settings and generate helpers configuration files.'),
        //     _('It only needs to run once.'),
        //     _('When config is saved, you can (and should) delete install.php file.')
        // ) ) . '</p>';

        $content .= $this->get_notices();

        $content .= $this->get_form('config');

        return $content;
    }

    public function process_ini() {
        $result = 'Processing ini';
        return $result;
    }
}

$page = new OpenSim_Install();
$page_title = $page->get_page_title();
$content = $page->get_content();

// Last step is to load template to display the page.
require( 'templates/templates.php' );

<?php
/**
 * TODO
 * 
 * Form class for OpenSimulator Helpers
 * 
 * Handles form rendering and global processing. Forms definition are passed by calling class.
 * 
 * Methods:
 * - register( $args, $fields ) register a new form (used in _construct)
 *      $args = array(
 *          'id' => unique id
 *          'html' => html code
 *          'callback' => callback to call to process form
 * )
 * - get_html()     return the form html code
 * - get_values()   return an array of field_id => value pairs
 * - get_fields()   return the array of defined fields 
 * - process()      process the form callback
 * 
 * @package		magicoli/opensim-helpers
**/

require_once( dirname(__DIR__) . '/classes/init.php' );

class OpenSim_Form {
    private $form_id;
    private $fields = array();
    private $callback;
    private static $forms = array();
    private $errors;
    private $html;

    public function __construct($args = array(), $step = 0) {
        if (!is_array($args)) {
            error_log(__METHOD__ . ' invalid argument type ' . gettype($args));
            throw new InvalidArgumentException('Invalid argument type: ' . gettype($args));
        }

        $args = OpenSim::parse_args($args, array(
            'form_id' => uniqid('form-', true),
            'fields' => array(),
            'callback' => null,
        ));
        $this->form_id = $args['form_id'];
        $this->add_fields($args['fields']);
        $this->callback = $args['callback'];

        self::$forms[$this->form_id] = $this;
    }

    /**
     * Static factory method to register an instance of OpenSim_Form.
     * Handles exceptions internally to avoid requiring try-catch blocks during instantiation.
     *
     * @param array $args Arguments for form initialization.
     * @param int $step Optional step parameter.
     * @return OpenSim_Form|false Returns an instance of OpenSim_Form on success, or false on failure.
     */
    public static function register($args = array(), $step = 0) {
        try {
            return new self($args, $step);
        } catch (InvalidArgumentException $e) {
            error_log($e->getMessage());
            OpenSim::notify_error($e->getMessage(), 'error');
            return false;
        }
    }

    public function add_fields( $fields) {
        if( empty( $fields )) {
            return;
        }
        $this->fields = OpenSim::parse_args( $fields, $this->fields );
    }

    public function error( $field_id, $message, $type = 'warning' ) {
        $this->errors[$field_id] = array(
            'message' => $message ?? 'Error',
            'type' => empty( $type ) ? 'warning' : $type,
        );
    }

    public function render() {
        if( ! empty( $this->html )) {
            error_log( 'return previously rendered html');
            return $this->html;
        }
        $fields = $this->fields;
        if( empty( $fields )) {
            error_log( 'empty fields' );
            return false;
        }
        
        $html = '';
        
        foreach ( $fields as $field => $data ) {
            $add_class = '';
            if( ! empty( $this->errors[$field] ) ) {
                $data['help'] = OpenSim::error_html( $this->errors[$field], 'warning' ) . $data['help'];
                $add_class .= ' is-invalid';
            }
            $add_attrs = '';
            $add_attrs .= isset( $data['readonly'] ) && $data['readonly'] ? ' readonly' : '';
            $add_attrs .= isset( $data['disabled'] ) && $data['disabled'] ? ' disabled' : '';
            $add_attrs .= isset( $data['required'] ) && $data['required'] ? ' required' : '';
            // $placeholder = isset( $data['placeholder'] ) ? $data['placeholder'] : '';

            $html .= sprintf(
            '<div class="form-group py-1">
                <label for="%s">%s</label>
                <input type="%s" name="%s" class="form-control %s" value="%s" placeholder="%s" %s>
                <small class="form-text text-muted">%s</small>
            </div>',
            $field,
            $data['label'],
            $data['type'],
            $field,
            $add_attrs . $add_class,
            $_POST[$field] ?? $data['value'] ?? '',
            $data['placeholder'] ?? '',
            $add_attrs,
            $data['help'] ?? ''
            );
        }

        if( empty( $html )) {
            return null;
        }

        $submit = sprintf(
            '<input type="hidden" name="form_id" value="%s">'
            . '<div class="form-group py-4 text-end"><button type="submit" class="btn btn-primary">%s</button></div>',
            $this->form_id,
            _('Submit')
        );

        $html = '<form id="' . $this->form_id . '" method="post" action="' . $_SERVER['PHP_SELF'] . '" class="bg-light p-4">' . $html . $submit . '</form>';
        $this->html = $html;
        return $html;
    }

    public function process() {
        if( empty( $_POST )) {
            // Ignore silently, nothing to do
            return null;
        }
        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $this->get_values());
        } else {
            if (is_array($this->callback)) {
                $callback_name = get_class($this->callback[0]) . '::' . $this->callback[1];
            } else {
                $callback_name = $this->callback;
            }
            error_log( $callback_name . ' is not callable from ' . __METHOD__ );
            return false;
        }
    }

    // Get the form HTML
    public function get_html() {
        // Implement form rendering logic
        return $this->render();
    }

    /**
     * Get values from fields definition and post.
     * 
     * TODO: make sure values are not replaced with post values before this step,
     * although it doesn't hurt with the current usage, it might be useful to
     * compare old and new value in the process() method called later.
     */
    public function get_values() {
        foreach( $this->fields as $key => $field ) {
            $values[$key] = $_POST[$key] ?? $field['value'] ?? null;
        }
        return $values;
    }

    // Get defined fields
    public function get_fields() {
        return $this->fields;
    }

    public function get_form( $form_id ) {
        if( empty( $formid )) {
            return $false;
        }
        return isset( self::$forms[$form_id] ) ? self::$forms[$form_id] : false;
    }

    public function get_forms() {
        return self::$forms ?? false;
    }
}

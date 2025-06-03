<?php
/**
 * Installation Wizard
 * 
 * Multi-step installation wizard for OpenSimulator Engine Settings.
 * Framework-agnostic design that can be used by helpers standalone or WordPress plugin.
 */

if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit;
}

class Installation_Wizard {
    
    private $session_key = 'opensim_installation_wizard';
    private $temp_file = null;
    private $steps = array();
    private $current_step = 0;
    private $data = array();
    
    const INSTALLATION_MODES = array(
        'console' => 'Console Credentials (Recommended)',
        'manual' => 'Full Manual Installation', 
        'ini_import' => 'Import from OpenSim INI Files'
    );
    
    public function __construct() {
        $this->init_session();
        $this->setup_steps();
        $this->load_temp_data();
    }
    
    /**
     * Initialize session handling
     */
    private function init_session() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Create temporary file for storing wizard data
        $temp_dir = Engine_Settings::get_config_dir() . '/temp';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0700, true);
        }
        
        $session_id = session_id();
        $this->temp_file = $temp_dir . '/wizard_' . $session_id . '.json';
    }
    
    /**
     * Setup installation steps
     */
    private function setup_steps() {
        $this->steps = array(
            'welcome' => array(
                'title' => 'Welcome',
                'description' => 'OpenSimulator Engine Installation',
                'required' => false
            ),
            'mode_selection' => array(
                'title' => 'Installation Mode',
                'description' => 'Choose how you want to configure the engine',
                'required' => true
            ),
            'console_credentials' => array(
                'title' => 'Console Credentials',
                'description' => 'Connect to OpenSimulator console for automatic configuration',
                'required' => true,
                'condition' => array('mode' => 'console')
            ),
            'manual_database' => array(
                'title' => 'Database Configuration',
                'description' => 'Configure database connections manually',
                'required' => true,
                'condition' => array('mode' => 'manual')
            ),
            'manual_grid_info' => array(
                'title' => 'Grid Information',
                'description' => 'Configure grid settings manually',
                'required' => true,
                'condition' => array('mode' => 'manual')
            ),
            'ini_file_selection' => array(
                'title' => 'INI File Import',
                'description' => 'Select OpenSimulator INI files to import',
                'required' => true,
                'condition' => array('mode' => 'ini_import')
            ),
            'validation' => array(
                'title' => 'Validation',
                'description' => 'Validate configuration and test connections',
                'required' => true
            ),
            'summary' => array(
                'title' => 'Summary',
                'description' => 'Review settings before final installation',
                'required' => true
            ),
            'complete' => array(
                'title' => 'Complete',
                'description' => 'Installation completed successfully',
                'required' => false
            )
        );
    }
    
    /**
     * Load temporary data from file
     */
    private function load_temp_data() {
        if (file_exists($this->temp_file)) {
            $json_data = file_get_contents($this->temp_file);
            $this->data = json_decode($json_data, true) ?: array();
            $this->current_step = $this->data['current_step'] ?? 0;
        }
    }
    
    /**
     * Save temporary data to file
     */
    private function save_temp_data() {
        $this->data['current_step'] = $this->current_step;
        $this->data['timestamp'] = time();
        
        $json_data = json_encode($this->data, JSON_PRETTY_PRINT);
        file_put_contents($this->temp_file, $json_data);
        chmod($this->temp_file, 0600); // Secure permissions
    }
    
    /**
     * Process form submission
     */
    public function process_form($form_data) {
        $step_key = $this->get_current_step_key();
        
        // Validate current step
        $validation_result = $this->validate_step($step_key, $form_data);
        if (!$validation_result['valid']) {
            return array(
                'success' => false,
                'errors' => $validation_result['errors'],
                'step' => $step_key
            );
        }
        
        // Store step data
        $this->data['steps'][$step_key] = $form_data;
        
        // Handle step-specific logic
        switch ($step_key) {
            case 'console_credentials':
                $result = $this->test_console_connection($form_data);
                if (!$result['success']) {
                    return $result;
                }
                // Import settings from console
                $this->import_from_console($form_data);
                break;
                
            case 'ini_file_selection':
                $result = $this->import_ini_files($form_data);
                if (!$result['success']) {
                    return $result;
                }
                break;
                
            case 'validation':
                $result = $this->validate_all_settings();
                if (!$result['success']) {
                    return $result;
                }
                break;
                
            case 'summary':
                // Final installation - write actual config files
                $result = $this->perform_installation();
                if (!$result['success']) {
                    return $result;
                }
                break;
        }
        
        // Move to next step
        if ($step_key !== 'complete') {
            $this->advance_step();
        }
        
        $this->save_temp_data();
        
        return array(
            'success' => true,
            'step' => $this->get_current_step_key(),
            'progress' => $this->get_progress()
        );
    }
    
    /**
     * Get current step information
     */
    public function get_current_step() {
        $step_key = $this->get_current_step_key();
        $step = $this->steps[$step_key];
        $step['key'] = $step_key;
        $step['data'] = $this->data['steps'][$step_key] ?? array();
        return $step;
    }
    
    /**
     * Get current step key
     */
    private function get_current_step_key() {
        $step_keys = array_keys($this->steps);
        
        // Skip conditional steps that don't apply
        for ($i = $this->current_step; $i < count($step_keys); $i++) {
            $step_key = $step_keys[$i];
            if ($this->step_applies($step_key)) {
                return $step_key;
            }
        }
        
        return $step_keys[$this->current_step] ?? 'complete';
    }
    
    /**
     * Check if step applies based on conditions
     */
    private function step_applies($step_key) {
        $step = $this->steps[$step_key];
        
        if (!isset($step['condition'])) {
            return true;
        }
        
        foreach ($step['condition'] as $field => $value) {
            $stored_value = $this->data['steps']['mode_selection'][$field] ?? null;
            if ($stored_value !== $value) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Advance to next applicable step
     */
    private function advance_step() {
        $step_keys = array_keys($this->steps);
        
        do {
            $this->current_step++;
        } while (
            $this->current_step < count($step_keys) && 
            !$this->step_applies($step_keys[$this->current_step])
        );
    }
    
    /**
     * Get installation progress percentage
     */
    public function get_progress() {
        $total_steps = count(array_filter($this->steps, function($step) {
            return $step['required'];
        }));
        
        $completed_steps = 0;
        foreach ($this->steps as $key => $step) {
            if ($step['required'] && isset($this->data['steps'][$key])) {
                $completed_steps++;
            }
        }
        
        return round(($completed_steps / $total_steps) * 100);
    }
    
    /**
     * Test console connection
     */
    private function test_console_connection($credentials) {
        try {
            // Use OpenSim_Service to test console connection
            $console_config = array(
                'host' => $credentials['console_host'],
                'port' => $credentials['console_port'],
                'user' => $credentials['console_user'],
                'pass' => $credentials['console_pass']
            );
            
            $service = new OpenSim_Service();
            $result = $service->test_console_connection($console_config);
            
            return array(
                'success' => $result,
                'message' => $result ? 'Console connection successful' : 'Failed to connect to console'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Console connection failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Import settings from console
     */
    private function import_from_console($credentials) {
        try {
            $service = new OpenSim_Service();
            $config = $service->get_config_from_console($credentials);
            
            // Store imported config for later use
            $this->data['imported_config'] = $config;
            
        } catch (Exception $e) {
            error_log('Failed to import from console: ' . $e->getMessage());
        }
    }
    
    /**
     * Import from INI files
     */
    private function import_ini_files($file_data) {
        try {
            $imported_config = array();
            
            foreach ($file_data['ini_files'] as $file_path) {
                if (file_exists($file_path)) {
                    $ini_config = OpenSim_Ini::import_ini_file($file_path);
                    $imported_config = array_merge_recursive($imported_config, $ini_config);
                }
            }
            
            $this->data['imported_config'] = $imported_config;
            
            return array(
                'success' => true,
                'message' => 'INI files imported successfully'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Failed to import INI files: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Validate step data
     */
    private function validate_step($step_key, $data) {
        $errors = array();
        
        switch ($step_key) {
            case 'mode_selection':
                if (empty($data['mode']) || !array_key_exists($data['mode'], self::INSTALLATION_MODES)) {
                    $errors[] = 'Please select a valid installation mode';
                }
                break;
                
            case 'console_credentials':
                $required_fields = ['console_host', 'console_port', 'console_user', 'console_pass'];
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        $errors[] = "Field '{$field}' is required";
                    }
                }
                break;
                
            case 'manual_database':
                $required_fields = ['db_host', 'db_name', 'db_user', 'db_pass'];
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        $errors[] = "Field '{$field}' is required";
                    }
                }
                break;
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Validate all collected settings
     */
    private function validate_all_settings() {
        $errors = array();
        
        // Test database connections
        $mode = $this->data['steps']['mode_selection']['mode'];
        
        if ($mode === 'manual') {
            $db_config = $this->data['steps']['manual_database'];
            $db_test = $this->test_database_connection($db_config);
            if (!$db_test['success']) {
                $errors[] = $db_test['message'];
            }
        }
        
        return array(
            'success' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Test database connection
     */
    private function test_database_connection($config) {
        try {
            $service = new OpenSim_Service();
            $result = $service->test_database_connection($config);
            
            return array(
                'success' => $result,
                'message' => $result ? 'Database connection successful' : 'Failed to connect to database'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Perform final installation - write config files
     */
    private function perform_installation() {
        try {
            $mode = $this->data['steps']['mode_selection']['mode'];
            
            switch ($mode) {
                case 'console':
                case 'ini_import':
                    // Use imported config
                    $config = $this->data['imported_config'];
                    Engine_Settings::import_from_opensim($config);
                    break;
                    
                case 'manual':
                    // Build config from manual input
                    $this->build_manual_config();
                    break;
            }
            
            // Clean up temporary file
            if (file_exists($this->temp_file)) {
                unlink($this->temp_file);
            }
            
            return array(
                'success' => true,
                'message' => 'Installation completed successfully'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Build config from manual input
     */
    private function build_manual_config() {
        $db_config = $this->data['steps']['manual_database'];
        $grid_config = $this->data['steps']['manual_grid_info'];
        
        // Set database configuration
        Engine_Settings::set('DatabaseService.ConnectionString', 
            sprintf('Data Source=%s;Database=%s;User ID=%s;Password=%s;Old Guids=true;',
                $db_config['db_host'],
                $db_config['db_name'], 
                $db_config['db_user'],
                $db_config['db_pass']
            )
        );
        
        // Set grid information
        Engine_Settings::set('GridInfoService.gridname', $grid_config['grid_name']);
        Engine_Settings::set('GridInfoService.login', $grid_config['login_uri']);
    }
    
    /**
     * Rollback installation - restore previous state
     */
    public function rollback() {
        // Remove any created config files
        $config_dir = Engine_Settings::get_config_dir();
        $files = glob($config_dir . '/*.ini');
        
        foreach ($files as $file) {
            if (is_writable($file)) {
                unlink($file);
            }
        }
        
        // Clean up temporary data
        if (file_exists($this->temp_file)) {
            unlink($this->temp_file);
        }
        
        // Reset session data
        unset($_SESSION[$this->session_key]);
        
        return true;
    }
    
    /**
     * Get summary of collected data
     */
    public function get_summary() {
        $mode = $this->data['steps']['mode_selection']['mode'] ?? 'unknown';
        $summary = array(
            'installation_mode' => self::INSTALLATION_MODES[$mode] ?? 'Unknown',
            'settings' => array()
        );
        
        // Add mode-specific summary data
        switch ($mode) {
            case 'console':
                $console_data = $this->data['steps']['console_credentials'] ?? array();
                $summary['settings']['Console Host'] = $console_data['console_host'] ?? 'Not set';
                $summary['settings']['Console Port'] = $console_data['console_port'] ?? 'Not set';
                break;
                
            case 'manual':
                $db_data = $this->data['steps']['manual_database'] ?? array();
                $grid_data = $this->data['steps']['manual_grid_info'] ?? array();
                $summary['settings']['Database Host'] = $db_data['db_host'] ?? 'Not set';
                $summary['settings']['Database Name'] = $db_data['db_name'] ?? 'Not set';
                $summary['settings']['Grid Name'] = $grid_data['grid_name'] ?? 'Not set';
                break;
                
            case 'ini_import':
                $ini_data = $this->data['steps']['ini_file_selection'] ?? array();
                $summary['settings']['INI Files'] = count($ini_data['ini_files'] ?? array()) . ' files selected';
                break;
        }
        
        return $summary;
    }
}
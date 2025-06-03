<?php
/**
 * Installation Wizard for OpenSimulator Helpers
 * 
 * Standalone installation wizard that uses the Engine's Installation_Wizard class
 * and renders HTML using Bootstrap for a clean interface.
 */

if (__FILE__ !== $_SERVER['SCRIPT_FILENAME']) {
    http_response_code(403);
    die("This file must be called directly.");
}

// Start session for wizard state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the engine
require_once __DIR__ . '/bootstrap.php';

class OpenSim_Installation_Page {
    private $wizard;
    private $page_title = 'OpenSimulator Installation Wizard';
    private $site_title = 'OpenSimulator Helpers';
    
    public function __construct() {
        $this->wizard = new Installation_Wizard();
        $this->process_form();
        $this->render_page();
    }
    
    /**
     * Process form submissions
     */
    private function process_form() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'next_step':
                        $result = $this->wizard->process_step($_POST);
                        if ($result['success']) {
                            $this->wizard->next_step();
                            $this->add_notice('success', $result['message'] ?? 'Step completed successfully');
                        } else {
                            $this->add_notice('error', $result['message'] ?? 'Please correct the errors below');
                            if (!empty($result['errors'])) {
                                foreach ($result['errors'] as $error) {
                                    $this->add_notice('error', $error);
                                }
                            }
                        }
                        break;
                        
                    case 'previous_step':
                        $this->wizard->previous_step();
                        break;
                        
                    case 'reset':
                        $this->wizard->reset();
                        $this->add_notice('info', 'Wizard has been reset');
                        break;
                }
            }
        }
        
        // Handle GET parameters
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'reset':
                    $this->wizard->reset();
                    $this->add_notice('info', 'Wizard has been reset');
                    break;
            }
        }
    }
    
    /**
     * Render the complete page
     */
    private function render_page() {
        $current_step = $this->wizard->get_current_step();
        
        if (!$current_step) {
            $this->render_completion_page();
            return;
        }
        
        $content = $this->render_wizard_content($current_step);
        
        // Use helpers template system
        $GLOBALS['site_title'] = $this->site_title;
        $GLOBALS['page_title'] = $this->page_title;
        $GLOBALS['content'] = $content;
        
        require_once __DIR__ . '/templates/templates.php';
    }
    
    /**
     * Render wizard step content
     */
    private function render_wizard_content($step) {
        $wizard_data = $this->wizard->get_wizard_data();
        $progress = $this->wizard->get_progress();
        
        ob_start();
        ?>
        
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo esc_html($step['title']); ?></h3>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: <?php echo $progress; ?>%" 
                                     aria-valuenow="<?php echo $progress; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">Step <?php echo $step['number']; ?> of <?php echo $step['total']; ?></small>
                        </div>
                        
                        <div class="card-body">
                            <?php $this->render_notices(); ?>
                            
                            <?php if (!empty($step['description'])): ?>
                                <p class="text-muted"><?php echo esc_html($step['description']); ?></p>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="next_step">
                                
                                <?php $this->render_step_fields($step, $wizard_data); ?>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <div>
                                        <?php if ($step['number'] > 1): ?>
                                            <button type="submit" name="action" value="previous_step" class="btn btn-outline-secondary">
                                                <i class="bi bi-arrow-left"></i> Previous
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div>
                                        <button type="button" class="btn btn-outline-danger me-2" onclick="confirmReset()">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </button>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <?php if ($step['number'] < $step['total']): ?>
                                                Next <i class="bi bi-arrow-right"></i>
                                            <?php else: ?>
                                                Complete Installation <i class="bi bi-check-circle"></i>
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function confirmReset() {
            if (confirm('Are you sure you want to reset the wizard? All entered data will be lost.')) {
                window.location.href = '?action=reset';
            }
        }
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render form fields for a step
     */
    private function render_step_fields($step, $wizard_data) {
        if (empty($step['fields'])) {
            return;
        }
        
        foreach ($step['fields'] as $field_key => $field_config) {
            $value = $wizard_data[$field_key] ?? ($field_config['default'] ?? '');
            $required = !empty($field_config['required']) ? 'required' : '';
            $field_id = 'field_' . $field_key;
            
            echo '<div class="mb-3">';
            echo '<label for="' . $field_id . '" class="form-label">';
            echo esc_html($field_config['label']);
            if (!empty($field_config['required'])) {
                echo ' <span class="text-danger">*</span>';
            }
            echo '</label>';
            
            switch ($field_config['type']) {
                case 'text':
                    $placeholder = !empty($field_config['placeholder']) ? 'placeholder="' . esc_attr($field_config['placeholder']) . '"' : '';
                    echo '<input type="text" class="form-control" id="' . $field_id . '" name="' . $field_key . '" value="' . esc_attr($value) . '" ' . $placeholder . ' ' . $required . '>';
                    break;
                    
                case 'password':
                    echo '<input type="password" class="form-control" id="' . $field_id . '" name="' . $field_key . '" value="' . esc_attr($value) . '" ' . $required . '>';
                    break;
                    
                case 'number':
                    echo '<input type="number" class="form-control" id="' . $field_id . '" name="' . $field_key . '" value="' . esc_attr($value) . '" ' . $required . '>';
                    break;
                    
                case 'radio':
                    if (!empty($field_config['options'])) {
                        foreach ($field_config['options'] as $option_key => $option_label) {
                            $checked = ($value === $option_key) ? 'checked' : '';
                            $radio_id = $field_id . '_' . $option_key;
                            echo '<div class="form-check">';
                            echo '<input class="form-check-input" type="radio" name="' . $field_key . '" id="' . $radio_id . '" value="' . $option_key . '" ' . $checked . ' ' . $required . '>';
                            echo '<label class="form-check-label" for="' . $radio_id . '">';
                            echo esc_html($option_label);
                            echo '</label>';
                            echo '</div>';
                        }
                    }
                    break;
                    
                case 'file':
                    $accept = !empty($field_config['accept']) ? 'accept="' . esc_attr($field_config['accept']) . '"' : '';
                    echo '<input type="file" class="form-control" id="' . $field_id . '" name="' . $field_key . '" ' . $accept . ' ' . $required . '>';
                    break;
            }
            
            if (!empty($field_config['help'])) {
                echo '<div class="form-text">' . esc_html($field_config['help']) . '</div>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Render completion page
     */
    private function render_completion_page() {
        $content = '
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-success mb-4">
                                <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                            </div>
                            <h2 class="card-title text-success">Installation Complete!</h2>
                            <p class="card-text">Your OpenSimulator helpers have been successfully configured.</p>
                            
                            <div class="mt-4">
                                <a href="/" class="btn btn-primary">Go to Home Page</a>
                                <a href="?action=reset" class="btn btn-outline-secondary ms-2">Run Wizard Again</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        $GLOBALS['site_title'] = $this->site_title;
        $GLOBALS['page_title'] = 'Installation Complete';
        $GLOBALS['content'] = $content;
        
        require_once __DIR__ . '/templates/templates.php';
    }
    
    /**
     * Add notice message
     */
    private function add_notice($type, $message) {
        if (!isset($_SESSION['install_notices'])) {
            $_SESSION['install_notices'] = array();
        }
        $_SESSION['install_notices'][] = array('type' => $type, 'message' => $message);
    }
    
    /**
     * Render notice messages
     */
    private function render_notices() {
        if (!isset($_SESSION['install_notices']) || empty($_SESSION['install_notices'])) {
            return;
        }
        
        foreach ($_SESSION['install_notices'] as $notice) {
            $alert_class = match($notice['type']) {
                'success' => 'alert-success',
                'error' => 'alert-danger',
                'warning' => 'alert-warning',
                'info' => 'alert-info',
                default => 'alert-info'
            };
            
            echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
            echo esc_html($notice['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
        
        // Clear notices after displaying
        unset($_SESSION['install_notices']);
    }
}

// Instantiate and run the installation page
new OpenSim_Installation_Page();

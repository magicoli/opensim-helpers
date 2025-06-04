<?php
/**
 * Standalone Installation Wizard
 * 
 * Direct access wizard for helpers installation
 */

// Start session for wizard state
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once dirname(__FILE__) . '/../engine/class-installation-wizard.php';
require_once dirname(__FILE__) . '/../engine/class-engine-settings.php';

// Initialize wizard
$wizard = new Installation_Wizard();

// Handle form submission
$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'next':
                $result = $wizard->process_step($_POST);
                if ($result['success']) {
                    if ($wizard->next_step()) {
                        // Continue to next step
                    } else {
                        // Wizard completed
                        $message = 'Installation completed successfully!';
                        $message_type = 'success';
                    }
                } else {
                    $message = isset($result['errors']) ? implode('<br>', $result['errors']) : $result['message'];
                    $message_type = 'error';
                }
                break;
                
            case 'previous':
                $wizard->previous_step();
                break;
                
            case 'reset':
                $wizard->reset();
                $message = 'Wizard has been reset';
                $message_type = 'info';
                break;
        }
    }
}

$current_step = $wizard->get_current_step();
$progress = $wizard->get_progress();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenSimulator Helpers Installation Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .wizard-container { max-width: 800px; margin: 2rem auto; }
        .step-indicator { margin-bottom: 2rem; }
        .step-indicator .step { display: inline-block; width: 30px; height: 30px; border-radius: 50%; background: #e9ecef; color: #6c757d; text-align: center; line-height: 30px; margin-right: 10px; }
        .step-indicator .step.active { background: #0d6efd; color: white; }
        .step-indicator .step.completed { background: #198754; color: white; }
        .field-group { margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="container wizard-container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">OpenSimulator Helpers Installation Wizard</h1>
                
                <!-- Progress Bar -->
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $progress; ?>%</div>
                </div>
                
                <!-- Step Indicator -->
                <div class="step-indicator text-center mb-4">
                    <?php for ($i = 1; $i <= $current_step['total']; $i++): ?>
                        <span class="step <?php echo $i < $current_step['number'] ? 'completed' : ($i == $current_step['number'] ? 'active' : ''); ?>"><?php echo $i; ?></span>
                    <?php endfor; ?>
                </div>
                
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : ($message_type === 'success' ? 'success' : 'info'); ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Current Step -->
                <?php if ($current_step): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($current_step['title']); ?></h3>
                            <p class="text-muted mb-0"><?php echo $current_step['description']; ?></p>
                        </div>
                        <div class="card-body">
                            <form method="post" id="wizardForm">
                                <?php foreach ($current_step['fields'] as $field_key => $field_config): ?>
                                    <?php 
                                    $field_section = $field_config['section'] ?? '';
                                    ?>
                                    <div class="field-group" <?php if (!empty($field_section)): ?>data-section="<?php echo $field_section; ?>" style="display: none;"<?php endif; ?>>
                                        <label for="<?php echo $field_key; ?>" class="form-label">
                                            <?php echo htmlspecialchars($field_config['label']); ?>
                                            <?php if (!empty($field_config['required'])): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </label>
                                        
                                        <?php
                                        $current_value = $wizard->get_wizard_data()[$field_key] ?? $field_config['default'] ?? '';
                                        $required_attr = !empty($field_config['required']) ? 'required' : '';
                                        
                                        switch ($field_config['type']):
                                            case 'text':
                                            case 'password':
                                            case 'number':
                                        ?>
                                            <input type="<?php echo $field_config['type']; ?>" 
                                                   class="form-control" 
                                                   id="<?php echo $field_key; ?>" 
                                                   name="<?php echo $field_key; ?>" 
                                                   value="<?php echo htmlspecialchars($current_value); ?>"
                                                   placeholder="<?php echo htmlspecialchars($field_config['placeholder'] ?? ''); ?>"
                                                   <?php echo $required_attr; ?>>
                                        <?php break; case 'radio': ?>
                                            <?php foreach ($field_config['options'] as $option_value => $option_label): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="radio" 
                                                           name="<?php echo $field_key; ?>" 
                                                           id="<?php echo $field_key . '_' . $option_value; ?>" 
                                                           value="<?php echo $option_value; ?>"
                                                           <?php echo $current_value === $option_value ? 'checked' : ''; ?>
                                                           <?php echo $required_attr; ?>
                                                           onchange="toggleSections('connection_method')">>
                                                    <label class="form-check-label" for="<?php echo $field_key . '_' . $option_value; ?>">
                                                        <?php echo htmlspecialchars($option_label); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php break; case 'file': ?>
                                            <input type="file" 
                                                   class="form-control" 
                                                   id="<?php echo $field_key; ?>" 
                                                   name="<?php echo $field_key; ?>"
                                                   accept="<?php echo htmlspecialchars($field_config['accept'] ?? ''); ?>">
                                        <?php break; endswitch; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <div>
                                        <?php if ($current_step['number'] > 1): ?>
                                            <button type="submit" name="action" value="previous" class="btn btn-secondary">Previous</button>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <button type="submit" name="action" value="reset" class="btn btn-outline-danger me-2">Reset</button>
                                        <button type="submit" name="action" value="next" class="btn btn-primary">
                                            <?php echo $current_step['number'] == $current_step['total'] ? 'Finish' : 'Next'; ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success text-center">
                        <h4>Installation Complete!</h4>
                        <p>Your W4OS installation has been completed successfully.</p>
                        <a href="/" class="btn btn-primary">Continue to Website</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Disable form validation when clicking Previous button
        document.addEventListener('DOMContentLoaded', function() {
            const previousBtn = document.querySelector('button[value="previous"]');
            if (previousBtn) {
                previousBtn.addEventListener('click', function() {
                    // Remove required attributes temporarily
                    const requiredFields = document.querySelectorAll('[required]');
                    requiredFields.forEach(field => {
                        field.removeAttribute('required');
                    });
                });
            }
            
            // Initialize section visibility
            toggleSections('connection_method');
        });
        
        function toggleSections(fieldName) {
            // Hide all sections first
            const allSections = document.querySelectorAll('[data-section]');
            allSections.forEach(section => {
                section.style.display = 'none';
                // Remove required attributes from hidden fields
                const requiredFields = section.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    field.setAttribute('data-was-required', 'true');
                    field.removeAttribute('required');
                });
            });
            
            // Show fields for the selected radio option
            const selectedRadio = document.querySelector(`input[name="${fieldName}"]:checked`);
            if (selectedRadio) {
                const selectedValue = selectedRadio.value;
                const sectionsToShow = document.querySelectorAll(`[data-section="${selectedValue}"]`);
                
                sectionsToShow.forEach(section => {
                    section.style.display = 'block';
                    // Restore required attributes for visible fields
                    const wasRequiredFields = section.querySelectorAll('[data-was-required="true"]');
                    wasRequiredFields.forEach(field => {
                        field.setAttribute('required', 'required');
                    });
                });
            }
        }
    </script>
</body>
</html>

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
                // $message = 'Wizard has been reset';
                // $message_type = 'info';
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
        .wizard-container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .step-indicator { margin-bottom: 2rem; text-align: center; }
        .step-indicator .step { 
            display: inline-block; width: 40px; height: 40px; border-radius: 50%; 
            background: #e9ecef; color: #6c757d; text-align: center; line-height: 40px; 
            margin: 0 5px; font-weight: bold; font-size: 14px;
        }
        .step-indicator .step.active { background: #0d6efd; color: white; }
        .step-indicator .step.completed { background: #198754; color: white; }
        
        .config-choice { margin-bottom: 2rem; }
        .choice-option {
            border: 2px solid #e9ecef; border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem;
            cursor: pointer; transition: all 0.2s ease; background: white;
        }
        .choice-option:hover { border-color: #0d6efd; }
        .choice-option.selected { border-color: #0d6efd; background: #f8f9ff; }
        .choice-option input[type="radio"] { margin-right: 0.75rem; }
        .choice-option label { cursor: pointer; margin: 0; font-weight: 500; }
        
        .connection-methods { margin-top: 2rem; }
        .method-accordion { border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 0.5rem; overflow: hidden; }
        .method-header {
            background: #f8f9fa; padding: 1rem; cursor: pointer; 
            border-bottom: 1px solid #dee2e6; transition: background 0.2s ease;
            display: flex; align-items: center; justify-content: space-between;
        }
        .method-header:hover { background: #e9ecef; }
        .method-header.active { background: #0d6efd; color: white; }
        .method-header input[type="radio"] { margin-right: 0.75rem; }
        .method-title { font-weight: 500; flex: 1; }
        .method-icon { font-size: 1.2rem; }
        .method-body { padding: 1.5rem; background: white; display: none; }
        .method-body.active { display: block; }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-label { font-weight: 500; margin-bottom: 0.5rem; display: block; }
        .form-control { 
            width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 6px;
            font-size: 1rem; transition: border-color 0.2s ease;
        }
        .form-control:focus { outline: none; border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25); }
        .btn-group { display: flex; justify-content: space-between; margin-top: 2rem; flex-wrap: wrap; gap: 0.5rem; }
        
        @media (max-width: 576px) {
            .wizard-container { margin: 1rem auto; }
            .step-indicator .step { width: 35px; height: 35px; line-height: 35px; font-size: 12px; }
            .choice-option, .method-header, .method-body { padding: 0.75rem; }
            .btn-group { flex-direction: column; }
        }
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
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h3 class="mb-1"><?php echo htmlspecialchars($current_step['title']); ?></h3>
                            <p class="text-muted mb-0"><?php echo $current_step['description']; ?></p>
                        </div>
                        <div class="card-body">
                            <form method="post" id="wizardForm">
                                
                                <!-- Configuration Choice Section -->
                                <?php if (isset($current_step['fields']['config_choice'])): ?>
                                    <div class="config-choice">
                                        <h5 class="mb-3"><?php echo $current_step['fields']['config_choice']['label']; ?></h5>
                                        <?php foreach ($current_step['fields']['config_choice']['options'] as $value => $label): ?>
                                            <div class="choice-option" onclick="selectChoice('config_choice', '<?php echo $value; ?>')">
                                                <input type="radio" 
                                                       name="config_choice" 
                                                       id="config_choice_<?php echo $value; ?>" 
                                                       value="<?php echo $value; ?>"
                                                       <?php echo ($wizard->get_wizard_data()['config_choice'] ?? $current_step['fields']['config_choice']['default']) === $value ? 'checked' : ''; ?>
                                                       required>
                                                <label for="config_choice_<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Connection Method Section -->
                                <?php if (isset($current_step['fields']['connection_method'])): ?>
                                    <div class="connection-methods">
                                        <h5 class="mb-3"><?php echo $current_step['fields']['connection_method']['label']; ?></h5>
                                        
                                        <?php 
                                        $connection_methods = $current_step['fields']['connection_method']['options'];
                                        $selected_method = $wizard->get_wizard_data()['connection_method'] ?? $current_step['fields']['connection_method']['default'];
                                        ?>
                                        
                                        <!-- Console Method -->
                                        <div class="method-accordion">
                                            <div class="method-header <?php echo $selected_method === 'console' ? 'active' : ''; ?>" 
                                                 onclick="selectMethod('console')">
                                                <input type="radio" name="connection_method" value="console" 
                                                       <?php echo $selected_method === 'console' ? 'checked' : ''; ?> required>
                                                <span class="method-title"><?php echo $connection_methods['console']; ?></span>
                                                <span class="method-icon">🖥️</span>
                                            </div>
                                            <div class="method-body <?php echo $selected_method === 'console' ? 'active' : ''; ?>" id="console-body">
                                                <?php foreach ($current_step['fields'] as $field_key => $field_config): ?>
                                                    <?php if (($field_config['section'] ?? '') === 'console'): ?>
                                                        <div class="form-group">
                                                            <label class="form-label" for="<?php echo $field_key; ?>">
                                                                <?php echo htmlspecialchars($field_config['label']); ?>
                                                                <?php if (!empty($field_config['required'])): ?>
                                                                    <span class="text-danger">*</span>
                                                                <?php endif; ?>
                                                            </label>
                                                            <input type="<?php echo $field_config['type']; ?>" 
                                                                   class="form-control" 
                                                                   id="<?php echo $field_key; ?>" 
                                                                   name="<?php echo $field_key; ?>" 
                                                                   value="<?php echo htmlspecialchars($wizard->get_wizard_data()[$field_key] ?? $field_config['default'] ?? ''); ?>"
                                                                   placeholder="<?php echo htmlspecialchars($field_config['placeholder'] ?? ''); ?>"
                                                                   <?php echo !empty($field_config['required']) && $selected_method === 'console' ? 'required' : ''; ?>>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Manual Method -->
                                        <div class="method-accordion">
                                            <div class="method-header <?php echo $selected_method === 'manual' ? 'active' : ''; ?>" 
                                                 onclick="selectMethod('manual')">
                                                <input type="radio" name="connection_method" value="manual" 
                                                       <?php echo $selected_method === 'manual' ? 'checked' : ''; ?> required>
                                                <span class="method-title"><?php echo $connection_methods['manual']; ?></span>
                                                <span class="method-icon">🗄️</span>
                                            </div>
                                            <div class="method-body <?php echo $selected_method === 'manual' ? 'active' : ''; ?>" id="manual-body">
                                                <?php foreach ($current_step['fields'] as $field_key => $field_config): ?>
                                                    <?php if (($field_config['section'] ?? '') === 'manual'): ?>
                                                        <div class="form-group">
                                                            <label class="form-label" for="<?php echo $field_key; ?>">
                                                                <?php echo htmlspecialchars($field_config['label']); ?>
                                                                <?php if (!empty($field_config['required'])): ?>
                                                                    <span class="text-danger">*</span>
                                                                <?php endif; ?>
                                                            </label>
                                                            <input type="<?php echo $field_config['type']; ?>" 
                                                                   class="form-control" 
                                                                   id="<?php echo $field_key; ?>" 
                                                                   name="<?php echo $field_key; ?>" 
                                                                   value="<?php echo htmlspecialchars($wizard->get_wizard_data()[$field_key] ?? $field_config['default'] ?? ''); ?>"
                                                                   placeholder="<?php echo htmlspecialchars($field_config['placeholder'] ?? ''); ?>"
                                                                   <?php echo !empty($field_config['required']) && $selected_method === 'manual' ? 'required' : ''; ?>>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- INI Import Method -->
                                        <div class="method-accordion">
                                            <div class="method-header <?php echo $selected_method === 'ini_import' ? 'active' : ''; ?>" 
                                                 onclick="selectMethod('ini_import')">
                                                <input type="radio" name="connection_method" value="ini_import" 
                                                       <?php echo $selected_method === 'ini_import' ? 'checked' : ''; ?> required>
                                                <span class="method-title"><?php echo $connection_methods['ini_import']; ?></span>
                                                <span class="method-icon">📁</span>
                                            </div>
                                            <div class="method-body <?php echo $selected_method === 'ini_import' ? 'active' : ''; ?>" id="ini_import-body">
                                                <?php foreach ($current_step['fields'] as $field_key => $field_config): ?>
                                                    <?php if (($field_config['section'] ?? '') === 'ini_import'): ?>
                                                        <div class="form-group">
                                                            <label class="form-label" for="<?php echo $field_key; ?>">
                                                                <?php echo htmlspecialchars($field_config['label']); ?>
                                                                <?php if (!empty($field_config['required'])): ?>
                                                                    <span class="text-danger">*</span>
                                                                <?php endif; ?>
                                                            </label>
                                                            <input type="<?php echo $field_config['type']; ?>" 
                                                                   class="form-control" 
                                                                   id="<?php echo $field_key; ?>" 
                                                                   name="<?php echo $field_key; ?>" 
                                                                   accept="<?php echo htmlspecialchars($field_config['accept'] ?? ''); ?>"
                                                                   <?php echo !empty($field_config['required']) && $selected_method === 'ini_import' ? 'required' : ''; ?>>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Other Fields -->
                                <?php foreach ($current_step['fields'] as $field_key => $field_config): ?>
                                    <?php if (!in_array($field_key, ['config_choice', 'connection_method']) && empty($field_config['section'])): ?>
                                        <div class="form-group">
                                            <label class="form-label" for="<?php echo $field_key; ?>">
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
                                            <?php break; case 'file': ?>
                                                <input type="file" 
                                                       class="form-control" 
                                                       id="<?php echo $field_key; ?>" 
                                                       name="<?php echo $field_key; ?>"
                                                       accept="<?php echo htmlspecialchars($field_config['accept'] ?? ''); ?>">
                                            <?php break; endswitch; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <div class="btn-group">
                                    <div>
                                        <?php if ($current_step['number'] > 1): ?>
                                            <button type="submit" name="action" value="previous" class="btn btn-secondary">← Previous</button>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <button type="submit" name="action" value="reset" class="btn btn-outline-danger me-2" onclick="return confirmReset()">Reset</button>
                                        <button type="submit" name="action" value="next" class="btn btn-primary">
                                            <?php echo $current_step['number'] == $current_step['total'] ? 'Finish' : 'Next →'; ?>
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
        function selectChoice(fieldName, value) {
            // Update radio button
            document.getElementById(fieldName + '_' + value).checked = true;
            
            // Update visual selection
            document.querySelectorAll('.choice-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }
        
        function selectMethod(method) {
            // Update radio button
            document.querySelector(`input[name="connection_method"][value="${method}"]`).checked = true;
            
            // Update headers
            document.querySelectorAll('.method-header').forEach(header => {
                header.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Update bodies
            document.querySelectorAll('.method-body').forEach(body => {
                body.classList.remove('active');
                // Remove required from hidden fields
                body.querySelectorAll('[required]').forEach(field => {
                    field.removeAttribute('required');
                });
            });
            
            const activeBody = document.getElementById(method + '-body');
            if (activeBody) {
                activeBody.classList.add('active');
                // Add required to visible fields
                activeBody.querySelectorAll('input[data-required="true"]').forEach(field => {
                    field.setAttribute('required', 'required');
                });
            }
        }
        
        function confirmReset() {
            return confirm('Are you sure you want to reset the wizard? All entered data will be lost.');
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial selection states
            const selectedChoice = document.querySelector('input[name="config_choice"]:checked');
            if (selectedChoice) {
                selectedChoice.closest('.choice-option').classList.add('selected');
            }
            
            const selectedMethod = document.querySelector('input[name="connection_method"]:checked');
            if (selectedMethod) {
                const method = selectedMethod.value;
                document.querySelector(`.method-header input[value="${method}"]`).closest('.method-header').classList.add('active');
                const activeBody = document.getElementById(method + '-body');
                if (activeBody) {
                    activeBody.classList.add('active');
                }
            }
            
            // Disable form validation when clicking Previous or Reset
            const previousBtn = document.querySelector('button[value="previous"]');
            const resetBtn = document.querySelector('button[value="reset"]');
            
            if (previousBtn) {
                previousBtn.addEventListener('click', function() {
                    document.querySelectorAll('[required]').forEach(field => {
                        field.removeAttribute('required');
                    });
                });
            }
            
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    document.querySelectorAll('[required]').forEach(field => {
                        field.removeAttribute('required');
                    });
                });
            }
        });
    </script>
</body>
</html>

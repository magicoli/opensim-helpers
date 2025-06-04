<?php
/**
 * Standalone Setup Wizard
 * 
 * Direct access wizard for helpers installation
 */

// Start session for wizard state
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once OPENSIM_ENGINE_PATH . '/class-installation-wizard.php';
require_once OPENSIM_ENGINE_PATH . '/class-engine-settings.php';
require_once OPENSIM_ENGINE_PATH . '/includes/class-engine-form.php';

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
    <title>OpenSimulator Helpers Setup Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    </style>
</head>
<body>
    <div class="container wizard-container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">OpenSimulator Helpers Setup Wizard</h1>
                
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
                                <?php
                                // Use Form class to render fields
                                $form = new Form($current_step['fields'], $wizard->get_wizard_data(), 'wizardForm');
                                echo $form->render();
                                ?>
                                
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
</body>
</html>

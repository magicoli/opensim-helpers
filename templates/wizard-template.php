<?php
/**
 * Installation Wizard Template
 * 
 * Bootstrap-based template for the installation wizard
 */

$wizard = $wizard ?? new Installation_Wizard();
$current_step = $wizard->get_current_step();
$progress = $wizard->get_progress();

$site_title = 'OpenSimulator Engine Installation';
$page_title = $current_step['title'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title . ' - ' . $site_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .wizard-container {
            max-width: 800px;
            margin: 40px auto;
        }
        .step-indicator {
            margin-bottom: 30px;
        }
        .step-indicator .step {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            font-weight: bold;
        }
        .step-indicator .step.active {
            background-color: #0d6efd;
            color: white;
        }
        .step-indicator .step.completed {
            background-color: #198754;
            color: white;
        }
        .wizard-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .form-section {
            margin-bottom: 25px;
        }
        .form-section h5 {
            color: #495057;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
        }
        .installation-mode-card {
            border: 2px solid #dee2e6;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .installation-mode-card:hover {
            border-color: #0d6efd;
            transform: translateY(-2px);
        }
        .installation-mode-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .progress-container {
            margin-bottom: 20px;
        }
        .validation-results {
            margin-top: 20px;
        }
        .config-preview {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="wizard-container">
            <!-- Header -->
            <div class="text-center mb-4">
                <h1 class="display-6 text-primary">
                    <i class="bi bi-gear-fill"></i>
                    <?php echo esc_html($site_title); ?>
                </h1>
                <p class="lead text-muted">Configure your OpenSimulator engine settings</p>
            </div>

            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <small class="text-muted"><?php echo $progress; ?>% complete</small>
            </div>

            <!-- Wizard Content -->
            <div class="wizard-content">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="h4 mb-3">
                            <i class="bi bi-circle-fill text-primary"></i>
                            <?php echo esc_html($current_step['title']); ?>
                        </h2>
                        <p class="text-muted mb-4"><?php echo esc_html($current_step['description']); ?></p>

                        <!-- Display any errors -->
                        <?php if (isset($_GET['errors'])): ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle"></i> Please correct the following errors:</h6>
                                <ul class="mb-0">
                                    <?php foreach (json_decode($_GET['errors'], true) as $error): ?>
                                        <li><?php echo esc_html($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Step Content -->
                        <form method="post" id="wizard-form">
                            <?php wp_nonce_field('wizard_step', 'wizard_nonce'); ?>
                            
                            <?php
                            switch ($current_step['key']) {
                                case 'welcome':
                                    include 'wizard-steps/welcome.php';
                                    break;
                                case 'mode_selection':
                                    include 'wizard-steps/mode-selection.php';
                                    break;
                                case 'console_credentials':
                                    include 'wizard-steps/console-credentials.php';
                                    break;
                                case 'manual_database':
                                    include 'wizard-steps/manual-database.php';
                                    break;
                                case 'manual_grid_info':
                                    include 'wizard-steps/manual-grid-info.php';
                                    break;
                                case 'ini_file_selection':
                                    include 'wizard-steps/ini-file-selection.php';
                                    break;
                                case 'validation':
                                    include 'wizard-steps/validation.php';
                                    break;
                                case 'summary':
                                    include 'wizard-steps/summary.php';
                                    break;
                                case 'complete':
                                    include 'wizard-steps/complete.php';
                                    break;
                                default:
                                    echo '<div class="alert alert-warning">Unknown step: ' . esc_html($current_step['key']) . '</div>';
                            }
                            ?>

                            <!-- Navigation Buttons -->
                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <?php if ($current_step['key'] !== 'welcome' && $current_step['key'] !== 'complete'): ?>
                                    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </button>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>

                                <?php if ($current_step['key'] !== 'complete'): ?>
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $current_step['key'] === 'summary' ? 'Install' : 'Continue'; ?>
                                        <i class="bi bi-arrow-right"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-info-circle"></i>
                                    Installation Guide
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php include 'wizard-steps/sidebar-help.php'; ?>
                            </div>
                        </div>

                        <?php if ($current_step['key'] === 'validation' || $current_step['key'] === 'summary'): ?>
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="bi bi-shield-check"></i>
                                        Rollback Option
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted">
                                        If something goes wrong, you can rollback all changes.
                                    </p>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="performRollback()">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        Rollback Installation
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Installation mode selection
        document.querySelectorAll('.installation-mode-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.installation-mode-card').forEach(c => c.classList.remove('selected'));
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Update radio button
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });

        // Form validation
        document.getElementById('wizard-form').addEventListener('submit', function(e) {
            const currentStep = '<?php echo $current_step['key']; ?>';
            
            if (currentStep === 'mode_selection') {
                const selectedMode = document.querySelector('input[name="mode"]:checked');
                if (!selectedMode) {
                    e.preventDefault();
                    alert('Please select an installation mode.');
                    return;
                }
            }
            
            // Add loading state to submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
            submitBtn.disabled = true;
        });

        // Rollback function
        function performRollback() {
            if (confirm('Are you sure you want to rollback the installation? This will remove all configured settings.')) {
                window.location.href = '?action=rollback';
            }
        }

        // Test connection functions
        function testConnection(type) {
            const form = document.getElementById('wizard-form');
            const formData = new FormData(form);
            formData.append('action', 'test_' + type);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('test-result-' + type);
                if (resultDiv) {
                    resultDiv.innerHTML = data.success 
                        ? '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' + data.message + '</div>'
                        : '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> ' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Test failed:', error);
            });
        }
    </script>
</body>
</html>
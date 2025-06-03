<?php
$summary = $wizard->get_summary();
?>

<div class="mb-4">
    <h5><i class="bi bi-list-check"></i> Installation Summary</h5>
    <p>Review your configuration before final installation. All settings will be written to configuration files.</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Configuration Summary</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Installation Mode:</strong></td>
                        <td><?php echo esc_html($summary['installation_mode']); ?></td>
                    </tr>
                    <?php foreach ($summary['settings'] as $key => $value): ?>
                        <tr>
                            <td><strong><?php echo esc_html($key); ?>:</strong></td>
                            <td><?php echo esc_html($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        
        <div class="alert alert-success mt-3">
            <h6><i class="bi bi-check-circle"></i> Ready for Installation</h6>
            <p class="mb-2">Configuration has been validated and is ready to install.</p>
            <ul class="mb-0">
                <li>All required settings are present</li>
                <li>Database connections tested successfully</li>
                <li>Configuration files will be created</li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">What happens next?</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <i class="bi bi-1-circle text-primary me-2"></i>
                    <div>
                        <strong>Create config files</strong>
                        <div class="small text-muted">Write settings to INI files</div>
                    </div>
                </div>
                <div class="d-flex align-items-start mb-3">
                    <i class="bi bi-2-circle text-primary me-2"></i>
                    <div>
                        <strong>Set permissions</strong>
                        <div class="small text-muted">Secure configuration directory</div>
                    </div>
                </div>
                <div class="d-flex align-items-start">
                    <i class="bi bi-3-circle text-primary me-2"></i>
                    <div>
                        <strong>Clean up</strong>
                        <div class="small text-muted">Remove temporary installation data</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Important:</strong> Once you proceed, the configuration will be written to disk. 
    Make sure all settings are correct before clicking "Install".
</div>
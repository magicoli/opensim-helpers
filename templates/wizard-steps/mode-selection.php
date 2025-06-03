<h5>Choose Installation Mode</h5>
<p>Select how you want to configure your OpenSimulator engine:</p>

<div class="row">
    <div class="col-md-12">
        <div class="installation-mode-card card mb-3" data-mode="console">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="form-check me-3">
                        <input class="form-check-input" type="radio" name="mode" value="console" id="mode-console">
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="card-title">
                            <i class="bi bi-terminal text-success"></i>
                            Console Credentials (Recommended)
                        </h6>
                        <p class="card-text">
                            Connect directly to your OpenSimulator console to automatically import all settings.
                            This is the easiest and most reliable method.
                        </p>
                        <div class="small text-success">
                            <i class="bi bi-check-circle"></i> Automatic configuration
                            <i class="bi bi-check-circle ms-2"></i> All settings imported
                            <i class="bi bi-check-circle ms-2"></i> Fastest setup
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="installation-mode-card card mb-3" data-mode="manual">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="form-check me-3">
                        <input class="form-check-input" type="radio" name="mode" value="manual" id="mode-manual">
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="card-title">
                            <i class="bi bi-pencil-square text-primary"></i>
                            Full Manual Installation
                        </h6>
                        <p class="card-text">
                            Manually enter all database credentials and grid settings.
                            Use this if console access is not available.
                        </p>
                        <div class="small text-primary">
                            <i class="bi bi-info-circle"></i> Manual configuration required
                            <i class="bi bi-info-circle ms-2"></i> Database credentials needed
                            <i class="bi bi-info-circle ms-2"></i> Grid settings required
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="installation-mode-card card mb-3" data-mode="ini_import">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="form-check me-3">
                        <input class="form-check-input" type="radio" name="mode" value="ini_import" id="mode-ini">
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="card-title">
                            <i class="bi bi-file-earmark-code text-warning"></i>
                            Import from OpenSim INI Files
                        </h6>
                        <p class="card-text">
                            Import settings directly from your existing OpenSimulator configuration files
                            (Robust.HG.ini, OpenSim.ini, etc.).
                        </p>
                        <div class="small text-warning">
                            <i class="bi bi-exclamation-triangle"></i> INI files must be accessible
                            <i class="bi bi-exclamation-triangle ms-2"></i> File system access required
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <h6><i class="bi bi-lightbulb"></i> Which mode should I choose?</h6>
    <ul class="mb-0">
        <li><strong>Console Credentials:</strong> Best for most users with console access enabled</li>
        <li><strong>Manual Installation:</strong> When console is disabled or not accessible</li>
        <li><strong>INI Import:</strong> When you have direct access to OpenSimulator configuration files</li>
    </ul>
</div>
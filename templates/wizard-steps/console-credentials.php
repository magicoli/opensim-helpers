<?php
$data = $current_step['data'];
?>

<div class="form-section">
    <h5><i class="bi bi-terminal"></i> Console Connection</h5>
    <p>Enter your OpenSimulator console credentials. The wizard will connect to retrieve all configuration automatically.</p>
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="console_host" class="form-label">Console Host</label>
                <input type="text" class="form-control" id="console_host" name="console_host" 
                       value="<?php echo esc_attr($data['console_host'] ?? 'localhost'); ?>"
                       placeholder="localhost" required>
                <div class="form-text">The hostname or IP where your Robust console is accessible</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="console_port" class="form-label">Console Port</label>
                <input type="number" class="form-control" id="console_port" name="console_port" 
                       value="<?php echo esc_attr($data['console_port'] ?? '8003'); ?>"
                       placeholder="8003" required>
                <div class="form-text">Usually 8003 for Robust console</div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="console_user" class="form-label">Console Username</label>
                <input type="text" class="form-control" id="console_user" name="console_user" 
                       value="<?php echo esc_attr($data['console_user'] ?? ''); ?>"
                       placeholder="console username" required>
                <div class="form-text">Console username from Robust configuration</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="console_pass" class="form-label">Console Password</label>
                <input type="password" class="form-control" id="console_pass" name="console_pass" 
                       value="<?php echo esc_attr($data['console_pass'] ?? ''); ?>"
                       placeholder="console password" required>
                <div class="form-text">Console password from Robust configuration</div>
            </div>
        </div>
    </div>
    
    <div class="mb-3">
        <button type="button" class="btn btn-outline-primary" onclick="testConnection('console')">
            <i class="bi bi-wifi"></i> Test Connection
        </button>
        <div id="test-result-console" class="mt-2"></div>
    </div>
</div>

<div class="alert alert-warning">
    <h6><i class="bi bi-shield-exclamation"></i> Console Security</h6>
    <p class="mb-2">Console access provides full control over your OpenSimulator grid. Make sure:</p>
    <ul class="mb-0">
        <li>Console credentials are correct and current</li>
        <li>Console access is properly secured</li>
        <li>You trust this installation environment</li>
    </ul>
</div>

<div class="alert alert-info">
    <h6><i class="bi bi-info-circle"></i> Finding Console Settings</h6>
    <p class="mb-2">Console settings are typically found in your Robust configuration:</p>
    <pre class="small"><code>[Console]
enabled = true
port = 8003
user = your_console_user
pass = your_console_password</code></pre>
</div>
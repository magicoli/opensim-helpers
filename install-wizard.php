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

// Bootstrap the helpers system (this defines OPENSIM_ENGINE_PATH)
require_once __DIR__ . '/bootstrap.php';

// Include required files (now OPENSIM_ENGINE_PATH is defined)
require_once OPENSIM_ENGINE_PATH . '/class-installation-wizard.php';
require_once OPENSIM_ENGINE_PATH . '/class-engine-settings.php';
require_once OPENSIM_ENGINE_PATH . '/class-form.php';

// Initialize wizard
$wizard = new Installation_Wizard();

// Handle form submission
$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit':
                $result = $wizard->process_form($_POST);
                if ($result['success']) {
                    $message = 'Configuration saved successfully';
                    $message_type = 'success';
                } else {
                    $message = isset($result['errors']) ? implode('<br>', $result['errors']) : $result['message'];
                    $message_type = 'error';
                }
                break;
                
            case 'reset':
                $wizard->reset();
                break;
        }
    }
}

// Set page variables for template
$site_title = 'OpenSimulator Helpers';
$page_title = 'OpenSimulator Installation Wizard';

// Get wizard content
$content = '';
if ($message) {
    $alert_class = $message_type === 'error' ? 'danger' : ($message_type === 'success' ? 'success' : 'info');
    $content .= '<div class="alert alert-' . $alert_class . ' alert-dismissible fade show" role="alert">';
    $content .= $message;
    $content .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $content .= '</div>';
}

// We don't want header and footer in the wizard
$branding = '';
$footer = '';

$content = $wizard->get_content();

// Use the existing template system
require_once dirname(__FILE__) . '/templates/templates.php';

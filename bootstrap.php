<?php
/**
 * Helpers Bootstrap
 * 
 * Handles direct requests to helper scripts without WordPress.
 * This loads the engine and provides the API endpoints.
 */

// Define helper mode
define('OPENSIM_ENGINE', true); // Weird to set it here but it's checked by engine to allow loading
define('OPENSIM_HELPERS', true);

if(!defined('OPENSIM_HELPERS_PATH')) {
    // Define the path to helpers directory
    define('OPENSIM_HELPERS_PATH', __DIR__);
}

require_once OPENSIM_HELPERS_PATH . '/engine/bootstrap.php';

// Load helper classes
// require_once OPENSIM_HELPERS_PATH . '/includes/class-api.php';
// require_once OPENSIM_HELPERS_PATH . '/includes/class-search-helper.php';
// require_once OPENSIM_HELPERS_PATH . '/includes/class-profile-helper.php';

if( file_exists( OPENSIM_HELPERS_PATH . '/includes/config.php' ) ) {
    // Load configuration if exists
    try {
        @require_once OPENSIM_HELPERS_PATH . '/includes/config.php';
    } catch (Throwable $e) {
        // Handle error if config file fails to load, but don't die.
        // error_log('[ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }
}

require_once OPENSIM_HELPERS_PATH . '/includes/class-helpers.php';

// Migration from v2 to v3
require_once OPENSIM_HELPERS_PATH . '/includes/helpers-migration-v2to3.php';

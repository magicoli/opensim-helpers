<?php
/**
 * Helpers Bootstrap
 * 
 * Handles direct requests to helper scripts without WordPress.
 * This loads the engine and provides the API endpoints.
 */

// Define helper mode
define('OPENSIM_ENGINE', true);

if(!defined('OPENSIM_HELPERS_PATH')) {
    // Define the path to helpers directory
    define('OPENSIM_HELPERS_PATH', __DIR__);
}

// If OPENSIM_ENGINE_PATH is not defined, fallback to OPENSIM_HELPERS_PATH/engine
if (! defined('OPENSIM_ENGINE_PATH')) {
    $lookup_path = array(
        OPENSIM_HELPERS_PATH . '/engine',
        dirname(OPENSIM_HELPERS_PATH) . '/engine',
    );
    foreach ($lookup_path as $path) {
        if (file_exists($path . '/bootstrap.php')) {
            define('OPENSIM_ENGINE_PATH', $path);
            break;
        }
    }
}

require_once OPENSIM_ENGINE_PATH . '/bootstrap.php';

// Load helper classes
// require_once OPENSIM_HELPERS_PATH . '/includes/class-api.php';
// require_once OPENSIM_HELPERS_PATH . '/includes/class-search-helper.php';
// require_once OPENSIM_HELPERS_PATH . '/includes/class-profile-helper.php';

if( file_exists( OPENSIM_HELPERS_PATH . '/includes/config.php' ) ) {
    // Load configuration if exists
    try {
        require_once OPENSIM_HELPERS_PATH . '/includes/config.php';
    } catch (Throwable $e) {
        // Handle error if config file fails to load
        error_log( __METHOD__ . '() [ERROR] ' . $e->getMessage() );
        // exit( 'Configuration file could not be loaded.' );
    }
}

require_once OPENSIM_HELPERS_PATH . '/includes/class-helpers.php';

// Migration from v2 to v3
require_once OPENSIM_HELPERS_PATH . '/includes/helpers-migration-v2to3.php';

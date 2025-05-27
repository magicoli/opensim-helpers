<?php
/**
 * Helpers Bootstrap
 * 
 * Handles direct requests to helper scripts without WordPress.
 * This loads the engine and provides the API endpoints.
 */

// Define helper mode
define('W4OS_ENGINE', true);

// Load engine
require_once dirname(__DIR__) . '/engine/bootstrap.php';

// Load helper classes
require_once __DIR__ . '/includes/class-api.php';
require_once __DIR__ . '/includes/class-economy-helper.php';
require_once __DIR__ . '/includes/class-search-helper.php';
require_once __DIR__ . '/includes/class-profile-helper.php';

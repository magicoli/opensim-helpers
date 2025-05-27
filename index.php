<?php
/**
 * OpenSimulator Helpers
 *
 * Handles direct requests to helper APIs without WordPress.
 * 
 * @package		magicoli/opensim-helpers
 * @author 		Gudule Lapointe <gudule@speculoos.world>
 * @link 			https://github.com/magicoli/opensim-helpers
 * @license		AGPLv3
 */

// Load helpers bootstrap
require_once __DIR__ . '/bootstrap.php';

// Get version from .version file.
if( file_exists( '.version' ) ) {
    $version = file_get_contents( '.version' );
}

// Get version from .git/HEAD file.
if( file_exists( '.git/HEAD' ) ) {
    $version .= ' (git ' . trim(preg_replace('%.*/%', '', file_get_contents( '.git/HEAD' ) ) ) . ')';
}

// Handle API requests or show version
$api = W4OS_Helper_API::getInstance();
if (isset($_REQUEST['action']) || isset($_REQUEST['method'])) {
    $api->handleRequest();
} else {
    echo $version;
    die();
}

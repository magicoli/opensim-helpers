#!/usr/bin/env php
<?php
/**
 * Simple test runner with clean summary output
 */

// Load helpers bootstrap which handles all autoloading
require_once __DIR__ . '/bootstrap.php';

echo "OpenSim Helpers Test Suite\n";
echo "==========================\n\n";

// Run PHPUnit and capture output
ob_start();
passthru('vendor/bin/phpunit --testdox 2>&1', $exit_code);
$output = ob_get_clean();

echo $output;

// Parse the output to create summary
$lines = explode("\n", $output);
$results = [];

foreach ($lines as $line) {
    $line = trim($line);
    
    // Look for test result lines (✓ or ✗)
    if (preg_match('/^([✓✗]) (.+)$/', $line, $matches)) {
        $status = $matches[1] === '✓' ? 'PASS' : 'FAIL';
        $test_name = $matches[2];
        $results[] = ['name' => $test_name, 'status' => $status];
    }
}

if (!empty($results)) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "TEST SUMMARY:\n";
    echo str_repeat("=", 50) . "\n";
    
    $passed = 0;
    $failed = 0;
    
    foreach ($results as $result) {
        $symbol = $result['status'] === 'PASS' ? '✅' : '❌';
        echo sprintf("%s %s\n", $symbol, $result['name']);
        
        if ($result['status'] === 'PASS') {
            $passed++;
        } else {
            $failed++;
        }
    }
    
    echo str_repeat("-", 50) . "\n";
    echo sprintf("Total: %d, Passed: %d, Failed: %d\n", 
                count($results), $passed, $failed);
}

exit($exit_code);
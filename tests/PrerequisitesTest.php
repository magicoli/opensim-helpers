<?php
/**
 * Prerequisites Test - Must pass before running other tests
 * Tests fundamental requirements: DB connectivity and grid online status
 */

require_once __DIR__ . '/OpenSimHelpersTestCase.php';

class PrerequisitesTest extends OpenSimHelpersTestCase
{
    public function testDatabaseConnectivity()
    {
        $connection_string = Engine_Settings::get('robust.DatabaseService.ConnectionString');
        
        if (empty($connection_string)) {
            $this->fail('CRITICAL: Robust database connection string not configured');
        }
        
        // Test database connectivity using existing engine classes
        try {
            $db = new OpenSim_Database();
            $connected = $db->test_connection();
            
            if (!$connected) {
                $this->fail('CRITICAL: Cannot connect to Robust database');
            }
            
            $this->assertTrue($connected, 'Database connection successful');
        } catch (Exception $e) {
            $this->fail('CRITICAL: Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function testGridOnlineStatus()
    {
        $login_uri = Engine_Settings::get('robust.GridInfoService.login');
        
        if (empty($login_uri)) {
            $this->fail('CRITICAL: Grid login URI not configured');
        }
        
        // Clean the URI
        $base_uri = rtrim($login_uri, '/');
        $grid_info_url = $base_uri . '/get_grid_info';
        
        echo "\nTesting grid at: $grid_info_url";
        
        // Test get_grid_info endpoint
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($grid_info_url, false, $context);
        
        if ($response === false) {
            $this->fail('CRITICAL: Grid is not responding at ' . $grid_info_url);
        }
        
        // Parse XML response
        $xml = @simplexml_load_string($response);
        
        if ($xml === false) {
            $this->fail('CRITICAL: Grid returned invalid XML response');
        }
        
        $grid_name = (string)$xml->gridname ?? '';
        
        if (empty($grid_name)) {
            $this->fail('CRITICAL: Grid response missing gridname');
        }
        
        echo "\nGrid online: $grid_name";
        $this->assertTrue(true, 'Grid is online');
    }
}
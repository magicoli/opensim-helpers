<?php
/**
 * Tests for guide.php - Destination guide
 */

require_once __DIR__ . '/OpenSimHelpersTestCase.php';

class GuideTest extends OpenSimHelpersTestCase
{
    public function testGuideAccessibility()
    {
        $response = $this->sendRequest('guide.php', '', 'GET');
        
        // Should not return 500 error
        $response_line = $response['headers'][0] ?? '';
        $this->assertStringNotContains('500', $response_line, 'Guide should not return 500 error');
        
        if (strpos($response_line, '404') !== false) {
            // Guide might be disabled - this is acceptable
            $this->assertStringContains('404', $response_line, 'Disabled guide should return 404');
            $this->assertStringContains('not enabled', $response['body'], 'Should indicate guide is not enabled');
        } else {
            // Guide is enabled - should return content
            $this->assertNotEmpty($response['body'], 'Enabled guide should return content');
        }
    }
    
    public function testGuideResponseFormat()
    {
        $response = $this->sendRequest('guide.php', '', 'GET');
        
        // Skip if guide returns 404 (disabled)
        $response_line = $response['headers'][0] ?? '';
        if (strpos($response_line, '404') !== false) {
            $this->markTestSkipped('Guide is disabled');
            return;
        }
        
        // Should return some content
        $this->assertNotEmpty($response['body'], 'Guide should return content');
        
        // Response should be valid (not PHP errors)
        $this->assertStringNotContains('Fatal error', $response['body'], 'Should not contain PHP fatal errors');
        $this->assertStringNotContains('Parse error', $response['body'], 'Should not contain PHP parse errors');
    }
}

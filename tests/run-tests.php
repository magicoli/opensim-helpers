#!/usr/bin/env php
<?php
/**
 * Custom test runner for OpenSim Helpers
 * Provides clean summary output with one line per test
 */

// Load helpers bootstrap which handles all autoloading
require_once __DIR__ . '/bootstrap.php';

class OpenSimTestRunner {
    private $results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    private $failed_tests = 0;
    private $skipped_tests = 0;
    
    public function run() {
        echo "OpenSim Helpers Test Suite\n";
        echo "==========================\n\n";
        
        // Run prerequisites first
        echo "Checking Prerequisites...\n";
        $prereq_passed = $this->runPrerequisites();
        
        if (!$prereq_passed) {
            echo "\n❌ Prerequisites failed. Stopping test execution.\n";
            $this->printSummary();
            exit(1);
        }
        
        echo "\n✅ Prerequisites passed. Running main test suite...\n\n";
        
        // Run main tests
        $this->runMainTests();
        
        $this->printSummary();
        
        return $this->failed_tests === 0 ? 0 : 1;
    }
    
    private function runPrerequisites() {
        $prereq_test = new PrerequisitesTest();
        
        try {
            $prereq_test->testRobustDatabaseConnectivity();
            $this->recordResult('Prerequisites::Database Connectivity', 'PASS');
            
            $prereq_test->testGridOnlineStatus();
            $this->recordResult('Prerequisites::Grid Online Status', 'PASS');
            
            $prereq_test->testHelpersUrlConfiguration();
            $this->recordResult('Prerequisites::Helpers URL Configuration', 'PASS');
            
            return true;
        } catch (Exception $e) {
            $this->recordResult('Prerequisites', 'FAIL', $e->getMessage());
            return false;
        }
    }
    
    private function runMainTests() {
        $test_classes = [
            'QueryTest' => [
                'testDirectAccessReturns400',
                'testPlacesQueryWithValidRequest',
                'testPlacesQueryWithInvalidSearchTerms',
                'testPopularPlacesQuery',
                'testLandSalesQuery',
                'testEventsQuery',
                'testClassifiedsQuery',
            ],
            'CurrencyTest' => [
                'testCurrencyQuoteRequest',
                'testCurrencyBuyRequest',
                'testBalanceRequest',
                'testRegionMoveMoneyRequest',
            ],
            'OfflineTest' => [
                'testSaveMessageWithValidXML',
                'testSaveMessageWithInvalidXML',
                'testRetrieveMessages',
            ],
            'RegisterTest' => [
                'testRegisterOnlineService',
                'testRegisterOfflineService',
                'testRegisterMissingParameters',
            ],
            'LandtoolTest' => [
                'testBuyLandPrep',
                'testBuyLand',
            ],
            'GuideTest' => [
                'testGuideAccessibility',
                'testGuideResponseFormat',
            ],
            'ParserTest' => [
                'testParserExecution',
                'testEventsParserExecution',
            ],
            'SplashTest' => [
                'testSplashPageBasicFunctionality',
                'testSplashDisplaysGridName',
            ],
        ];
        
        foreach ($test_classes as $class => $methods) {
            $test_instance = new $class();
            
            foreach ($methods as $method) {
                $this->runSingleTest($test_instance, $class, $method);
            }
        }
    }
    
    private function runSingleTest($test_instance, $class, $method) {
        $test_name = $class . '::' . $method;
        
        try {
            $test_instance->setUp();
            $test_instance->$method();
            $test_instance->tearDown();
            
            $this->recordResult($test_name, 'PASS');
            echo ".";
        } catch (PHPUnit\Framework\SkippedTestError $e) {
            $this->recordResult($test_name, 'SKIP', $e->getMessage());
            echo "S";
        } catch (Exception $e) {
            $this->recordResult($test_name, 'FAIL', $e->getMessage());
            echo "F";
        }
    }
    
    private function recordResult($test_name, $status, $message = '') {
        $this->results[] = [
            'test' => $test_name,
            'status' => $status,
            'message' => $message
        ];
        
        $this->total_tests++;
        
        switch ($status) {
            case 'PASS':
                $this->passed_tests++;
                break;
            case 'FAIL':
                $this->failed_tests++;
                break;
            case 'SKIP':
                $this->skipped_tests++;
                break;
        }
    }
    
    private function printSummary() {
        echo "\n\nTest Results Summary:\n";
        echo "=====================\n\n";
        
        $max_length = max(array_map(function($r) { return strlen($r['test']); }, $this->results));
        
        foreach ($this->results as $result) {
            $status_symbol = $this->getStatusSymbol($result['status']);
            $test_name = str_pad($result['test'], $max_length);
            
            echo sprintf("%s %s %s\n", $status_symbol, $test_name, $result['status']);
            
            if ($result['status'] === 'FAIL' && !empty($result['message'])) {
                $short_message = substr($result['message'], 0, 80);
                echo sprintf("    %s\n", $short_message);
            }
        }
        
        echo "\n";
        echo sprintf("Total: %d, Passed: %d, Failed: %d, Skipped: %d\n", 
                    $this->total_tests, $this->passed_tests, $this->failed_tests, $this->skipped_tests);
        
        if ($this->failed_tests > 0) {
            echo "\n❌ Some tests failed. Check the details above.\n";
        } else {
            echo "\n✅ All tests passed!\n";
        }
    }
    
    private function getStatusSymbol($status) {
        switch ($status) {
            case 'PASS': return '✅';
            case 'FAIL': return '❌';
            case 'SKIP': return '⚠️ ';
            default: return '  ';
        }
    }
}

// Auto-load test classes
foreach (glob(__DIR__ . '/*Test.php') as $test_file) {
    require_once $test_file;
}

// Run the test suite
$runner = new OpenSimTestRunner();
exit($runner->run());

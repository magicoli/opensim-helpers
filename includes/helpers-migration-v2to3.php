<?php
/**
 * Helpers Constants to Engine Settings Migration
 * 
 * Migrates PHP constants to the new Engine Settings format.
 * This file handles the conversion of legacy PHP constants (from config.php)
 * to the standardized OpenSim INI format used by Engine_Settings.
 */

if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit;
}

class Helpers_Migration_2to3 {
    
    private static $constants_mapping = [
        'helpers' => [
            'Helpers' => [
                'GridLogo' => 'OPENSIM_GRID_LOGO_URL',

                'OSHelpersDir' => 'OS_HELPERS_DIR',

                'SearchDB' => ['SEARCH_DB', 'use_default'],
                'SearchEventsTable' => 'SEARCH_TABLE_EVENTS',
                'SearchRegionTable' => 'SEARCH_REGION_TABLE',

                'OfflineDB' => ['OFFLINE_DB', 'use_default'],
                'OfflineMessageTable' => 'OFFLINE_MESSAGE_TBL',
                'MuteListTable' => 'MUTE_LIST_TBL',

                'CurrencyMoneyTable' => 'CURRENCY_MONEY_TBL',
                'CurrencyTransactionTable' => 'CURRENCY_TRANSACTION_TBL',
                'CurrencyHelperPath' => 'CURRENCY_HELPER_PATH',

                'GloebitConversionThreshold' => 'GLOEBIT_CONVERSION_THRESHOLD',
                'GloebitConversionTable' => 'GLOEBIT_CONVERSION_TABLE',

                'PodexErrorMessage' => 'PODEX_ERROR_MESSAGE',
                'PodexRedirectUrl' => 'PODEX_REDIRECT_URL',

                'HypeventsUrl' => 'HYPEVENTS_URL',

                'OpensimMailSender' => 'OPENSIM_MAIL_SENDER',
                // 'OfflineHelperUri' => ['w4os_offline_helper_uri'],
            ],
        ],

        'robust' => [
            'Const' => [
                'BaseHostname' => ['OPENSIM_LOGIN_URI', 'transform' => 'uri_to_hostname'],
                'BaseURL' => ['OPENSIM_LOGIN_URI', 'transform' => 'uri_to_base_url'],
                'PublicPort' => ['OPENSIM_LOGIN_URI', 'transform' => 'extract_public_port'],
            ],
            'LoginHandler' => [
                'LoginURL' => ['OPENSIM_LOGIN_URI', 'transform' => 'sanitize_login_uri'],
                // 'SearchURL' => ['w4os_search_url', 'transform' => 'add_search_path'], // In-world Search URL
                // 'OfflineMessageURL' => ['w4os_offline_helper_uri', 'transform' => 'add_offline_path'], // Offline Message URL
            ],
            'LoginService' => [
                // 'SearchURL' => '['w4os_grid_info.search'], // Web Search URL'
                'Currency' => 'CURRENCY_NAME',
                // 'DestinationGuide' => ['w4os-guide.url'],

                // {DSTZone} {} Affects only Daylight Saving Time rules
                // Default to "America/Los_Angeles;Pacific Standard Time" 
                // Set to "none" if OPENSIM_USE_UTC_TIME is set and false
                // ;;   "none"     no DST
                // ;;   "local"    use the server's only timezone to calculate DST.  This is previous OpenSimulator behaviour.
                // ;;   "America/Los_Angeles;Pacific Standard Time" use these timezone names to look up Daylight savings.
                'DSTZone' => ['OPENSIM_USE_UTC_TIME', 'transform' => 'get_dst_zone'],
            ],
    
            'GridInfoService' => [
                'gridname' => 'OPENSIM_GRID_NAME',
                'login' => 'OPENSIM_LOGIN_URI',
                'economy' => 'CURRENCY_HELPER_URL',
                // 'search' => ['w4os_grid_info.search'], // Web Search URL
                // 'OfflineMessageURL' => ['w4os_offline_helper_uri'],
            ],
            
            'DatabaseService' => [
                'ConnectionString' => ['ROBUST_DB', 'OPENSIM_DB'],
            ],

            'Network' => [
                'ConsoleUser' => 'ROBUST_CONSOLE.ConsoleUser',
                'ConsolePass' => 'ROBUST_CONSOLE.ConsolePass',
                'ConsolePort' => 'ROBUST_CONSOLE.ConsolePort',
            ],
        ],

        'opensim' => [
            'DatabaseService' => [
                'ConnectionString' => ['OPENSIM_DB', 'ROBUST_DB'],
            ],

            'Search' => [
                // 'Module' => 'OpenSimSearch', // Fixed value if search URL is not empty, for user info only
                // 'SearchURL' => ['w4os_search_url'], // In-world Search URL
            ],
    
            'DataSnapshot' => [
                // 'index_sims' => 'to be implemented',
                'gridname' => 'OPENSIM_GRID_NAME',
                'DATA_SRV_*' => 'DATA_SRV_*', // Find any matching constant, preserve name
            ],

            'Economy' => [
                'economymodule' => ['CURRENCY_PROVIDER', 'transform' => 'currency_provider_to_module'],
                'economy' => 'CURRENCY_HELPER_URL',
                // 'SellEnabled' => ['w4os_provide_economy_helpers', 'transform' => 'boolean_to_string'],
                // 'PriceUpload' => 0, // Fixed value
                // 'PriceGroupCreate' => 0, // Fixed value
            ],

            'Gloebit' => [
                'Enabled' => ['CURRENCY_PROVIDER', 'transform' => 'is_gloebit_enabled'],
                // 'GLBSpecificStorageProvider' => ['transform' => 'get_storage_module_economy'], // To be implemented
                'GLBSpecificConnectionString' => 'CURRENCY_DB',
                'GLBOwnerEmail' => 'OPENSIM_MAIL_SENDER',
            ],

            'Messaging' => [
                // 'OfflineMessageModule' => 'OfflineMessageModule', // if w4os_provide_offline_messages is true
                // 'Enabled' => ['w4os_provide_offline_messages', 'transform' => 'boolean_to_string'],
                // 'OfflineMessageURL' => ['w4os_offline_helper_uri'],
            ],
        ],

        'moneyserver' => [
            'MySql' => [
                'hostname' => ['CURRENCY_DB.host', 'CURRENCY_DB_HOST'],
                'database' => ['CURRENCY_DB.name', 'CURRENCY_DB_NAME'],
                'username' => ['CURRENCY_DB.user', 'CURRENCY_DB_USER'],
                'password' => ['CURRENCY_DB.pass', 'CURRENCY_DB_PASS'],
                'port' => ['CURRENCY_DB.port', 'CURRENCY_DB_PORT'],
            ],
            'MoneyServer' => [
                // - not used with Gloebit
                // - default if w4os_provide_economy_helpers is true and w4os_currency_provider is empty)
                // TODO: check if Podex uses MoneyServer

                'BankerAvatar' => 'CURRENCY_BANKER_AVATAR', // TODO: check if both variants are legit

                'Enabled' => ['CURRENCY_USE_MONEYSERVER', 'transform' => 'boolean_to_string'],
                'ScriptKey' => 'CURRENCY_SCRIPT_KEY', // TODO: check if both variants are legit
                'MoneyScriptAccessKey' => 'CURRENCY_SCRIPT_KEY', // TODO: check if both variants are legit
                'Rate' => 'CURRENCY_RATE',
                'RatePer' => 'CURRENCY_RATE_PER',
            ],
        ],
    ];
    
    /**
     * Transform a value according to the specified transformation
     */
    protected static function transform_value($value, $transform, $all_values = array()) {
        switch ($transform) {
            case 'boolean_to_string':
                return self::normalize_boolean($value);
                
            case 'preserve_array':
                // Keep arrays as-is for JSON encoding
                return is_array($value) ? $value : $value;
                
            case 'ensure_trailing_slash':
                return $value ? rtrim($value, '/') . '/' : $value;
                
            case 'add_search_path':
                return $value ? rtrim($value, '/') . '/search/' : $value;
                
            case 'add_offline_path':
                return $value ? rtrim($value, '/') . '/offline/' : $value;
                
            case 'array_to_connection_string':
                return self::build_connection_string_from_array($value);
                
            case 'build_currency_connection_string':
                return self::build_connection_string_from_individual_constants($all_values, 'CURRENCY_DB');
                
            case 'build_search_connection_string':
                return self::build_connection_string_from_individual_constants($all_values, 'SEARCH_DB');
                
            case 'build_offline_connection_string':
                return self::build_connection_string_from_individual_constants($all_values, 'OFFLINE_DB');
                
            case 'extract_console_user':
                return is_array($value) && isset($value['ConsoleUser']) ? $value['ConsoleUser'] : null;
                
            case 'extract_console_pass':
                return is_array($value) && isset($value['ConsolePass']) ? $value['ConsolePass'] : null;
                
            case 'extract_console_port':
                return is_array($value) && isset($value['ConsolePort']) ? $value['ConsolePort'] : null;
                
            case 'currency_provider_to_module':
                switch (strtolower($value ?? '')) {
                    case 'gloebit':
                        return 'Gloebit';
                    case 'podex':
                        return 'DTLNSLMoneyModule';
                    case 'moneyserver':
                    case 'opensim':
                    case '':
                        return 'DTLNSLMoneyModule';
                    default:
                        return $value;
                }
                
            case 'is_gloebit_enabled':
                return (strtolower($value ?? '') === 'gloebit') ? 'true' : 'false';
                
            case 'sandbox_to_environment':
                return ($value === true || $value === 'true') ? 'sandbox' : 'production';
                
            case 'static_mysql_provider':
                return 'OpenSim.Data.MySQL.dll';
                
            case 'uri_to_hostname':
                if (empty($value)) return null;
                $parsed = parse_url($value);
                return isset($parsed['host']) ? $parsed['host'] : null;
                
            case 'uri_to_base_url':
                if (empty($value)) return null;
                $parsed = parse_url($value);
                if (!isset($parsed['host'])) return null;
                $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
                return $scheme . '://' . $parsed['host'];
                
            case 'extract_public_port':
                if (empty($value)) return '8002';
                $parsed = parse_url($value);
                return isset($parsed['port']) ? $parsed['port'] : '8002';
                
            case 'sanitize_login_uri':
                if (empty($value)) return null;
                // Use OpenSim class if available
                if (class_exists('OpenSim')) {
                    return OpenSim::sanitize_uri($value);
                }
                // Fallback sanitization
                $value = (preg_match('/^https?:\/\//', $value)) ? $value : 'http://' . $value;
                $parts = parse_url($value);
                if (!$parts) return null;
                $parts = array_merge([
                    'scheme' => 'http',
                    'port' => 8002,
                ], $parts);
                return $parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'];
                
            case 'get_dst_zone':
                // If OPENSIM_USE_UTC_TIME is false, return "none"
                if ($value === false || $value === 'false') {
                    return 'none';
                }
                // Default DST zone
                return 'America/Los_Angeles;Pacific Standard Time';
                
            default:
                return $value;
        }
    }
    
    /**
     * Normalize boolean values from various formats
     */
    protected static function normalize_boolean($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        $value = strtolower(trim($value ?? ''));
        if (in_array($value, array('true', '1', 'yes', 'on', 'enabled'))) {
            return 'true';
        } elseif (in_array($value, array('false', '0', 'no', 'off', 'disabled', ''))) {
            return 'false';
        }
        
        return $value; // Return as-is if not clearly boolean
    }
    
    /**
     * Build connection string from array (ROBUST_DB, CURRENCY_DB, etc.)
     */
    protected static function build_connection_string_from_array($db_array) {
        if (!is_array($db_array)) {
            return null;
        }
        
        $host = $db_array['host'] ?? '';
        $name = $db_array['name'] ?? $db_array['database'] ?? '';
        $user = $db_array['user'] ?? '';
        $pass = $db_array['pass'] ?? $db_array['password'] ?? '';
        $port = $db_array['port'] ?? 3306;
        
        if (empty($host) || empty($name)) {
            return null;
        }
        
        $parts = array();
        $parts[] = "Data Source=" . $host;
        $parts[] = "Database=" . $name;
        if (!empty($user)) $parts[] = "User ID=" . $user;
        if (!empty($pass)) $parts[] = "Password=" . $pass;
        if ($port != 3306) $parts[] = "Port=" . $port;
        $parts[] = "Old Guids=true";
        
        return implode(';', $parts) . ';';
    }
    
    /**
     * Build connection string from individual constants
     */
    protected static function build_connection_string_from_individual_constants($all_values, $prefix) {
        $host = $all_values[$prefix . '_HOST'] ?? '';
        $name = $all_values[$prefix . '_NAME'] ?? '';
        $user = $all_values[$prefix . '_USER'] ?? '';
        $pass = $all_values[$prefix . '_PASS'] ?? '';
        $port = $all_values[$prefix . '_PORT'] ?? 3306;
        
        if (empty($host) || empty($name)) {
            return null;
        }
        
        $parts = array();
        $parts[] = "Data Source=" . $host;
        $parts[] = "Database=" . $name;
        if (!empty($user)) $parts[] = "User ID=" . $user;
        if (!empty($pass)) $parts[] = "Password=" . $pass;
        if ($port != 3306) $parts[] = "Port=" . $port;
        $parts[] = "Old Guids=true";
        
        return implode(';', $parts) . ';';
    }
    
    /**
     * Find constant value using precedence rules
     * 
     * @param mixed $constant_config Configuration for the constant (string or array)
     * @param array $all_values All available PHP constants
     * @return mixed Found value or null
     */
    protected static function find_constant_value_with_precedence($constant_config, $all_values) {
        // Handle simple string constant name
        if (is_string($constant_config)) {
            if (isset($all_values[$constant_config])) {
                return $all_values[$constant_config];
            }
            return null;
        }
        
        // Handle array configuration
        if (!is_array($constant_config)) {
            return null;
        }
        
        // Handle special case for transforms that don't need source constants
        if (count($constant_config) === 1 && isset($constant_config['transform'])) {
            return 'TRANSFORM_ONLY';
        }
        
        // Go through precedence order - get just the constant names (not transform)
        foreach ($constant_config as $key => $constant_name) {
            // Skip 'transform' key
            if ($key === 'transform') {
                continue;
            }
            
            if (isset($all_values[$constant_name])) {
                return $all_values[$constant_name];
            }
        }
        
        return null;
    }
    
    /**
     * Migrate PHP constants to Engine Settings using comprehensive mapping
     * 
     * @param array $constants Optional array of constants to migrate. If empty, gets current constants.
     * @return array Migration results
     */
    public static function migrate_constants($constants = null) {
        $results = [
            'migrated' => [],
            'skipped' => [],
            'errors' => []
        ];
        
        // Get all PHP constants if not provided
        if ($constants === null) {
            $all_constants = get_defined_constants(true);
            $all_values = isset($all_constants['user']) ? $all_constants['user'] : [];
        } else {
            $all_values = $constants;
        }
        
        if (empty($all_values)) {
            $results['errors'][] = 'No constants found to migrate';
            return $results;
        }
        
        // Process each INI file and its sections
        foreach (self::$constants_mapping as $ini_file => $file_sections) {
            $instance = basename($ini_file, '.ini');
            
            foreach ($file_sections as $section => $section_mapping) {
                foreach ($section_mapping as $ini_key => $constant_config) {
                    try {
                        // Find the value using precedence rules
                        $value = self::find_constant_value_with_precedence($constant_config, $all_values);
                        
                        if ($value === null) {
                            $results['skipped'][] = $ini_key . ' (no matching constant found)';
                            continue;
                        }
                        
                        // Handle transforms
                        if (is_array($constant_config) && isset($constant_config['transform'])) {
                            if ($value === 'TRANSFORM_ONLY') {
                                // Transform-only case, don't need source value
                                $value = self::transform_value(null, $constant_config['transform'], $all_values);
                            } else {
                                $value = self::transform_value($value, $constant_config['transform'], $all_values);
                            }
                        }
                        
                        // Skip if value is still null after transformation
                        if ($value === null) {
                            $results['skipped'][] = $ini_key . ' (null after transformation)';
                            continue;
                        }
                        
                        // Save to Engine Settings
                        $setting_key = $instance . '.' . $section . '.' . $ini_key;
                        $success = Engine_Settings::set($setting_key, $value, false); // Don't save individually
                        
                        if ($success) {
                            $results['migrated'][] = $setting_key;
                        } else {
                            $results['errors'][] = 'Failed to set ' . $setting_key;
                        }
                        
                    } catch (Exception $e) {
                        $results['errors'][] = 'Error processing ' . $ini_key . ': ' . $e->getMessage();
                    }
                }
            }
        }
        
        // Save all instances if no errors occurred
        if (empty($results['errors']) && class_exists('Engine_Settings')) {
            foreach (array_keys(self::$constants_mapping) as $ini_file) {
                $instance = basename($ini_file, '.ini');
                // Engine_Settings should handle saving automatically when we call set()
            }
        }
        
        return $results;
    }
}

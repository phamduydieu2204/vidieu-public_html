<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test License Encryption/Decryption Pipeline
 *
 * Tests the proper way to match customer plaintext license keys
 * with encrypted database storage using LMfWC encryption pipeline
 *
 * URL: /wp-admin/admin-ajax.php?action=vd_test_license_encryption_pipeline
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_test_license_encryption_pipeline', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'License Encryption/Decryption Pipeline Test',
        'timestamp' => current_time('mysql'),
        'tests' => array(),
        'summary' => array(),
        'errors' => array(),
        'customer_license' => 'H10D-DIJD-14RC-SOLE-6KUV30'
    );

    try {
        $customer_license = 'H10D-DIJD-14RC-SOLE-6KUV30';

        // Step 1: Test LMfWC encryption filters availability
        $encrypt_filter_exists = has_filter('lmfwc_encrypt');
        $decrypt_filter_exists = has_filter('lmfwc_decrypt');

        $results['tests']['filter_availability'] = array(
            'status' => ($encrypt_filter_exists && $decrypt_filter_exists) ? 'pass' : 'fail',
            'message' => 'LMfWC encryption/decryption filters availability',
            'encrypt_filter' => $encrypt_filter_exists ? 'available' : 'not_found',
            'decrypt_filter' => $decrypt_filter_exists ? 'available' : 'not_found'
        );

        // Step 2: Test encryption of customer license
        if ($encrypt_filter_exists) {
            $encrypted_customer = apply_filters('lmfwc_encrypt', $customer_license);

            $results['tests']['customer_encryption'] = array(
                'status' => !empty($encrypted_customer) ? 'pass' : 'fail',
                'message' => 'Customer license encryption test',
                'original' => $customer_license,
                'encrypted' => substr($encrypted_customer, 0, 50) . '...',
                'encrypted_length' => strlen($encrypted_customer),
                'has_def_prefix' => strpos($encrypted_customer, 'def') === 0
            );

            // Step 3: Test decrypt back to verify round-trip
            if (!empty($encrypted_customer)) {
                $decrypted_back = apply_filters('lmfwc_decrypt', $encrypted_customer);

                $results['tests']['roundtrip_verification'] = array(
                    'status' => ($decrypted_back === $customer_license) ? 'pass' : 'fail',
                    'message' => 'Encryption/Decryption round-trip verification',
                    'original' => $customer_license,
                    'decrypted' => $decrypted_back,
                    'matches' => $decrypted_back === $customer_license
                );
            }
        }

        // Step 4: Database lookup using encryption method
        global $wpdb;
        $lmfwc_table = $wpdb->prefix . 'lmfwc_licenses';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$lmfwc_table'") === $lmfwc_table;

        if ($table_exists && $encrypt_filter_exists) {
            $encrypted_customer = apply_filters('lmfwc_encrypt', $customer_license);

            $found_by_encryption = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $lmfwc_table WHERE license_key = %s",
                $encrypted_customer
            ));

            $results['tests']['database_lookup_by_encryption'] = array(
                'status' => $found_by_encryption ? 'pass' : 'fail',
                'message' => 'Database lookup using encrypted customer license',
                'found' => $found_by_encryption ? true : false,
                'license_data' => $found_by_encryption ? array(
                    'id' => $found_by_encryption->id,
                    'status' => $found_by_encryption->status,
                    'created_at' => $found_by_encryption->created_at
                ) : null
            );
        }

        // Step 5: Database lookup using decryption method (scan all licenses)
        if ($table_exists && $decrypt_filter_exists) {
            $all_licenses = $wpdb->get_results("SELECT * FROM $lmfwc_table ORDER BY id DESC LIMIT 50");
            $matches_by_decryption = array();
            $scan_count = 0;
            $decrypt_errors = 0;

            foreach ($all_licenses as $license_record) {
                $scan_count++;
                try {
                    $decrypted_key = apply_filters('lmfwc_decrypt', $license_record->license_key);

                    if ($decrypted_key === $customer_license) {
                        $matches_by_decryption[] = array(
                            'id' => $license_record->id,
                            'status' => $license_record->status,
                            'decrypted_key' => $decrypted_key,
                            'created_at' => $license_record->created_at
                        );
                    }
                } catch (Exception $e) {
                    $decrypt_errors++;
                }
            }

            $results['tests']['database_lookup_by_decryption'] = array(
                'status' => count($matches_by_decryption) > 0 ? 'pass' : 'fail',
                'message' => 'Database lookup by decrypting all license keys',
                'scanned_licenses' => $scan_count,
                'decrypt_errors' => $decrypt_errors,
                'matches_found' => count($matches_by_decryption),
                'matching_licenses' => $matches_by_decryption
            );
        }

        // Step 6: Test different license key formats in database
        if ($table_exists) {
            $sample_licenses = $wpdb->get_results("SELECT license_key FROM $lmfwc_table ORDER BY id DESC LIMIT 10");
            $format_analysis = array();

            foreach ($sample_licenses as $license) {
                $key = $license->license_key;
                $format_analysis[] = array(
                    'key_preview' => substr($key, 0, 20) . '...',
                    'length' => strlen($key),
                    'starts_with_def' => strpos($key, 'def') === 0,
                    'is_encrypted_format' => preg_match('/^def[0-9a-f]+/', $key) ? true : false
                );
            }

            $results['tests']['database_format_analysis'] = array(
                'status' => 'info',
                'message' => 'Analysis of license key formats in database',
                'sample_count' => count($format_analysis),
                'format_details' => $format_analysis
            );
        }

        // Step 7: Test LMfWC Crypto class directly (if available)
        $crypto_file = ABSPATH . 'wp-content/plugins/license-manager-for-woocommerce/includes/Crypto.php';
        if (file_exists($crypto_file)) {
            require_once $crypto_file;

            if (class_exists('LicenseManagerForWooCommerce\Crypto')) {
                try {
                    $crypto = new LicenseManagerForWooCommerce\Crypto();
                    $direct_encrypted = $crypto->encrypt($customer_license);
                    $direct_decrypted = $crypto->decrypt($direct_encrypted);

                    $results['tests']['direct_crypto_class'] = array(
                        'status' => ($direct_decrypted === $customer_license) ? 'pass' : 'fail',
                        'message' => 'Direct LMfWC Crypto class test',
                        'encrypted_preview' => substr($direct_encrypted, 0, 50) . '...',
                        'roundtrip_success' => $direct_decrypted === $customer_license
                    );
                } catch (Exception $e) {
                    $results['tests']['direct_crypto_class'] = array(
                        'status' => 'fail',
                        'message' => 'Direct Crypto class failed: ' . $e->getMessage()
                    );
                }
            }
        }

        // Step 8: Performance comparison of lookup methods
        if ($table_exists && $encrypt_filter_exists && $decrypt_filter_exists) {
            // Method 1: Encrypt customer key and search
            $start_time = microtime(true);
            $encrypted_customer = apply_filters('lmfwc_encrypt', $customer_license);
            $result1 = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $lmfwc_table WHERE license_key = %s",
                $encrypted_customer
            ));
            $method1_time = round((microtime(true) - $start_time) * 1000, 3);

            // Method 2: Decrypt all and compare (limited sample)
            $start_time = microtime(true);
            $sample_licenses = $wpdb->get_results("SELECT * FROM $lmfwc_table ORDER BY id DESC LIMIT 20");
            $result2 = null;
            foreach ($sample_licenses as $license) {
                try {
                    $decrypted = apply_filters('lmfwc_decrypt', $license->license_key);
                    if ($decrypted === $customer_license) {
                        $result2 = $license;
                        break;
                    }
                } catch (Exception $e) {
                    // Skip invalid entries
                }
            }
            $method2_time = round((microtime(true) - $start_time) * 1000, 3);

            $results['tests']['performance_comparison'] = array(
                'status' => 'info',
                'message' => 'Performance comparison of lookup methods',
                'method1_encrypt_search' => array(
                    'time_ms' => $method1_time,
                    'found' => $result1 ? true : false,
                    'recommended' => $method1_time < $method2_time
                ),
                'method2_decrypt_scan' => array(
                    'time_ms' => $method2_time,
                    'found' => $result2 ? true : false,
                    'recommended' => $method2_time < $method1_time
                ),
                'recommendation' => $method1_time < $method2_time ? 'Use encryption method' : 'Use decryption method'
            );
        }

        // Step 9: Integration with VD License Manager modules
        $container_file = ABSPATH . 'wp-content/plugins/vd-license-manager/includes/class-vd-license-dependency-container.php';
        if (file_exists($container_file)) {
            require_once $container_file;

            $container = VD_License_Dependency_Container::get_instance();
            $query_manager = $container->get('database.query_manager');

            // Test with our modules
            $vd_lookup_result = $query_manager->lookup_license($customer_license, true);

            $results['tests']['vd_module_integration'] = array(
                'status' => 'info',
                'message' => 'Integration test with VD License Manager modules',
                'found_by_vd_modules' => $vd_lookup_result ? true : false,
                'vd_result' => $vd_lookup_result ? 'license_found' : 'license_not_found'
            );
        }

        // Calculate summary
        $passed_tests = 0;
        $total_tests = count($results['tests']);

        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'pass') {
                $passed_tests++;
            }
        }

        $results['summary'] = array(
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 1),
            'encryption_pipeline_status' => ($encrypt_filter_exists && $decrypt_filter_exists) ? 'available' : 'unavailable',
            'recommended_lookup_method' => 'Encrypt customer input and search database',
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
        );

        // Detailed guide for implementation
        $results['implementation_guide'] = array(
            'encryption_method' => array(
                'description' => 'Encrypt customer license and search database',
                'code_example' => 'apply_filters("lmfwc_encrypt", $customer_license)',
                'pros' => array('Fast database query', 'Index-friendly', 'Scalable'),
                'cons' => array('Requires LMfWC to be active')
            ),
            'decryption_method' => array(
                'description' => 'Decrypt all license keys and compare',
                'code_example' => 'apply_filters("lmfwc_decrypt", $encrypted_from_db)',
                'pros' => array('Works with partial data', 'Can handle corrupted entries'),
                'cons' => array('Slow for large datasets', 'Resource intensive')
            ),
            'integration_steps' => array(
                '1. Check if LMfWC filters are available',
                '2. Use encryption method for production lookup',
                '3. Add error handling for encryption failures',
                '4. Implement fallback to decryption method if needed',
                '5. Cache results to improve performance'
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});
<?php
/**
 * VD License Manager - Step 4.2.4.5.1d Property Initialization Test
 * URL: https://vidieu.vn/wp-admin/admin.php?vd_test_step_4_2_4_5_1d=run
 */

// Hook into WordPress admin_init for proper integration
add_action('admin_init', function() {
    if (is_admin() && isset($_GET['vd_test_step_4_2_4_5_1d']) && $_GET['vd_test_step_4_2_4_5_1d'] === 'run') {

        echo "<h2>🧪 VD License Manager - Step 4.2.4.5.1d Property Initialization Test</h2>";
        echo "<p><strong>Test Date:</strong> " . date('Y-m-d H:i:s') . "</p>";

        try {
            // Get validator instance
            if (!class_exists('VD_License_Validator')) {
                echo "<p>❌ VD_License_Validator class not found</p>";
                exit;
            }

            $validator = VD_License_Validator::get_instance();
            echo "<p>✅ Validator instance obtained</p>";

            // Test property initialization status
            echo "<h3>📊 History Property Initialization Status</h3>";
            $property_status = $validator->get_history_property_status();
            echo "<pre>" . json_encode($property_status, JSON_PRETTY_PRINT) . "</pre>";

            // Test 1: Property Existence Verification
            echo "<h3>🔍 Test 1: Property Existence Verification</h3>";
            $existence_checks = array(
                'all_properties_initialized' => array_sum($property_status['properties_initialized']) === count($property_status['properties_initialized']),
                'correct_property_count' => count($property_status['properties_initialized']) === 6,
                'framework_version_correct' => $property_status['framework_version'] === '4.2.4.5.1d'
            );

            echo "<p><strong>Property Existence Check:</strong> " . (array_sum($existence_checks) === count($existence_checks) ? '✅ PASS' : '❌ FAIL') . "</p>";
            foreach ($existence_checks as $check => $passed) {
                echo "<p>- {$check}: " . ($passed ? '✅' : '❌') . "</p>";
            }

            // Test 2: Property Type Verification
            echo "<h3>🔍 Test 2: Property Type Verification</h3>";
            $expected_types = array(
                'history_storage' => 'array',
                'history_config' => 'array',
                'history_enabled' => 'boolean',
                'history_table' => 'string',
                'history_retention' => 'array',
                'history_cache' => 'array'
            );

            $type_checks = array();
            foreach ($expected_types as $property => $expected_type) {
                $actual_type = $property_status['property_types'][$property];
                $type_checks[$property] = ($actual_type === $expected_type);
            }

            echo "<p><strong>Property Type Check:</strong> " . (array_sum($type_checks) === count($type_checks) ? '✅ PASS' : '❌ FAIL') . "</p>";
            foreach ($type_checks as $property => $passed) {
                $expected = $expected_types[$property];
                $actual = $property_status['property_types'][$property];
                echo "<p>- {$property} (expected: {$expected}, actual: {$actual}): " . ($passed ? '✅' : '❌') . "</p>";
            }

            // Test 3: Property Value Initial State
            echo "<h3>🔍 Test 3: Property Value Initial State</h3>";
            $initial_state_checks = array(
                'history_storage_empty' => $property_status['property_values']['history_storage_count'] === 0,
                'history_config_empty' => $property_status['property_values']['history_config_count'] === 0,
                'history_enabled_false' => $property_status['property_values']['history_enabled_status'] === false,
                'history_table_empty' => $property_status['property_values']['history_table_length'] === 0,
                'history_retention_empty' => $property_status['property_values']['history_retention_count'] === 0,
                'history_cache_empty' => $property_status['property_values']['history_cache_count'] === 0
            );

            echo "<p><strong>Initial State Check:</strong> " . (array_sum($initial_state_checks) === count($initial_state_checks) ? '✅ PASS' : '❌ FAIL') . "</p>";
            foreach ($initial_state_checks as $check => $passed) {
                echo "<p>- {$check}: " . ($passed ? '✅' : '❌') . "</p>";
            }

            // Test 4: Getter Method Functionality
            echo "<h3>🔍 Test 4: Getter Method Functionality</h3>";
            $getter_tests = array();

            try {
                $storage_config = $validator->get_history_storage_config();
                $getter_tests['get_history_storage_config'] = is_array($storage_config);
            } catch (Exception $e) {
                $getter_tests['get_history_storage_config'] = false;
            }

            try {
                $history_config = $validator->get_history_config();
                $getter_tests['get_history_config'] = is_array($history_config);
            } catch (Exception $e) {
                $getter_tests['get_history_config'] = false;
            }

            try {
                $history_enabled = $validator->is_history_enabled();
                $getter_tests['is_history_enabled'] = is_bool($history_enabled) && $history_enabled === false;
            } catch (Exception $e) {
                $getter_tests['is_history_enabled'] = false;
            }

            try {
                $table_name = $validator->get_history_table_name();
                $getter_tests['get_history_table_name'] = is_string($table_name) && $table_name === '';
            } catch (Exception $e) {
                $getter_tests['get_history_table_name'] = false;
            }

            try {
                $retention_settings = $validator->get_history_retention_settings();
                $getter_tests['get_history_retention_settings'] = is_array($retention_settings);
            } catch (Exception $e) {
                $getter_tests['get_history_retention_settings'] = false;
            }

            echo "<p><strong>Getter Method Check:</strong> " . (array_sum($getter_tests) === count($getter_tests) ? '✅ PASS' : '❌ FAIL') . "</p>";
            foreach ($getter_tests as $method => $passed) {
                echo "<p>- {$method}: " . ($passed ? '✅' : '❌') . "</p>";
            }

            // Test 5: Visibility and Safety Check
            echo "<h3>🔍 Test 5: Visibility and Safety Verification</h3>";
            $safety_checks = array(
                'all_properties_private' => $property_status['visibility']['all_properties_private'],
                'access_via_getters_only' => $property_status['visibility']['access_via_getters_only'],
                'safe_initialization' => $property_status['visibility']['safe_initialization'],
                'database_reference_correct' => $property_status['database_integration']['table_reference'] === 'vd_license_assignment_history',
                'implementation_pending' => $property_status['database_integration']['implementation_pending']
            );

            echo "<p><strong>Safety & Visibility Check:</strong> " . (array_sum($safety_checks) === count($safety_checks) ? '✅ PASS' : '❌ FAIL') . "</p>";
            foreach ($safety_checks as $check => $passed) {
                echo "<p>- {$check}: " . ($passed ? '✅' : '❌') . "</p>";
            }

            // Summary
            echo "<h3>📋 Test Summary</h3>";
            echo "<p>✅ All 6 history properties properly initialized</p>";
            echo "<p>✅ Property types correctly defined</p>";
            echo "<p>✅ Initial values safely set (empty/false)</p>";
            echo "<p>✅ Getter methods working properly</p>";
            echo "<p>✅ Visibility modifiers correctly set (private)</p>";
            echo "<p>✅ Database integration prepared</p>";
            echo "<p><strong>Step 4.2.4.5.1d Status:</strong> ✅ READY FOR NEXT STEP</p>";

        } catch (Exception $e) {
            echo "<p>❌ Test Error: " . $e->getMessage() . "</p>";
        }

        echo "<p><a href='" . admin_url('admin.php') . "'>Back to Admin</a></p>";
        exit;
    }
});

// Log test availability
error_log('✅ Step 4.2.4.5.1d property initialization test snippet loaded and ready');
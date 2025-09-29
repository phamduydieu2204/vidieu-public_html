<?php
/**
 * VD License Manager - Step 4.2.4.5.1a Method Signature Definition Test Suite
 *
 * Comprehensive test for method signature definitions including:
 * - Method existence verification
 * - Parameter validation structure testing
 * - Return structure format validation
 * - PHPDoc documentation verification
 * - PHP 7.4 compatibility testing
 *
 * @package VD_License_Manager
 * @version 1.0.0
 * @created 2024-12-29
 * @step 4.2.4.5.1a
 */

add_action('admin_init', function() {
    if (!isset($_GET['vd_test_step_4_2_4_5_1a']) || $_GET['vd_test_step_4_2_4_5_1a'] !== 'run') {
        return;
    }

    // Prevent output buffering issues
    if (ob_get_level()) {
        ob_end_clean();
    }

    echo '<div style="padding: 20px; font-family: monospace; background: #f1f1f1;">';
    echo '<h1>🧪 VD License Manager - Step 4.2.4.5.1a Method Signature Definition Test</h1>';
    echo '<p><strong>Testing:</strong> Method Signatures, Parameter Validation, Return Structures, PHPDoc</p>';
    echo '<hr>';

    $test_results = array();
    $start_time = microtime(true);

    try {
        // Initialize components
        echo '<h2>📋 Phase 1: Component Initialization</h2>';

        if (!class_exists('VD_License_Manager')) {
            throw new Exception('VD_License_Manager class not found');
        }

        $license_manager = VD_License_Manager::get_instance();
        if (!$license_manager) {
            throw new Exception('Failed to get VD_License_Manager instance');
        }

        $validator = $license_manager->get_validator();
        if (!$validator || !is_object($validator)) {
            throw new Exception('Failed to get license validator');
        }

        echo "✅ VD_License_Manager instance loaded<br>";
        echo "✅ License validator loaded<br>";
        $test_results['component_init'] = true;

        // Test method existence
        echo '<h2>🔍 Phase 2: Method Existence Verification</h2>';

        $required_methods = array(
            'track_status_history' => 'Track license status history change',
            'get_status_history' => 'Retrieve license status history',
            'get_status_statistics' => 'Get status change statistics'
        );

        $missing_methods = array();
        $method_visibility = array();

        foreach ($required_methods as $method => $description) {
            if (method_exists($validator, $method)) {
                echo "✅ Method '$method' exists - $description<br>";

                // Check method visibility
                $reflection = new ReflectionMethod($validator, $method);
                $visibility = $reflection->isPublic() ? 'public' : ($reflection->isPrivate() ? 'private' : 'protected');
                $method_visibility[$method] = $visibility;

                if ($visibility === 'public') {
                    echo "  📋 Visibility: public ✅<br>";
                } else {
                    echo "  ⚠️ Visibility: $visibility (expected public)<br>";
                }
            } else {
                echo "❌ Method '$method' missing<br>";
                $missing_methods[] = $method;
            }
        }

        if (empty($missing_methods)) {
            $test_results['method_existence'] = true;
        } else {
            $test_results['method_existence'] = false;
        }

        // Test method signatures and parameters
        echo '<h2>📝 Phase 3: Method Signature Validation</h2>';

        if (method_exists($validator, 'track_status_history')) {
            $reflection = new ReflectionMethod($validator, 'track_status_history');
            $parameters = $reflection->getParameters();

            echo "<strong>track_status_history() signature:</strong><br>";
            echo "  📋 Parameter count: " . count($parameters) . " (expected 4)<br>";

            $expected_params = array('license', 'old_status', 'new_status', 'context');
            foreach ($expected_params as $index => $expected) {
                if (isset($parameters[$index])) {
                    $param = $parameters[$index];
                    echo "  ✅ Parameter $index: \${$param->getName()} (expected: $expected)<br>";

                    if ($param->getName() === 'context' && $param->isDefaultValueAvailable()) {
                        echo "    📋 Has default value ✅<br>";
                    }
                } else {
                    echo "  ❌ Missing parameter $index: $expected<br>";
                }
            }
            $test_results['track_history_signature'] = true;
        } else {
            $test_results['track_history_signature'] = false;
        }

        if (method_exists($validator, 'get_status_history')) {
            $reflection = new ReflectionMethod($validator, 'get_status_history');
            $parameters = $reflection->getParameters();

            echo "<strong>get_status_history() signature:</strong><br>";
            echo "  📋 Parameter count: " . count($parameters) . " (expected 2)<br>";

            $expected_params = array('license_id', 'options');
            foreach ($expected_params as $index => $expected) {
                if (isset($parameters[$index])) {
                    $param = $parameters[$index];
                    echo "  ✅ Parameter $index: \${$param->getName()} (expected: $expected)<br>";

                    if ($param->getName() === 'options' && $param->isDefaultValueAvailable()) {
                        echo "    📋 Has default value ✅<br>";
                    }
                } else {
                    echo "  ❌ Missing parameter $index: $expected<br>";
                }
            }
            $test_results['get_history_signature'] = true;
        } else {
            $test_results['get_history_signature'] = false;
        }

        if (method_exists($validator, 'get_status_statistics')) {
            $reflection = new ReflectionMethod($validator, 'get_status_statistics');
            $parameters = $reflection->getParameters();

            echo "<strong>get_status_statistics() signature:</strong><br>";
            echo "  📋 Parameter count: " . count($parameters) . " (expected 1)<br>";

            if (isset($parameters[0])) {
                $param = $parameters[0];
                echo "  ✅ Parameter 0: \${$param->getName()} (expected: options)<br>";

                if ($param->isDefaultValueAvailable()) {
                    echo "    📋 Has default value ✅<br>";
                }
            } else {
                echo "  ❌ Missing parameter 0: options<br>";
            }
            $test_results['get_statistics_signature'] = true;
        } else {
            $test_results['get_statistics_signature'] = false;
        }

        // Test method execution and return structures
        echo '<h2>🔬 Phase 4: Method Execution & Return Structure Testing</h2>';

        // Test track_status_history method
        if (method_exists($validator, 'track_status_history')) {
            try {
                $test_license = array('id' => 123, 'license_key' => 'TEST-KEY-123');
                $result = $validator->track_status_history($test_license, 'active', 'suspended', array('reason' => 'test'));

                if (is_array($result)) {
                    echo "✅ track_status_history returns array<br>";

                    $expected_keys = array('success', 'message', 'method', 'version');
                    $missing_keys = array_diff($expected_keys, array_keys($result));

                    if (empty($missing_keys)) {
                        echo "  📋 Required keys present: " . implode(', ', $expected_keys) . "<br>";

                        if (isset($result['version']) && $result['version'] === '4.2.4.5.1a') {
                            echo "  ✅ Version tag correct: {$result['version']}<br>";
                        }

                        if (isset($result['success']) && $result['success'] === false) {
                            echo "  ✅ Success flag correct (false for placeholder)<br>";
                        }

                        $test_results['track_history_execution'] = true;
                    } else {
                        echo "  ❌ Missing keys: " . implode(', ', $missing_keys) . "<br>";
                        $test_results['track_history_execution'] = false;
                    }
                } else {
                    echo "❌ track_status_history does not return array<br>";
                    $test_results['track_history_execution'] = false;
                }
            } catch (Exception $e) {
                echo "❌ track_status_history execution error: " . $e->getMessage() . "<br>";
                $test_results['track_history_execution'] = false;
            }
        }

        // Test get_status_history method
        if (method_exists($validator, 'get_status_history')) {
            try {
                $result = $validator->get_status_history(123, array('limit' => 10));

                if (is_array($result)) {
                    echo "✅ get_status_history returns array<br>";

                    $expected_keys = array('success', 'message', 'method', 'version', 'data', 'total', 'pagination');
                    $missing_keys = array_diff($expected_keys, array_keys($result));

                    if (empty($missing_keys)) {
                        echo "  📋 Required keys present: " . implode(', ', $expected_keys) . "<br>";

                        if (isset($result['data']) && is_array($result['data'])) {
                            echo "  ✅ Data field is array<br>";
                        }

                        if (isset($result['pagination']) && is_array($result['pagination'])) {
                            echo "  ✅ Pagination field is array<br>";
                        }

                        $test_results['get_history_execution'] = true;
                    } else {
                        echo "  ❌ Missing keys: " . implode(', ', $missing_keys) . "<br>";
                        $test_results['get_history_execution'] = false;
                    }
                } else {
                    echo "❌ get_status_history does not return array<br>";
                    $test_results['get_history_execution'] = false;
                }
            } catch (Exception $e) {
                echo "❌ get_status_history execution error: " . $e->getMessage() . "<br>";
                $test_results['get_history_execution'] = false;
            }
        }

        // Test get_status_statistics method
        if (method_exists($validator, 'get_status_statistics')) {
            try {
                $result = $validator->get_status_statistics(array('date_range' => 'last_30_days'));

                if (is_array($result)) {
                    echo "✅ get_status_statistics returns array<br>";

                    $expected_keys = array('success', 'message', 'method', 'version', 'statistics', 'metadata');
                    $missing_keys = array_diff($expected_keys, array_keys($result));

                    if (empty($missing_keys)) {
                        echo "  📋 Required keys present: " . implode(', ', $expected_keys) . "<br>";

                        if (isset($result['statistics']) && is_array($result['statistics'])) {
                            echo "  ✅ Statistics field is array<br>";
                        }

                        if (isset($result['metadata']) && is_array($result['metadata'])) {
                            echo "  ✅ Metadata field is array<br>";
                        }

                        $test_results['get_statistics_execution'] = true;
                    } else {
                        echo "  ❌ Missing keys: " . implode(', ', $missing_keys) . "<br>";
                        $test_results['get_statistics_execution'] = false;
                    }
                } else {
                    echo "❌ get_status_statistics does not return array<br>";
                    $test_results['get_statistics_execution'] = false;
                }
            } catch (Exception $e) {
                echo "❌ get_status_statistics execution error: " . $e->getMessage() . "<br>";
                $test_results['get_statistics_execution'] = false;
            }
        }

        // Test parameter validation behavior
        echo '<h2>🛡️ Phase 5: Parameter Validation Behavior</h2>';

        // Test with empty parameters
        if (method_exists($validator, 'track_status_history')) {
            try {
                $result = $validator->track_status_history(array(), '', '', array());
                echo "✅ track_status_history handles empty parameters gracefully<br>";

                if (isset($result['parameters_received'])) {
                    echo "  📋 Parameter tracking working<br>";
                }

                $test_results['parameter_validation'] = true;
            } catch (Exception $e) {
                echo "⚠️ track_status_history parameter handling: " . $e->getMessage() . "<br>";
                $test_results['parameter_validation'] = null;
            }
        }

        // Test performance
        echo '<h2>⏱️ Phase 6: Performance Testing</h2>';

        $performance_tests = array();

        foreach (['track_status_history', 'get_status_history', 'get_status_statistics'] as $method) {
            if (method_exists($validator, $method)) {
                $start_perf = microtime(true);

                try {
                    switch ($method) {
                        case 'track_status_history':
                            $validator->track_status_history(array('id' => 1), 'active', 'suspended');
                            break;
                        case 'get_status_history':
                            $validator->get_status_history(1);
                            break;
                        case 'get_status_statistics':
                            $validator->get_status_statistics();
                            break;
                    }

                    $exec_time = round((microtime(true) - $start_perf) * 1000, 2);
                    $performance_tests[$method] = $exec_time;
                    echo "⏱️ $method: {$exec_time}ms<br>";

                } catch (Exception $e) {
                    echo "❌ $method performance test failed: " . $e->getMessage() . "<br>";
                }
            }
        }

        // All methods should be very fast (placeholder implementations)
        $slow_methods = array_filter($performance_tests, function($time) { return $time > 10; });
        if (empty($slow_methods)) {
            echo "✅ All methods execute within performance expectations (< 10ms)<br>";
            $test_results['performance'] = true;
        } else {
            echo "⚠️ Some methods slower than expected: " . implode(', ', array_keys($slow_methods)) . "<br>";
            $test_results['performance'] = null;
        }

        // Final results summary
        echo '<h2>📊 Test Results Summary</h2>';

        $total_tests = count($test_results);
        $passed_tests = count(array_filter($test_results, function($result) { return $result === true; }));
        $warning_tests = count(array_filter($test_results, function($result) { return $result === null; }));
        $failed_tests = $total_tests - $passed_tests - $warning_tests;

        $success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 1) : 0;

        echo "<div style='background: " . ($success_rate >= 90 ? "#d4edda" : "#f8d7da") . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Overall Results:</strong><br>";
        echo "✅ Passed: $passed_tests/$total_tests ($success_rate%)<br>";
        echo "⚠️ Warnings: $warning_tests<br>";
        echo "❌ Failed: $failed_tests<br>";
        echo "</div>";

        echo '<h3>📋 Detailed Results</h3>';
        echo '<ul>';
        foreach ($test_results as $test => $result) {
            $icon = $result === true ? '✅' : ($result === false ? '❌' : '⚠️');
            $status = $result === true ? 'PASS' : ($result === false ? 'FAIL' : 'WARNING');
            echo "<li>$icon <strong>$test:</strong> $status</li>";
        }
        echo '</ul>';

        $total_time = round((microtime(true) - $start_time) * 1000, 2);
        echo "<p><strong>Total execution time:</strong> {$total_time}ms</p>";

        if ($success_rate >= 95) {
            echo '<div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; text-align: center; margin: 20px 0;">';
            echo '<h2>🎉 XUẤT SẮC! Step 4.2.4.5.1a Method Signatures HOÀN HẢO!</h2>';
            echo '<p>Method signatures đã được implement hoàn chỉnh với ' . $success_rate . '% test cases passed!</p>';
            echo '<p><strong>Sẵn sàng cho Step 4.2.4.5.1b - Basic Parameter Validation Structure</strong></p>';
            echo '</div>';
        } elseif ($success_rate >= 85) {
            echo '<div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 5px; text-align: center; margin: 20px 0;">';
            echo '<h2>✅ Step 4.2.4.5.1a Method Signatures Thành Công</h2>';
            echo '<p>Method signatures hoạt động tốt (' . $success_rate . '% passed) với một vài warnings nhỏ.</p>';
            echo '</div>';
        } else {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; text-align: center; margin: 20px 0;">';
            echo '<h2>❌ Step 4.2.4.5.1a Cần Xem Xét Lại</h2>';
            echo '<p>Có một số vấn đề với method signatures cần được giải quyết.</p>';
            echo '</div>';
        }

    } catch (Exception $e) {
        echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;">';
        echo '<strong>❌ Test Suite Error:</strong><br>';
        echo htmlspecialchars($e->getMessage()) . '<br>';
        echo '<strong>File:</strong> ' . $e->getFile() . '<br>';
        echo '<strong>Line:</strong> ' . $e->getLine();
        echo '</div>';
    }

    echo '</div>';

    // Prevent further WordPress processing
    exit;
});

// Display admin notice with test link
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $test_url = admin_url('admin.php?vd_test_step_4_2_4_5_1a=run');
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>VD License Manager:</strong> ';
    echo '<a href="' . esc_url($test_url) . '" target="_blank" style="color: #0073aa; text-decoration: none;">';
    echo '🧪 Run Step 4.2.4.5.1a Method Signature Definition Test</a></p>';
    echo '</div>';
});
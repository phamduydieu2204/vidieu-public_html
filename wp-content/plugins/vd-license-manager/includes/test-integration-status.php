<?php
/**
 * Safe test endpoint for Step 4.3 Integration Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only add hooks if not already added
if (!has_action('wp_ajax_vd_integration_status', 'vd_test_integration_status')) {
    add_action('wp_ajax_vd_integration_status', 'vd_test_integration_status');
    add_action('wp_ajax_nopriv_vd_integration_status', 'vd_test_integration_status');
}

function vd_test_integration_status() {
    $response = array(
        'step' => 'Step 4.3: Third-party Integrations',
        'timestamp' => current_time('mysql'),
        'status' => 'checking',
        'tests' => array()
    );

    try {
        // Test 1: Check if module loader exists
        $response['tests']['module_loader'] = array(
            'name' => 'Module Loader Availability',
            'status' => class_exists('VD_License_Module_Loader') ? 'passed' : 'failed',
            'details' => class_exists('VD_License_Module_Loader') ? 'Module loader class available' : 'Module loader class not found'
        );

        if (class_exists('VD_License_Module_Loader')) {
            $loader = VD_License_Module_Loader::get_instance();

            // Test 2: Check module registry
            $registry = $loader->get_registry();
            $integration_registered = isset($registry['integration.manager']);

            $response['tests']['module_registry'] = array(
                'name' => 'Integration Module Registration',
                'status' => $integration_registered ? 'passed' : 'failed',
                'details' => array(
                    'registered' => $integration_registered,
                    'total_modules' => count($registry),
                    'registry_keys' => array_keys($registry)
                )
            );

            // Test 3: Check file existence
            $integration_file = plugin_dir_path(__FILE__) . 'modules/integration/class-vd-license-integration-manager.php';
            $file_exists = file_exists($integration_file);

            $response['tests']['file_existence'] = array(
                'name' => 'Integration Manager File',
                'status' => $file_exists ? 'passed' : 'failed',
                'details' => array(
                    'file_path' => $integration_file,
                    'exists' => $file_exists,
                    'size' => $file_exists ? filesize($integration_file) : 0
                )
            );

            // Test 4: Try to load module (safely)
            $module_loaded = false;
            $load_error = null;

            try {
                if ($integration_registered) {
                    $integration_manager = $loader->load_module('integration.manager');
                    $module_loaded = is_object($integration_manager);

                    if ($module_loaded) {
                        $response['tests']['module_loading'] = array(
                            'name' => 'Module Loading',
                            'status' => 'passed',
                            'details' => array(
                                'class_name' => get_class($integration_manager),
                                'methods' => get_class_methods($integration_manager),
                                'is_singleton' => method_exists($integration_manager, 'get_instance')
                            )
                        );

                        // Test 5: Check supported providers
                        if (method_exists($integration_manager, 'get_supported_providers')) {
                            $providers = $integration_manager->get_supported_providers();

                            $response['tests']['providers'] = array(
                                'name' => 'Supported Providers',
                                'status' => count($providers) > 0 ? 'passed' : 'failed',
                                'details' => array(
                                    'count' => count($providers),
                                    'providers' => array_keys($providers),
                                    'provider_details' => $providers
                                )
                            );
                        }

                        // Test 6: Check configuration
                        if (method_exists($integration_manager, 'get_config')) {
                            $config = $integration_manager->get_config();

                            $response['tests']['configuration'] = array(
                                'name' => 'Configuration System',
                                'status' => is_array($config) && !empty($config) ? 'passed' : 'failed',
                                'details' => array(
                                    'config_keys' => array_keys($config),
                                    'enabled' => isset($config['enabled']) ? $config['enabled'] : false
                                )
                            );
                        }

                        // Test 7: Check statistics
                        if (method_exists($integration_manager, 'get_stats')) {
                            $stats = $integration_manager->get_stats();

                            $response['tests']['statistics'] = array(
                                'name' => 'Statistics Tracking',
                                'status' => is_array($stats) ? 'passed' : 'failed',
                                'details' => $stats
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                $load_error = $e->getMessage();
            }

            if (!$module_loaded) {
                $response['tests']['module_loading'] = array(
                    'name' => 'Module Loading',
                    'status' => 'failed',
                    'details' => array(
                        'error' => $load_error ?: 'Module could not be loaded',
                        'registered' => $integration_registered
                    )
                );
            }

            // Test 8: Check dependency container
            if (class_exists('VD_License_Dependency_Container')) {
                $container = VD_License_Dependency_Container::get_instance();

                $response['tests']['dependency_container'] = array(
                    'name' => 'Dependency Container',
                    'status' => 'passed',
                    'details' => array(
                        'container_available' => true,
                        'container_class' => get_class($container)
                    )
                );
            } else {
                $response['tests']['dependency_container'] = array(
                    'name' => 'Dependency Container',
                    'status' => 'failed',
                    'details' => array(
                        'container_available' => false
                    )
                );
            }
        }

        // Calculate overall status
        $passed = 0;
        $total = count($response['tests']);

        foreach ($response['tests'] as $test) {
            if ($test['status'] === 'passed') {
                $passed++;
            }
        }

        $response['summary'] = array(
            'total_tests' => $total,
            'passed' => $passed,
            'failed' => $total - $passed,
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 1) . '%' : '0%'
        );

        $response['status'] = $passed === $total ? 'all_passed' : ($passed > 0 ? 'partial' : 'failed');
        $response['message'] = "Integration Manager test completed: {$passed}/{$total} tests passed";

    } catch (Exception $e) {
        $response['status'] = 'error';
        $response['message'] = 'Test execution failed: ' . $e->getMessage();
        $response['error'] = $e->getMessage();
    }

    wp_send_json($response);
}
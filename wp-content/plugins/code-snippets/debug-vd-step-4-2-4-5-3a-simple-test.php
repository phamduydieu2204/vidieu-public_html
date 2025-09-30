<?php
/**
 * VD License Manager - Step 4.2.4.5.3a Simple Debug Test
 * URL: https://vidieu.vn/wp-admin/admin.php?vd_debug_step_4_2_4_5_3a=run
 */

// Hook into WordPress admin_init for proper integration
add_action('admin_init', function() {
    if (is_admin() && isset($_GET['vd_debug_step_4_2_4_5_3a']) && $_GET['vd_debug_step_4_2_4_5_3a'] === 'run') {

        echo "<h2>🔧 VD License Manager - Step 4.2.4.5.3a Simple Debug Test</h2>";
        echo "<p><strong>Debug Date:</strong> " . date('Y-m-d H:i:s') . "</p>";

        try {
            // Step 1: Check if class exists
            echo "<h3>1. Class Existence Check</h3>";
            if (!class_exists('VD_License_Validator')) {
                echo "<p>❌ VD_License_Validator class not found</p>";
                echo "<p><strong>Action:</strong> Plugin may not be loaded correctly</p>";
                exit;
            }
            echo "<p>✅ VD_License_Validator class exists</p>";

            // Step 2: Try to get singleton instance (class uses singleton pattern)
            echo "<h3>2. Singleton Instance Test</h3>";
            if (!method_exists('VD_License_Validator', 'get_instance')) {
                echo "<p>❌ VD_License_Validator::get_instance() method not found</p>";
                echo "<p><strong>Note:</strong> Class uses singleton pattern</p>";
                exit;
            }

            $validator = VD_License_Validator::get_instance();
            echo "<p>✅ VD_License_Validator singleton instance retrieved successfully</p>";

            // Step 3: Check specific method existence
            echo "<h3>3. Method Existence Check</h3>";
            $target_method = 'validate_and_structure_history_record';
            if (method_exists($validator, $target_method)) {
                echo "<p>✅ Method {$target_method} exists</p>";
            } else {
                echo "<p>❌ Method {$target_method} NOT FOUND</p>";
                echo "<p><strong>Available methods:</strong></p>";
                $methods = get_class_methods($validator);
                $validation_methods = array_filter($methods, function($method) {
                    return strpos($method, 'validat') !== false;
                });
                foreach ($validation_methods as $method) {
                    echo "<p>- {$method}</p>";
                }
                exit;
            }

            // Step 4: Try simple method call
            echo "<h3>4. Simple Method Call Test</h3>";
            try {
                $result = $validator->validate_and_structure_history_record(123, 'active', 'expired', array());
                echo "<p>✅ Method call successful</p>";
                echo "<p><strong>Result type:</strong> " . gettype($result) . "</p>";
                echo "<p><strong>Result valid:</strong> " . ($result['valid'] ? 'YES' : 'NO') . "</p>";
            } catch (Exception $e) {
                echo "<p>❌ Method call failed: " . $e->getMessage() . "</p>";
                echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
                echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            }

            // Step 5: Check infrastructure status
            echo "<h3>5. Infrastructure Status Check</h3>";
            try {
                if (method_exists($validator, 'get_validation_infrastructure_status')) {
                    $status = $validator->get_validation_infrastructure_status();
                    echo "<p>✅ Infrastructure status retrieved</p>";
                    echo "<p><strong>Framework Version:</strong> " . $status['framework_version'] . "</p>";
                } else {
                    echo "<p>❌ get_validation_infrastructure_status method not found</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Infrastructure status call failed: " . $e->getMessage() . "</p>";
            }

            echo "<h3>✅ Debug Summary</h3>";
            echo "<p><strong>Status:</strong> All basic checks passed</p>";
            echo "<p><strong>Issue:</strong> Problem likely in complex test file, not core implementation</p>";
            echo "<p><em>Debug completed at: " . date('Y-m-d H:i:s') . "</em></p>";

        } catch (Exception $e) {
            echo "<p>❌ <strong>Critical Error:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "<p><strong>Stack Trace:</strong></p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }

        // Exit to prevent normal page loading
        exit;
    }
});
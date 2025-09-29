<?php
/**
 * VD License Manager - Step 4.2.2 Admin Test Page
 */

// Add admin menu for testing
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'VD Step 4.2.2 Test',
        'VD Step 4.2.2 Test',
        'manage_options',
        'vd-step-4-2-2-test',
        'vd_step_4_2_2_admin_test_page'
    );
});

function vd_step_4_2_2_admin_test_page() {
    echo '<div class="wrap">';
    echo '<h1>VD License Manager - Step 4.2.2 Test</h1>';

    // Check if we should run test
    if (isset($_GET['run_test']) && $_GET['run_test'] === '1') {
        echo '<div style="background: white; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">';

        // Run the test function if it exists
        if (function_exists('vd_test_step_4_2_2_enhanced_validation')) {
            vd_test_step_4_2_2_enhanced_validation();
        } else {
            echo '<p><strong>❌ Test function not found!</strong></p>';

            // Run simple debug instead
            echo '<h2>🔧 Running Simple Debug Test</h2>';

            if (class_exists('VD_License_Validator')) {
                echo '✅ VD_License_Validator class found<br>';

                $validator = VD_License_Validator::get_instance();
                $test_result = $validator->validate_license_key_format('H10D-DIJD-14RC-SOLE-6KUV30');
                echo '✅ Basic validation test: ' . ($test_result ? 'PASSED' : 'FAILED') . '<br>';

                if (method_exists($validator, 'get_detailed_validation')) {
                    echo '✅ Enhanced validation method found<br>';
                    $detailed = $validator->get_detailed_validation('H10D-DIJD-14RC-SOLE-6KUV30');
                    echo '✅ Detailed validation: ' . ($detailed['valid'] ? 'PASSED' : 'FAILED') . '<br>';
                } else {
                    echo '❌ Enhanced validation method NOT found<br>';
                }
            } else {
                echo '❌ VD_License_Validator class NOT found<br>';
            }
        }

        echo '</div>';
    } else {
        echo '<p>Click the button below to run Step 4.2.2 validation test:</p>';
        echo '<a href="' . admin_url('tools.php?page=vd-step-4-2-2-test&run_test=1') . '" class="button button-primary">Run Test</a>';
    }

    echo '</div>';
}
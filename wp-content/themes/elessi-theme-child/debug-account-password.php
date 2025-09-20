<?php
/**
 * Debug Account Password Tool
 * Tool to check and debug account creation issues
 *
 * @package Elessi-theme-child
 * @since 2025-09-20
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add debug info to admin
 */
add_action('admin_menu', 'elessi_child_add_debug_menu');
function elessi_child_add_debug_menu() {
    add_submenu_page(
        'tools.php',
        'Debug Account Password',
        'Debug Account Password',
        'manage_options',
        'debug-account-password',
        'elessi_child_debug_account_password_page'
    );
}

/**
 * Debug page
 */
function elessi_child_debug_account_password_page() {
    ?>
    <div class="wrap">
        <h1>Debug Account Password</h1>

        <?php if (isset($_POST['check_email'])) {
            $email = sanitize_email($_POST['email']);
            if (!empty($email)) {
                $user = get_user_by('email', $email);
                if ($user) {
                    $auto_password = get_user_meta($user->ID, 'auto_generated_password', true);
                    echo '<div class="notice notice-success"><p>';
                    echo '<strong>User Found:</strong><br>';
                    echo 'Email: ' . esc_html($user->user_email) . '<br>';
                    echo 'Username: ' . esc_html($user->user_login) . '<br>';
                    echo 'User ID: ' . esc_html($user->ID) . '<br>';
                    echo 'Display Name: ' . esc_html($user->display_name) . '<br>';
                    if ($auto_password) {
                        echo 'Auto Generated Password: <code>' . esc_html($auto_password) . '</code><br>';
                    }
                    echo '</p></div>';

                    // Test password verification
                    if (isset($_POST['test_password']) && !empty($_POST['password'])) {
                        $test_password = $_POST['password'];
                        $check_result = wp_check_password($test_password, $user->user_pass, $user->ID);
                        if ($check_result) {
                            echo '<div class="notice notice-success"><p><strong>✅ Password is CORRECT!</strong></p></div>';
                        } else {
                            echo '<div class="notice notice-error"><p><strong>❌ Password is INCORRECT!</strong></p></div>';

                            // Try to update password manually
                            if (isset($_POST['fix_password'])) {
                                wp_set_password($test_password, $user->ID);
                                echo '<div class="notice notice-info"><p><strong>🔧 Password has been reset manually.</strong></p></div>';
                            }
                        }
                    }
                } else {
                    echo '<div class="notice notice-error"><p><strong>User not found for email:</strong> ' . esc_html($email) . '</p></div>';
                }
            }
        } ?>

        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Email Address</th>
                    <td>
                        <input type="email" name="email" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="check_email" class="button-primary" value="Check Account">
            </p>
        </form>

        <?php if (isset($_POST['check_email']) && isset($user) && $user) { ?>
        <hr>
        <h2>Test Password</h2>
        <form method="post">
            <input type="hidden" name="email" value="<?php echo esc_attr($_POST['email']); ?>">
            <input type="hidden" name="check_email" value="1">
            <table class="form-table">
                <tr>
                    <th scope="row">Test Password</th>
                    <td>
                        <input type="text" name="password" value="<?php echo isset($auto_password) ? esc_attr($auto_password) : ''; ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="test_password" class="button" value="Test Password">
                <input type="submit" name="fix_password" class="button button-secondary" value="Fix Password (Force Update)" onclick="return confirm('Are you sure you want to reset the password?')">
            </p>
        </form>
        <?php } ?>

        <hr>
        <h2>Recent Account Creation Logs</h2>
        <?php
        // Show recent error logs
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file)) {
            $logs = file_get_contents($log_file);
            $lines = explode("\n", $logs);
            $recent_logs = array_slice($lines, -50); // Last 50 lines

            echo '<textarea readonly style="width: 100%; height: 300px;">';
            foreach ($recent_logs as $line) {
                if (strpos($line, 'Account created for') !== false || strpos($line, 'Failed to create account') !== false) {
                    echo esc_html($line) . "\n";
                }
            }
            echo '</textarea>';
        } else {
            echo '<p>Debug log file not found. Make sure WP_DEBUG_LOG is enabled.</p>';
        }
        ?>
    </div>
    <?php
}

/**
 * Add admin notice for password issues
 */
add_action('admin_notices', 'elessi_child_password_debug_notice');
function elessi_child_password_debug_notice() {
    $screen = get_current_screen();
    if ($screen->id === 'tools_page_debug-account-password') {
        return;
    }

    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>Account Password Debug:</strong> ';
    echo 'If you\'re having login issues with auto-generated passwords, ';
    echo '<a href="' . admin_url('tools.php?page=debug-account-password') . '">use the debug tool</a> to check accounts.';
    echo '</p>';
    echo '</div>';
}
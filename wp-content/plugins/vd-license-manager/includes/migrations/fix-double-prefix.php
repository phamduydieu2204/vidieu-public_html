<?php
/**
 * Fix Double Prefix Table Migration
 *
 * Issue: bz_bz_vd_product_pools should be bz_vd_product_pools
 * This script checks and fixes the double prefix issue.
 *
 * @package    VD_License_Manager
 * @subpackage Migrations
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

/**
 * Fix double prefix table issue
 *
 * @return array Status and message
 */
function vd_fix_double_prefix_table() {
    global $wpdb;

    // Table names
    $wrong_table = $wpdb->prefix . 'bz_vd_product_pools';  // Results in: bz_bz_vd_product_pools
    $correct_table = $wpdb->prefix . 'vd_product_pools';   // Results in: bz_vd_product_pools

    $results = [];

    // Check if wrong table exists
    $wrong_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $wrong_table
    ));

    if (!$wrong_exists) {
        $results[] = [
            'status' => 'ok',
            'table' => $wrong_table,
            'message' => 'No double prefix issue found'
        ];

        // Check if correct table exists
        $correct_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $correct_table
        ));

        if ($correct_exists) {
            $results[] = [
                'status' => 'ok',
                'table' => $correct_table,
                'message' => 'Correct table exists'
            ];
        } else {
            $results[] = [
                'status' => 'warning',
                'table' => $correct_table,
                'message' => 'Correct table missing (may need database installer)'
            ];
        }

        return $results;
    }

    // Wrong table exists - need to fix
    $results[] = [
        'status' => 'found_issue',
        'table' => $wrong_table,
        'message' => 'Found double prefix table'
    ];

    // Check if correct table exists
    $correct_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $correct_table
    ));

    if (!$correct_exists) {
        // Simple case: just rename the table
        $rename_result = $wpdb->query($wpdb->prepare(
            "RENAME TABLE `{$wrong_table}` TO `{$correct_table}`"
        ));

        if ($rename_result !== false) {
            $results[] = [
                'status' => 'fixed',
                'table' => $correct_table,
                'message' => 'Successfully renamed table from double prefix'
            ];
        } else {
            $results[] = [
                'status' => 'error',
                'table' => $wrong_table,
                'message' => 'Failed to rename table: ' . $wpdb->last_error
            ];
        }

        return $results;
    }

    // Both tables exist - need to merge data
    $wrong_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$wrong_table}`");
    $correct_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$correct_table}`");

    $results[] = [
        'status' => 'info',
        'table' => $wrong_table,
        'message' => "Double prefix table has {$wrong_count} rows"
    ];

    $results[] = [
        'status' => 'info',
        'table' => $correct_table,
        'message' => "Correct table has {$correct_count} rows"
    ];

    if ($wrong_count > 0) {
        // Copy data to correct table (skip duplicates)
        $copy_result = $wpdb->query("
            INSERT IGNORE INTO `{$correct_table}`
            SELECT * FROM `{$wrong_table}`
        ");

        if ($copy_result !== false) {
            $results[] = [
                'status' => 'fixed',
                'table' => $correct_table,
                'message' => "Merged {$wrong_count} rows from double-prefix table"
            ];
        } else {
            $results[] = [
                'status' => 'error',
                'table' => $wrong_table,
                'message' => 'Failed to copy data: ' . $wpdb->last_error
            ];
            return $results; // Don't drop table if copy failed
        }
    } else {
        $results[] = [
            'status' => 'info',
            'table' => $wrong_table,
            'message' => 'Double-prefix table is empty'
        ];
    }

    // Drop wrong table
    $drop_result = $wpdb->query("DROP TABLE IF EXISTS `{$wrong_table}`");

    if ($drop_result !== false) {
        $results[] = [
            'status' => 'fixed',
            'table' => $wrong_table,
            'message' => 'Successfully dropped double-prefix table'
        ];
    } else {
        $results[] = [
            'status' => 'warning',
            'table' => $wrong_table,
            'message' => 'Could not drop double-prefix table: ' . $wpdb->last_error
        ];
    }

    return $results;
}

/**
 * Run migration and log results
 */
function vd_run_double_prefix_migration() {
    $results = vd_fix_double_prefix_table();

    // Log all results
    foreach ($results as $result) {
        $log_message = sprintf(
            'VD Migration [%s]: %s - %s',
            strtoupper($result['status']),
            $result['table'],
            $result['message']
        );

        error_log($log_message);

        // Also log to WordPress debug if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($log_message, 3, WP_CONTENT_DIR . '/debug.log');
        }
    }

    return $results;
}

/**
 * Auto-run migration on admin page load (once per day)
 *
 * This prevents the migration from running repeatedly while still
 * allowing manual re-runs if needed.
 */
if (is_admin() && !get_transient('vd_double_prefix_migration_run')) {
    add_action('admin_init', function() {
        // Only run for users who can manage options
        if (!current_user_can('manage_options')) {
            return;
        }

        // Run migration
        $results = vd_run_double_prefix_migration();

        // Set transient to prevent running again for 24 hours
        set_transient('vd_double_prefix_migration_run', time(), DAY_IN_SECONDS);

        // Check if any errors occurred
        $has_errors = false;
        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                $has_errors = true;
                break;
            }
        }

        // Show admin notice if there were issues
        if ($has_errors) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>VD License Manager:</strong> Database migration encountered errors. ';
                echo 'Please check the error logs for details.';
                echo '</p></div>';
            });
        } else {
            // Check if any fixes were applied
            $has_fixes = false;
            foreach ($results as $result) {
                if ($result['status'] === 'fixed') {
                    $has_fixes = true;
                    break;
                }
            }

            if ($has_fixes) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    echo '<strong>VD License Manager:</strong> Database schema issues have been automatically fixed.';
                    echo '</p></div>';
                });
            }
        }
    });
}

/**
 * Manual migration trigger for testing
 *
 * Usage: Add ?vd_run_migration=1 to admin URL
 */
if (is_admin() && isset($_GET['vd_run_migration']) && $_GET['vd_run_migration'] === '1' && current_user_can('manage_options')) {
    add_action('admin_init', function() {
        // Force run migration regardless of transient
        delete_transient('vd_double_prefix_migration_run');

        $results = vd_run_double_prefix_migration();

        // Display results
        add_action('admin_notices', function() use ($results) {
            echo '<div class="notice notice-info">';
            echo '<h3>VD Migration Results:</h3>';
            echo '<ul>';
            foreach ($results as $result) {
                $class = $result['status'] === 'error' ? 'error' :
                        ($result['status'] === 'fixed' ? 'success' : 'info');
                echo '<li><strong>' . strtoupper($result['status']) . ':</strong> ' .
                     esc_html($result['table']) . ' - ' . esc_html($result['message']) . '</li>';
            }
            echo '</ul></div>';
        });

        // Reset transient
        set_transient('vd_double_prefix_migration_run', time(), DAY_IN_SECONDS);
    });
}
?>
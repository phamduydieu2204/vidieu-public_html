<?php
/**
 * Account assignment logic for VD License Manager
 *
 * Handles assignment of provider accounts to licenses with capacity management
 *
 * @package VD_License_Manager
 * @subpackage Core
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD Assignment class
 *
 * Core logic for assigning provider accounts to licenses
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Assignment {

    /**
     * Assign provider account to license
     *
     * Uses pool strategy to select appropriate account and validates capacity
     *
     * @since 1.0.0
     * @param int $license_id License ID to assign account to
     * @param int $product_id Product ID for pool lookup
     * @return array Assignment result with account ID or error
     */
    public static function assign_account_to_license($license_id, $product_id) {
        global $wpdb;

        if (!is_numeric($license_id) || !is_numeric($product_id)) {
            return array(
                'ok' => false,
                'provider_account_id' => 0,
                'error' => 'Invalid license ID or product ID'
            );
        }

        // Get pool configuration for product
        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT pool_id, strategy
             FROM {$wpdb->prefix}vd_product_pools
             WHERE product_id = %d AND is_active = 1
             LIMIT 1",
            $product_id
        ));

        if (!$pool) {
            return array(
                'ok' => false,
                'provider_account_id' => 0,
                'error' => 'No active pool found for product'
            );
        }

        // Use assignment strategy to select account
        $strategy_method = 'select_by_' . $pool->strategy;
        if (!method_exists('VD_Assignment_Strategies', $strategy_method)) {
            return array(
                'ok' => false,
                'provider_account_id' => 0,
                'error' => 'Invalid assignment strategy: ' . $pool->strategy
            );
        }

        $selected_account = VD_Assignment_Strategies::$strategy_method($pool->pool_id);
        if (!$selected_account) {
            return array(
                'ok' => false,
                'provider_account_id' => 0,
                'error' => 'No available accounts in pool'
            );
        }

        // Check account capacity
        $capacity_check = self::check_capacity($selected_account);
        if (!$capacity_check['ok']) {
            return array(
                'ok' => false,
                'provider_account_id' => 0,
                'error' => 'Account at capacity limit'
            );
        }

        // Insert assignment
        $result = $wpdb->insert(
            $wpdb->prefix . 'vd_cookie_assignments',
            array(
                'license_id' => $license_id,
                'provider_account_id' => $selected_account,
                'status' => 'active',
                'assigned_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result === false) {
            return array(
                'ok' => false,
                'provider_account_id' => 0,
                'error' => 'Failed to create assignment'
            );
        }

        return array(
            'ok' => true,
            'provider_account_id' => $selected_account,
            'error' => ''
        );
    }

    /**
     * Get assigned account for license
     *
     * Retrieves active provider account assignment for license
     *
     * @since 1.0.0
     * @param int $license_id License ID to get account for
     * @return int|false Provider account ID or false if not found
     */
    public static function get_account_for_license($license_id) {
        global $wpdb;

        if (!is_numeric($license_id)) {
            return false;
        }

        $provider_account_id = $wpdb->get_var($wpdb->prepare(
            "SELECT provider_account_id
             FROM {$wpdb->prefix}vd_cookie_assignments
             WHERE license_id = %d AND status = 'active'
             ORDER BY assigned_at DESC
             LIMIT 1",
            $license_id
        ));

        return $provider_account_id ? (int) $provider_account_id : false;
    }

    /**
     * Migrate license to new account
     *
     * Revokes current assignment and creates new one using database transaction
     *
     * @since 1.0.0
     * @param int $license_id License ID to migrate
     * @param int $new_account_id New provider account ID
     * @param string $reason Migration reason for audit trail
     * @return bool True on success, false on failure
     */
    public static function migrate_license($license_id, $new_account_id, $reason) {
        global $wpdb;

        if (!is_numeric($license_id) || !is_numeric($new_account_id)) {
            return false;
        }

        $reason = sanitize_text_field($reason);
        $table_name = $wpdb->prefix . 'vd_cookie_assignments';

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Revoke current assignment
            $revoke_result = $wpdb->update(
                $table_name,
                array(
                    'status' => 'revoked',
                    'notes' => $reason,
                    'revoked_at' => current_time('mysql')
                ),
                array(
                    'license_id' => $license_id,
                    'status' => 'active'
                ),
                array('%s', '%s', '%s'),
                array('%d', '%s')
            );

            if ($revoke_result === false) {
                throw new Exception('Failed to revoke current assignment');
            }

            // Create new assignment
            $assign_result = $wpdb->insert(
                $table_name,
                array(
                    'license_id' => $license_id,
                    'provider_account_id' => $new_account_id,
                    'status' => 'active',
                    'assigned_at' => current_time('mysql'),
                    'notes' => 'Migrated: ' . $reason
                ),
                array('%d', '%d', '%s', '%s', '%s')
            );

            if ($assign_result === false) {
                throw new Exception('Failed to create new assignment');
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            // Log migration action
            VD_License_Verify::log_access(
                $license_id,
                '',
                'migrate',
                'success',
                'Migrated to account ' . $new_account_id . ': ' . $reason
            );

            return true;

        } catch (Exception $e) {
            // Rollback on any error
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Check provider account capacity
     *
     * Validates if account can accept new assignments based on capacity limits
     *
     * @since 1.0.0
     * @param int $provider_account_id Provider account ID to check
     * @return array Capacity information with availability status
     */
    public static function check_capacity($provider_account_id) {
        global $wpdb;

        if (!is_numeric($provider_account_id)) {
            return array(
                'ok' => false,
                'used' => 0,
                'capacity' => 0,
                'available' => 0
            );
        }

        // Get account capacity
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT capacity
             FROM {$wpdb->prefix}vd_provider_accounts
             WHERE account_id = %d AND is_active = 1",
            $provider_account_id
        ));

        if (!$account) {
            return array(
                'ok' => false,
                'used' => 0,
                'capacity' => 0,
                'available' => 0
            );
        }

        $capacity = (int) $account->capacity;

        // Count active assignments
        $used = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}vd_cookie_assignments
             WHERE provider_account_id = %d AND status = 'active'",
            $provider_account_id
        ));

        $available = max(0, $capacity - $used);

        return array(
            'ok' => $available > 0,
            'used' => $used,
            'capacity' => $capacity,
            'available' => $available
        );
    }
}
<?php
/**
 * Assignment strategies for VD License Manager
 *
 * Implements different algorithms for selecting provider accounts from pools
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
 * VD Assignment Strategies class
 *
 * Static methods for different account selection strategies
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Assignment_Strategies {

    /**
     * Select account using sticky strategy
     *
     * Selects account with lowest active assignment count
     *
     * @since 1.0.0
     * @param int $pool_id Pool ID to select from
     * @return int|false Provider account ID or false if none available
     */
    public static function select_by_sticky($pool_id) {
        global $wpdb;

        if (!is_numeric($pool_id)) {
            return false;
        }

        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT
                pa.account_id,
                pa.capacity,
                COUNT(ca.assignment_id) as used_count
             FROM {$wpdb->prefix}vd_provider_accounts pa
             JOIN {$wpdb->prefix}vd_pool_accounts poa ON pa.account_id = poa.provider_account_id
             LEFT JOIN {$wpdb->prefix}vd_cookie_assignments ca
                ON pa.account_id = ca.provider_account_id AND ca.status = 'active'
             WHERE poa.pool_id = %d AND pa.is_active = 1 AND poa.is_active = 1
             GROUP BY pa.account_id
             HAVING used_count < pa.capacity
             ORDER BY used_count ASC, pa.account_id ASC
             LIMIT 1",
            $pool_id
        ));

        return $account ? (int) $account->account_id : false;
    }

    /**
     * Select account using weighted strategy
     *
     * Selects account with highest weighted score based on capacity and weight
     *
     * @since 1.0.0
     * @param int $pool_id Pool ID to select from
     * @return int|false Provider account ID or false if none available
     */
    public static function select_by_weighted($pool_id) {
        global $wpdb;

        if (!is_numeric($pool_id)) {
            return false;
        }

        $accounts = $wpdb->get_results($wpdb->prepare(
            "SELECT
                pa.account_id,
                pa.capacity,
                poa.weight,
                COUNT(ca.assignment_id) as used_count
             FROM {$wpdb->prefix}vd_provider_accounts pa
             JOIN {$wpdb->prefix}vd_pool_accounts poa ON pa.account_id = poa.provider_account_id
             LEFT JOIN {$wpdb->prefix}vd_cookie_assignments ca
                ON pa.account_id = ca.provider_account_id AND ca.status = 'active'
             WHERE poa.pool_id = %d AND pa.is_active = 1 AND poa.is_active = 1
             GROUP BY pa.account_id
             HAVING used_count < pa.capacity",
            $pool_id
        ));

        if (empty($accounts)) {
            return false;
        }

        $best_account = null;
        $highest_score = -1;

        foreach ($accounts as $account) {
            $available_capacity = $account->capacity - $account->used_count;
            $weight = max(1, (int) $account->weight);
            $score = $weight * $available_capacity;

            if ($score > $highest_score) {
                $highest_score = $score;
                $best_account = $account;
            }
        }

        return $best_account ? (int) $best_account->account_id : false;
    }

    /**
     * Select account using priority strategy
     *
     * Selects first available account ordered by priority (lowest number = highest priority)
     *
     * @since 1.0.0
     * @param int $pool_id Pool ID to select from
     * @return int|false Provider account ID or false if none available
     */
    public static function select_by_priority($pool_id) {
        global $wpdb;

        if (!is_numeric($pool_id)) {
            return false;
        }

        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT
                pa.account_id,
                pa.capacity,
                COUNT(ca.assignment_id) as used_count
             FROM {$wpdb->prefix}vd_provider_accounts pa
             JOIN {$wpdb->prefix}vd_pool_accounts poa ON pa.account_id = poa.provider_account_id
             LEFT JOIN {$wpdb->prefix}vd_cookie_assignments ca
                ON pa.account_id = ca.provider_account_id AND ca.status = 'active'
             WHERE poa.pool_id = %d AND pa.is_active = 1 AND poa.is_active = 1
             GROUP BY pa.account_id
             HAVING used_count < pa.capacity
             ORDER BY poa.priority ASC, pa.account_id ASC
             LIMIT 1",
            $pool_id
        ));

        return $account ? (int) $account->account_id : false;
    }

    /**
     * Get all accounts in pool with optional usage statistics
     *
     * Retrieves active accounts from pool with capacity and usage information
     *
     * @since 1.0.0
     * @param int $pool_id Pool ID to get accounts from
     * @param bool $with_stats Include usage statistics
     * @return array Array of account objects
     */
    public static function get_pool_accounts($pool_id, $with_stats = false) {
        global $wpdb;

        if (!is_numeric($pool_id)) {
            return array();
        }

        if ($with_stats) {
            $accounts = $wpdb->get_results($wpdb->prepare(
                "SELECT
                    pa.account_id,
                    pa.name,
                    pa.provider,
                    pa.capacity,
                    pa.is_active,
                    poa.weight,
                    poa.priority,
                    poa.is_active as pool_active,
                    COUNT(ca.assignment_id) as used_count,
                    (pa.capacity - COUNT(ca.assignment_id)) as available_count
                 FROM {$wpdb->prefix}vd_provider_accounts pa
                 JOIN {$wpdb->prefix}vd_pool_accounts poa ON pa.account_id = poa.provider_account_id
                 LEFT JOIN {$wpdb->prefix}vd_cookie_assignments ca
                    ON pa.account_id = ca.provider_account_id AND ca.status = 'active'
                 WHERE poa.pool_id = %d AND pa.is_active = 1
                 GROUP BY pa.account_id
                 ORDER BY poa.priority ASC, pa.account_id ASC",
                $pool_id
            ));
        } else {
            $accounts = $wpdb->get_results($wpdb->prepare(
                "SELECT
                    pa.account_id,
                    pa.name,
                    pa.provider,
                    pa.capacity,
                    pa.is_active,
                    poa.weight,
                    poa.priority,
                    poa.is_active as pool_active
                 FROM {$wpdb->prefix}vd_provider_accounts pa
                 JOIN {$wpdb->prefix}vd_pool_accounts poa ON pa.account_id = poa.provider_account_id
                 WHERE poa.pool_id = %d AND pa.is_active = 1
                 ORDER BY poa.priority ASC, pa.account_id ASC",
                $pool_id
            ));
        }

        return $accounts ? $accounts : array();
    }
}
<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Hash Optimization Column to LMfWC Table
 *
 * Adds license_key_hash column to wp_lmfwc_licenses table for performance optimization
 * This allows instant hash-based lookups instead of decryption scans
 *
 * URL: /wp-admin/admin-ajax.php?action=vd_add_hash_optimization_column
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager
 */

add_action('wp_ajax_vd_add_hash_optimization_column', function() {
    header('Content-Type: application/json; charset=utf-8');

    $results = array(
        'status' => 'success',
        'test_name' => 'Add Hash Optimization Column',
        'timestamp' => current_time('mysql'),
        'operations' => array(),
        'summary' => array(),
        'errors' => array()
    );

    try {
        global $wpdb;
        $lmfwc_table = $wpdb->prefix . 'lmfwc_licenses';
        $hash_column = 'license_key_hash';

        // Step 1: Check if LMfWC table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$lmfwc_table'") === $lmfwc_table;

        if (!$table_exists) {
            throw new Exception("LMfWC table '$lmfwc_table' does not exist");
        }

        $results['operations']['table_check'] = array(
            'status' => 'pass',
            'message' => 'LMfWC table exists',
            'table_name' => $lmfwc_table
        );

        // Step 2: Check current table structure
        $columns = $wpdb->get_col("DESCRIBE $lmfwc_table");
        $has_hash_column = in_array($hash_column, $columns);

        $results['operations']['column_check'] = array(
            'status' => $has_hash_column ? 'pass' : 'pending',
            'message' => $has_hash_column ? 'Hash column already exists' : 'Hash column needs to be created',
            'has_hash_column' => $has_hash_column,
            'existing_columns' => $columns
        );

        // Step 3: Add hash column if it doesn't exist
        if (!$has_hash_column) {
            $add_column_sql = "ALTER TABLE $lmfwc_table ADD COLUMN $hash_column VARCHAR(64) NULL DEFAULT NULL";
            $add_result = $wpdb->query($add_column_sql);

            if ($add_result === false) {
                throw new Exception("Failed to add hash column: " . $wpdb->last_error);
            }

            $results['operations']['add_column'] = array(
                'status' => 'pass',
                'message' => 'Hash column added successfully',
                'sql_executed' => $add_column_sql,
                'rows_affected' => $add_result
            );

            // Add index for performance
            $add_index_sql = "ALTER TABLE $lmfwc_table ADD INDEX idx_license_key_hash ($hash_column)";
            $index_result = $wpdb->query($add_index_sql);

            $results['operations']['add_index'] = array(
                'status' => $index_result !== false ? 'pass' : 'warn',
                'message' => $index_result !== false ? 'Hash index added successfully' : 'Hash index creation failed',
                'sql_executed' => $add_index_sql,
                'index_created' => $index_result !== false
            );
        } else {
            $results['operations']['add_column'] = array(
                'status' => 'skip',
                'message' => 'Hash column already exists - skipping creation'
            );
        }

        // Step 4: Count existing licenses and plan hash population
        $total_licenses = $wpdb->get_var("SELECT COUNT(*) FROM $lmfwc_table");
        $licenses_with_hash = $wpdb->get_var("SELECT COUNT(*) FROM $lmfwc_table WHERE $hash_column IS NOT NULL");
        $licenses_needing_hash = $total_licenses - $licenses_with_hash;

        $results['operations']['hash_population_analysis'] = array(
            'status' => 'info',
            'message' => 'Hash population analysis completed',
            'total_licenses' => $total_licenses,
            'licenses_with_hash' => $licenses_with_hash,
            'licenses_needing_hash' => $licenses_needing_hash,
            'population_needed' => $licenses_needing_hash > 0
        );

        // Step 5: Populate hash values for existing licenses (batch process)
        if ($licenses_needing_hash > 0) {
            $batch_size = 20; // Process in small batches to avoid timeouts
            $processed = 0;
            $errors = 0;
            $batch_results = array();

            // Check if LMfWC decrypt filter is available
            if (!has_filter('lmfwc_decrypt')) {
                throw new Exception('LMfWC decrypt filter not available - cannot populate hashes');
            }

            $licenses_without_hash = $wpdb->get_results(
                "SELECT id, license_key FROM $lmfwc_table WHERE $hash_column IS NULL ORDER BY id DESC LIMIT $batch_size",
                ARRAY_A
            );

            foreach ($licenses_without_hash as $license) {
                try {
                    $license_id = $license['id'];
                    $encrypted_key = $license['license_key'];

                    // Decrypt the license key
                    $decrypted_key = apply_filters('lmfwc_decrypt', $encrypted_key);

                    if ($decrypted_key) {
                        // Generate hash of plaintext license
                        $license_hash = hash('sha256', $decrypted_key);

                        // Update the record with hash
                        $update_result = $wpdb->update(
                            $lmfwc_table,
                            array($hash_column => $license_hash),
                            array('id' => $license_id),
                            array('%s'),
                            array('%d')
                        );

                        if ($update_result !== false) {
                            $processed++;
                            $batch_results[] = array(
                                'license_id' => $license_id,
                                'decrypted_preview' => substr($decrypted_key, 0, 10) . '...',
                                'hash_preview' => substr($license_hash, 0, 16) . '...',
                                'status' => 'success'
                            );
                        } else {
                            $errors++;
                            $batch_results[] = array(
                                'license_id' => $license_id,
                                'status' => 'update_failed',
                                'error' => $wpdb->last_error
                            );
                        }
                    } else {
                        $errors++;
                        $batch_results[] = array(
                            'license_id' => $license_id,
                            'status' => 'decrypt_failed'
                        );
                    }

                } catch (Exception $e) {
                    $errors++;
                    $batch_results[] = array(
                        'license_id' => $license['id'],
                        'status' => 'exception',
                        'error' => $e->getMessage()
                    );
                }
            }

            $results['operations']['hash_population_batch'] = array(
                'status' => $processed > 0 ? 'pass' : 'warn',
                'message' => "Processed $processed licenses in batch, $errors errors",
                'batch_size' => $batch_size,
                'processed_successfully' => $processed,
                'errors' => $errors,
                'batch_details' => $batch_results,
                'remaining_licenses' => max(0, $licenses_needing_hash - $processed),
                'completion_percentage' => round(($processed / $licenses_needing_hash) * 100, 1)
            );

            // Provide guidance for remaining licenses
            if ($licenses_needing_hash - $processed > 0) {
                $results['operations']['remaining_work'] = array(
                    'status' => 'info',
                    'message' => 'Additional batches needed to complete hash population',
                    'remaining_count' => $licenses_needing_hash - $processed,
                    'estimated_batches' => ceil(($licenses_needing_hash - $processed) / $batch_size),
                    'recommendation' => 'Run this script multiple times or increase batch size'
                );
            }
        } else {
            $results['operations']['hash_population_batch'] = array(
                'status' => 'skip',
                'message' => 'All licenses already have hash values'
            );
        }

        // Step 6: Test hash-based lookup
        if ($licenses_with_hash > 0 || $processed > 0) {
            $test_license = $wpdb->get_row(
                "SELECT id, license_key, $hash_column FROM $lmfwc_table WHERE $hash_column IS NOT NULL LIMIT 1",
                ARRAY_A
            );

            if ($test_license) {
                $test_hash = $test_license[$hash_column];
                $start_time = microtime(true);
                $hash_lookup_result = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $lmfwc_table WHERE $hash_column = %s",
                    $test_hash
                ), ARRAY_A);
                $hash_lookup_time = round((microtime(true) - $start_time) * 1000, 3);

                $results['operations']['hash_lookup_test'] = array(
                    'status' => $hash_lookup_result ? 'pass' : 'fail',
                    'message' => 'Hash-based lookup test',
                    'test_hash' => substr($test_hash, 0, 16) . '...',
                    'lookup_time_ms' => $hash_lookup_time,
                    'found_license_id' => $hash_lookup_result ? $hash_lookup_result['id'] : null,
                    'performance_excellent' => $hash_lookup_time < 5
                );
            }
        }

        // Calculate summary
        $total_operations = count($results['operations']);
        $successful_operations = 0;

        foreach ($results['operations'] as $operation) {
            if (in_array($operation['status'], ['pass', 'skip'])) {
                $successful_operations++;
            }
        }

        $results['summary'] = array(
            'total_operations' => $total_operations,
            'successful_operations' => $successful_operations,
            'success_rate' => round(($successful_operations / $total_operations) * 100, 1),
            'hash_column_ready' => in_array($hash_column, $wpdb->get_col("DESCRIBE $lmfwc_table")),
            'optimization_status' => $licenses_with_hash > 0 ? 'partially_optimized' : 'ready_for_optimization',
            'licenses_optimized' => $licenses_with_hash + ($processed ?? 0),
            'total_licenses' => $total_licenses,
            'next_steps' => array(
                '1. Enable hash optimization: add_filter("vd_enable_license_hash_optimization", "__return_true")',
                '2. Complete hash population if needed',
                '3. Test improved lookup performance',
                '4. Monitor system performance'
            )
        );

    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['errors'][] = $e->getMessage();
    }

    echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_die();
});
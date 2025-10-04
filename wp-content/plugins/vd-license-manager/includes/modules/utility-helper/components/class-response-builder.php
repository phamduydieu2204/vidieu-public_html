<?php

namespace VD\LicenseManager\UtilityHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Response Builder Component
 *
 * Extracted utility functions for response structure creation from VD_License_Validator.
 * Implements Micro-Step 2B.1.3 - Response Builder Implementation.
 *
 * @package VD_License_Manager
 * @subpackage UtilityHelper
 * @since 2B.1.3
 */
class ResponseBuilder implements ResponseBuilderInterface {

    /**
     * Component version
     *
     * @var string
     */
    const VERSION = '2B.1.3';

    /**
     * Create success response
     *
     * Extracted from class-vd-license-validator.php:4047
     * Original method: create_success_response()
     *
     * @param array $data Response data
     * @param string $message Success message
     * @return array Success response structure
     */
    public static function create_success_response($data = array(), $message = '') {
        // Extract method and metadata from data if provided
        $method = isset($data['method']) ? $data['method'] : 'unknown';
        $response_data = isset($data['data']) ? $data['data'] : $data;
        $metadata = isset($data['metadata']) ? $data['metadata'] : array();

        $response = array(
            'success' => true,
            'method' => $method,
            'version' => '4.2.4.5.1c',
            'timestamp' => current_time('mysql')
        );

        if (!empty($response_data)) {
            $response['data'] = $response_data;
        }

        if (!empty($message)) {
            $response['message'] = $message;
        }

        if (!empty($metadata)) {
            $response['metadata'] = array_merge(array(
                'generated_at' => current_time('mysql'),
                'response_time_ms' => 0
            ), $metadata);
        }

        return $response;
    }

    /**
     * Create error response
     *
     * Extracted from class-vd-license-validator.php:4079
     * Original method: create_error_response()
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param array $data Additional error data
     * @return array Error response structure
     */
    public static function create_error_response($message, $code = 0, $data = array()) {
        // Extract method and error_code from data if provided
        $method = isset($data['method']) ? $data['method'] : 'unknown';
        $error_code = isset($data['error_code']) ? $data['error_code'] : 'GENERAL_ERROR';
        $details = isset($data['details']) ? $data['details'] : array();

        $response = array(
            'success' => false,
            'method' => $method,
            'version' => '4.2.4.5.1c',
            'error' => $message,
            'error_code' => $error_code,
            'timestamp' => current_time('mysql')
        );

        if ($code !== 0) {
            $response['code'] = $code;
        }

        if (!empty($details)) {
            $response['error_details'] = $details;
        }

        return $response;
    }

    /**
     * Create history record structure
     *
     * Extracted from class-vd-license-validator.php:4103
     * Original method: create_history_record_structure()
     *
     * @param array $data History record data
     * @return array Structured history record
     */
    public static function create_history_record_structure($data) {
        if (!is_array($data)) {
            $data = array();
        }

        return array(
            'id' => isset($data['id']) ? $data['id'] : 0,
            'license_id' => isset($data['license_id']) ? $data['license_id'] : 0,
            'old_status' => isset($data['old_status']) ? $data['old_status'] : '',
            'new_status' => isset($data['new_status']) ? $data['new_status'] : '',
            'changed_at' => isset($data['changed_at']) ? $data['changed_at'] : current_time('mysql'),
            'changed_by' => isset($data['changed_by']) ? $data['changed_by'] : 'system',
            'reason' => isset($data['reason']) ? $data['reason'] : '',
            'context' => isset($data['context']) ? $data['context'] : array(),
            'metadata' => array(
                'ip_address' => isset($data['ip_address']) ? $data['ip_address'] : '',
                'user_agent' => isset($data['user_agent']) ? $data['user_agent'] : '',
                'source' => isset($data['source']) ? $data['source'] : 'manual'
            )
        );
    }

    /**
     * Create statistics structure
     *
     * Extracted from class-vd-license-validator.php:4129
     * Original method: create_statistics_structure()
     *
     * @param array $data Statistics data
     * @return array Structured statistics
     */
    public static function create_statistics_structure($data) {
        if (!is_array($data)) {
            $data = array();
        }

        $stats_data = isset($data['stats_data']) ? $data['stats_data'] : $data;
        $options = isset($data['options']) ? $data['options'] : array();

        return array(
            'summary' => array(
                'total_changes' => isset($stats_data['total_changes']) ? $stats_data['total_changes'] : 0,
                'date_range' => array(
                    'from' => isset($options['date_from']) ? $options['date_from'] : '',
                    'to' => isset($options['date_to']) ? $options['date_to'] : ''
                ),
                'group_by' => isset($options['group_by']) ? $options['group_by'] : 'status'
            ),
            'breakdown' => array(
                'by_status' => isset($stats_data['by_status']) ? $stats_data['by_status'] : array(),
                'by_date' => isset($stats_data['by_date']) ? $stats_data['by_date'] : array(),
                'by_month' => isset($stats_data['by_month']) ? $stats_data['by_month'] : array(),
                'by_year' => isset($stats_data['by_year']) ? $stats_data['by_year'] : array()
            ),
            'trends' => array(
                'most_common_change' => isset($stats_data['most_common_change']) ? $stats_data['most_common_change'] : '',
                'peak_activity_day' => isset($stats_data['peak_activity_day']) ? $stats_data['peak_activity_day'] : '',
                'average_changes_per_day' => isset($stats_data['avg_per_day']) ? $stats_data['avg_per_day'] : 0
            ),
            'metadata' => array(
                'query_executed_at' => current_time('mysql'),
                'options_used' => $options,
                'generated_by' => 'ResponseBuilder'
            )
        );
    }

    /**
     * Create pagination structure
     *
     * Extracted from class-vd-license-validator.php:4167
     * Original method: create_pagination_structure()
     *
     * @param array $data Pagination data including options and total_records
     * @return array Structured pagination
     */
    public static function create_pagination_structure($data) {
        if (!is_array($data)) {
            $data = array();
        }

        $options = isset($data['options']) ? $data['options'] : $data;
        $total_records = isset($data['total_records']) ? $data['total_records'] : 0;

        $limit = isset($options['limit']) ? (int) $options['limit'] : 50;
        $offset = isset($options['offset']) ? (int) $options['offset'] : 0;

        $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
        $current_page = $limit > 0 ? floor($offset / $limit) + 1 : 1;

        return array(
            'total_records' => $total_records,
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'has_next_page' => ($offset + $limit) < $total_records,
            'has_previous_page' => $offset > 0,
            'next_offset' => ($offset + $limit) < $total_records ? ($offset + $limit) : null,
            'previous_offset' => $offset > 0 ? max(0, $offset - $limit) : null
        );
    }

    /**
     * Get component status
     *
     * @return array Component status information
     */
    public static function get_status() {
        return array(
            'component' => 'ResponseBuilder',
            'version' => self::VERSION,
            'methods' => array(
                'create_success_response',
                'create_error_response',
                'create_history_record_structure',
                'create_statistics_structure',
                'create_pagination_structure'
            ),
            'extracted_from' => 'class-vd-license-validator.php',
            'extraction_lines' => array(
                'create_success_response' => 4047,
                'create_error_response' => 4079,
                'create_history_record_structure' => 4103,
                'create_statistics_structure' => 4129,
                'create_pagination_structure' => 4167
            ),
            'ready' => true
        );
    }

    /**
     * Test all response building methods
     *
     * @return array Test results
     */
    public static function run_tests() {
        $results = array();

        // Test create_success_response
        $test_data = array('method' => 'test', 'data' => array('test' => 'value'));
        $result = self::create_success_response($test_data, 'Test success');
        $results['create_success_response'] = array(
            'input' => $test_data,
            'output' => $result,
            'passed' => (
                isset($result['success']) && $result['success'] === true &&
                isset($result['method']) && $result['method'] === 'test' &&
                isset($result['message']) && $result['message'] === 'Test success'
            )
        );

        // Test create_error_response
        $test_error = 'Test error message';
        $error_data = array('method' => 'test', 'error_code' => 'TEST_ERROR');
        $result = self::create_error_response($test_error, 400, $error_data);
        $results['create_error_response'] = array(
            'input' => array('message' => $test_error, 'code' => 400, 'data' => $error_data),
            'output' => $result,
            'passed' => (
                isset($result['success']) && $result['success'] === false &&
                isset($result['error']) && $result['error'] === $test_error &&
                isset($result['error_code']) && $result['error_code'] === 'TEST_ERROR'
            )
        );

        // Test create_history_record_structure
        $test_record = array('id' => 123, 'license_id' => 456, 'old_status' => 'active', 'new_status' => 'expired');
        $result = self::create_history_record_structure($test_record);
        $results['create_history_record_structure'] = array(
            'input' => $test_record,
            'output' => $result,
            'passed' => (
                isset($result['id']) && $result['id'] === 123 &&
                isset($result['license_id']) && $result['license_id'] === 456 &&
                isset($result['old_status']) && $result['old_status'] === 'active'
            )
        );

        // Test create_statistics_structure
        $test_stats = array('stats_data' => array('total_changes' => 100));
        $result = self::create_statistics_structure($test_stats);
        $results['create_statistics_structure'] = array(
            'input' => $test_stats,
            'output' => $result,
            'passed' => (
                isset($result['summary']['total_changes']) && $result['summary']['total_changes'] === 100 &&
                isset($result['breakdown']) && is_array($result['breakdown'])
            )
        );

        // Test create_pagination_structure
        $test_pagination = array('options' => array('limit' => 20, 'offset' => 40), 'total_records' => 150);
        $result = self::create_pagination_structure($test_pagination);
        $results['create_pagination_structure'] = array(
            'input' => $test_pagination,
            'output' => $result,
            'passed' => (
                isset($result['total_records']) && $result['total_records'] === 150 &&
                isset($result['limit']) && $result['limit'] === 20 &&
                isset($result['current_page']) && $result['current_page'] === 3
            )
        );

        return $results;
    }
}
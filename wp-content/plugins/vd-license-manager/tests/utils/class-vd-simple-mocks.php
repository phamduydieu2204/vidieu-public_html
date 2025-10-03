<?php
/**
 * Simple Test Mocks - Mock objects for testing
 *
 * Simplified version that works without WordPress test suite
 *
 * @package VD_License_Manager
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple Test Mocks for Integration Testing
 */
class VD_Simple_Mocks {

    private $mock_responses = [];
    private $mock_calls = [];

    /**
     * Create a mock WordPress database
     */
    public function mockWordPressDatabase() {
        return new VD_Mock_Database();
    }

    /**
     * Create a mock HTTP client
     */
    public function mockHttpClient() {
        return new VD_Mock_Http_Client();
    }

    /**
     * Create a mock cache system
     */
    public function mockCache() {
        return new VD_Mock_Cache();
    }

    /**
     * Set mock response for specific method calls
     */
    public function setMockResponse($method, $response) {
        $this->mock_responses[$method] = $response;
    }

    /**
     * Get mock response for method
     */
    public function getMockResponse($method) {
        return isset($this->mock_responses[$method]) ? $this->mock_responses[$method] : null;
    }

    /**
     * Record mock method calls
     */
    public function recordCall($method, $args = []) {
        $this->mock_calls[] = [
            'method' => $method,
            'args' => $args,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Get recorded calls
     */
    public function getCalls() {
        return $this->mock_calls;
    }

    /**
     * Clear recorded calls
     */
    public function clearCalls() {
        $this->mock_calls = [];
    }
}

/**
 * Mock WordPress Database
 */
class VD_Mock_Database {

    private $data = [];
    private $last_query = '';

    public function get_results($query) {
        $this->last_query = $query;

        if (strpos($query, 'SELECT') === 0) {
            return [
                (object) ['id' => 1, 'license_key' => 'VD-TEST-1234', 'status' => 'active'],
                (object) ['id' => 2, 'license_key' => 'VD-TEST-5678', 'status' => 'inactive']
            ];
        }

        return [];
    }

    public function insert($table, $data) {
        $this->data[$table][] = $data;
        return true;
    }

    public function update($table, $data, $where) {
        return true;
    }

    public function get_last_query() {
        return $this->last_query;
    }
}

/**
 * Mock HTTP Client
 */
class VD_Mock_Http_Client {

    private $responses = [];

    public function get($url, $args = []) {
        return [
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'success', 'data' => 'mock response'])
        ];
    }

    public function post($url, $args = []) {
        return [
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'success', 'message' => 'Data posted successfully'])
        ];
    }

    public function setResponse($url, $response) {
        $this->responses[$url] = $response;
    }
}

/**
 * Mock Cache System
 */
class VD_Mock_Cache {

    private $cache_data = [];

    public function get($key) {
        return isset($this->cache_data[$key]) ? $this->cache_data[$key] : false;
    }

    public function set($key, $value, $expiration = 3600) {
        $this->cache_data[$key] = [
            'value' => $value,
            'expires' => time() + $expiration
        ];
        return true;
    }

    public function delete($key) {
        unset($this->cache_data[$key]);
        return true;
    }

    public function flush() {
        $this->cache_data = [];
        return true;
    }

    public function getStats() {
        return [
            'total_keys' => count($this->cache_data),
            'memory_usage' => strlen(serialize($this->cache_data))
        ];
    }
}
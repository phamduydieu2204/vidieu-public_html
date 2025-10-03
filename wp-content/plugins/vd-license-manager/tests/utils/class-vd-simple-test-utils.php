<?php
/**
 * Simple Test Utils - WordPress-compatible testing utilities
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
 * Simple Test Utilities for Integration Testing
 */
class VD_Simple_Test_Utils {

    /**
     * Assert that a condition is true
     */
    public function assertTrue($condition, $message = '') {
        return [
            'success' => (bool) $condition,
            'message' => $message ?: 'Assertion ' . ($condition ? 'passed' : 'failed'),
            'type' => 'assertTrue'
        ];
    }

    /**
     * Assert that two values are equal
     */
    public function assertEquals($expected, $actual, $message = '') {
        $equal = ($expected === $actual);
        return [
            'success' => $equal,
            'message' => $message ?: sprintf('Expected: %s, Actual: %s',
                $this->formatValue($expected),
                $this->formatValue($actual)
            ),
            'type' => 'assertEquals',
            'expected' => $expected,
            'actual' => $actual
        ];
    }

    /**
     * Assert that a class exists
     */
    public function assertClassExists($className, $message = '') {
        $exists = class_exists($className);
        return [
            'success' => $exists,
            'message' => $message ?: "Class '$className' " . ($exists ? 'exists' : 'does not exist'),
            'type' => 'assertClassExists',
            'class' => $className
        ];
    }

    /**
     * Assert that a method exists
     */
    public function assertMethodExists($className, $methodName, $message = '') {
        $exists = class_exists($className) && method_exists($className, $methodName);
        return [
            'success' => $exists,
            'message' => $message ?: "Method '$methodName' in class '$className' " . ($exists ? 'exists' : 'does not exist'),
            'type' => 'assertMethodExists',
            'class' => $className,
            'method' => $methodName
        ];
    }

    /**
     * Assert that a value is not null
     */
    public function assertNotNull($value, $message = '') {
        $notNull = ($value !== null);
        return [
            'success' => $notNull,
            'message' => $message ?: 'Value is ' . ($notNull ? 'not null' : 'null'),
            'type' => 'assertNotNull'
        ];
    }

    /**
     * Assert that an array has a specific key
     */
    public function assertArrayHasKey($key, $array, $message = '') {
        $hasKey = is_array($array) && array_key_exists($key, $array);
        return [
            'success' => $hasKey,
            'message' => $message ?: "Array " . ($hasKey ? 'has' : 'does not have') . " key '$key'",
            'type' => 'assertArrayHasKey',
            'key' => $key
        ];
    }

    /**
     * Simulate a WordPress action/filter
     */
    public function simulateWordPressHook($hook_name, $args = []) {
        return [
            'success' => true,
            'message' => "Simulated WordPress hook: $hook_name",
            'hook' => $hook_name,
            'args' => $args,
            'type' => 'simulateHook'
        ];
    }

    /**
     * Check if a WordPress function exists
     */
    public function checkWordPressFunction($function_name) {
        $exists = function_exists($function_name);
        return [
            'success' => $exists,
            'message' => "WordPress function '$function_name' " . ($exists ? 'exists' : 'does not exist'),
            'function' => $function_name,
            'type' => 'checkFunction'
        ];
    }

    /**
     * Format value for display
     */
    private function formatValue($value) {
        if (is_string($value)) {
            return "'$value'";
        } elseif (is_array($value)) {
            return 'Array(' . count($value) . ')';
        } elseif (is_object($value)) {
            return get_class($value) . ' Object';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        }
        return (string) $value;
    }

    /**
     * Get execution time for performance testing
     */
    public function getExecutionTime($start_time = null) {
        static $start = null;

        if ($start_time !== null) {
            $start = $start_time;
        } elseif ($start === null) {
            $start = microtime(true);
        }

        return round((microtime(true) - $start) * 1000, 2); // milliseconds
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage() {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'formatted' => [
                'current' => $this->formatBytes(memory_get_usage(true)),
                'peak' => $this->formatBytes(memory_get_peak_usage(true))
            ]
        ];
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
<?php
/**
 * Vidieu Performance Logger
 * 
 * Logs queries and bootstrap hooks for performance analysis
 * Only runs on frontend when flags are enabled
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_Perf_Logger {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Current route being tracked
     */
    private $current_route = '';
    
    /**
     * Query log data
     */
    private $queries = [];
    
    /**
     * Bootstrap hooks data
     */
    private $bootstrap_hooks = [];
    
    /**
     * Start time for bootstrap tracking
     */
    private $bootstrap_start = 0;
    
    /**
     * Routes to track
     */
    private $tracked_routes = [
        '/' => 'home',
        '/san-pham/' => 'shop',
        '/bai-viet/' => 'blog',
        '/contact/' => 'contact',
        '/cart/' => 'cart',
        '/checkout/' => 'checkout',
        '/my-account/' => 'account'
    ];
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Only run on frontend
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Initialize based on flags
        if (defined('VIDIEU_PERF_LOG_QUERIES') && VIDIEU_PERF_LOG_QUERIES) {
            add_action('init', [$this, 'init_query_logging'], 1);
        }
        
        if (defined('VIDIEU_PERF_LOG_BOOTSTRAP') && VIDIEU_PERF_LOG_BOOTSTRAP) {
            $this->bootstrap_start = microtime(true);
            add_action('plugins_loaded', [$this, 'track_bootstrap_hook'], 1);
            add_action('after_setup_theme', [$this, 'track_bootstrap_hook'], 1);
            add_action('init', [$this, 'track_bootstrap_hook'], 1);
            add_action('wp_loaded', [$this, 'track_bootstrap_hook'], 1);
            add_action('template_redirect', [$this, 'finalize_bootstrap_logging'], 999);
        }
        
        // Save logs on shutdown
        add_action('shutdown', [$this, 'save_logs'], 999);
    }
    
    /**
     * Initialize query logging
     */
    public function init_query_logging() {
        if (!defined('SAVEQUERIES')) {
            define('SAVEQUERIES', true);
        }
        
        // Determine current route
        $this->current_route = $this->get_current_route();
        
        // Hook into query filter
        add_filter('log_query_custom_data', [$this, 'log_query_data'], 10, 5);
        add_action('shutdown', [$this, 'process_queries'], 998);
    }
    
    /**
     * Get current route identifier
     */
    private function get_current_route() {
        $uri = $_SERVER['REQUEST_URI'];
        $parsed = parse_url($uri);
        $path = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';
        
        // Handle empty path as home
        if (empty($path)) {
            $path = '/';
        }
        
        // Check for exact route match
        foreach ($this->tracked_routes as $route => $name) {
            if ($path === rtrim($route, '/') || $path === $route) {
                return $name;
            }
        }
        
        // Check for product pages
        if (preg_match('/^\/product\//', $path)) {
            return 'product';
        }
        
        // Default to 'other'
        return 'other';
    }
    
    /**
     * Track bootstrap hooks
     */
    public function track_bootstrap_hook($hook = null) {
        if (!$hook) {
            $hook = current_action();
        }
        
        $time_elapsed = microtime(true) - $this->bootstrap_start;
        
        $this->bootstrap_hooks[] = [
            'hook' => $hook,
            'time' => $time_elapsed,
            'memory' => memory_get_usage(true),
            'callbacks' => $this->get_hook_callbacks($hook)
        ];
    }
    
    /**
     * Get callbacks for a hook
     */
    private function get_hook_callbacks($hook) {
        global $wp_filter;
        
        if (!isset($wp_filter[$hook])) {
            return [];
        }
        
        $callbacks = [];
        foreach ($wp_filter[$hook] as $priority => $functions) {
            foreach ($functions as $function) {
                $callback_name = $this->get_callback_name($function['function']);
                if ($callback_name && !$this->is_core_callback($callback_name)) {
                    $callbacks[] = [
                        'priority' => $priority,
                        'function' => $callback_name
                    ];
                }
            }
        }
        
        return $callbacks;
    }
    
    /**
     * Get human-readable callback name
     */
    private function get_callback_name($callback) {
        if (is_string($callback)) {
            return $callback;
        }
        
        if (is_array($callback)) {
            if (is_object($callback[0])) {
                return get_class($callback[0]) . '::' . $callback[1];
            } else {
                return $callback[0] . '::' . $callback[1];
            }
        }
        
        if (is_object($callback) && $callback instanceof Closure) {
            return 'Closure';
        }
        
        return 'Unknown';
    }
    
    /**
     * Check if callback is WordPress core
     */
    private function is_core_callback($callback_name) {
        $core_prefixes = ['wp_', 'WP_', '_wp_', 'rest_', 'do_action', 'apply_filters'];
        foreach ($core_prefixes as $prefix) {
            if (strpos($callback_name, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Finalize bootstrap logging
     */
    public function finalize_bootstrap_logging() {
        $this->track_bootstrap_hook('template_redirect');
    }
    
    /**
     * Process queries from WordPress
     */
    public function process_queries() {
        global $wpdb;
        
        if (!isset($wpdb->queries) || empty($wpdb->queries)) {
            return;
        }
        
        foreach ($wpdb->queries as $query_data) {
            $query = $query_data[0];
            $time = $query_data[1];
            $caller = isset($query_data[2]) ? $query_data[2] : '';
            
            // Skip queries from this logger
            if (strpos($caller, 'Vidieu_Perf_Logger') !== false) {
                continue;
            }
            
            // Categorize query
            $type = $this->get_query_type($query);
            
            $this->queries[] = [
                'query' => $this->sanitize_query($query),
                'time' => $time,
                'caller' => $caller,
                'type' => $type
            ];
        }
    }
    
    /**
     * Get query type
     */
    private function get_query_type($query) {
        $query = strtolower(trim($query));
        
        if (strpos($query, 'select') === 0) {
            if (strpos($query, 'wp_options') !== false) {
                return 'options';
            } elseif (strpos($query, 'postmeta') !== false) {
                return 'postmeta';
            } elseif (strpos($query, 'termmeta') !== false) {
                return 'termmeta';
            } elseif (strpos($query, 'wp_posts') !== false) {
                return 'posts';
            } elseif (strpos($query, 'wp_term') !== false) {
                return 'terms';
            }
        } elseif (strpos($query, 'update') === 0) {
            return 'update';
        } elseif (strpos($query, 'insert') === 0) {
            return 'insert';
        }
        
        return 'other';
    }
    
    /**
     * Sanitize query for logging
     */
    private function sanitize_query($query) {
        // Remove actual values to avoid logging sensitive data
        $query = preg_replace('/= \'[^\']*\'/', '= \'***\'', $query);
        $query = preg_replace('/= "[^"]*"/', '= "***"', $query);
        $query = preg_replace('/= \d+/', '= ###', $query);
        
        // Limit query length
        if (strlen($query) > 500) {
            $query = substr($query, 0, 500) . '...';
        }
        
        return $query;
    }
    
    /**
     * Save logs to files
     */
    public function save_logs() {
        if (empty($this->current_route)) {
            $this->current_route = $this->get_current_route();
        }
        
        $log_dir = WP_CONTENT_DIR . '/plugins/vidieu-home-sections/perf/server/logs';
        
        // Save query logs
        if (!empty($this->queries) && defined('VIDIEU_PERF_LOG_QUERIES') && VIDIEU_PERF_LOG_QUERIES) {
            $query_file = $log_dir . '/' . $this->current_route . '-queries.log';
            $this->write_query_log($query_file);
        }
        
        // Save bootstrap logs
        if (!empty($this->bootstrap_hooks) && defined('VIDIEU_PERF_LOG_BOOTSTRAP') && VIDIEU_PERF_LOG_BOOTSTRAP) {
            $bootstrap_file = $log_dir . '/' . $this->current_route . '-bootstrap.log';
            $this->write_bootstrap_log($bootstrap_file);
        }
    }
    
    /**
     * Write query log
     */
    private function write_query_log($file) {
        $timestamp = date('Y-m-d H:i:s');
        $content = "\n\n=== Query Log: {$this->current_route} - {$timestamp} ===\n";
        
        // Summary
        $total_time = array_sum(array_column($this->queries, 'time'));
        $query_count = count($this->queries);
        
        $content .= "Total Queries: {$query_count}\n";
        $content .= "Total Time: " . number_format($total_time, 4) . "s\n\n";
        
        // Group by type
        $by_type = [];
        foreach ($this->queries as $query) {
            if (!isset($by_type[$query['type']])) {
                $by_type[$query['type']] = [
                    'count' => 0,
                    'time' => 0,
                    'queries' => []
                ];
            }
            $by_type[$query['type']]['count']++;
            $by_type[$query['type']]['time'] += $query['time'];
            $by_type[$query['type']]['queries'][] = $query;
        }
        
        // Sort types by time
        uasort($by_type, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        foreach ($by_type as $type => $data) {
            $content .= "Type: {$type} - Count: {$data['count']} - Time: " . number_format($data['time'], 4) . "s\n";
            
            // Show top 5 slowest queries of this type
            usort($data['queries'], function($a, $b) {
                return $b['time'] <=> $a['time'];
            });
            
            $shown = 0;
            foreach ($data['queries'] as $query) {
                if ($shown >= 5) break;
                if ($query['time'] > 0.01) { // Only show queries > 10ms
                    $content .= "  - Time: " . number_format($query['time'], 4) . "s\n";
                    $content .= "    Query: " . $query['query'] . "\n";
                    $content .= "    Caller: " . $query['caller'] . "\n";
                    $shown++;
                }
            }
            $content .= "\n";
        }
        
        file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Write bootstrap log
     */
    private function write_bootstrap_log($file) {
        $timestamp = date('Y-m-d H:i:s');
        $content = "\n\n=== Bootstrap Log: {$this->current_route} - {$timestamp} ===\n";
        
        foreach ($this->bootstrap_hooks as $hook_data) {
            $content .= "\nHook: {$hook_data['hook']}\n";
            $content .= "Time: " . number_format($hook_data['time'], 4) . "s\n";
            $content .= "Memory: " . number_format($hook_data['memory'] / 1048576, 2) . " MB\n";
            
            if (!empty($hook_data['callbacks'])) {
                $content .= "Callbacks:\n";
                foreach ($hook_data['callbacks'] as $callback) {
                    $content .= "  - [{$callback['priority']}] {$callback['function']}\n";
                }
            }
        }
        
        file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
    }
}

// Initialize logger
Vidieu_Perf_Logger::get_instance();
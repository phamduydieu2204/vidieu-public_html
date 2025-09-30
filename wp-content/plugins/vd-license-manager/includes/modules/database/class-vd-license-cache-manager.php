<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD License Cache Manager Module
 *
 * Handles caching operations for license validation and database queries
 * Step 1.5 - Extracted from VD_License_Validator class
 *
 * Responsibilities:
 * - Validation result caching with TTL
 * - License settings caching
 * - Performance optimization through intelligent caching
 * - Cache statistics and monitoring
 * - Cache invalidation and cleanup
 *
 * @since 1.5.0-rc.1
 * @package VD_License_Manager\Database
 * @namespace VD\LicenseManager\Database
 */
class VD_License_Cache_Manager {

    /**
     * Module information
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $module_info = array(
        'name' => 'VD License Cache Manager',
        'version' => '1.5.0-rc.1',
        'namespace' => 'VD\\LicenseManager\\Database',
        'description' => 'Handles caching operations for license validation and database queries',
        'dependencies' => array(),
        'supports' => array('validation_cache', 'settings_cache', 'ttl_cache', 'statistics')
    );

    /**
     * Main validation cache storage
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $validation_cache = array();

    /**
     * History validation cache
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $history_cache = array();

    /**
     * Cache configuration settings
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $cache_config = array();

    /**
     * Cache statistics and performance metrics
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $cache_stats = array(
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'clears' => 0,
        'memory_usage' => 0,
        'avg_lookup_time' => 0,
        'total_requests' => 0
    );

    /**
     * Cache TTL (Time To Live) settings in seconds
     *
     * @since 1.5.0-rc.1
     * @var array
     */
    private $cache_ttl = array(
        'validation' => 300,      // 5 minutes for validation results
        'settings' => 600,        // 10 minutes for license settings
        'history' => 180,         // 3 minutes for history data
        'stats' => 60,           // 1 minute for statistics
        'default' => 300         // Default TTL
    );

    /**
     * Maximum cache entries before cleanup
     *
     * @since 1.5.0-rc.1
     * @var int
     */
    private $max_cache_entries = 1000;

    /**
     * Constructor
     *
     * @since 1.5.0-rc.1
     */
    public function __construct() {
        $this->init_cache_config();
        $this->init_cache_stats();
    }

    /**
     * Initialize cache configuration
     *
     * @since 1.5.0-rc.1
     * @return void
     */
    private function init_cache_config() {
        $this->cache_config = array(
            'enabled' => true,
            'memory_limit' => 50 * 1024 * 1024, // 50MB
            'cleanup_threshold' => 0.8, // Cleanup when 80% full
            'auto_cleanup' => true,
            'ttl_enabled' => true,
            'statistics_enabled' => true,
            'debug_mode' => defined('VD_DEBUG') && VD_DEBUG
        );

        // Allow configuration override via filters
        $this->cache_config = apply_filters('vd_cache_manager_config', $this->cache_config);
    }

    /**
     * Initialize cache statistics
     *
     * @since 1.5.0-rc.1
     * @return void
     */
    private function init_cache_stats() {
        $this->cache_stats['start_time'] = microtime(true);
        $this->cache_stats['memory_limit'] = $this->cache_config['memory_limit'];
        $this->cache_stats['last_cleanup'] = time();
    }

    /**
     * Get cached validation result
     *
     * @since 1.5.0-rc.1
     * @param string $license_key License key for cache lookup
     * @return array|null Cached validation result or null if not found/expired
     */
    public function get_validation_cache($license_key) {
        $cache_key = $this->generate_cache_key('validation', $license_key);
        return $this->get_cache_entry($cache_key, 'validation');
    }

    /**
     * Set validation result in cache
     *
     * @since 1.5.0-rc.1
     * @param string $license_key License key
     * @param array $result Validation result to cache
     * @param int|null $ttl Time to live in seconds (null for default)
     * @return bool True if cached successfully, false otherwise
     */
    public function set_validation_cache($license_key, $result, $ttl = null) {
        $cache_key = $this->generate_cache_key('validation', $license_key);
        return $this->set_cache_entry($cache_key, $result, 'validation', $ttl);
    }

    /**
     * Get cached license settings
     *
     * @since 1.5.0-rc.1
     * @param int $license_id License ID
     * @param int $product_id Product ID
     * @return array|null Cached settings or null if not found/expired
     */
    public function get_settings_cache($license_id, $product_id) {
        $cache_key = $this->generate_cache_key('settings', "{$license_id}_{$product_id}");
        return $this->get_cache_entry($cache_key, 'settings');
    }

    /**
     * Set license settings in cache
     *
     * @since 1.5.0-rc.1
     * @param int $license_id License ID
     * @param int $product_id Product ID
     * @param array $settings Settings to cache
     * @param int|null $ttl Time to live in seconds (null for default)
     * @return bool True if cached successfully, false otherwise
     */
    public function set_settings_cache($license_id, $product_id, $settings, $ttl = null) {
        $cache_key = $this->generate_cache_key('settings', "{$license_id}_{$product_id}");
        return $this->set_cache_entry($cache_key, $settings, 'settings', $ttl);
    }

    /**
     * Get cached history data
     *
     * @since 1.5.0-rc.1
     * @param string $history_key History cache key
     * @return array|null Cached history data or null if not found/expired
     */
    public function get_history_cache($history_key) {
        $cache_key = $this->generate_cache_key('history', $history_key);

        if (isset($this->history_cache[$cache_key])) {
            $entry = $this->history_cache[$cache_key];

            if ($this->is_cache_entry_valid($entry, 'history')) {
                $this->cache_stats['hits']++;
                return $entry['data'];
            } else {
                unset($this->history_cache[$cache_key]);
                $this->cache_stats['misses']++;
            }
        } else {
            $this->cache_stats['misses']++;
        }

        return null;
    }

    /**
     * Set history data in cache
     *
     * @since 1.5.0-rc.1
     * @param string $history_key History cache key
     * @param array $data History data to cache
     * @param int|null $ttl Time to live in seconds (null for default)
     * @return bool True if cached successfully, false otherwise
     */
    public function set_history_cache($history_key, $data, $ttl = null) {
        if (!$this->cache_config['enabled']) {
            return false;
        }

        $cache_key = $this->generate_cache_key('history', $history_key);
        $effective_ttl = $ttl ?? $this->cache_ttl['history'];

        $this->history_cache[$cache_key] = array(
            'data' => $data,
            'timestamp' => time(),
            'ttl' => $effective_ttl,
            'expires_at' => time() + $effective_ttl,
            'access_count' => 0,
            'last_access' => time()
        );

        $this->cache_stats['sets']++;
        $this->update_memory_usage();
        $this->maybe_cleanup_cache();

        return true;
    }

    /**
     * Generate cache key
     *
     * @since 1.5.0-rc.1
     * @param string $type Cache type (validation, settings, history)
     * @param string $identifier Unique identifier
     * @return string Generated cache key
     */
    private function generate_cache_key($type, $identifier) {
        return "vd_cache_{$type}_" . md5($identifier);
    }

    /**
     * Get cache entry with TTL validation
     *
     * @since 1.5.0-rc.1
     * @param string $cache_key Cache key
     * @param string $type Cache type for TTL lookup
     * @return mixed|null Cache data or null if not found/expired
     */
    private function get_cache_entry($cache_key, $type) {
        $start_time = microtime(true);
        $this->cache_stats['total_requests']++;

        if (isset($this->validation_cache[$cache_key])) {
            $entry = $this->validation_cache[$cache_key];

            if ($this->is_cache_entry_valid($entry, $type)) {
                $this->cache_stats['hits']++;
                $entry['access_count']++;
                $entry['last_access'] = time();

                // Update average lookup time
                $lookup_time = (microtime(true) - $start_time) * 1000;
                $this->update_avg_lookup_time($lookup_time);

                return $entry['data'];
            } else {
                // Entry expired, remove it
                unset($this->validation_cache[$cache_key]);
                $this->cache_stats['misses']++;
            }
        } else {
            $this->cache_stats['misses']++;
        }

        return null;
    }

    /**
     * Set cache entry with TTL
     *
     * @since 1.5.0-rc.1
     * @param string $cache_key Cache key
     * @param mixed $data Data to cache
     * @param string $type Cache type for TTL lookup
     * @param int|null $ttl Time to live in seconds
     * @return bool True if cached successfully, false otherwise
     */
    private function set_cache_entry($cache_key, $data, $type, $ttl = null) {
        if (!$this->cache_config['enabled']) {
            return false;
        }

        $effective_ttl = $ttl ?? $this->cache_ttl[$type] ?? $this->cache_ttl['default'];

        $this->validation_cache[$cache_key] = array(
            'data' => $data,
            'timestamp' => time(),
            'ttl' => $effective_ttl,
            'expires_at' => time() + $effective_ttl,
            'type' => $type,
            'access_count' => 0,
            'last_access' => time(),
            'size' => strlen(serialize($data))
        );

        $this->cache_stats['sets']++;
        $this->update_memory_usage();
        $this->maybe_cleanup_cache();

        return true;
    }

    /**
     * Check if cache entry is still valid
     *
     * @since 1.5.0-rc.1
     * @param array $entry Cache entry
     * @param string $type Cache type
     * @return bool True if valid, false if expired
     */
    private function is_cache_entry_valid($entry, $type) {
        if (!$this->cache_config['ttl_enabled']) {
            return true; // TTL disabled, always valid
        }

        if (!isset($entry['expires_at'])) {
            return false; // Invalid entry structure
        }

        return time() < $entry['expires_at'];
    }

    /**
     * Clear all validation cache
     *
     * @since 1.5.0-rc.1
     * @return void
     */
    public function clear_validation_cache() {
        $this->validation_cache = array();
        $this->cache_stats['clears']++;
        $this->update_memory_usage();
    }

    /**
     * Clear all history cache
     *
     * @since 1.5.0-rc.1
     * @return void
     */
    public function clear_history_cache() {
        $this->history_cache = array();
        $this->cache_stats['clears']++;
        $this->update_memory_usage();
    }

    /**
     * Clear all caches
     *
     * @since 1.5.0-rc.1
     * @return void
     */
    public function clear_all_cache() {
        $this->clear_validation_cache();
        $this->clear_history_cache();
    }

    /**
     * Remove expired cache entries
     *
     * @since 1.5.0-rc.1
     * @return int Number of entries removed
     */
    public function cleanup_expired_cache() {
        $removed_count = 0;
        $current_time = time();

        // Cleanup validation cache
        foreach ($this->validation_cache as $key => $entry) {
            if (isset($entry['expires_at']) && $current_time >= $entry['expires_at']) {
                unset($this->validation_cache[$key]);
                $removed_count++;
            }
        }

        // Cleanup history cache
        foreach ($this->history_cache as $key => $entry) {
            if (isset($entry['expires_at']) && $current_time >= $entry['expires_at']) {
                unset($this->history_cache[$key]);
                $removed_count++;
            }
        }

        if ($removed_count > 0) {
            $this->cache_stats['last_cleanup'] = $current_time;
            $this->update_memory_usage();
        }

        return $removed_count;
    }

    /**
     * Maybe perform cache cleanup based on thresholds
     *
     * @since 1.5.0-rc.1
     * @return void
     */
    private function maybe_cleanup_cache() {
        if (!$this->cache_config['auto_cleanup']) {
            return;
        }

        $total_entries = count($this->validation_cache) + count($this->history_cache);

        // Cleanup if approaching memory limit or max entries
        if ($total_entries > $this->max_cache_entries ||
            $this->cache_stats['memory_usage'] > ($this->cache_config['memory_limit'] * $this->cache_config['cleanup_threshold'])) {

            $this->cleanup_expired_cache();

            // If still too many entries, remove oldest accessed entries
            if ($total_entries > $this->max_cache_entries) {
                $this->cleanup_least_used_cache();
            }
        }
    }

    /**
     * Remove least recently used cache entries
     *
     * @since 1.5.0-rc.1
     * @return int Number of entries removed
     */
    private function cleanup_least_used_cache() {
        $entries_to_remove = array();

        // Collect entries with access info
        foreach ($this->validation_cache as $key => $entry) {
            $entries_to_remove[] = array(
                'key' => $key,
                'last_access' => $entry['last_access'] ?? 0,
                'access_count' => $entry['access_count'] ?? 0,
                'type' => 'validation'
            );
        }

        foreach ($this->history_cache as $key => $entry) {
            $entries_to_remove[] = array(
                'key' => $key,
                'last_access' => $entry['last_access'] ?? 0,
                'access_count' => $entry['access_count'] ?? 0,
                'type' => 'history'
            );
        }

        // Sort by last access time (oldest first)
        usort($entries_to_remove, function($a, $b) {
            return $a['last_access'] - $b['last_access'];
        });

        // Remove oldest 20% of entries
        $remove_count = (int)(count($entries_to_remove) * 0.2);
        $removed = 0;

        for ($i = 0; $i < $remove_count && $i < count($entries_to_remove); $i++) {
            $entry = $entries_to_remove[$i];

            if ($entry['type'] === 'validation') {
                unset($this->validation_cache[$entry['key']]);
            } else {
                unset($this->history_cache[$entry['key']]);
            }

            $removed++;
        }

        if ($removed > 0) {
            $this->update_memory_usage();
        }

        return $removed;
    }

    /**
     * Update memory usage statistics
     *
     * @since 1.5.0-rc.1
     * @return void
     */
    private function update_memory_usage() {
        $validation_memory = strlen(serialize($this->validation_cache));
        $history_memory = strlen(serialize($this->history_cache));
        $this->cache_stats['memory_usage'] = $validation_memory + $history_memory;
    }

    /**
     * Update average lookup time
     *
     * @since 1.5.0-rc.1
     * @param float $lookup_time Lookup time in milliseconds
     * @return void
     */
    private function update_avg_lookup_time($lookup_time) {
        $total_requests = $this->cache_stats['total_requests'];
        $current_avg = $this->cache_stats['avg_lookup_time'];

        $this->cache_stats['avg_lookup_time'] =
            (($current_avg * ($total_requests - 1)) + $lookup_time) / $total_requests;
    }

    /**
     * Get cache statistics
     *
     * @since 1.5.0-rc.1
     * @return array Cache statistics and performance metrics
     */
    public function get_cache_stats() {
        $total_requests = $this->cache_stats['hits'] + $this->cache_stats['misses'];
        $hit_rate = $total_requests > 0 ? ($this->cache_stats['hits'] / $total_requests) * 100 : 0;

        return array_merge($this->cache_stats, array(
            'hit_rate_percentage' => round($hit_rate, 2),
            'total_entries' => count($this->validation_cache) + count($this->history_cache),
            'validation_entries' => count($this->validation_cache),
            'history_entries' => count($this->history_cache),
            'memory_usage_mb' => round($this->cache_stats['memory_usage'] / 1024 / 1024, 2),
            'memory_limit_mb' => round($this->cache_config['memory_limit'] / 1024 / 1024, 2),
            'uptime_seconds' => round(microtime(true) - $this->cache_stats['start_time'], 2),
            'config' => $this->cache_config
        ));
    }

    /**
     * Get module information
     *
     * @since 1.5.0-rc.1
     * @return array Module information and metadata
     */
    public function get_module_info() {
        return array_merge($this->module_info, array(
            'statistics' => $this->get_cache_stats()
        ));
    }

    /**
     * Invalidate cache by pattern
     *
     * @since 1.5.0-rc.1
     * @param string $pattern Pattern to match cache keys
     * @return int Number of entries invalidated
     */
    public function invalidate_cache_by_pattern($pattern) {
        $invalidated = 0;

        foreach ($this->validation_cache as $key => $entry) {
            if (fnmatch($pattern, $key)) {
                unset($this->validation_cache[$key]);
                $invalidated++;
            }
        }

        foreach ($this->history_cache as $key => $entry) {
            if (fnmatch($pattern, $key)) {
                unset($this->history_cache[$key]);
                $invalidated++;
            }
        }

        if ($invalidated > 0) {
            $this->update_memory_usage();
        }

        return $invalidated;
    }

    /**
     * Warm up cache with common queries
     *
     * @since 1.5.0-rc.1
     * @param array $license_keys Array of license keys to pre-cache
     * @return int Number of entries warmed up
     */
    public function warm_up_cache($license_keys) {
        $warmed_up = 0;

        // This would typically be called by the main validator
        // to pre-populate cache with frequently accessed licenses
        foreach ($license_keys as $license_key) {
            // Cache key would be set by the calling validation method
            $cache_key = $this->generate_cache_key('validation', $license_key);

            if (!isset($this->validation_cache[$cache_key])) {
                // Placeholder for warmup logic
                // In practice, this would trigger the actual validation
                // and cache the result
                $warmed_up++;
            }
        }

        return $warmed_up;
    }

    /**
     * Export cache data for debugging
     *
     * @since 1.5.0-rc.1
     * @param bool $include_data Whether to include actual cached data
     * @return array Cache export data
     */
    public function export_cache_debug_info($include_data = false) {
        $debug_info = array(
            'module_info' => $this->module_info,
            'config' => $this->cache_config,
            'statistics' => $this->get_cache_stats(),
            'ttl_settings' => $this->cache_ttl
        );

        if ($include_data) {
            $debug_info['validation_cache_keys'] = array_keys($this->validation_cache);
            $debug_info['history_cache_keys'] = array_keys($this->history_cache);

            // Include sample entries (sanitized)
            $debug_info['sample_entries'] = array();
            $count = 0;
            foreach ($this->validation_cache as $key => $entry) {
                if ($count >= 3) break;
                $debug_info['sample_entries'][$key] = array(
                    'timestamp' => $entry['timestamp'],
                    'ttl' => $entry['ttl'],
                    'expires_at' => $entry['expires_at'],
                    'access_count' => $entry['access_count'],
                    'data_type' => gettype($entry['data']),
                    'data_size' => strlen(serialize($entry['data']))
                );
                $count++;
            }
        }

        return $debug_info;
    }
}
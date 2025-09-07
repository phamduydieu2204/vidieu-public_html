<?php
/**
 * Vidieu Performance Configuration
 * 
 * Central configuration for all performance optimization features
 * 
 * @package Vidieu_Home_Sections
 * @subpackage Performance
 * @since 2.6.0
 */

// Policy Engine Mode
// Options: 'learning', 'allowlist', 'denylist', 'disabled'
if (!defined('VIDIEU_PERF_POLICY_MODE')) {
    define('VIDIEU_PERF_POLICY_MODE', 'learning');
}

// Enable detailed logging (for debugging)
if (!defined('VIDIEU_PERF_DEBUG')) {
    define('VIDIEU_PERF_DEBUG', false);
}

// Emergency kill switch for all optimizations
if (!defined('VIDIEU_DISABLE_ALL_OPTIMIZATIONS')) {
    define('VIDIEU_DISABLE_ALL_OPTIMIZATIONS', false);
}
<?php
/**
 * Performance Optimization Flags for Vidieu.vn HOME
 * Version: 1.0
 * 
 * These flags control performance optimizations for the HOME page only.
 * Set to false to disable any optimization that causes issues.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Master switch - disable all optimizations if false
define('VIDIEU_PERF_HOME_ENABLE', true);

// Individual feature flags
define('VIDIEU_PERF_HOME_FIX_404', true);           // H1.2 - Fix missing resources
define('VIDIEU_PERF_HOME_DISABLE_RECAPTCHA_ON_HOME', true); // H1.2b - Disable reCAPTCHA on HOME page
define('VIDIEU_PERF_HOME_DEFER_JS', false);         // H2.1 - Defer non-critical JS (disabled until ready)
define('VIDIEU_PERF_HOME_CRITICAL_CSS', false);     // H2.2 - Inline critical CSS (disabled until ready)
define('VIDIEU_PERF_HOME_FONT_OPTIMIZE', false);    // H2.4 - Font optimization (disabled until ready)
define('VIDIEU_PERF_HOME_LAZY_IMAGES', false);      // H3.1 - Lazy load images (disabled until ready)

// Future flags (for reference)
define('VIDIEU_PERF_HOME_WEBP', false);             // Convert images to WebP
define('VIDIEU_PERF_HOME_CACHE', false);            // Page caching
define('VIDIEU_PERF_HOME_COMPRESS', false);         // Text compression
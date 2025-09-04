<?php
/**
 * Plugin Name: Force Vietnamese Fonts
 * Description: Ensures Vietnamese characters display correctly by overriding theme fonts
 * Version: 1.0.0
 * 
 * This is a Must-Use plugin that loads before themes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Elessi theme Google Fonts completely
 */
add_filter('elessi_google_fonts_url', '__return_empty_string', 1);
add_filter('nasa_google_fonts_url', '__return_empty_string', 1);

/**
 * Override any theme font functions
 */
if (!function_exists('elessi_google_fonts_url')) {
    function elessi_google_fonts_url() {
        return '';
    }
}

/**
 * Filter all style URLs to block Google Fonts
 */
add_filter('style_loader_src', function($src, $handle) {
    if (strpos($src, 'fonts.googleapis.com') !== false) {
        // Log blocked font for debugging
        error_log('Blocked Google Font: ' . $handle . ' - ' . $src);
        
        // Return Vietnamese-supported font instead
        if (strpos($src, 'Jost') !== false || strpos($src, 'Open+Sans') !== false) {
            return 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap&subset=latin,latin-ext,vietnamese';
        }
        
        return false;
    }
    return $src;
}, 1, 2);

/**
 * Add critical CSS early in the head
 */
add_action('wp_head', function() {
    ?>
    <style id="vietnamese-fonts-critical">
        /* Critical: Apply Vietnamese fonts immediately */
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@400;600;700&display=swap&subset=latin,latin-ext,vietnamese');
        
        * {
            font-family: "Roboto", "Noto Sans", sans-serif !important;
        }
        
        /* Map Jost to Roboto */
        @font-face {
            font-family: 'Jost';
            src: local('Roboto'), local('Roboto-Regular');
            font-weight: 400;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Jost';
            src: local('Roboto Medium'), local('Roboto-Medium');
            font-weight: 500;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Jost';
            src: local('Roboto Bold'), local('Roboto-Bold');
            font-weight: 700;
            font-display: swap;
        }
    </style>
    <?php
}, 1);

/**
 * Remove actions that might load fonts
 */
add_action('init', function() {
    // Remove any font-related actions from Elessi theme
    remove_action('wp_enqueue_scripts', 'elessi_register_fonts', 10);
    remove_action('wp_enqueue_scripts', 'nasa_register_fonts', 10);
    
    // Remove theme's font loading
    remove_action('wp_head', 'elessi_load_fonts', 5);
    remove_action('wp_head', 'nasa_load_fonts', 5);
}, 1);
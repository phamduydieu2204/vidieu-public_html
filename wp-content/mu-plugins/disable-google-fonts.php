<?php
/**
 * Plugin Name: Disable Google Fonts
 * Description: Prevents loading of Google Fonts and replaces with local fonts
 * Version: 1.0.0
 * Author: Vidieu.vn
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Override the elessi_google_fonts_url function early
 * This runs before the theme loads
 */
if (!function_exists('elessi_google_fonts_url')) {
    function elessi_google_fonts_url($font_families = array(), $font_set = array()) {
        // Always return false to prevent Google Fonts loading
        return false;
    }
}

/**
 * Remove Google Fonts from all registered styles
 */
add_filter('style_loader_src', 'mu_disable_google_fonts', 1, 2);
function mu_disable_google_fonts($src, $handle) {
    // Block any Google Fonts URLs
    if (strpos($src, 'fonts.googleapis.com') !== false || strpos($src, 'fonts.gstatic.com') !== false) {
        // If this is the nasa-fonts handle, replace with local version
        if ($handle === 'nasa-fonts') {
            // Check if child theme has local fonts
            $child_theme_dir = get_stylesheet_directory();
            $local_font_path = $child_theme_dir . '/assets/fonts/jost/jost.css';
            
            if (file_exists($local_font_path)) {
                return get_stylesheet_directory_uri() . '/assets/fonts/jost/jost.css';
            }
        }
        
        // For other Google Fonts, return empty to block them
        return '';
    }
    
    return $src;
}

/**
 * Remove font registration actions
 */
add_action('after_setup_theme', 'mu_remove_font_actions', 1);
function mu_remove_font_actions() {
    remove_action('wp_enqueue_scripts', 'elessi_register_fonts');
}

/**
 * Alternative: Block Google Fonts via DNS prefetch removal
 */
add_action('wp_head', 'mu_remove_google_fonts_dns_prefetch', 1);
function mu_remove_google_fonts_dns_prefetch() {
    remove_action('wp_head', 'wp_resource_hints', 2);
    add_filter('wp_resource_hints', 'mu_remove_google_fonts_resource_hints', 10, 2);
}

function mu_remove_google_fonts_resource_hints($hints, $relation_type) {
    if ('dns-prefetch' === $relation_type || 'preconnect' === $relation_type) {
        $filtered = array();
        foreach ($hints as $hint) {
            if (is_string($hint)) {
                if (strpos($hint, 'fonts.googleapis.com') === false && 
                    strpos($hint, 'fonts.gstatic.com') === false) {
                    $filtered[] = $hint;
                }
            } elseif (is_array($hint) && isset($hint['href'])) {
                if (strpos($hint['href'], 'fonts.googleapis.com') === false && 
                    strpos($hint['href'], 'fonts.gstatic.com') === false) {
                    $filtered[] = $hint;
                }
            }
        }
        return $filtered;
    }
    return $hints;
}
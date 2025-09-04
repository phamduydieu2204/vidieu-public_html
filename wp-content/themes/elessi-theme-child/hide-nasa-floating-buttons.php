<?php
/**
 * Hide NASA Theme Floating Buttons (Back to Top & Recently Viewed)
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove NASA floating buttons actions
 */
add_action('init', 'vidieu_remove_nasa_floating_buttons', 999);

function vidieu_remove_nasa_floating_buttons() {
    // Remove back to top button
    remove_action('nasa_static_group_btns', 'elessi_static_back_to_top_btns', 10);
    
    // Remove recently viewed button (from nasa-core plugin)
    remove_action('nasa_static_group_btns', 'nasa_static_viewed_btns', 15);
    
    // Alternative method - remove the entire static group if needed
    // remove_action('nasa_static_content', 'elessi_static_group_btns', 10);
}

/**
 * Add CSS to hide NASA floating buttons (fallback method)
 */
add_action('wp_head', 'vidieu_hide_nasa_buttons_css', 999);

function vidieu_hide_nasa_buttons_css() {
    ?>
    <style id="hide-nasa-floating-buttons">
        /* Hide NASA theme floating buttons */
        #nasa-back-to-top,
        #nasa-init-viewed,
        .nasa-static-group-btn {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        /* Ensure our floating widget is visible */
        .vd-floating-contact-widget {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>
    <?php
}

/**
 * Remove NASA buttons via JavaScript (additional fallback)
 */
add_action('wp_footer', 'vidieu_remove_nasa_buttons_js', 999);

function vidieu_remove_nasa_buttons_js() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Remove NASA floating buttons
        var nasaButtons = document.querySelectorAll('#nasa-back-to-top, #nasa-init-viewed, .nasa-static-group-btn');
        nasaButtons.forEach(function(button) {
            if (button) {
                button.remove();
            }
        });
        
    });
    </script>
    <?php
}
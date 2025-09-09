<?php
/**
 * Elessi Child Theme Functions - VCB-MH Clean Version
 * This is a cleaned version that works with VCB-MH plugin
 * 
 * INSTRUCTIONS:
 * 1. Rename current functions.php to functions-backup.php
 * 2. Rename this file to functions.php
 * 3. Test checkout with VCB-MH payment gateway
 * 
 * @package Elessi-theme-child
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue parent theme styles
 */
add_action('wp_enqueue_scripts', 'elessi_child_enqueue_styles', 10);
function elessi_child_enqueue_styles() {
    $parent_theme = wp_get_theme('elessi-theme');
    $parent_version = $parent_theme->get('Version');
    
    wp_enqueue_style(
        'elessi-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        $parent_version
    );
    
    wp_enqueue_style(
        'elessi-child-style',
        get_stylesheet_uri(),
        array('elessi-parent-style'),
        wp_get_theme()->get('Version')
    );
}

/**
 * Load Performance Optimizations (KEEP)
 */
require_once get_stylesheet_directory() . '/functions-performance.php';
require_once get_stylesheet_directory() . '/functions-ajax-performance.php';

/**
 * Include Vietnamese Translations (KEEP)
 */
require_once get_stylesheet_directory() . '/translations-vietnamese.php';

/**
 * Enqueue Contact Form 7 Vietnamese Styles (KEEP)
 */
add_action('wp_enqueue_scripts', 'elessi_child_contact_form_styles', 20);
function elessi_child_contact_form_styles() {
    if (function_exists('wpcf7_enqueue_styles')) {
        wp_enqueue_style(
            'vidieu-contact-form',
            get_stylesheet_directory_uri() . '/contact-form-styles.css',
            array(),
            '1.0.0'
        );
    }
}

/**
 * Hide NASA/Elessi Breadcrumb Elements (KEEP)
 */
add_action('wp_enqueue_scripts', 'elessi_child_hide_breadcrumb_assets', 20);
add_action('admin_enqueue_scripts', 'elessi_child_hide_breadcrumb_assets', 20);

function elessi_child_hide_breadcrumb_assets() {
    $child_theme_url = get_stylesheet_directory_uri();
    
    wp_enqueue_style(
        'elessi-hide-breadcrumb',
        $child_theme_url . '/assets/css/hide-breadcrumb.css',
        array(),
        '1.0.0'
    );
    
    wp_enqueue_script(
        'elessi-hide-breadcrumb',
        $child_theme_url . '/assets/js/hide-breadcrumb.js',
        array(),
        '1.0.0',
        true
    );
}

/**
 * CSS inline for critical hiding (KEEP)
 */
add_action('wp_head', 'elessi_child_inline_hide_breadcrumb', 1);
add_action('admin_head', 'elessi_child_inline_hide_breadcrumb', 1);

function elessi_child_inline_hide_breadcrumb() {
    ?>
    <style id="hide-nasa-breadcrumb-critical">
        span.nasa-flex.jc { display: none !important; visibility: hidden !important; }
        #top-bar:empty, .top-bar:empty { display: none !important; }
        #top-bar:not(:has(*)), .top-bar:not(:has(*)) { display: none !important; }
        @media (max-width: 767px) {
            #top-bar:not(:has(.login)):not(:has(.register)):not(:has(.account)) {
                display: none !important;
            }
        }
    </style>
    <?php
}

/**
 * Hide Empty Top Bar (KEEP)
 */
add_action('wp_enqueue_scripts', 'elessi_child_hide_empty_topbar_assets', 20);

function elessi_child_hide_empty_topbar_assets() {
    $child_theme_url = get_stylesheet_directory_uri();
    
    wp_enqueue_style(
        'elessi-hide-empty-topbar',
        $child_theme_url . '/assets/css/hide-empty-topbar.css',
        array(),
        '1.0.0'
    );
    
    wp_enqueue_script(
        'elessi-hide-empty-topbar',
        $child_theme_url . '/assets/js/hide-empty-topbar.js',
        array(),
        '1.0.0',
        true
    );
}

/**
 * Force checkout layout to 'default' (KEEP)
 */
add_action('init', 'elessi_child_force_default_checkout_layout', 1);
function elessi_child_force_default_checkout_layout() {
    if (!defined('NASA_CHECKOUT_LAYOUT')) {
        define('NASA_CHECKOUT_LAYOUT', 'default');
    }
}

/**
 * Filter theme options to ensure checkout_layout is 'default' (KEEP)
 */
add_filter('nasa_theme_options', 'elessi_child_checkout_options', 999);
function elessi_child_checkout_options($options) {
    $options['checkout_layout'] = 'default';
    return $options;
}

/**
 * Include WooCommerce thankyou page fix (KEEP - fixes duplicate content)
 */
require_once get_stylesheet_directory() . '/woocommerce-thankyou-fix.php';

/**
 * Include WooCommerce admin order display enhancements (KEEP)
 */
require_once get_stylesheet_directory() . '/woocommerce-admin-order-display.php';

/**
 * Include Floating Contact Widget (KEEP)
 */
require_once get_stylesheet_directory() . '/floating-contact-widget.php';

/**
 * Hide NASA Theme Floating Buttons (KEEP)
 */
require_once get_stylesheet_directory() . '/hide-nasa-floating-buttons.php';

/**
 * Mobile Bottom Bar Customization (KEEP)
 */
require_once get_stylesheet_directory() . '/mobile-bottom-bar-customization.php';

/**
 * Load Cart Count Display Fix (KEEP)
 */
require_once get_stylesheet_directory() . '/fix-cart-count-display.php';

/**
 * Load Cart Page Boxed Layout (KEEP)
 */
require_once get_stylesheet_directory() . '/cart-page-boxed-layout.php';

/**
 * Load Login State Consistency Fix (KEEP)
 */
require_once get_stylesheet_directory() . '/fix-login-state-consistency.php';

/**
 * === REMOVED/COMMENTED FILES FOR VCB-MH COMPATIBILITY ===
 * 
 * The following files have been removed to allow VCB-MH to work properly:
 * - woocommerce-checkout-simple-fix.php (interferes with checkout fields)
 * - woocommerce-ajax-checkout-bypass.php (hardcodes BACS payment)
 * - woocommerce-vietqr-integration.php (conflicts with VCB-MH)
 * - Block checkout hide fields scripts (may interfere with VCB-MH)
 */

// REMOVED: require_once get_stylesheet_directory() . '/woocommerce-checkout-simple-fix.php';
// REMOVED: require_once get_stylesheet_directory() . '/woocommerce-ajax-checkout-bypass.php';
// REMOVED: require_once get_stylesheet_directory() . '/woocommerce-vietqr-integration.php';

/**
 * Optional: Simple checkout for VCB-MH only
 * Uncomment if you want to simplify checkout ONLY when VCB-MH is selected
 */
/*
add_filter('woocommerce_checkout_fields', 'elessi_child_vcb_simple_checkout', 25);
function elessi_child_vcb_simple_checkout($fields) {
    // Only simplify if VCB-MH is the chosen payment method
    if (WC()->session && WC()->session->get('chosen_payment_method') === 'vcb-gateway-mh') {
        // Remove unnecessary fields
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        
        // Set defaults for hidden fields
        add_action('woocommerce_checkout_process', function() {
            if (empty($_POST['billing_country'])) $_POST['billing_country'] = 'VN';
            if (empty($_POST['billing_address_1'])) $_POST['billing_address_1'] = 'N/A';
            if (empty($_POST['billing_city'])) $_POST['billing_city'] = 'Ho Chi Minh';
            if (empty($_POST['billing_state'])) $_POST['billing_state'] = 'VN';
            if (empty($_POST['billing_postcode'])) $_POST['billing_postcode'] = '700000';
        }, 5);
    }
    return $fields;
}
*/

// Debug output removed for production
<?php
/**
 * Elessi Child Theme Functions
 * 
 * @package Elessi-theme-child
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue parent theme styles
 * This ensures the child theme inherits parent theme styles
 */
add_action('wp_enqueue_scripts', 'elessi_child_enqueue_styles', 10);
function elessi_child_enqueue_styles() {
    // Get parent theme version for cache busting
    $parent_theme = wp_get_theme('elessi-theme');
    $parent_version = $parent_theme->get('Version');
    
    // Enqueue parent theme style
    wp_enqueue_style(
        'elessi-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        $parent_version
    );
    
    // Enqueue child theme style
    wp_enqueue_style(
        'elessi-child-style',
        get_stylesheet_uri(),
        array('elessi-parent-style'),
        wp_get_theme()->get('Version')
    );
}

/**
 * Load Performance Optimizations
 * Comment out these lines to disable performance optimizations
 */
require_once get_stylesheet_directory() . '/functions-performance.php';
require_once get_stylesheet_directory() . '/functions-ajax-performance.php';

/**
 * Add your custom functions below this line
 * ----------------------------------------
 */

/**
 * Include Vietnamese Translations
 * Tập trung tất cả translations tiếng Việt vào một file riêng
 * @since 2025-09-04
 */
require_once get_stylesheet_directory() . '/translations-vietnamese.php';

/**
 * Enqueue Contact Form 7 Vietnamese Styles
 * @since 2025-09-04
 */
add_action('wp_enqueue_scripts', 'elessi_child_contact_form_styles', 20);
function elessi_child_contact_form_styles() {
    // Only load on pages with Contact Form 7
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
 * Hide NASA/Elessi Breadcrumb Elements
 * Removes "Dashboard > Static Blocks > Topbar" breadcrumb
 */
add_action('wp_enqueue_scripts', 'elessi_child_hide_breadcrumb_assets', 20);
add_action('admin_enqueue_scripts', 'elessi_child_hide_breadcrumb_assets', 20);

function elessi_child_hide_breadcrumb_assets() {
    // Get child theme URL
    $child_theme_url = get_stylesheet_directory_uri();
    
    // Enqueue CSS for immediate hiding
    wp_enqueue_style(
        'elessi-hide-breadcrumb',
        $child_theme_url . '/assets/css/hide-breadcrumb.css',
        array(),
        '1.0.0'
    );
    
    // Enqueue JS for complete removal
    wp_enqueue_script(
        'elessi-hide-breadcrumb',
        $child_theme_url . '/assets/js/hide-breadcrumb.js',
        array(),
        '1.0.0',
        true // Load in footer
    );
}

/**
 * Alternative PHP method to remove breadcrumb
 * This runs on both frontend and admin
 */
add_action('init', 'elessi_child_remove_breadcrumb_actions', 999);
function elessi_child_remove_breadcrumb_actions() {
    // Remove NASA theme breadcrumb actions if they exist
    remove_action('nasa_breadcrumb', 'elessi_breadcrumb', 20);
    remove_action('nasa_breadcrumb_node', 'elessi_breadcrumb_node', 20);
    
    // Add filter to modify breadcrumb output
    add_filter('nasa_breadcrumb_output', '__return_empty_string', 999);
    add_filter('elessi_breadcrumb_output', '__return_empty_string', 999);
}

/**
 * CSS inline for critical hiding (loads before external CSS)
 */
add_action('wp_head', 'elessi_child_inline_hide_breadcrumb', 1);
add_action('admin_head', 'elessi_child_inline_hide_breadcrumb', 1);

function elessi_child_inline_hide_breadcrumb() {
    ?>
    <style id="hide-nasa-breadcrumb-critical">
        /* Critical CSS to hide breadcrumb immediately */
        span.nasa-flex.jc { display: none !important; visibility: hidden !important; }
        
        /* Hide empty top bar immediately */
        #top-bar:empty, .top-bar:empty { display: none !important; }
        #top-bar:not(:has(*)), .top-bar:not(:has(*)) { display: none !important; }
        
        /* Mobile: Hide if no login visible */
        @media (max-width: 767px) {
            #top-bar:not(:has(.login)):not(:has(.register)):not(:has(.account)) {
                display: none !important;
            }
        }
    </style>
    <?php
}

/**
 * Hide Empty Top Bar
 * Removes top bar if it doesn't contain login/register content
 */
add_action('wp_enqueue_scripts', 'elessi_child_hide_empty_topbar_assets', 20);

function elessi_child_hide_empty_topbar_assets() {
    // Get child theme URL
    $child_theme_url = get_stylesheet_directory_uri();
    
    // Enqueue CSS
    wp_enqueue_style(
        'elessi-hide-empty-topbar',
        $child_theme_url . '/assets/css/hide-empty-topbar.css',
        array(),
        '1.0.0'
    );
    
    // Enqueue JS
    wp_enqueue_script(
        'elessi-hide-empty-topbar',
        $child_theme_url . '/assets/js/hide-empty-topbar.js',
        array(),
        '1.0.0',
        true
    );
}

/**
 * Add body class if mobile menu has account links
 */
add_filter('body_class', 'elessi_child_topbar_body_classes');

function elessi_child_topbar_body_classes($classes) {
    // Check if we're on mobile
    if (wp_is_mobile()) {
        // Check if mobile menu has account functionality
        if (has_nav_menu('mobile-menu') || has_nav_menu('primary')) {
            $locations = get_nav_menu_locations();
            $menu_id = isset($locations['mobile-menu']) ? $locations['mobile-menu'] : 
                      (isset($locations['primary']) ? $locations['primary'] : 0);
            
            if ($menu_id) {
                $menu_items = wp_get_nav_menu_items($menu_id);
                foreach ($menu_items as $item) {
                    if (stripos($item->title, 'login') !== false || 
                        stripos($item->title, 'register') !== false ||
                        stripos($item->title, 'account') !== false ||
                        stripos($item->url, 'account') !== false) {
                        $classes[] = 'mobile-menu-has-account';
                        break;
                    }
                }
            }
        }
    }
    
    return $classes;
}

/**
 * PHP method to conditionally hide top bar
 */
add_action('wp', 'elessi_child_maybe_hide_topbar');

function elessi_child_maybe_hide_topbar() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        // Check if registration is enabled
        $users_can_register = get_option('users_can_register');
        
        // If registration is disabled and user not logged in, hide top bar
        if (!$users_can_register) {
            add_filter('elessi_topbar_enable', '__return_false');
            add_filter('nasa_topbar_enable', '__return_false');
        }
    }
}

// Example: Custom logo size
// add_image_size('custom-logo', 300, 100, true);

// Example: Add custom post type support
// add_theme_support('post-formats', array('aside', 'gallery', 'quote'));

// Example: Register custom widget area
/*
add_action('widgets_init', 'elessi_child_widgets_init');
function elessi_child_widgets_init() {
    register_sidebar(array(
        'name'          => __('Child Theme Sidebar', 'elessi-child'),
        'id'            => 'child-sidebar',
        'description'   => __('Add widgets here.', 'elessi-child'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
*/

// Example: Modify WooCommerce settings
/*
add_filter('woocommerce_product_thumbnails_columns', function() {
    return 4; // 4 product columns
});
*/

// Example: Custom excerpt length
/*
add_filter('excerpt_length', function($length) {
    return 20; // 20 words
}, 999);
*/

/**
 * Force checkout layout to 'default' to ensure header/footer display
 * This prevents the theme from using 'modern' layout which has minimal header/footer
 */
add_action('init', 'elessi_child_force_default_checkout_layout', 1);
function elessi_child_force_default_checkout_layout() {
    if (!defined('NASA_CHECKOUT_LAYOUT')) {
        define('NASA_CHECKOUT_LAYOUT', 'default');
    }
}

/**
 * Filter theme options to ensure checkout_layout is 'default'
 */
add_filter('nasa_theme_options', 'elessi_child_checkout_options', 999);
function elessi_child_checkout_options($options) {
    $options['checkout_layout'] = 'default';
    return $options;
}

/**
 * Fix checkout display to use WooCommerce Checkout Block properly
 */
add_action('wp', 'elessi_child_checkout_modifications', 20);
function elessi_child_checkout_modifications() {
    if (is_checkout() && !is_wc_endpoint_url('order-received')) {
        // Add CSS for WooCommerce Checkout Block styling
        add_action('wp_head', 'elessi_child_checkout_block_styles', 99);
    }
}

/**
 * CSS for WooCommerce Checkout Block
 */
function elessi_child_checkout_block_styles() {
    ?>
    <style>
        /* Hide duplicate classic checkout form if exists */
        .page-checkout form.woocommerce-checkout:not(.wc-block-checkout__form) {
            display: none !important;
        }
        
        /* Ensure checkout block displays correctly */
        .page-checkout .wp-block-woocommerce-checkout {
            display: block !important;
        }
        
        /* Style adjustments for checkout block */
        .page-checkout .wc-block-components-sidebar-layout.wc-block-checkout {
            padding: 20px !important;
            margin: 0 !important;
        }
    </style>
    <?php
}

/**
 * Include WooCommerce thankyou page fix
 * Fixes duplicate content on order-received page
 * @since 2025-08-30
 */
require_once get_stylesheet_directory() . '/woocommerce-thankyou-fix.php';

/**
 * Include WooCommerce checkout simple fix
 * @since 2025-08-30
 */
require_once get_stylesheet_directory() . '/woocommerce-checkout-simple-fix.php';

/**
 * Include WooCommerce AJAX checkout bypass
 * @since 2025-08-30
 */
require_once get_stylesheet_directory() . '/woocommerce-ajax-checkout-bypass.php';

/**
 * Include WooCommerce admin order display enhancements
 * @since 2025-08-30
 */
require_once get_stylesheet_directory() . '/woocommerce-admin-order-display.php';

/**
 * Include VietQR integration for BACS payment
 * @since 2025-08-30
 */
require_once get_stylesheet_directory() . '/woocommerce-vietqr-integration.php';

/**
 * Include Floating Contact Widget
 * @since 2025-08-30
 */
require_once get_stylesheet_directory() . '/floating-contact-widget.php';

/**
 * Hide NASA Theme Floating Buttons
 * @since 2025-08-30
 */
require_once get_stylesheet_directory() . '/hide-nasa-floating-buttons.php';

/**
 * Mobile Bottom Bar Customization
 * @since 2025-08-30
 */
require_once get_stylesheet_directory() . '/mobile-bottom-bar-customization.php';

/**
 * Load Cart Count Display Fix
 * Ensures cart count is always visible when there are items in cart
 * @since 2025-08-31
 */
require_once get_stylesheet_directory() . '/fix-cart-count-display.php';

/**
 * Load Cart Page Boxed Layout
 * Makes cart page use boxed layout like homepage
 * @since 2025-08-31
 */
require_once get_stylesheet_directory() . '/cart-page-boxed-layout.php';

/**
 * Load Login State Consistency Fix
 * Ensures login state is consistent between popup and my-account page
 * @since 2025-08-31
 */
require_once get_stylesheet_directory() . '/fix-login-state-consistency.php';

/**
 * Enqueue scripts for WooCommerce Block Checkout field hiding
 * @since 2025-08-30
 */
add_action('wp_enqueue_scripts', 'elessi_child_block_checkout_hide_scripts', 100);
function elessi_child_block_checkout_hide_scripts() {
    // Only load on checkout page
    if (!is_checkout()) {
        return;
    }
    
    // Enqueue the hide fields script
    wp_enqueue_script(
        'elessi-block-checkout-hide-fields',
        get_stylesheet_directory_uri() . '/woocommerce-block-checkout-hide-fields.js',
        array(),
        '1.0.0',
        true
    );
    
    // Enqueue validation fix script with higher priority
    wp_enqueue_script(
        'elessi-block-checkout-validation-fix',
        get_stylesheet_directory_uri() . '/woocommerce-block-checkout-validation-fix.js',
        array('wc-blocks-checkout'),
        '1.0.0',
        true
    );
    
    // Enqueue force submit script
    wp_enqueue_script(
        'elessi-block-checkout-force-submit',
        get_stylesheet_directory_uri() . '/woocommerce-block-checkout-force-submit.js',
        array('wc-blocks-checkout'),
        '1.0.0',
        true
    );
    
    // Enqueue the hide fields CSS
    wp_enqueue_style(
        'elessi-block-checkout-hide-fields',
        get_stylesheet_directory_uri() . '/woocommerce-block-checkout-hide-fields.css',
        array(),
        '1.0.0'
    );
}


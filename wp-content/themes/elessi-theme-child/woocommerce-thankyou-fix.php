<?php
/**
 * Fix duplicate content on WooCommerce order-received page
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove default WooCommerce order details table from thankyou hook
 * to prevent duplicate content since Elessi theme already displays it
 */
add_action('init', function() {
    // Remove the default WooCommerce order details table from thankyou page
    // This prevents duplicate content as Elessi theme already includes order details
    remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
});

/**
 * Prevent duplicate rendering of checkout/thankyou content
 * Track if thankyou template has been rendered to prevent duplicates
 */
add_filter('wc_get_template', function($template, $template_name, $args, $template_path, $default_path) {
    static $thankyou_rendered = false;
    
    // Check if this is the thankyou template
    if ($template_name === 'checkout/thankyou.php') {
        // If already rendered, return empty template
        if ($thankyou_rendered) {
            // Return a dummy file that exists but won't output duplicate content
            return __DIR__ . '/empty-template.php';
        }
        // Mark as rendered
        $thankyou_rendered = true;
    }
    
    return $template;
}, 10, 5);

/**
 * Alternative fix: Override the checkout page template behavior
 * to prevent duplicate content rendering
 */
add_action('template_redirect', function() {
    // Only on order-received endpoint
    if (is_wc_endpoint_url('order-received')) {
        // Remove potential duplicate content from the_content
        add_filter('the_content', function($content) {
            // Check if we're in the main query and on checkout page
            if (is_page() && in_the_loop() && is_main_query()) {
                $checkout_page_id = wc_get_page_id('checkout');
                if (get_the_ID() == $checkout_page_id) {
                    // Remove any woocommerce shortcodes from content on order-received
                    $content = str_replace('[woocommerce_checkout]', '', $content);
                    $content = preg_replace('/\[woocommerce_checkout[^\]]*\]/', '', $content);
                    // Remove WooCommerce blocks
                    $content = preg_replace('/<!-- wp:woocommerce\/checkout.*?\/wp:woocommerce\/checkout -->/s', '', $content);
                    $content = preg_replace('/<!-- wp:woocommerce\/classic-shortcode.*?"shortcode":"checkout".*?-->/s', '', $content);
                }
            }
            return $content;
        }, 99);
    }
}, 20);
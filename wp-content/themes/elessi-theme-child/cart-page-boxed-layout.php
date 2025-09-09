<?php
/**
 * Cart Page Boxed Layout
 * Makes cart page use boxed layout like homepage instead of full width
 * 
 * @package Elessi-theme-child
 * @since 2025-08-31
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add CSS for boxed cart layout
 */
add_action('wp_head', 'vidieu_cart_page_boxed_layout_css');
function vidieu_cart_page_boxed_layout_css() {
    // Only on cart page
    if (is_cart()) {
        ?>
        <style id="vidieu-cart-boxed-layout">
            /* Container width matching homepage */
            .woocommerce-cart .site-main,
            .woocommerce-cart .content-area {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 15px;
            }
            
            /* Remove alignwide class effect on cart block */
            .woocommerce-cart .wp-block-woocommerce-cart.alignwide {
                max-width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            
            /* Ensure proper padding for cart content */
            .woocommerce-cart .wp-block-woocommerce-cart {
                padding: 30px 0;
            }
            
            /* Match NASA theme container widths */
            @media (min-width: 1200px) {
                .woocommerce-cart .site-main,
                .woocommerce-cart .content-area {
                    max-width: 1200px;
                }
            }
            
            @media (min-width: 992px) and (max-width: 1199px) {
                .woocommerce-cart .site-main,
                .woocommerce-cart .content-area {
                    max-width: 970px;
                }
            }
            
            @media (min-width: 768px) and (max-width: 991px) {
                .woocommerce-cart .site-main,
                .woocommerce-cart .content-area {
                    max-width: 750px;
                }
            }
            
            /* Ensure sidebar layout doesn't break */
            .woocommerce-cart .wc-block-components-sidebar-layout {
                gap: 30px;
            }
            
            /* Cart main content */
            .woocommerce-cart .wc-block-cart__main {
                flex: 1;
            }
            
            /* Cart sidebar */
            .woocommerce-cart .wc-block-cart__sidebar {
                flex-basis: 400px;
                max-width: 400px;
            }
            
            /* Mobile responsive */
            @media (max-width: 767px) {
                .woocommerce-cart .wc-block-cart__sidebar {
                    flex-basis: 100%;
                    max-width: 100%;
                }
                
                .woocommerce-cart .wc-block-components-sidebar-layout {
                    flex-direction: column;
                }
                
                .woocommerce-cart .site-main,
                .woocommerce-cart .content-area {
                    padding: 0 15px;
                }
            }
            
            /* Additional NASA theme compatibility */
            .woocommerce-cart.nasa-boxed-layout .site-main,
            .woocommerce-cart .nasa-container,
            .woocommerce-cart .container-wrap {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            /* Fix any full-width elements inside cart */
            .woocommerce-cart .alignfull {
                max-width: 100vw;
                margin-left: calc(50% - 50vw);
                margin-right: calc(50% - 50vw);
            }
            
            /* But keep cart content boxed */
            .woocommerce-cart .alignfull .wp-block-woocommerce-cart {
                max-width: 1200px;
                margin-left: auto;
                margin-right: auto;
                padding-left: 15px;
                padding-right: 15px;
            }
            
            /* Style for Proceed to Checkout button */
            .woocommerce-cart .wc-block-cart__submit-button {
                background-color: #F76B6A !important;
                color: #FFFFFF !important;
                font-size: 16px !important;
                font-weight: 600;
                padding: 12px 30px;
                border: none;
                border-radius: 5px;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s ease;
                text-align: center;
                line-height: 1.5;
                cursor: pointer;
            }
            
            /* Button text color */
            .woocommerce-cart .wc-block-cart__submit-button .wc-block-components-button__text {
                color: #FFFFFF !important;
                font-size: 16px !important;
            }
            
            /* Hover state */
            .woocommerce-cart .wc-block-cart__submit-button:hover {
                background-color: #e55b5a !important;
                color: #FFFFFF !important;
                opacity: 0.9;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(247, 107, 106, 0.3);
            }
            
            /* Focus state for accessibility */
            .woocommerce-cart .wc-block-cart__submit-button:focus {
                outline: 2px solid #F76B6A;
                outline-offset: 2px;
            }
            
            /* Active/clicked state */
            .woocommerce-cart .wc-block-cart__submit-button:active {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(247, 107, 106, 0.3);
            }
            
            /* Ensure link doesn't have underline */
            .woocommerce-cart a.wc-block-cart__submit-button {
                text-decoration: none !important;
            }
            
            /* Make button full width on mobile */
            @media (max-width: 767px) {
                .woocommerce-cart .wc-block-cart__submit-button {
                    width: 100%;
                    display: block;
                }
            }
        </style>
        <?php
    }
}

/**
 * Add body class for cart page
 */
add_filter('body_class', 'vidieu_cart_page_body_class');
function vidieu_cart_page_body_class($classes) {
    if (is_cart()) {
        $classes[] = 'vidieu-cart-boxed';
        
        // Add NASA boxed layout class if theme uses it
        if (!in_array('nasa-boxed-layout', $classes)) {
            $classes[] = 'nasa-boxed-layout';
        }
    }
    return $classes;
}

/**
 * Force container wrapper on cart content
 */
add_action('woocommerce_before_cart', 'vidieu_cart_container_start', 5);
function vidieu_cart_container_start() {
    echo '<div class="vidieu-cart-container nasa-container">';
}

add_action('woocommerce_after_cart', 'vidieu_cart_container_end', 95);
function vidieu_cart_container_end() {
    echo '</div>';
}
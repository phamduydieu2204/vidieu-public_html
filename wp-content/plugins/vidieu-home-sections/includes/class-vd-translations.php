<?php
/**
 * Translation customizations class
 *
 * @package VidieuHomeSections
 * @since 1.1.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Translations class
 */
class VD_Translations {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Hook into WooCommerce filters
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'custom_add_to_cart_text'), 999, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'custom_single_add_to_cart_text'), 999, 2);
        add_filter('gettext', array($this, 'custom_gettext'), 999, 3);
        
        // Add custom JavaScript and CSS
        add_action('wp_footer', array($this, 'add_translations_script'), 999);
        add_action('wp_head', array($this, 'add_translations_css'), 999);
    }
    
    /**
     * Custom add to cart text for loop
     */
    public function custom_add_to_cart_text($text, $product) {
        if (!$product) {
            return $text;
        }
        
        // For variable products
        if ($product->is_type('variable')) {
            if ($text === 'Select options' || $text === 'Lựa chọn các tùy chọn') {
                return 'Tùy chọn';
            }
        }
        
        // For simple/other products
        if ($text === 'Add to cart') {
            return 'Thêm vào giỏ hàng';
        }
        
        return $text;
    }
    
    /**
     * Custom single add to cart text
     */
    public function custom_single_add_to_cart_text($text, $product) {
        if ($text === 'Add to cart') {
            return 'Thêm vào giỏ hàng';
        }
        
        return $text;
    }
    
    /**
     * General text translations
     */
    public function custom_gettext($translated, $text, $domain) {
        // Handle both WooCommerce and NASA theme translations
        if ($domain === 'woocommerce' || $domain === 'elessi-theme') {
            switch ($text) {
                case 'Select options':
                    $translated = 'Tùy chọn';
                    break;
                case 'Add to cart':
                    $translated = 'Thêm vào giỏ hàng';
                    break;
                case 'Read more':
                    $translated = 'Xem thêm';
                    break;
            }
        }
        
        return $translated;
    }
    
    /**
     * Add CSS for button text translation
     */
    public function add_translations_css() {
        ?>
        <style type="text/css">
        /* Simple products */
        .add_to_cart_button.product_type_simple .add_to_cart_text { 
            font-size: 0 !important; 
        }
        .add_to_cart_button.product_type_simple .add_to_cart_text:after { 
            content: "Thêm vào giỏ hàng"; 
            font-size: 12px !important; 
        }
        
        /* Variable products - no variation selected */
        .add_to_cart_button.product_type_variable:not(.nasa-active) .add_to_cart_text { 
            font-size: 0 !important; 
        }
        .add_to_cart_button.product_type_variable:not(.nasa-active) .add_to_cart_text:after { 
            content: "Tùy chọn"; 
            font-size: 12px !important; 
        }
        
        /* Variable products - variation selected */
        .add_to_cart_button.product_type_variable.nasa-active .add_to_cart_text,
        .add_to_cart_button.product_type_variation .add_to_cart_text { 
            font-size: 0 !important; 
        }
        .add_to_cart_button.product_type_variable.nasa-active .add_to_cart_text:after,
        .add_to_cart_button.product_type_variation .add_to_cart_text:after { 
            content: "Thêm vào giỏ hàng"; 
            font-size: 12px !important; 
        }
        </style>
        <?php
    }
    
    /**
     * Add JavaScript to handle dynamic translations
     */
    public function add_translations_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function translateElements() {
                // Translate button text based on product type
                $('.add_to_cart_button').each(function() {
                    var $button = $(this);
                    var $text = $button.find('.add_to_cart_text');
                    if (!$text.length) return;
                    
                    var text = $text.text().trim();
                    var upperText = text.toUpperCase();
                    
                    // Variable products
                    if ($button.hasClass('product_type_variable')) {
                        if ($button.hasClass('nasa-active')) {
                            // Variation selected
                            if (upperText === 'ADD TO CART' || text === 'Select options') {
                                $text.text('Thêm vào giỏ hàng');
                            }
                        } else {
                            // No variation selected
                            if (upperText === 'ADD TO CART' || text === 'Select options') {
                                $text.text('Tùy chọn');
                            }
                        }
                    } 
                    // Simple products
                    else if ($button.hasClass('product_type_simple')) {
                        if (upperText === 'ADD TO CART') {
                            $text.text('Thêm vào giỏ hàng');
                        }
                    }
                    // Variation type (already selected)
                    else if ($button.hasClass('product_type_variation')) {
                        if (upperText === 'ADD TO CART' || text === 'Select options') {
                            $text.text('Thêm vào giỏ hàng');
                        }
                    }
                });
                
                // Translate tooltips
                $('[data-tip]').each(function() {
                    var tip = $(this).attr('data-tip');
                    if (tip === 'Add to cart' || tip === 'ADD TO CART') {
                        $(this).attr('data-tip', 'Thêm vào giỏ hàng');
                    } else if (tip === 'Select options' || tip === 'Lựa chọn các tùy chọn') {
                        $(this).attr('data-tip', 'Tùy chọn');
                    }
                });
                
                // Translate aria-labels  
                $('[aria-label*="Add to cart"]').each(function() {
                    var label = $(this).attr('aria-label');
                    if (label) {
                        $(this).attr('aria-label', label.replace(/Add to cart/gi, 'Thêm vào giỏ hàng'));
                    }
                });
            }
            
            // Initial translation
            translateElements();
            
            // Delayed translation for late-loading elements
            setTimeout(translateElements, 300);
            
            // Re-translate after AJAX operations
            $(document).on('nasa_after_loaded_ajax_complete nasa_after_load_ajax added_to_cart wc_fragments_refreshed nasa_changed_variation', function() {
                setTimeout(translateElements, 100);
            });
            
            // Re-translate when variation items are clicked
            $(document).on('click', '.nasa-attr-ux-item', function() {
                setTimeout(translateElements, 100);
            });
        });
        </script>
        <?php
    }
}
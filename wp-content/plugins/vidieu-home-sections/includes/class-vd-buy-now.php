<?php
/**
 * Buy Now functionality class
 *
 * @package VidieuHomeSections
 * @since 1.1.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Buy_Now class
 */
class VD_Buy_Now {
    
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
        // Only initialize if enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Add Buy Now button after add to cart button
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_buy_now_button'), 15);
        
        // Handle Buy Now AJAX
        add_action('wp_ajax_vidieu_buy_now', array($this, 'handle_buy_now_ajax'));
        add_action('wp_ajax_nopriv_vidieu_buy_now', array($this, 'handle_buy_now_ajax'));
        
        // Add filter to check if we're in vidieu context
        add_filter('vidieu_is_rendering_products', '__return_false');
    }
    
    /**
     * Check if Buy Now is enabled
     */
    private function is_enabled() {
        return VD_Admin::get_option('enable_buy_now', false);
    }
    
    /**
     * Add Buy Now button to product cards
     */
    public function add_buy_now_button() {
        global $product;
        
        // Only add button when rendering vidieu products
        if (!apply_filters('vidieu_is_rendering_products', false)) {
            return;
        }
        
        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            return;
        }
        
        $product_id = $product->get_id();
        $product_type = $product->get_type();
        
        // Get button labels from settings
        $simple_label = VD_Admin::get_option('buy_now_label', __('Buy Now', VD_HOME_TEXT_DOMAIN));
        $variable_label = VD_Admin::get_option('buy_now_variable_label', __('Select Options', VD_HOME_TEXT_DOMAIN));
        
        // Determine button label and action
        if ($product_type === 'simple') {
            $button_label = $simple_label;
            $button_class = 'vd-buy-now-button vd-buy-now-simple';
            $button_action = 'buy-now';
        } else {
            $button_label = $variable_label;
            $button_class = 'vd-buy-now-button vd-buy-now-variable';
            $button_action = 'select-options';
        }
        
        // Output button HTML
        ?>
        <a href="#" 
           class="button <?php echo esc_attr($button_class); ?>" 
           data-product-id="<?php echo esc_attr($product_id); ?>"
           data-product-type="<?php echo esc_attr($product_type); ?>"
           data-action="<?php echo esc_attr($button_action); ?>"
           data-buy-now-label="<?php echo esc_attr($simple_label); ?>"
           data-select-label="<?php echo esc_attr($variable_label); ?>">
            <?php echo esc_html($button_label); ?>
        </a>
        <?php
    }
    
    /**
     * Handle Buy Now AJAX request
     */
    public function handle_buy_now_ajax() {
        // Security check
        if (!check_ajax_referer('vd_home_nonce', 'nonce', false)) {
            $this->send_json_error(
                'SECURITY_FAILED',
                __('Security check failed. Please refresh the page and try again.', VD_HOME_TEXT_DOMAIN)
            );
        }
        
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        
        // Get variation attributes from POST data
        $variation = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'attribute_') === 0) {
                $variation[$key] = sanitize_text_field(wp_unslash($value));
            }
        }
        
        if (!$product_id) {
            $this->send_json_error(
                'INVALID_PRODUCT',
                __('Invalid product.', VD_HOME_TEXT_DOMAIN)
            );
        }
        
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            $this->send_json_error(
                'PRODUCT_UNAVAILABLE',
                __('This product is currently unavailable.', VD_HOME_TEXT_DOMAIN)
            );
        }
        
        // Handle variable products
        if ($product->is_type('variable')) {
            // Check if variation is selected
            if (!$variation_id && empty($variation) && $action === 'select-options') {
                wp_send_json_success(array(
                    'action' => 'open_quickview',
                    'product_id' => $product_id,
                    'message' => __('Please select product options.', VD_HOME_TEXT_DOMAIN)
                ));
                return;
            }
            
            // If variation attributes are provided but no variation_id, find it
            if (!$variation_id && !empty($variation)) {
                // Try to find matching variation
                $data_store = WC_Data_Store::load('product');
                $variation_id = $data_store->find_matching_product_variation($product, $variation);
                
                // If not found, try with normalized attributes
                if (!$variation_id) {
                    // Get all variations
                    $variations = $product->get_available_variations();
                    
                    foreach ($variations as $variation_data) {
                        $match = true;
                        $variation_attributes = $variation_data['attributes'];
                        
                        // Check if all provided attributes match
                        foreach ($variation as $attr_key => $attr_value) {
                            $normalized_key = str_replace('attribute_', '', $attr_key);
                            $normalized_key = str_replace('pa_', '', $normalized_key);
                            
                            // Try to find matching attribute in various formats
                            $found = false;
                            $attr_value_lower = strtolower($attr_value);
                            
                            // Check exact key match
                            if (isset($variation_attributes[$attr_key])) {
                                if (strtolower($variation_attributes[$attr_key]) === $attr_value_lower || 
                                    $variation_attributes[$attr_key] === '' || 
                                    $variation_attributes[$attr_key] === 'any') {
                                    $found = true;
                                }
                            }
                            
                            // Check various attribute key formats
                            $possible_keys = array(
                                'attribute_pa_' . $normalized_key,
                                'attribute_' . $normalized_key,
                                'pa_' . $normalized_key,
                                $normalized_key
                            );
                            
                            foreach ($possible_keys as $key) {
                                if (isset($variation_attributes[$key])) {
                                    if (strtolower($variation_attributes[$key]) === $attr_value_lower || 
                                        $variation_attributes[$key] === '' || 
                                        $variation_attributes[$key] === 'any') {
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            
                            if (!$found) {
                                $match = false;
                                break;
                            }
                        }
                        
                        if ($match) {
                            $variation_id = $variation_data['variation_id'];
                            break;
                        }
                    }
                }
                
                if (!$variation_id) {
                    // Last attempt - try using WooCommerce's variation matching with slugified values
                    $slugified_variation = array();
                    foreach ($variation as $key => $value) {
                        $slugified_variation[$key] = sanitize_title($value);
                    }
                    
                    $variation_id = $data_store->find_matching_product_variation($product, $slugified_variation);
                }
                
                if (!$variation_id) {
                    $this->send_json_error(
                        'VARIATION_NOT_FOUND',
                        __('Selected variation not available. Please try selecting options again.', VD_HOME_TEXT_DOMAIN)
                    );
                }
            }
            
            // If variation_id is found or provided, use it
            if ($variation_id) {
                $product_id = $variation_id;
                $product = wc_get_product($variation_id);
                
                if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
                    $this->send_json_error(
                        'VARIATION_UNAVAILABLE',
                        __('This variation is currently unavailable.', VD_HOME_TEXT_DOMAIN)
                    );
                }
            }
        }
        
        // Handle simple products - add to cart
        try {
            // Clear cart if going directly to checkout
            $destination = VD_Admin::get_option('buy_now_destination', 'checkout');
            // Commented out to preserve existing cart items when using Buy Now
            // if ($destination === 'checkout') {
            //     WC()->cart->empty_cart();
            // }
            
            // Add product to cart
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
            
            if (!$cart_item_key) {
                throw new Exception(__('Failed to add product to cart.', VD_HOME_TEXT_DOMAIN));
            }
            
            // Get redirect URL
            if ($destination === 'checkout') {
                $redirect_url = wc_get_checkout_url();
            } else {
                $redirect_url = wc_get_cart_url();
            }
            
            // Success response
            wp_send_json_success(array(
                'action' => 'redirect',
                'redirect_url' => $redirect_url,
                'message' => __('Product added to cart. Redirecting...', VD_HOME_TEXT_DOMAIN)
            ));
            
        } catch (Exception $e) {
            $this->send_json_error(
                'ADD_TO_CART_FAILED',
                $e->getMessage()
            );
        }
    }
    
    /**
     * Send standardized JSON error response
     */
    private function send_json_error($code, $message, $details = array()) {
        $response = array(
            'code' => $code,
            'message' => $message,
            'timestamp' => current_time('timestamp')
        );
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        wp_send_json_error($response);
    }
}
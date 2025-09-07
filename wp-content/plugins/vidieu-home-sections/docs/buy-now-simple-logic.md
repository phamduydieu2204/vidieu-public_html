# Buy Now Button Logic for Simple Products

**Version:** 1.1.0  
**Last Updated:** 2025-09-07  
**Context:** WooCommerce Buy Now functionality for simple products in Vidieu Home Sections  
**Security:** Single global nonce via wp_localize_script (Removed data-nonce from DOM to avoid inconsistency)

## 1. DOM & Data Attributes

### Button HTML Structure
```html
<a href="#" class="button vd-buy-now-button vd-buy-now-simple"
   data-product-id="7337"
   data-product-type="simple"
   data-action="buy-now"
   data-buy-now-label="Mua Ngay"
   data-select-label="Tùy chọn">Mua Ngay</a>
```

### Data Attributes Reference

| Attribute | Type | Description | Example |
|-----------|------|-------------|---------|
| `data-product-id` | Integer | WooCommerce product ID | `7337` |
| `data-product-type` | String | Product type (simple/variable/grouped) | `simple` |
| `data-action` | String | Button action type | `buy-now` for simple, `select-options` for variable |
| `data-buy-now-label` | String | Label for buy now action | `Mua Ngay` |
| `data-select-label` | String | Label for select options (variable products) | `Tùy chọn` |

## 2. Flow Diagram - Simple Product Click

```
User clicks "Buy Now" button
    ↓
[CLIENT] preventDefault() + check processing state
    ↓
[CLIENT] Collect button data attributes
    ↓
[CLIENT] Trigger event: 'buy_now_clicked'
    ↓
[CLIENT] AJAX POST to admin-ajax.php
    ├─ action: 'vidieu_buy_now'
    ├─ nonce: from vd_home_ajax.nonce
    ├─ product_id: from button
    ├─ quantity: 1 (default)
    └─ action_type: 'buy-now'
    ↓
[SERVER] handle_buy_now_ajax()
    ├─ Verify nonce (vd_buy_now)
    ├─ Validate product exists & purchasable
    ├─ Check stock status
    ├─ WC()->cart->add_to_cart()
    └─ Determine redirect destination
    ↓
[SERVER] Return JSON response
    ├─ success: true
    ├─ action: 'redirect'
    └─ redirect_url: checkout/cart URL
    ↓
[CLIENT] Handle response
    ├─ Remove processing state
    └─ Redirect after 100ms delay
```

## 3. Client-Side Pseudocode (Simple Products)

```javascript
// Event binding (vidieu-home.js:283-285)
$(document).on('click', '.vd-buy-now-button', debounce(function(e) {
    e.preventDefault();
    handleBuyNowClick($(this));
}, 300));

// Handler function (vidieu-home.js:1125-1261)
function handleBuyNowClick($button) {
    // 1. Prevent double-click
    if ($button.hasClass('vd-processing')) return;
    $button.addClass('vd-processing');
    
    // 2. Extract data
    const data = {
        productId: $button.data('product-id'),
        productType: $button.data('product-type'), 
        action: $button.data('action')
        // Note: nonce removed from data attributes, using global nonce
    };
    
    // 3. Trigger compatibility event
    triggerCompatibleEvent('buy_now_clicked', payload);
    
    // 4. For simple products - direct AJAX
    if (data.productType === 'simple') {
        $.ajax({
            url: vd_home_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vidieu_buy_now',
                nonce: vd_home_ajax.nonce, // Global nonce from wp_localize_script
                product_id: data.productId,
                quantity: 1,
                action_type: data.action
            },
            success: function(response) {
                $button.removeClass('vd-processing');
                
                if (response.success && response.data.action === 'redirect') {
                    // Redirect without changing button text
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url;
                    }, 100);
                }
            },
            error: function() {
                $button.removeClass('vd-processing');
                alert(vd_home_ajax.error_text);
            }
        });
    }
}
```

## 4. Server-Side Flow & Hooks

### AJAX Handler Registration (class-vd-buy-now.php:47-48)
```php
add_action('wp_ajax_vidieu_buy_now', array($this, 'handle_buy_now_ajax'));
add_action('wp_ajax_nopriv_vidieu_buy_now', array($this, 'handle_buy_now_ajax'));
```

### Processing Flow (class-vd-buy-now.php:112-294)
```php
function handle_buy_now_ajax() {
    // 1. Security check
    check_ajax_referer('vd_buy_now', 'nonce', false);
    
    // 2. Sanitize inputs
    $product_id = absint($_POST['product_id']);
    $quantity = absint($_POST['quantity']) ?: 1;
    
    // 3. Validate product
    $product = wc_get_product($product_id);
    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        wp_send_json_error(['code' => 'PRODUCT_UNAVAILABLE']);
    }
    
    // 4. Add to cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
    
    // 5. Determine redirect
    $destination = VD_Admin::get_option('buy_now_destination', 'checkout');
    $redirect_url = ($destination === 'checkout') 
        ? wc_get_checkout_url() 
        : wc_get_cart_url();
    
    // 6. Return response
    wp_send_json_success([
        'action' => 'redirect',
        'redirect_url' => $redirect_url,
        'message' => 'Product added to cart. Redirecting...'
    ]);
}
```

### Related WooCommerce Hooks
- `woocommerce_add_to_cart` - Fired when product added to cart
- `woocommerce_ajax_added_to_cart` - AJAX specific add to cart hook
- `woocommerce_add_to_cart_redirect` - Filter for redirect URL

## 5. Redirect Rules

### Configuration
- Stored in: `VD_Admin::get_option('buy_now_destination', 'checkout')`
- Options: `checkout` (default) or `cart`

### URL Building
```php
// Checkout redirect (default)
$redirect_url = wc_get_checkout_url(); // Returns: /checkout/

// Cart redirect
$redirect_url = wc_get_cart_url(); // Returns: /cart/
```

### Cart Preservation
- Original implementation cleared cart before adding
- Current: Preserves existing cart items (lines 263-265 commented out)

## 6. Error Handling & UX

### Error Types & Messages

| Error Code | Condition | User Message |
|------------|-----------|--------------|
| `SECURITY_FAILED` | Nonce verification fails | "Security check failed. Please refresh the page and try again." |
| `INVALID_PRODUCT` | Product ID missing/invalid | "Invalid product." |
| `PRODUCT_UNAVAILABLE` | Out of stock or not purchasable | "This product is currently unavailable." |
| `ADD_TO_CART_FAILED` | WC cart operation fails | Exception message from WooCommerce |

### Client-Side Error Display
```javascript
// Alert for errors (vidieu-home.js:1247)
alert(response.data?.message || 'An error occurred');

// Timeout error
if (status === 'timeout') {
    alert('Request timed out. Please try again.');
}
```

### Loading States
- Button gets `vd-processing` class during operation
- Prevents double-click via class check
- Removed on success or error

## 7. Security Measures

### Nonce Implementation
1. **Generation**: Single global nonce via `wp_localize_script()` - `wp_create_nonce('vd_buy_now')`
2. **Global Nonce**: Uses `vd_home_ajax.nonce` for all AJAX requests
3. **Verification**: `check_ajax_referer('vd_buy_now', 'nonce', false)`
4. **DOM Security**: Removed data-nonce from DOM to avoid inconsistency and exposure

### Input Sanitization
```php
$product_id = absint($_POST['product_id']);
$quantity = absint($_POST['quantity']);
$action = sanitize_text_field($_POST['action_type']);
```

### CSRF Protection
- WordPress nonce system prevents CSRF
- Nonce tied to user session
- Expires after 24 hours by default

### Potential Risks
- No rate limiting on Buy Now actions
- Consider implementing request throttling for abuse prevention

## 8. Edge Cases

### Quantity Handling
- Always uses quantity = 1 for simple products
- No quantity selector integration in current implementation
- To support quantity: Find nearest `.quantity input` and read value

### Stock Management
```php
// Checked conditions
if (!$product->is_purchasable()) // Catalog visibility
if (!$product->is_in_stock())     // Stock status
```

### Sold Individually
- Not explicitly checked
- WooCommerce `add_to_cart()` handles internally
- Will fail if product already in cart

### Backorders
- Handled by WooCommerce stock status
- `is_in_stock()` returns true for backorders allowed

### Guest Checkout
- No login requirement in current implementation
- Follows WooCommerce guest checkout settings

## 9. Performance Considerations

### Optimization Techniques
1. **Debounce**: 300ms on button click prevents rapid clicks
2. **Processing State**: CSS class prevents concurrent requests
3. **Minimal Data**: Only essential fields sent in AJAX

### Potential Improvements
- Cache product availability check
- Batch multiple add-to-cart operations
- Implement request queue for multiple clicks

## 10. Testing Checklist

### Functional Tests
- [ ] Click Buy Now on simple product → Redirects to checkout/cart
- [ ] Double-click prevention → Only one request sent
- [ ] Out of stock product → Shows error message
- [ ] Network error → Shows timeout message
- [ ] Page refresh → New nonce generated

### Security Tests
- [ ] Modified nonce → Request fails with security error
- [ ] Invalid product ID → Shows invalid product error
- [ ] SQL injection attempts → Sanitized by `absint()`

### UX Tests
- [ ] Button shows loading state during request
- [ ] Error messages are user-friendly
- [ ] Redirect happens smoothly (100ms delay)
- [ ] No page scroll on button click

## 11. Implementation Guide for Other Locations

### Required HTML Structure
```html
<a href="#" 
   class="button vd-buy-now-button vd-buy-now-simple"
   data-product-id="{product_id}"
   data-product-type="simple"
   data-action="buy-now">
   Buy Now
</a>
```

### Required JavaScript
- Include: `vidieu-home.js` or implement similar handler
- Ensure: `vd_home_ajax` object available with ajax_url and nonce

### Required PHP
- Active: `VD_Buy_Now` class instance
- Setting: `enable_buy_now` must be true
- Hooks: AJAX handlers registered

## 12. Source Files Reference

### JavaScript Files
- **Main Handler**: `wp-content/plugins/vidieu-home-sections/assets/js/vidieu-home.js`
  - Event binding: Line 283-285
  - Handler function: Line 1125-1261
  - AJAX call: Line 1212-1260

### PHP Files  
- **Buy Now Class**: `wp-content/plugins/vidieu-home-sections/includes/class-vd-buy-now.php`
  - Button generation: Line 64-107
  - AJAX handler: Line 112-294
  - Security checks: Line 114-119

- **Admin Settings**: `wp-content/plugins/vidieu-home-sections/includes/class-vd-admin.php`
  - Buy now settings registration
  - Destination option configuration

### CSS Files
- **Styles**: `wp-content/plugins/vidieu-home-sections/assets/css/vidieu-home.css`
  - `.vd-buy-now-button` styling
  - `.vd-processing` state

## Summary Table

| Component | File Path | Key Functions | Notes |
|-----------|-----------|---------------|-------|
| JS Handler | `assets/js/vidieu-home.js:1125` | `handleBuyNowClick()` | Debounced click handler |
| PHP Handler | `includes/class-vd-buy-now.php:112` | `handle_buy_now_ajax()` | AJAX endpoint |
| Button HTML | `includes/class-vd-buy-now.php:95` | `add_buy_now_button()` | Generates button markup |
| Settings | `includes/class-vd-admin.php` | Buy now configuration | Redirect destination |

## Pseudocode Reference Implementation

```javascript
/**
 * Standard Buy Now implementation for simple products
 * Copy this as reference for other implementations
 */
function initBuyNowSimple() {
    // Prevent default and handle click
    $(document).on('click', '.buy-now-simple', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        if ($btn.hasClass('processing')) return;
        
        $btn.addClass('processing');
        
        // Collect data
        const data = {
            action: 'vidieu_buy_now',
            nonce: vd_home_ajax.nonce, // Global nonce from wp_localize_script
            product_id: $btn.data('product-id'),
            quantity: 1,
            action_type: 'buy-now'
        };
        
        // AJAX request
        $.post(vd_home_ajax.ajax_url, data)
            .done(function(response) {
                if (response.success && response.data.redirect_url) {
                    window.location.href = response.data.redirect_url;
                } else {
                    alert(response.data?.message || 'Error occurred');
                }
            })
            .fail(function() {
                alert('Network error. Please try again.');
            })
            .always(function() {
                $btn.removeClass('processing');
            });
    });
}
```
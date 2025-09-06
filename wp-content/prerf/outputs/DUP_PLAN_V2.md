# KẾ HOẠCH TỐI ƯU V2 - ENHANCED FIXES
*Cập nhật: 2025-09-06*

## PHASE 1: EMERGENCY FIXES (Deploy ngay)

### 1.1. Fix 404 Errors - Nuclear Option

#### Option A: Must-Use Plugin
```php
// wp-content/mu-plugins/fix-404-resources.php
<?php
// Hook cực sớm trước mọi plugin/theme
add_action('muplugins_loaded', function() {
    // Redirect 404 resources
    if (strpos($_SERVER['REQUEST_URI'], 'main-font.woff2') !== false) {
        header('Location: ' . content_url('themes/elessi-theme-child/assets/fonts/main-font.woff'));
        exit;
    }
    
    if (strpos($_SERVER['REQUEST_URI'], 'elessi-theme/style.min.css') !== false) {
        header('Location: ' . content_url('themes/elessi-theme/style.css'));
        exit;
    }
});
```

#### Option B: .htaccess Redirect
```apache
# Add to .htaccess
RedirectMatch 301 ^/wp-content/themes/elessi-theme/assets/fonts/main-font\.woff2$ /wp-content/themes/elessi-theme-child/assets/fonts/main-font.woff
RedirectMatch 301 ^/wp-content/themes/elessi-theme/style\.min\.css$ /wp-content/themes/elessi-theme/style.css
```

#### Option C: Physical File Creation
```bash
# Create symbolic links
cd wp-content/themes/elessi-theme/assets/fonts/
ln -s ../../../elessi-theme-child/assets/fonts/main-font.woff main-font.woff2

cd wp-content/themes/elessi-theme/
touch style.min.css
echo "/* Redirect to style.css */" > style.min.css
echo "@import url('style.css');" >> style.min.css
```

### 1.2. reCAPTCHA Nuclear Fix

#### Step 1: Identify ALL sources
```php
// Add to debugging
add_action('wp_footer', function() {
    global $wp_scripts;
    echo "<!-- reCAPTCHA Debug:\n";
    foreach ($wp_scripts->queue as $handle) {
        if (strpos($wp_scripts->registered[$handle]->src ?? '', 'recaptcha') !== false) {
            echo "$handle: " . $wp_scripts->registered[$handle]->src . "\n";
        }
    }
    echo "-->";
}, 9999);
```

#### Step 2: Aggressive removal
```php
// In V2 class, enhance the removal
public function nuclear_recaptcha_removal() {
    // Remove ALL reCAPTCHA except one
    remove_all_actions('wp_head', 9);
    remove_all_actions('wp_footer', 9);
    
    // Re-add only one
    add_action('wp_footer', function() {
        echo '<script src="https://www.google.com/recaptcha/api.js?render=6Le797wrAAAAAJjq4x2B7SZfG0bDBTkz5myGslZQ" async defer></script>';
    }, 999);
    
    // Block via JavaScript
    add_action('wp_head', function() {
        ?>
        <script>
        // Block duplicate reCAPTCHA loads
        (function() {
            var loaded = false;
            var originalAppend = Element.prototype.appendChild;
            Element.prototype.appendChild = function(child) {
                if (child.tagName === 'SCRIPT' && 
                    child.src && 
                    child.src.includes('recaptcha') && 
                    loaded) {
                    console.log('Blocked duplicate reCAPTCHA:', child.src);
                    return child;
                }
                if (child.src && child.src.includes('recaptcha')) {
                    loaded = true;
                }
                return originalAppend.apply(this, arguments);
            };
        })();
        </script>
        <?php
    }, 1);
}
```

## PHASE 2: CART/CHECKOUT OPTIMIZATION

### 2.1. Cart Page Specific
```php
public function aggressive_cart_optimization() {
    if (!is_cart()) return;
    
    // Whitelist approach - only allow essentials
    $allowed_scripts = array(
        'jquery',
        'wc-cart',
        'wc-cart-fragments',
        'woocommerce',
        'selectWoo'
    );
    
    global $wp_scripts;
    foreach ($wp_scripts->queue as $handle) {
        if (!in_array($handle, $allowed_scripts)) {
            wp_dequeue_script($handle);
        }
    }
    
    // Remove all non-essential styles
    $allowed_styles = array(
        'woocommerce-layout',
        'woocommerce-general',
        'elessi-style'
    );
    
    global $wp_styles;
    foreach ($wp_styles->queue as $handle) {
        if (!in_array($handle, $allowed_styles)) {
            wp_dequeue_style($handle);
        }
    }
}
```

### 2.2. Checkout Page Specific
```php
public function aggressive_checkout_optimization() {
    if (!is_checkout()) return;
    
    // Fix 27x reCAPTCHA issue
    add_filter('woocommerce_checkout_fields', function($fields) {
        // Disable reCAPTCHA on payment fields
        remove_all_filters('woocommerce_checkout_process', 20);
        return $fields;
    });
    
    // Consolidate payment scripts
    add_action('wp_enqueue_scripts', function() {
        // Remove duplicate payment gateway scripts
        $payment_scripts = array();
        global $wp_scripts;
        
        foreach ($wp_scripts->queue as $handle) {
            if (strpos($handle, 'payment') !== false || 
                strpos($handle, 'stripe') !== false ||
                strpos($handle, 'paypal') !== false) {
                
                $src = basename($wp_scripts->registered[$handle]->src);
                if (isset($payment_scripts[$src])) {
                    wp_dequeue_script($handle);
                } else {
                    $payment_scripts[$src] = true;
                }
            }
        }
    }, 999);
}
```

## PHASE 3: ENHANCED DEDUPLICATION

### 3.1. JavaScript-based deduplication
```javascript
// Enhanced AJAX deduplication
window.VidieuOptimizer = {
    loaded: {},
    
    blockDuplicate: function(url) {
        var cleanUrl = url.split('?')[0];
        if (this.loaded[cleanUrl]) {
            console.log('Blocked duplicate:', cleanUrl);
            return true;
        }
        this.loaded[cleanUrl] = true;
        return false;
    }
};

// Override jQuery.getScript
var originalGetScript = jQuery.getScript;
jQuery.getScript = function(url, callback) {
    if (VidieuOptimizer.blockDuplicate(url)) {
        if (callback) callback();
        return jQuery.Deferred().resolve();
    }
    return originalGetScript.apply(this, arguments);
};
```

### 3.2. Image deduplication (Cart specific)
```php
// Fix duplicate product images
add_filter('woocommerce_cart_item_thumbnail', function($thumbnail, $cart_item) {
    static $loaded_images = array();
    
    if (preg_match('/src="([^"]+)"/', $thumbnail, $matches)) {
        $src = $matches[1];
        $clean_src = strtok($src, '?');
        
        if (in_array($clean_src, $loaded_images)) {
            // Return placeholder for duplicates
            return '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIj48L3N2Zz4=" data-src="' . esc_attr($src) . '" class="lazy-duplicate">';
        }
        
        $loaded_images[] = $clean_src;
    }
    
    return $thumbnail;
}, 10, 2);
```

## PHASE 4: MONITORING & VALIDATION

### 4.1. Real-time monitoring
```php
add_action('wp_footer', function() {
    if (!current_user_can('manage_options')) return;
    ?>
    <script>
    // Monitor actual network requests
    (function() {
        var observer = new PerformanceObserver(function(list) {
            var entries = list.getEntries();
            var duplicates = {};
            
            entries.forEach(function(entry) {
                if (entry.entryType === 'resource') {
                    var cleanUrl = entry.name.split('?')[0];
                    duplicates[cleanUrl] = (duplicates[cleanUrl] || 0) + 1;
                }
            });
            
            // Log duplicates
            Object.keys(duplicates).forEach(function(url) {
                if (duplicates[url] > 1) {
                    console.warn('Duplicate resource:', url, 'loaded', duplicates[url], 'times');
                }
            });
        });
        
        observer.observe({entryTypes: ['resource']});
    })();
    </script>
    <?php
});
```

## IMPLEMENTATION CHECKLIST

### Day 1 (Emergency)
- [ ] Deploy mu-plugin for 404 fixes
- [ ] Implement nuclear reCAPTCHA fix
- [ ] Test all forms
- [ ] Monitor console for errors

### Day 2 (Cart/Checkout)
- [ ] Deploy cart whitelist approach
- [ ] Fix checkout reCAPTCHA overload
- [ ] Test purchase flow
- [ ] Verify payment gateways

### Day 3 (Validation)
- [ ] Run new HAR analysis
- [ ] Compare metrics
- [ ] Document improvements
- [ ] Plan further optimization

## SUCCESS CRITERIA

1. **404 errors:** 0 on all pages
2. **reCAPTCHA:** Max 1 load per page
3. **Cart page:** < 150 requests
4. **Checkout page:** < 180 requests
5. **No functionality broken**

## ROLLBACK PLAN

1. **Quick disable:** 
   ```php
   define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);
   ```

2. **Remove mu-plugin** if 404 fixes cause issues

3. **Restore V1 class** if V2 causes problems

4. **Emergency contacts:** Keep hosting support ready
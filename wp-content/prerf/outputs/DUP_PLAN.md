# KẾ HOẠCH TỐI ƯU REQUEST TRÙNG LẶP - VIDIEU.VN
*Ngày lập: 2025-09-06*

## PHASE 1: CRITICAL FIXES (Triển khai ngay)

### 1.1. Fix 404 Errors

#### A. main-font.woff2
**Issue:** `/wp-content/themes/elessi-theme/assets/fonts/main-font.woff2` (404)
**Action:**
```php
// Trong class-vidieu-dup-requests-guard.php
add_filter('style_loader_src', 'vidieu_fix_font_paths', 999, 2);

function vidieu_fix_font_paths($src, $handle) {
    if (strpos($src, 'main-font.woff2') !== false) {
        // Redirect to child theme path
        $src = str_replace(
            '/elessi-theme/assets/fonts/',
            '/elessi-theme-child/assets/fonts/',
            $src
        );
    }
    return $src;
}
```
**Impact:** -4 failed requests, loại bỏ retry
**Risk:** Low - chỉ redirect path

#### B. style.min.css 
**Issue:** `/wp-content/themes/elessi-theme/style.min.css` (404)
**Action:**
```php
// Dequeue minified version nếu không tồn tại
add_action('wp_enqueue_scripts', 'vidieu_fix_missing_styles', 999);

function vidieu_fix_missing_styles() {
    // Remove enqueue của style.min.css
    wp_dequeue_style('elessi-style-min');
    
    // Ensure non-minified version is loaded
    if (!wp_style_is('elessi-style', 'enqueued')) {
        wp_enqueue_style('elessi-style', 
            get_template_directory_uri() . '/style.css',
            array(),
            ELESSI_THEME_VERSION
        );
    }
}
```
**Impact:** -1 failed request per page
**Risk:** Low

#### C. Elementor Google Fonts
**Issue:** `/wp-content/uploads/elementor/google-fonts/css/jost.css` (404)
**Action:**
```php
// Disable Elementor Google Fonts caching
add_filter('elementor/frontend/print_google_fonts', '__return_false');

// Or regenerate fonts
add_action('init', 'vidieu_regenerate_elementor_fonts');
function vidieu_regenerate_elementor_fonts() {
    if (get_option('vidieu_fonts_regenerated') !== '1') {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
        update_option('vidieu_fonts_regenerated', '1');
    }
}
```
**Impact:** -1 failed request
**Risk:** Medium - cần test Elementor styling

### 1.2. Fix reCAPTCHA Trùng Lặp

**Issue:** 3 instances của reCAPTCHA, 2 API keys khác nhau
**Action:**
```php
// Trong filters-dup-requests.php
add_action('wp_print_scripts', 'vidieu_consolidate_recaptcha', 5);

function vidieu_consolidate_recaptcha() {
    global $wp_scripts;
    
    $recaptcha_handles = array();
    $valid_key = '6Le797wrAAAAAJjq4x2B7SZfG0bDBTkz5myGslZQ'; // WP Captcha key
    
    foreach ($wp_scripts->queue as $handle) {
        $src = $wp_scripts->registered[$handle]->src ?? '';
        
        // Find all reCAPTCHA instances
        if (strpos($src, 'google.com/recaptcha/api.js') !== false ||
            strpos($src, 'gstatic.com/recaptcha') !== false) {
            
            // Keep only the valid one
            if (strpos($src, $valid_key) === false) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
            }
        }
    }
    
    // Remove inline reCAPTCHA calls
    add_filter('script_loader_tag', 'vidieu_remove_duplicate_recaptcha_inline', 10, 3);
}

function vidieu_remove_duplicate_recaptcha_inline($tag, $handle, $src) {
    if (strpos($src, '6LdAg_UqAAAAANiCMOwwgJ_I3We6DGQ3T5LohPX0') !== false) {
        return ''; // Remove invalid key script
    }
    return $tag;
}
```
**Impact:** -2 requests, -200KB per page
**Risk:** Medium - cần test form submissions

## PHASE 2: HIGH PRIORITY

### 2.1. Optimize admin-ajax.php Calls

**Issue:** 2-4 duplicate calls per page
**Action:**
```php
// Route-based AJAX optimization
add_action('wp_footer', 'vidieu_optimize_ajax_calls', 1);

function vidieu_optimize_ajax_calls() {
    ?>
    <script>
    // Debounce AJAX calls
    (function() {
        var ajaxQueue = {};
        var originalAjax = jQuery.ajax;
        
        jQuery.ajax = function(options) {
            var key = options.url + '?' + jQuery.param(options.data || {});
            
            // Skip if same request in queue
            if (ajaxQueue[key] && ajaxQueue[key].state() === 'pending') {
                return ajaxQueue[key];
            }
            
            ajaxQueue[key] = originalAjax.call(this, options);
            return ajaxQueue[key];
        };
    })();
    </script>
    <?php
}

// Server-side: Cache AJAX responses
add_filter('wp_ajax_nopriv_get_refreshed_fragments', 'vidieu_cache_fragments', 1);
add_filter('wp_ajax_get_refreshed_fragments', 'vidieu_cache_fragments', 1);

function vidieu_cache_fragments() {
    $cache_key = 'vidieu_cart_fragments_' . WC()->cart->get_cart_hash();
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        wp_send_json($cached);
        wp_die();
    }
}
```
**Impact:** -1 to -3 requests per page
**Risk:** Low - chỉ debounce duplicate calls

### 2.2. Fix Child Theme CSS Duplication

**Issue:** style.css loaded 2x with different versions
**Action:**
```php
add_action('wp_enqueue_scripts', 'vidieu_fix_child_style_duplication', 999);

function vidieu_fix_child_style_duplication() {
    // Remove duplicate enqueues
    global $wp_styles;
    
    $child_style_url = get_stylesheet_directory_uri() . '/style.css';
    $found_handles = array();
    
    foreach ($wp_styles->registered as $handle => $style) {
        if (strpos($style->src, $child_style_url) !== false) {
            $found_handles[] = $handle;
        }
    }
    
    // Keep only first instance
    if (count($found_handles) > 1) {
        for ($i = 1; $i < count($found_handles); $i++) {
            wp_dequeue_style($found_handles[$i]);
            wp_deregister_style($found_handles[$i]);
        }
    }
}
```
**Impact:** -1 request, -10KB
**Risk:** Low

## PHASE 3: ROUTE-BASED OPTIMIZATION

### 3.1. Conditional Loading by Route

```php
class Vidieu_Route_Based_Loading {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'optimize_by_route'), 999);
    }
    
    public function optimize_by_route() {
        // Không load WooCommerce compare trên non-product pages
        if (!is_shop() && !is_product_category() && !is_product()) {
            wp_dequeue_script('yith-woocompare-main');
            wp_dequeue_style('yith-woocompare-widget');
        }
        
        // Không load Elementor trên pages không dùng
        if (!$this->is_elementor_page()) {
            wp_dequeue_script('elementor-frontend');
            wp_dequeue_style('elementor-frontend');
        }
        
        // Optimize cho specific routes
        if (is_page('contact')) {
            // Contact page không cần WooCommerce scripts
            $this->dequeue_woo_scripts();
        }
        
        if (is_home() || is_front_page()) {
            // Home có thể defer một số scripts
            $this->defer_non_critical_scripts();
        }
    }
    
    private function is_elementor_page() {
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }
        
        global $post;
        if (!$post) return false;
        
        return \Elementor\Plugin::$instance->documents->get($post->ID)->is_built_with_elementor();
    }
    
    private function dequeue_woo_scripts() {
        $woo_scripts = array(
            'wc-add-to-cart',
            'wc-cart-fragments',
            'woocommerce',
            'wc-single-product'
        );
        
        foreach ($woo_scripts as $handle) {
            wp_dequeue_script($handle);
        }
    }
}
```

## PHASE 4: CONSOLIDATION

### 4.1. CSS Consolidation Plan

**Current:** 39-41 CSS files
**Target:** < 15 files

```php
// Auto-combine inline styles
add_filter('print_styles_array', 'vidieu_combine_inline_styles');

function vidieu_combine_inline_styles($styles) {
    // Group small CSS files
    $combined = array();
    $inline_buffer = '';
    
    foreach ($styles as $handle) {
        $style = wp_styles()->registered[$handle];
        if ($style->src && filesize($style->src) < 2048) { // < 2KB
            $inline_buffer .= file_get_contents($style->src);
            wp_deregister_style($handle);
        } else {
            $combined[] = $handle;
        }
    }
    
    if ($inline_buffer) {
        wp_add_inline_style('elessi-style', $inline_buffer);
    }
    
    return $combined;
}
```

## IMPLEMENTATION CHECKLIST

### Immediate Actions (Day 1)
- [ ] Create `class-vidieu-dup-requests-guard.php`
- [ ] Implement 404 fixes
- [ ] Fix reCAPTCHA duplication
- [ ] Test all forms still work

### Day 2-3
- [ ] Implement AJAX debouncing
- [ ] Fix CSS duplications
- [ ] Add route-based guards
- [ ] Performance testing

### Day 4-5
- [ ] CSS/JS consolidation
- [ ] Final testing all routes
- [ ] Create rollback documentation
- [ ] Monitor error logs

## ROLLBACK PLAN

```php
// Emergency kill switch
define('VIDIEU_DISABLE_DUP_OPTIMIZATION', false);

// In all optimization functions:
if (defined('VIDIEU_DISABLE_DUP_OPTIMIZATION') && VIDIEU_DISABLE_DUP_OPTIMIZATION) {
    return;
}
```

## SUCCESS METRICS

1. **Requests reduction:** 25-30 per page (20-25%)
2. **No 404 errors** in network tab
3. **Single reCAPTCHA instance**
4. **All forms functional**
5. **No visual regressions**
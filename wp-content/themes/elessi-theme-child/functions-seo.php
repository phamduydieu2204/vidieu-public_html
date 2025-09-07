<?php
/**
 * SEO Enhancements for Elessi Child Theme
 * 
 * @package Elessi_Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clean up problematic preloads
 */
add_action('init', function() {
    // Remove preloads for non-existent files
    remove_action('wp_head', 'elessi_preload_resources', 1);
});

/**
 * Add filtered preloads
 */
add_action('wp_head', function() {
    // Only preload files that actually exist
    $template_dir = get_template_directory();
    $template_uri = get_template_directory_uri();
    
    // Check if style.min.css exists before preloading
    if (!file_exists($template_dir . '/style.min.css')) {
        // Don't preload if it doesn't exist
        return;
    }
    
    // Fonts preload (if they exist)
    $font_files = array(
        '/assets/fonts/main-font.woff2',
        '/assets/fonts/icons.woff2'
    );
    
    foreach ($font_files as $font) {
        if (file_exists($template_dir . $font)) {
            echo '<link rel="preload" href="' . esc_url($template_uri . $font) . '" as="font" type="font/woff2" crossorigin>' . "\n";
        }
    }
}, 1);

/**
 * Fix incorrect 'as' attribute in preloads
 */
add_filter('script_loader_tag', function($tag, $handle, $src) {
    // Fix preload attributes
    if (strpos($tag, 'rel="preload"') !== false || strpos($tag, "rel='preload'") !== false) {
        // Ensure correct 'as' attribute based on file type
        if (strpos($src, '.js') !== false && strpos($tag, 'as="style"') !== false) {
            $tag = str_replace('as="style"', 'as="script"', $tag);
        }
        if (strpos($src, '.css') !== false && strpos($tag, 'as="script"') !== false) {
            $tag = str_replace('as="script"', 'as="style"', $tag);
        }
    }
    
    return $tag;
}, 10, 3);

/**
 * Remove Elementor preloads if not using Elementor
 */
if (!defined('ELEMENTOR_VERSION')) {
    add_filter('elementor/frontend/print_google_fonts', '__return_false');
    
    // Remove Elementor font preloads
    add_action('wp_head', function() {
        ?>
        <script>
        // Remove Elementor font preloads if they exist
        document.addEventListener('DOMContentLoaded', function() {
            var elementorPreloads = document.querySelectorAll('link[href*="elementor"][rel="preload"]');
            elementorPreloads.forEach(function(link) {
                link.remove();
            });
        });
        </script>
        <?php
    }, 999);
}

/**
 * Add critical meta tags if missing (backup for main SEO module)
 */
add_action('wp_head', function() {
    // Only add if main SEO module hasn't already
    if (!class_exists('Vidieu_SEO_Bootstrap')) {
        // Add viewport if missing
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    }
}, 0);

/**
 * Ensure proper language attributes
 */
add_filter('language_attributes', function($output) {
    if (!is_admin()) {
        // Ensure lang="vi" is set
        if (strpos($output, 'lang=') === false) {
            $output .= ' lang="vi"';
        }
    }
    return $output;
});

/**
 * Add aria-labels to icon links in header
 */
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cart icon
        var cartIcons = document.querySelectorAll('a[href*="cart"]:not([aria-label]), .cart-link:not([aria-label])');
        cartIcons.forEach(function(icon) {
            if (!icon.textContent.trim() || icon.querySelector('i')) {
                icon.setAttribute('aria-label', 'Giỏ hàng');
            }
        });
        
        // Wishlist icon
        var wishlistIcons = document.querySelectorAll('a[href*="wishlist"]:not([aria-label]), .wishlist-link:not([aria-label])');
        wishlistIcons.forEach(function(icon) {
            if (!icon.textContent.trim() || icon.querySelector('i')) {
                icon.setAttribute('aria-label', 'Danh sách yêu thích');
            }
        });
        
        // Search icon
        var searchIcons = document.querySelectorAll('.search-link:not([aria-label]), [class*="search-icon"]:not([aria-label])');
        searchIcons.forEach(function(icon) {
            if (!icon.textContent.trim() || icon.querySelector('i')) {
                icon.setAttribute('aria-label', 'Tìm kiếm');
            }
        });
        
        // Account icon
        var accountIcons = document.querySelectorAll('a[href*="account"]:not([aria-label]), .account-link:not([aria-label])');
        accountIcons.forEach(function(icon) {
            if (!icon.textContent.trim() || icon.querySelector('i')) {
                icon.setAttribute('aria-label', 'Tài khoản');
            }
        });
    });
    </script>
    <?php
});
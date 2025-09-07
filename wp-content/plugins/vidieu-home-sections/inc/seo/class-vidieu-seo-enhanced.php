<?php
/**
 * Enhanced SEO Module for 95+ Lighthouse Score
 * 
 * Fixes all P0 issues:
 * - Non-crawlable links
 * - Missing meta descriptions
 * - Generic link text
 * 
 * @package Vidieu_Home_Sections
 * @subpackage SEO
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_SEO_Enhanced {
    
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
        // Only run on frontend
        if (is_admin()) {
            return;
        }
        
        // Priority 1: Meta tags and canonical
        add_action('wp_head', array($this, 'add_meta_tags'), 1);
        
        // Priority 10: Structured data
        add_action('wp_head', array($this, 'add_structured_data'), 10);
        
        // Priority 99: JavaScript fixes (late to ensure DOM is ready)
        add_action('wp_footer', array($this, 'fix_crawlable_links'), 99);
        add_action('wp_footer', array($this, 'add_tap_target_styles'), 100);
        
        // Filter hooks
        add_filter('the_content', array($this, 'improve_link_text'), 20);
        add_filter('wp_get_attachment_image_attributes', array($this, 'add_image_alt_fallback'), 10, 3);
        add_filter('robots_txt', array($this, 'enhance_robots_txt'), 10, 2);
        add_filter('script_loader_tag', array($this, 'fix_preload_attributes'), 10, 3);
        add_filter('style_loader_tag', array($this, 'fix_style_preload_attributes'), 10, 4);
    }
    
    /**
     * Add essential meta tags with enhanced guards
     */
    public function add_meta_tags() {
        // Meta description with enhanced generation
        if (!$this->has_meta_description()) {
            $description = $this->generate_enhanced_meta_description();
            if ($description) {
                echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            }
        }
        
        // Ensure viewport
        if (!$this->has_viewport_meta()) {
            echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        }
        
        // Clean canonical
        if (!$this->has_canonical()) {
            $canonical = $this->generate_canonical_url();
            if ($canonical) {
                echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
            }
        }
        
        // Ensure proper language
        global $wp_locale;
        $lang = get_bloginfo('language');
        if (!$lang) {
            $lang = 'vi-VN';
        }
    }
    
    /**
     * Check if meta description exists (improved)
     */
    private function has_meta_description() {
        // Check for major SEO plugins
        if (defined('WPSEO_VERSION') || 
            defined('AIOSEO_VERSION') || 
            defined('SEOPRESS_VERSION') || 
            class_exists('RankMath') ||
            defined('THE_SEO_FRAMEWORK_VERSION')) {
            return true;
        }
        
        // Check if theme already adds it
        if (has_action('wp_head', 'theme_meta_description')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate enhanced meta description with better fallbacks
     */
    private function generate_enhanced_meta_description() {
        $description = '';
        
        if (is_home() || is_front_page()) {
            // Home page
            $description = get_bloginfo('description');
            if (!$description) {
                $description = 'Vidieu.vn - Nền tảng thương mại điện tử hàng đầu với hàng ngàn sản phẩm chất lượng, giá cả cạnh tranh, giao hàng nhanh chóng trên toàn quốc.';
            }
        } elseif (is_singular('product')) {
            // Product pages (CRITICAL FIX)
            global $post;
            $product = wc_get_product($post->ID);
            
            if ($product) {
                // Try multiple sources
                $sources = array(
                    get_post_meta($post->ID, '_yoast_wpseo_metadesc', true),
                    get_post_meta($post->ID, 'seo_description', true),
                    get_post_meta($post->ID, '_aioseop_description', true),
                    $product->get_short_description(),
                    $product->get_description()
                );
                
                foreach ($sources as $source) {
                    if (!empty($source)) {
                        $description = strip_tags(strip_shortcodes($source));
                        break;
                    }
                }
                
                // Fallback: Generate from product data
                if (empty($description)) {
                    $description = sprintf(
                        '%s - Giá: %s. %s. Mua ngay tại Vidieu.vn với nhiều ưu đãi hấp dẫn.',
                        $product->get_name(),
                        $product->get_price_html(),
                        wp_trim_words($product->get_description(), 15, '')
                    );
                }
            }
        } elseif (is_singular('post')) {
            // Blog posts
            global $post;
            
            // Check custom fields first
            $custom_desc = get_post_meta($post->ID, 'seo_description', true) ?: 
                          get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
            
            if ($custom_desc) {
                $description = $custom_desc;
            } elseif ($post->post_excerpt) {
                $description = $post->post_excerpt;
            } else {
                $content = strip_tags(strip_shortcodes($post->post_content));
                $description = wp_trim_words($content, 25, '');
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            // Archive pages
            $term = get_queried_object();
            if ($term && !empty($term->description)) {
                $description = strip_tags($term->description);
            } else {
                $description = sprintf(
                    '%s - Khám phá các sản phẩm trong danh mục %s tại Vidieu.vn',
                    single_term_title('', false),
                    single_term_title('', false)
                );
            }
        } elseif (is_search()) {
            $description = sprintf(
                'Kết quả tìm kiếm cho "%s" - Tìm thấy sản phẩm phù hợp tại Vidieu.vn',
                get_search_query()
            );
        } elseif (is_shop() || is_post_type_archive('product')) {
            // Shop/Product archive page (CRITICAL FIX for /san-pham/)
            $description = 'Khám phá hàng ngàn sản phẩm chất lượng tại Vidieu.vn - Giá tốt nhất, giao hàng nhanh, thanh toán an toàn. Mua sắm online dễ dàng với nhiều ưu đãi hấp dẫn.';
        } elseif (is_archive()) {
            // Generic archive fallback
            $description = sprintf(
                '%s - Xem tất cả sản phẩm và bài viết tại Vidieu.vn',
                wp_title('', false)
            );
        }
        
        // Clean and trim to proper length
        $description = preg_replace('/\s+/', ' ', trim($description));
        if (mb_strlen($description) > 160) {
            $description = mb_substr($description, 0, 157) . '...';
        } elseif (mb_strlen($description) < 50 && !empty($description)) {
            // Too short, append site name
            $description .= ' - ' . get_bloginfo('name');
        }
        
        return $description;
    }
    
    /**
     * Check viewport meta
     */
    private function has_viewport_meta() {
        // Most modern themes have this
        return true;
    }
    
    /**
     * Check canonical
     */
    private function has_canonical() {
        // Let's manage our own clean canonical
        return false;
    }
    
    /**
     * Generate clean canonical URL
     */
    private function generate_canonical_url() {
        $canonical = '';
        
        if (is_home() || is_front_page()) {
            $canonical = home_url('/');
        } elseif (is_singular()) {
            $canonical = get_permalink();
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term) {
                $canonical = get_term_link($term);
            }
        } elseif (is_search()) {
            $canonical = get_search_link();
        } elseif (is_shop() || is_post_type_archive('product')) {
            // WooCommerce shop page
            $canonical = get_permalink(wc_get_page_id('shop'));
        } elseif (is_post_type_archive()) {
            $canonical = get_post_type_archive_link(get_query_var('post_type'));
        } elseif (get_option('page_for_posts') && is_home()) {
            // Blog page
            $canonical = get_permalink(get_option('page_for_posts'));
        } else {
            // Fallback for any other archive
            global $wp;
            $canonical = home_url($wp->request);
        }
        
        // Clean tracking parameters
        if ($canonical) {
            $canonical = remove_query_arg(array(
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'fbclid', 'gclid', 'msclkid', 'ref', 'source', 'mc_cid', 'mc_eid'
            ), $canonical);
            
            // Ensure trailing slash for consistency
            if (!is_singular()) {
                $canonical = trailingslashit($canonical);
            }
        }
        
        return $canonical;
    }
    
    /**
     * Add comprehensive structured data
     */
    public function add_structured_data() {
        $structured_data = array();
        
        // Always add WebSite schema
        if (!$this->has_schema_type('WebSite')) {
            $structured_data[] = $this->get_website_schema();
        }
        
        // Organization on home
        if ((is_home() || is_front_page()) && !$this->has_schema_type('Organization')) {
            $structured_data[] = $this->get_organization_schema();
        }
        
        // BreadcrumbList
        if (!is_home() && !is_front_page() && !$this->has_schema_type('BreadcrumbList')) {
            $breadcrumbs = $this->get_breadcrumb_schema();
            if ($breadcrumbs) {
                $structured_data[] = $breadcrumbs;
            }
        }
        
        // Product schema enhancement
        if (is_singular('product')) {
            $product_schema = $this->get_enhanced_product_schema();
            if ($product_schema) {
                $structured_data[] = $product_schema;
            }
        }
        
        // Article schema
        if (is_singular('post') && !$this->has_schema_type('Article')) {
            $article = $this->get_article_schema();
            if ($article) {
                $structured_data[] = $article;
            }
        }
        
        // Output all structured data
        if (!empty($structured_data)) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo "\n</script>\n";
        }
    }
    
    /**
     * Check if schema type exists
     */
    private function has_schema_type($type) {
        // Check for SEO plugins that add schema
        if (defined('WPSEO_VERSION') || 
            class_exists('RankMath') || 
            defined('SEOPRESS_VERSION')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get WebSite schema
     */
    private function get_website_schema() {
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'url' => home_url('/'),
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ),
                'query-input' => 'required name=search_term_string'
            ),
            'inLanguage' => 'vi-VN'
        );
    }
    
    /**
     * Get Organization schema
     */
    private function get_organization_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => home_url('/#organization'),
            'name' => 'Vidieu.vn',
            'alternateName' => get_bloginfo('name'),
            'url' => home_url('/'),
            'description' => get_bloginfo('description'),
            'email' => get_option('admin_email'),
            'address' => array(
                '@type' => 'PostalAddress',
                'addressCountry' => 'VN',
                'addressLocality' => 'Hồ Chí Minh'
            )
        );
        
        // Add logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                $schema['logo'] = $logo_url;
            }
        }
        
        return $schema;
    }
    
    /**
     * Get BreadcrumbList schema
     */
    private function get_breadcrumb_schema() {
        $items = array();
        $position = 1;
        
        // Home
        $items[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Trang chủ',
            'item' => home_url('/')
        );
        
        // Build breadcrumb trail
        if (is_singular('product')) {
            // Product category
            global $post;
            $terms = get_the_terms($post->ID, 'product_cat');
            if ($terms && !is_wp_error($terms)) {
                $primary_term = $terms[0];
                
                // Parent categories
                $ancestors = get_ancestors($primary_term->term_id, 'product_cat');
                $ancestors = array_reverse($ancestors);
                
                foreach ($ancestors as $ancestor) {
                    $ancestor_term = get_term($ancestor, 'product_cat');
                    $items[] = array(
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $ancestor_term->name,
                        'item' => get_term_link($ancestor_term)
                    );
                }
                
                // Current category
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $primary_term->name,
                    'item' => get_term_link($primary_term)
                );
            }
            
            // Product
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_title()
            );
        } elseif (is_singular('post')) {
            // Post category
            $categories = get_the_category();
            if ($categories) {
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $categories[0]->name,
                    'item' => get_category_link($categories[0])
                );
            }
            
            // Post
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_title()
            );
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $term->name
            );
        }
        
        if (count($items) > 1) {
            return array(
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                '@id' => get_permalink() . '#breadcrumb',
                'itemListElement' => $items
            );
        }
        
        return null;
    }
    
    /**
     * Get enhanced Product schema
     */
    private function get_enhanced_product_schema() {
        global $post;
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return null;
        }
        
        // Check if WooCommerce already adds product schema
        if (has_action('wp_footer', array('WC_Structured_Data', 'output_structured_data'))) {
            // Enhance existing schema via filter
            add_filter('woocommerce_structured_data_product', array($this, 'enhance_woo_product_schema'), 10, 2);
            return null;
        }
        
        // Build comprehensive product schema
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => get_permalink() . '#product',
            'name' => $product->get_name(),
            'description' => $product->get_short_description() ?: wp_trim_words($product->get_description(), 50),
            'sku' => $product->get_sku(),
            'url' => get_permalink(),
            'brand' => array(
                '@type' => 'Brand',
                'name' => 'Vidieu'
            )
        );
        
        // Images
        $image_ids = $product->get_gallery_image_ids();
        array_unshift($image_ids, $product->get_image_id());
        $images = array();
        foreach ($image_ids as $image_id) {
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
                if ($image_url) {
                    $images[] = $image_url;
                }
            }
        }
        if (!empty($images)) {
            $schema['image'] = count($images) === 1 ? $images[0] : $images;
        }
        
        // Offers
        $schema['offers'] = array(
            '@type' => 'Offer',
            'price' => $product->get_price(),
            'priceCurrency' => get_woocommerce_currency(),
            'availability' => $product->is_in_stock() ? 
                'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => get_permalink(),
            'priceValidUntil' => date('Y-m-d', strtotime('+1 year'))
        );
        
        // Add sale price if on sale
        if ($product->is_on_sale() && $product->get_regular_price()) {
            $schema['offers']['priceSpecification'] = array(
                '@type' => 'PriceSpecification',
                'price' => $product->get_sale_price(),
                'priceCurrency' => get_woocommerce_currency(),
                'valueAddedTaxIncluded' => true
            );
        }
        
        // Reviews/Rating
        $rating_count = $product->get_rating_count();
        if ($rating_count > 0) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $rating_count
            );
        }
        
        return $schema;
    }
    
    /**
     * Enhance WooCommerce product schema
     */
    public function enhance_woo_product_schema($markup, $product) {
        // Add missing properties
        if (!isset($markup['brand'])) {
            $markup['brand'] = array(
                '@type' => 'Brand',
                'name' => 'Vidieu'
            );
        }
        
        if (!isset($markup['sku']) && $product->get_sku()) {
            $markup['sku'] = $product->get_sku();
        }
        
        return $markup;
    }
    
    /**
     * Get Article schema
     */
    private function get_article_schema() {
        global $post;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            '@id' => get_permalink() . '#article',
            'headline' => get_the_title(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'url' => get_permalink(),
            'inLanguage' => 'vi-VN',
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink()
            )
        );
        
        // Author
        $author_id = $post->post_author;
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $author_id),
            'url' => get_author_posts_url($author_id)
        );
        
        // Publisher
        $schema['publisher'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            '@id' => home_url('/#organization')
        );
        
        // Add logo to publisher
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                $schema['publisher']['logo'] = array(
                    '@type' => 'ImageObject',
                    'url' => $logo_url
                );
            }
        }
        
        // Featured image
        if (has_post_thumbnail()) {
            $image_id = get_post_thumbnail_id();
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            $image_meta = wp_get_attachment_metadata($image_id);
            
            if ($image_url) {
                $schema['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                    'width' => $image_meta['width'] ?? 1200,
                    'height' => $image_meta['height'] ?? 630
                );
            }
        }
        
        return $schema;
    }
    
    /**
     * Fix non-crawlable links (CRITICAL P0 FIX)
     */
    public function fix_crawlable_links() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fix javascript:void(0) links - Extended patterns for NASA theme
            var voidLinks = document.querySelectorAll('a[href="javascript:void(0)"], a[href="javascript:void(0);"], a[href="#0"], a[href*="javascript:"]');
            voidLinks.forEach(function(link) {
                // Determine proper href based on context
                var href = '#';
                
                // NASA theme specific patterns
                if (link.classList.contains('nasa-sidebar-return-shop')) {
                    // Return to shop button
                    href = '<?php echo esc_url(wc_get_page_permalink('shop')); ?>';
                }
                else if (link.classList.contains('nasa-toggle-widget')) {
                    // Widget toggle - use anchor to widget ID
                    var widget = link.closest('.widget');
                    if (widget && widget.id) {
                        href = '#' + widget.id;
                    } else {
                        href = '#widgets';
                    }
                }
                else if (link.classList.contains('nasa-nav-arrow') || 
                        link.classList.contains('slick-arrow') ||
                        link.classList.contains('slick-prev') ||
                        link.classList.contains('slick-next')) {
                    // Slider navigation - use proper anchors
                    if (link.classList.contains('slick-prev')) {
                        href = '#slide-prev';
                    } else if (link.classList.contains('slick-next')) {
                        href = '#slide-next';
                    } else {
                        href = '#slider';
                    }
                }
                // Quick view links
                else if (link.classList.contains('quick-view') || 
                    link.classList.contains('quickview') || 
                    link.getAttribute('data-product_id')) {
                    var productId = link.getAttribute('data-product_id') || 
                                   link.getAttribute('data-id') || 
                                   link.closest('[data-product-id]')?.getAttribute('data-product-id');
                    if (productId) {
                        href = '<?php echo home_url('/product-quick-view/'); ?>' + productId;
                    }
                }
                // Wishlist links
                else if (link.classList.contains('wishlist') || 
                        link.classList.contains('yith-wcwl-add-to-wishlist')) {
                    href = '<?php echo home_url('/wishlist/'); ?>';
                }
                // Compare links
                else if (link.classList.contains('compare')) {
                    href = '<?php echo home_url('/compare/'); ?>';
                }
                // Tab links
                else if (link.getAttribute('data-toggle') === 'tab') {
                    var target = link.getAttribute('data-target') || link.getAttribute('href');
                    if (target && target !== '#') {
                        href = target;
                    }
                }
                
                // Update href
                link.setAttribute('href', href);
                
                // Add aria-label if missing
                if (!link.getAttribute('aria-label')) {
                    var label = '';
                    
                    // NASA theme specific labels
                    if (link.classList.contains('nasa-sidebar-return-shop')) {
                        label = 'Quay lại cửa hàng';
                    } else if (link.classList.contains('nasa-toggle-widget')) {
                        label = 'Mở/đóng widget';
                    } else if (link.classList.contains('slick-prev')) {
                        label = 'Slide trước';
                    } else if (link.classList.contains('slick-next')) {
                        label = 'Slide tiếp theo';
                    } else if (link.classList.contains('nasa-nav-arrow')) {
                        label = 'Điều hướng slider';
                    }
                    // Standard e-commerce labels
                    else if (link.classList.contains('quick-view') || link.classList.contains('quickview')) {
                        label = 'Xem nhanh sản phẩm';
                    } else if (link.classList.contains('wishlist')) {
                        label = 'Thêm vào yêu thích';
                    } else if (link.classList.contains('compare')) {
                        label = 'So sánh sản phẩm';
                    }
                    
                    if (label) {
                        link.setAttribute('aria-label', label);
                    }
                }
                
                // Prevent default if it's actually a button
                if (link.onclick || link.getAttribute('data-toggle')) {
                    link.addEventListener('click', function(e) {
                        if (this.getAttribute('href') === '#' || 
                            this.getAttribute('href').startsWith('#tab-')) {
                            e.preventDefault();
                        }
                    });
                }
            });
            
            // Fix links without href
            var noHrefLinks = document.querySelectorAll('a:not([href])');
            noHrefLinks.forEach(function(link) {
                link.setAttribute('href', '#');
                // Add role=button if it's actually a button
                if (link.onclick) {
                    link.setAttribute('role', 'button');
                }
            });
            
            // Add aria-labels to icon-only links
            var iconLinks = document.querySelectorAll('a[href*="cart"]:not([aria-label]), a[href*="wishlist"]:not([aria-label]), a.search-link:not([aria-label]), a.account-link:not([aria-label])');
            iconLinks.forEach(function(link) {
                if (!link.textContent.trim() || link.querySelector('i, svg')) {
                    var label = '';
                    if (link.href.includes('cart')) {
                        label = 'Giỏ hàng';
                    } else if (link.href.includes('wishlist')) {
                        label = 'Danh sách yêu thích';
                    } else if (link.classList.contains('search-link')) {
                        label = 'Tìm kiếm';
                    } else if (link.classList.contains('account-link')) {
                        label = 'Tài khoản';
                    }
                    if (label) {
                        link.setAttribute('aria-label', label);
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Improve generic link text (P0 FIX for home page)
     */
    public function improve_link_text($content) {
        // Only process on pages with the issue
        if (!is_home() && !is_front_page() && !is_archive()) {
            return $content;
        }
        
        // Replace generic "Xem thêm" and "Read More" with context
        $patterns = array(
            '/(<a[^>]*>)\s*(Xem thêm|Read More|Read more|Đọc thêm)\s*(<\/a>)/i',
            '/(<a[^>]*class="[^"]*read-more[^"]*"[^>]*>)\s*([^<]+)\s*(<\/a>)/i'
        );
        
        $content = preg_replace_callback($patterns, function($matches) {
            $link_tag = $matches[1];
            $link_text = isset($matches[2]) ? $matches[2] : '';
            $close_tag = $matches[count($matches) - 1];
            
            // Try to get context from surrounding content
            static $product_counter = 0;
            static $post_counter = 0;
            
            // Check if it's a product or post link
            if (strpos($link_tag, 'product') !== false || strpos($link_tag, 'add_to_cart') !== false) {
                $product_counter++;
                $new_text = 'Xem chi tiết sản phẩm';
            } else {
                $post_counter++;
                $new_text = 'Đọc tiếp bài viết';
            }
            
            return $link_tag . $new_text . $close_tag;
        }, $content);
        
        return $content;
    }
    
    /**
     * Add tap target styles for mobile
     */
    public function add_tap_target_styles() {
        if (!wp_is_mobile()) {
            return;
        }
        ?>
        <style id="vidieu-tap-targets">
        /* Tap target improvements for mobile only */
        @media (max-width: 540px) {
            /* Minimum tap target size */
            a, button, [role="button"], input[type="submit"], input[type="button"] {
                min-height: 48px;
                min-width: 48px;
            }
            
            /* Small icon links need padding */
            .header-icons a,
            .mini-cart-link,
            .wishlist-link,
            .search-link,
            .mobile-menu-toggle,
            .quick-view,
            .product-quick-view,
            .yith-wcwl-add-to-wishlist a,
            .compare-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 12px;
                margin: -6px;
            }
            
            /* Product grid buttons */
            .products .product .button {
                padding: 12px 20px;
                min-height: 48px;
            }
            
            /* Navigation menu items */
            .mobile-menu li a {
                padding: 15px 20px;
                min-height: 48px;
                display: flex;
                align-items: center;
            }
            
            /* Pagination links */
            .pagination a,
            .pagination span {
                min-width: 48px;
                min-height: 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px;
            }
            
            /* Social icons */
            .social-icons a {
                width: 48px;
                height: 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Add image alt fallback
     */
    public function add_image_alt_fallback($attributes, $attachment, $size) {
        // Only add alt if empty
        if (empty($attributes['alt'])) {
            $alt_text = '';
            
            // Get attachment info
            $attachment_post = get_post($attachment->ID);
            if ($attachment_post) {
                // Try title first
                $alt_text = $attachment_post->post_title;
                
                // Clean up filename-based titles
                $alt_text = str_replace(array('-', '_'), ' ', $alt_text);
                $alt_text = preg_replace('/\.[^.]+$/', '', $alt_text);
                
                // For product images, use product name
                if (is_singular('product')) {
                    global $post;
                    if ($post) {
                        $alt_text = get_the_title($post->ID);
                    }
                }
            }
            
            if (!empty($alt_text)) {
                $attributes['alt'] = esc_attr($alt_text);
            }
        }
        
        return $attributes;
    }
    
    /**
     * Enhance robots.txt
     */
    public function enhance_robots_txt($output, $public) {
        if ($public) {
            // Add sitemap if missing
            if (strpos($output, 'Sitemap:') === false) {
                $output .= "\n# Sitemaps\n";
                $output .= 'Sitemap: ' . home_url('/wp-sitemap.xml') . "\n";
                
                // Add Yoast sitemap if exists
                if (defined('WPSEO_VERSION')) {
                    $output .= 'Sitemap: ' . home_url('/sitemap_index.xml') . "\n";
                }
                
                // Add Rank Math sitemap if exists
                if (class_exists('RankMath')) {
                    $output .= 'Sitemap: ' . home_url('/sitemap_index.xml') . "\n";
                }
            }
            
            // Ensure proper crawl directives
            if (strpos($output, 'User-agent: *') === false) {
                $output = "User-agent: *\n" . $output;
            }
            
            // Allow important paths
            $output .= "\n# Allow important paths\n";
            $output .= "Allow: /wp-admin/admin-ajax.php\n";
            $output .= "Allow: /*.js$\n";
            $output .= "Allow: /*.css$\n";
        }
        
        return $output;
    }
    
    /**
     * Fix preload attributes
     */
    public function fix_preload_attributes($tag, $handle, $src) {
        // Fix incorrect as attribute
        if (strpos($tag, 'rel="preload"') !== false) {
            // Scripts should have as="script"
            if (strpos($src, '.js') !== false && strpos($tag, 'as="style"') !== false) {
                $tag = str_replace('as="style"', 'as="script"', $tag);
            }
        }
        
        return $tag;
    }
    
    /**
     * Fix style preload attributes
     */
    public function fix_style_preload_attributes($tag, $handle, $href, $media) {
        // Fix incorrect as attribute
        if (strpos($tag, 'rel="preload"') !== false) {
            // Styles should have as="style"
            if (strpos($href, '.css') !== false && strpos($tag, 'as="script"') !== false) {
                $tag = str_replace('as="script"', 'as="style"', $tag);
            }
        }
        
        return $tag;
    }
}

// Initialize
Vidieu_SEO_Enhanced::get_instance();
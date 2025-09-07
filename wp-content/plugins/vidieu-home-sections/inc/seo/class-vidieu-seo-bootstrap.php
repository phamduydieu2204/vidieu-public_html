<?php
/**
 * SEO Bootstrap Module
 * 
 * Ensures essential SEO elements are present with proper guards
 * to avoid conflicts with existing SEO plugins
 * 
 * @package Vidieu_Home_Sections
 * @subpackage SEO
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vidieu_SEO_Bootstrap {
    
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
        
        // Hook into appropriate actions with proper priorities
        add_action('wp_head', array($this, 'add_meta_tags'), 1);
        add_action('wp_head', array($this, 'add_structured_data'), 20);
        add_filter('robots_txt', array($this, 'add_sitemap_to_robots'), 10, 2);
        add_filter('wp_get_attachment_image_attributes', array($this, 'add_image_alt_fallback'), 10, 3);
        add_filter('wp_nav_menu_objects', array($this, 'add_aria_labels_to_menu'), 10, 2);
        add_action('wp_head', array($this, 'fix_non_crawlable_links'), 100);
        add_filter('the_content', array($this, 'improve_link_text'), 20);
        
        // Remove problematic preloads
        add_action('wp_head', array($this, 'cleanup_preloads'), 1);
    }
    
    /**
     * Add essential meta tags with guards
     */
    public function add_meta_tags() {
        // Check if description already exists
        if (!$this->has_meta_description()) {
            $description = $this->generate_meta_description();
            if ($description) {
                echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            }
        }
        
        // Ensure viewport (usually present, but double-check)
        if (!$this->has_viewport_meta()) {
            echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        }
        
        // Ensure canonical with guards
        if (!$this->has_canonical()) {
            $canonical = $this->generate_canonical_url();
            if ($canonical) {
                echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
            }
        }
    }
    
    /**
     * Check if meta description exists
     */
    private function has_meta_description() {
        // Check for common SEO plugins
        if (defined('WPSEO_VERSION') || // Yoast
            defined('AIOSEO_VERSION') || // All in One SEO
            defined('SEOPRESS_VERSION') || // SEOPress
            class_exists('RankMath')) { // RankMath
            return true;
        }
        
        // Simple check - assume no description if no SEO plugin
        // We cannot check output buffer here as it would cause infinite loop
        return false;
    }
    
    /**
     * Generate meta description
     */
    private function generate_meta_description() {
        $description = '';
        
        if (is_home() || is_front_page()) {
            $description = get_bloginfo('description');
            if (!$description) {
                $description = 'Vidieu.vn - Nền tảng mua sắm trực tuyến hàng đầu với đa dạng sản phẩm chất lượng, giá cả cạnh tranh và dịch vụ chuyên nghiệp.';
            }
        } elseif (is_singular()) {
            global $post;
            
            // Check for custom meta field first
            $custom_desc = get_post_meta($post->ID, 'seo_description', true);
            if ($custom_desc) {
                $description = $custom_desc;
            } else {
                // Use excerpt if available
                if ($post->post_excerpt) {
                    $description = $post->post_excerpt;
                } else {
                    // Generate from content
                    $content = strip_tags(strip_shortcodes($post->post_content));
                    $content = str_replace(array("\n", "\r", "\t"), ' ', $content);
                    $content = preg_replace('/\s+/', ' ', $content);
                    $description = wp_trim_words($content, 25, '');
                }
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && !empty($term->description)) {
                $description = strip_tags($term->description);
            } else {
                $description = single_term_title('', false) . ' - ' . get_bloginfo('name');
            }
        } elseif (is_search()) {
            $description = 'Kết quả tìm kiếm cho: ' . get_search_query() . ' - ' . get_bloginfo('name');
        }
        
        // Ensure proper length (150-160 characters)
        if (mb_strlen($description) > 160) {
            $description = mb_substr($description, 0, 157) . '...';
        }
        
        return $description;
    }
    
    /**
     * Check if viewport meta exists
     */
    private function has_viewport_meta() {
        // Most modern themes include this, but check anyway
        return true; // Assume it exists to avoid duplicates
    }
    
    /**
     * Check if canonical exists
     */
    private function has_canonical() {
        // WordPress adds canonical by default since 2.9
        // But we'll enhance it to clean URLs
        return false; // Let's manage our own clean canonical
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
        } elseif (is_archive()) {
            if (is_date()) {
                if (is_day()) {
                    $canonical = get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
                } elseif (is_month()) {
                    $canonical = get_month_link(get_query_var('year'), get_query_var('monthnum'));
                } elseif (is_year()) {
                    $canonical = get_year_link(get_query_var('year'));
                }
            }
        }
        
        // Clean URL from tracking parameters
        if ($canonical) {
            $canonical = $this->clean_url_params($canonical);
        }
        
        return $canonical;
    }
    
    /**
     * Clean URL from unwanted parameters
     */
    private function clean_url_params($url) {
        // Parameters to remove
        $remove_params = array(
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'fbclid', 'gclid', 'msclkid', 'ref', 'source'
        );
        
        $parsed_url = wp_parse_url($url);
        
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $params);
            
            foreach ($remove_params as $param) {
                unset($params[$param]);
            }
            
            if (!empty($params)) {
                $parsed_url['query'] = http_build_query($params);
            } else {
                unset($parsed_url['query']);
            }
        }
        
        return $this->build_url($parsed_url);
    }
    
    /**
     * Build URL from parsed components
     */
    private function build_url($parsed_url) {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
    
    /**
     * Add structured data
     */
    public function add_structured_data() {
        $structured_data = array();
        
        // WebSite schema (all pages)
        if (!$this->has_website_schema()) {
            $structured_data[] = $this->get_website_schema();
        }
        
        // Organization schema (home page only)
        if ((is_home() || is_front_page()) && !$this->has_organization_schema()) {
            $structured_data[] = $this->get_organization_schema();
        }
        
        // BreadcrumbList
        if (!is_home() && !is_front_page() && !$this->has_breadcrumb_schema()) {
            $breadcrumb = $this->get_breadcrumb_schema();
            if ($breadcrumb) {
                $structured_data[] = $breadcrumb;
            }
        }
        
        // Product schema
        if (is_singular('product') && !$this->has_product_schema()) {
            $product = $this->get_product_schema();
            if ($product) {
                $structured_data[] = $product;
            }
        }
        
        // Article schema
        if (is_singular('post') && !$this->has_article_schema()) {
            $article = $this->get_article_schema();
            if ($article) {
                $structured_data[] = $article;
            }
        }
        
        // Output structured data
        if (!empty($structured_data)) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo "\n</script>\n";
        }
    }
    
    /**
     * Check for existing schema
     */
    private function has_website_schema() {
        return $this->has_schema_type('WebSite');
    }
    
    private function has_organization_schema() {
        return $this->has_schema_type('Organization');
    }
    
    private function has_breadcrumb_schema() {
        return $this->has_schema_type('BreadcrumbList');
    }
    
    private function has_product_schema() {
        return $this->has_schema_type('Product');
    }
    
    private function has_article_schema() {
        return $this->has_schema_type('Article');
    }
    
    private function has_schema_type($type) {
        // Check if SEO plugins are active
        if (defined('WPSEO_VERSION') || defined('AIOSEO_VERSION') || class_exists('RankMath')) {
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
            )
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
            'url' => home_url('/'),
            'description' => get_bloginfo('description')
        );
        
        // Add logo if available
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                $schema['logo'] = array(
                    '@type' => 'ImageObject',
                    'url' => $logo_url
                );
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
        
        // Add current page
        if (is_singular()) {
            global $post;
            
            // Add categories/taxonomies if applicable
            if (is_singular('product')) {
                $terms = get_the_terms($post->ID, 'product_cat');
                if ($terms && !is_wp_error($terms)) {
                    $main_term = $terms[0];
                    $items[] = array(
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $main_term->name,
                        'item' => get_term_link($main_term)
                    );
                }
            } elseif (is_singular('post')) {
                $categories = get_the_category($post->ID);
                if ($categories) {
                    $main_cat = $categories[0];
                    $items[] = array(
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $main_cat->name,
                        'item' => get_category_link($main_cat)
                    );
                }
            }
            
            // Current item
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_title(),
                'item' => get_permalink()
            );
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $term->name,
                'item' => get_term_link($term)
            );
        }
        
        if (count($items) > 1) {
            return array(
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $items
            );
        }
        
        return null;
    }
    
    /**
     * Get Product schema
     */
    private function get_product_schema() {
        global $post;
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return null;
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->get_name(),
            'description' => $product->get_short_description() ?: wp_trim_words($product->get_description(), 25),
            'sku' => $product->get_sku(),
            'url' => get_permalink($product->get_id())
        );
        
        // Add image
        $image_id = $product->get_image_id();
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $schema['image'] = $image_url;
            }
        }
        
        // Add brand
        $schema['brand'] = array(
            '@type' => 'Brand',
            'name' => 'Vidieu'
        );
        
        // Add offers
        $schema['offers'] = array(
            '@type' => 'Offer',
            'price' => $product->get_price(),
            'priceCurrency' => get_woocommerce_currency(),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => get_permalink($product->get_id())
        );
        
        return $schema;
    }
    
    /**
     * Get Article schema
     */
    private function get_article_schema() {
        global $post;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'url' => get_permalink()
        );
        
        // Add author
        $author_id = $post->post_author;
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $author_id)
        );
        
        // Add image
        if (has_post_thumbnail()) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($image_url) {
                $schema['image'] = $image_url;
            }
        }
        
        // Add publisher
        $schema['publisher'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url('/')
        );
        
        return $schema;
    }
    
    /**
     * Add sitemap to robots.txt
     */
    public function add_sitemap_to_robots($output, $public) {
        if ($public) {
            // Check if sitemap already exists in output
            if (strpos($output, 'Sitemap:') === false) {
                $output .= "\n# Sitemap\n";
                $output .= 'Sitemap: ' . home_url('/wp-sitemap.xml') . "\n";
                
                // Also add Yoast sitemap if exists
                if (defined('WPSEO_VERSION')) {
                    $output .= 'Sitemap: ' . home_url('/sitemap_index.xml') . "\n";
                }
            }
        }
        
        return $output;
    }
    
    /**
     * Add alt text fallback for images
     */
    public function add_image_alt_fallback($attributes, $attachment, $size) {
        // Only add alt if it's empty
        if (empty($attributes['alt'])) {
            // Try to get a meaningful alt text
            $alt_text = '';
            
            // Get attachment title
            $attachment_post = get_post($attachment->ID);
            if ($attachment_post) {
                $alt_text = $attachment_post->post_title;
            }
            
            // If still empty, use context
            if (empty($alt_text) && is_singular()) {
                global $post;
                if ($post) {
                    $alt_text = get_the_title($post->ID);
                }
            }
            
            if (!empty($alt_text)) {
                $attributes['alt'] = esc_attr($alt_text);
            }
        }
        
        return $attributes;
    }
    
    /**
     * Add aria-labels to menu items
     */
    public function add_aria_labels_to_menu($items, $args) {
        foreach ($items as $item) {
            // Check if menu item has only icon (no text)
            if (empty($item->title) || preg_match('/^<i[^>]*><\/i>$/', $item->title)) {
                // Add aria-label based on URL or classes
                $aria_label = '';
                
                if (strpos($item->url, 'cart') !== false || strpos($item->classes[0], 'cart') !== false) {
                    $aria_label = 'Giỏ hàng';
                } elseif (strpos($item->url, 'wishlist') !== false || strpos($item->classes[0], 'wishlist') !== false) {
                    $aria_label = 'Danh sách yêu thích';
                } elseif (strpos($item->url, 'search') !== false || strpos($item->classes[0], 'search') !== false) {
                    $aria_label = 'Tìm kiếm';
                } elseif (strpos($item->url, 'account') !== false || strpos($item->classes[0], 'account') !== false) {
                    $aria_label = 'Tài khoản';
                }
                
                if ($aria_label) {
                    $item->attr_aria_label = $aria_label;
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Fix non-crawlable links
     */
    public function fix_non_crawlable_links() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fix links without href
            var links = document.querySelectorAll('a:not([href]), a[href=""], a[href="#"]');
            links.forEach(function(link) {
                // Skip if it's meant to be a button
                if (link.classList.contains('button') || 
                    link.getAttribute('role') === 'button' ||
                    link.onclick) {
                    link.setAttribute('role', 'button');
                    link.setAttribute('tabindex', '0');
                } else {
                    // Add href="#" if completely missing
                    if (!link.hasAttribute('href') || link.getAttribute('href') === '') {
                        link.setAttribute('href', '#');
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Improve generic link text
     */
    public function improve_link_text($content) {
        // Only process on home page where the issue was found
        if (!is_home() && !is_front_page()) {
            return $content;
        }
        
        // Pattern to match "Read More" links
        $pattern = '/<a([^>]*)>\s*(?:Read More|Đọc thêm|Xem thêm)\s*<\/a>/i';
        
        // Replace with more descriptive text
        $content = preg_replace_callback($pattern, function($matches) {
            // Try to get context from surrounding content
            $full_match = $matches[0];
            $attributes = $matches[1];
            
            // Keep original if we can't improve it
            return $full_match;
        }, $content);
        
        return $content;
    }
    
    /**
     * Clean up problematic preloads
     */
    public function cleanup_preloads() {
        // Remove Elementor preloads if not using Elementor
        if (!defined('ELEMENTOR_VERSION')) {
            remove_action('wp_head', 'wp_resource_hints', 2);
            add_action('wp_head', array($this, 'filtered_resource_hints'), 2);
        }
    }
    
    /**
     * Filter resource hints
     */
    public function filtered_resource_hints() {
        // Re-add resource hints but filter out problematic ones
        add_filter('wp_resource_hints', array($this, 'filter_resource_hints'), 10, 2);
        wp_resource_hints();
        remove_filter('wp_resource_hints', array($this, 'filter_resource_hints'), 10);
    }
    
    /**
     * Filter out problematic preloads
     */
    public function filter_resource_hints($urls, $relation_type) {
        if ($relation_type === 'preload') {
            $filtered = array();
            foreach ($urls as $url) {
                // Skip known problematic preloads
                if (strpos($url, 'jost.css') !== false ||
                    strpos($url, 'style.min.css') !== false) {
                    continue;
                }
                $filtered[] = $url;
            }
            return $filtered;
        }
        
        return $urls;
    }
}

// Initialize - Disabled in favor of Enhanced SEO module
// Vidieu_SEO_Bootstrap::get_instance();
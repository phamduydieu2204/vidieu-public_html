<?php
/**
 * Template for pages with sidebar mapping
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include renderer
require_once VD_HOME_PLUGIN_DIR . 'includes/class-vd-page-sidebar-renderer.php';

// Get mapping data
$mapping = isset($GLOBALS['vd_current_page_mapping']) ? $GLOBALS['vd_current_page_mapping'] : null;

if (!$mapping) {
    return;
}
?>

<div class="vd-container">
    <div class="vd-layout-wrapper">
        <?php echo VD_Page_Sidebar_Renderer::render_sidebar($mapping); ?>
        
        <div class="vd-main">
            <div class="vd-section-header">
                <h2 class="vd-section-title"><?php the_title(); ?></h2>
            </div>
            
            <div class="vd-content-wrapper">
                <?php 
                // Get page content
                while (have_posts()) : the_post();
                    the_content();
                endwhile;
                ?>
                
                <?php
                // Check if we should display products or posts
                if ($mapping->sidebar_type === 'product_categories' || 
                    ($mapping->sidebar_type === 'custom_taxonomy' && taxonomy_exists($mapping->sidebar_config))) {
                    
                    // Display products grid
                    echo do_shortcode('[vidieu_home_products per_page="12" columns="4"]');
                    
                } elseif ($mapping->sidebar_type === 'post_categories') {
                    
                    // Display posts grid
                    echo do_shortcode('[vidieu_home_posts per_page="9" columns="3"]');
                    
                } elseif ($mapping->sidebar_type === 'homepage_preset') {
                    
                    // Display both sections
                    ?>
                    <div class="vd-home-sections">
                        <div class="vd-section" data-section="products">
                            <?php echo do_shortcode('[vidieu_home_products per_page="12" columns="4" title="Products"]'); ?>
                        </div>
                        
                        <div class="vd-section" data-section="posts" style="display: none;">
                            <?php echo do_shortcode('[vidieu_home_posts per_page="9" columns="3" title="Posts"]'); ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
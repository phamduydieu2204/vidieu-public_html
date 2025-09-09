<?php
if (!isset($nasa_opt)) :
    global $nasa_opt;
endif;

global $product;

$slideHoz = false;
if (
    isset($nasa_opt['product_detail_layout']) && $nasa_opt['product_detail_layout'] === 'classic' &&
    isset($nasa_opt['product_thumbs_style']) && $nasa_opt['product_thumbs_style'] === 'hoz'
) :
    $slideHoz = true; 
endif;

$main_class .= ' classic-layout medium-12';

$class_image = 'large-6 small-12 columns product-gallery rtl-right';
$class_info = 'large-6 small-12 columns product-info summary entry-summary left rtl-left';

$dots = isset($nasa_opt['product_slide_dot']) && $nasa_opt['product_slide_dot'] ? 'true' : 'false';
$auto = isset($nasa_opt['product_slide_auto']) && $nasa_opt['product_slide_auto'] ? 'true' : 'false';

if ($slideHoz) :
    $class_image = 'large-6 small-12 columns product-gallery rtl-right desktop-padding-right-20 rtl-desktop-padding-right-10 rtl-desktop-padding-left-20';
    $class_info = 'large-6 small-12 columns product-info summary entry-summary left rtl-left';
    
    $dots = 'false';
endif;

$main_attrs = apply_filters('nasa_single_product_classic_slide_attrs', array(
    'class="' . esc_attr($main_class) . '"',
    'data-num_main="1"',
    'data-num_thumb="6"',
    'data-speed="300"',
    'data-dots="' . esc_attr($dots) . '"',
    'data-autoplay="' . esc_attr($auto) . '"',
));
?>

<div id="product-<?php echo (int) $product->get_id(); ?>" <?php post_class(); ?>>
    <?php if ($nasa_actsidebar && $nasa_sidebar != 'no') : ?>
        <div class="div-toggle-sidebar center">
            <a class="toggle-sidebar" href="javascript:void(0);" rel="nofollow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="26px" height="24px" fill="currentColor"><path d="M 4 7 L 4 9 L 28 9 L 28 7 Z M 4 15 L 4 17 L 28 17 L 28 15 Z M 4 23 L 4 25 L 28 25 L 28 23 Z"></path></svg>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="row nasa-product-details-page nasa-layout-classic">
        <div<?php echo !empty($main_attrs) ? ' ' . implode(' ', $main_attrs) : ''; ?>>
            <div class="row focus-info">
                <div class="<?php echo $class_image; ?>"> 
                    <?php do_action('woocommerce_before_single_product_summary'); ?>
                </div>
                
                <div class="<?php echo $class_info; ?>">
                    <?php do_action('woocommerce_single_product_summary'); ?>
                </div>
            </div>
            
            <?php do_action('woocommerce_after_single_product_summary'); ?>

        </div>

        <?php if ($nasa_actsidebar && $nasa_sidebar != 'no') : ?>
            <div class="<?php echo esc_attr($bar_class); ?>">
                <a href="javascript:void(0);" title="<?php echo esc_attr__('Close', 'elessi-theme'); ?>" class="hidden-tag nasa-close-sidebar" rel="nofollow">
                    <?php echo esc_html__('Close', 'elessi-theme'); ?>
                </a>
                
                <div class="nasa-sidebar-off-canvas">
                    <?php dynamic_sidebar('product-sidebar'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

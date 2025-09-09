<?php

/**
 * Quantity Input
 * 
 * @author  NasaTheme
 * @package Elessi-theme/WooCommerce
 * @version 7.4.0
 */

defined('ABSPATH') || exit;

$label = !empty($args['product_name']) ? sprintf(esc_html__('%s quantity', 'elessi-theme'), wp_strip_all_tags($args['product_name'])) : esc_html__('Quantity', 'elessi-theme');

// In some cases we wish to display the quantity but not allow for it to be changed.
if ($max_value && $min_value === $max_value) :
    $is_readonly = true;
    $input_value = $min_value;
else :
    $is_readonly = false;
endif;

$classes = isset($classes) ? $classes : array('input-text', 'qty', 'text');

$mini_cart = isset($mini_cart) && $mini_cart ? true : false;
?>

<div class="quantity<?php echo $is_readonly ? ' qnot' : ''; ?>">
    <?php
    /**
     * Hook to output something before the quantity input field.
     *
     * @since 7.2.0
     */
    do_action('woocommerce_before_quantity_input_field');
    ?>

    <label class="screen-reader-text hidden-tag" for="<?php echo esc_attr($input_id); ?>">
        <?php echo $label; ?>
    </label>

    <?php if (!$is_readonly) : ?>
        <a href="javascript:void(0);" class="plus" rel="nofollow">&plus;</a>
    <?php endif; ?>

    <?php if ((!$mini_cart) || (!$is_readonly && $mini_cart)) : ?>
        <input 
            type="<?php echo $is_readonly ? 'text' : 'number'; ?>" 
            <?php wp_readonly($is_readonly); ?> 
            id="<?php echo esc_attr($input_id); ?>" 
            class="<?php echo esc_attr(join(' ', (array) $classes)); ?>" 
            name="<?php echo esc_attr($input_name); ?>" 
            value="<?php echo esc_attr($input_value); ?>"
            data-old="<?php echo esc_attr($input_value); ?>"
            title="<?php echo esc_attr_x('Qty', 'Product quantity input tooltip', 'elessi-theme'); ?>" 
            size="4" 
            min="<?php echo esc_attr($min_value); ?>" 
            max="<?php echo esc_attr(0 < $max_value ? $max_value : ''); ?>" 
            <?php if (!$is_readonly) : ?>
                step="<?php echo esc_attr($step); ?>" 
                placeholder="<?php echo esc_attr($placeholder); ?>" 
                inputmode="<?php echo esc_attr( $inputmode ); ?>" 
                autocomplete="<?php echo esc_attr(isset($autocomplete) ? $autocomplete : 'on'); ?>"
            <?php endif; ?>
        />
    <?php endif; ?>
        
    <?php echo $is_readonly && $mini_cart ? $input_value : ''; ?>
    
    <?php if (!$is_readonly) : ?>
        <a href="javascript:void(0);" class="minus" rel="nofollow">&minus;</a>
    <?php endif; ?>

    <?php
    /**
     * Hook to output something after quantity input field
     *
     * @since 3.6.0
     */
    do_action('woocommerce_after_quantity_input_field');
    ?>
</div>
<?php

<?php
$title = isset($instance['title']) ? $instance['title'] : '';
$title_font_size = isset($instance['title_font_size']) ? $instance['title_font_size'] : 'm';

$glb_link = isset($instance['glb_link']) ? $instance['glb_link'] : '';
$glb_link_text = isset($instance['glb_link_text']) ? $instance['glb_link_text'] : 'See All&nbsp;<i class="fa fa-arrow-circle-right primary-color"></i>';

$column_number = isset($instance['column_number']) ? $instance['column_number'] : 5;
$column_number_small = isset($instance['column_number_small']) ? $instance['column_number_small'] : 2;
$column_number_tablet = isset($instance['column_number_tablet']) ? $instance['column_number_tablet'] : 4;

$el_class = isset($instance['el_class']) ? $instance['el_class'] : '';

$class_wrap = 'nasa-flex flex-wrap flex-items-' . ((int) $column_number) . ' medium-flex-items-' . ((int) $column_number_tablet) . ' small-flex-items-' . ((int) $column_number_small);
$class_wrap .= $el_class != '' ? ' ' . esc_attr($el_class) : '';
?>

<?php if ($title || $glb_link) :
    $class_title = 'nasa-dft nasa-title margin-bottom-15 nasa-flex jbw flex-wrap align-baseline nasa-' . $title_font_size;
    ?>
    <div class="<?php echo esc_attr($class_title); ?>">
        <h3 class="nasa-heading-title nasa-min-height margin-top-10"><?php echo $title ? esc_attr($title) : ''; ?></h3>

        <?php if ($glb_link) : ?>
            <a href="<?php echo esc_url($glb_link); ?>" class="nasa-bold fs-15 margin-top-10">
                <?php echo $glb_link_text; ?>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="nasa-image-box-grid">
    <div class="<?php echo $class_wrap; ?>">
        <?php
        foreach ($instance['boxgrid'] as $key => $args) :
            $_this->render_shortcode_text($args);
        endforeach;
        ?>
    </div>
</div>

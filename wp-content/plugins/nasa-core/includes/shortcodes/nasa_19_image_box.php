<?php

/**
 * Shortcode [nasa_image_box ...]
 * 
 * @global type $nasa_opt
 * @param type $atts
 * @param type $content
 * @return type
 */
function nasa_sc_image_box($atts = array(), $content = null) {
    $dfAttr = array(
        'link_text' => '',
        'link_target' => '',
        'alt' => '',
        'image' => '',
        'hide_in_m' => '',
        'el_class' => ''
    );
    extract(shortcode_atts($dfAttr, $atts));
    
    if (isset($hide_in_m) && $hide_in_m == 1) {
        global $nasa_opt;
        
        $el_class .=  $el_class != '' ? ' hide-for-small' : 'hide-for-small';
        
        if (isset($nasa_opt['nasa_in_mobile']) && $nasa_opt['nasa_in_mobile']) {
            return '';
        }
    }

    if ($image && $alt) {
        $img = wp_get_attachment_image_src($image, 'full');
        
        if (!$img) {
            return $content;
        }
        
        $open = $close = '';
        $wrap_attrs = array();
        
        $class_wrap = 'nasa-img-box nasa-flex flex-nowrap nasa-transition';
        $class_wrap .= $el_class != '' ? ' ' . trim($el_class) : '';
        $wrap_attrs[] = 'class="' . $class_wrap . '"';
        
        if ($link_text) {
            $wrap_attrs[] = 'href="' . esc_url($link_text) . '"';
            
            if ($link_target) {
               $wrap_attrs[] =  'target="' . esc_attr($link_target) . '"';
            }
            
            $wrap_attrs[] =  'title="' . esc_attr($alt) . '"';
            
            $open = '<a ' . implode(' ', $wrap_attrs) . '>';
            $close = '</a>';
        } else {
            $open = '<div ' . implode(' ', $wrap_attrs) . '>';
            $close = '</div>';
        }
        
        $inner_html = '<img src="' . esc_url($img[0]) . '" alt="' . esc_attr($alt) . '" width="' . absint($img[1]) . '" height="' . absint($img[2]) . '" />';
        $inner_html .= '<p class="img-text fs-17 nasa-bold margin-bottom-0 margin-left-10 rtl-margin-left-0 rtl-margin-right-10">' . $alt . '</p>';
        
        $content = '<div class="item-wrap">' . $open . $inner_html . $close . '</div>';
        
    }
    
    return $content;
}

/**
 * Shortcode [nasa_image_box_grid]...[/nasa_image_box_grid]
 * 
 * @param type $atts
 * @param type $content
 * @return type
 */
function nasa_sc_image_box_grid($atts = array(), $content = null) {
    $dfAttr = array(
        'title' => '',
        'title_font_size' => 's',
        'glb_link' => '',
        'glb_link_text' => 'See All&nbsp;<i class="fa fa-arrow-circle-right primary-color"></i>',
        'column_number' => '5',
        'column_number_tablet' => '4',
        'column_number_small' => '2',
        'el_class' => ''
    );
    extract(shortcode_atts($dfAttr, $atts));
    
    $class_wrap = 'nasa-flex flex-wrap flex-items-' . ((int) $column_number) . ' medium-flex-items-' . ((int) $column_number_tablet) . ' small-flex-items-' . ((int) $column_number_small);
    $class_wrap .= $el_class != '' ? ' ' . esc_attr($el_class) : '';
    
    ob_start();
    ?>
    <?php if ($title || $glb_link) :
        $class_title = 'nasa-dft nasa-title margin-bottom-15 nasa-flex jbw flex-wrap align-baseline nasa-' . $title_font_size;
        ?>
        <div class="<?php echo esc_attr($class_title); ?>">
            <h3 class="nasa-heading-title nasa-min-height margin-top-10"><?php echo $title ? esc_attr($title) : ''; ?></h3>
            
            <?php if ($glb_link) :
                $glb_link_text = !$glb_link_text ? 'See All&nbsp;<i class="fa fa-arrow-circle-right primary-color"></i>' : $glb_link_text;
                ?>
                <a href="<?php echo esc_url($glb_link); ?>" class="nasa-bold fs-15 margin-top-10">
                    <?php echo $glb_link_text; ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="nasa-image-box-grid">
        <div class="<?php echo $class_wrap; ?>">
            <?php echo do_shortcode($content); ?>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

// **********************************************************************// 
// ! Register New Element: Slider
// **********************************************************************//
function nasa_register_image_box(){
    // **********************************************************************// 
    // ! Register New Element: nasa Image Box
    // **********************************************************************//
    $params = array(
        "name" => esc_html__("Image Box", 'nasa-core'),
        "base" => "nasa_image_box",
        'icon' => 'icon-wpb-nasatheme',
        'description' => esc_html__("Image Box.", 'nasa-core'),
        "content_element" => true,
        "category" => 'Nasa Core',
        "params" => array(
            array(
                'type' => 'textfield',
                'heading' => esc_html__('ALT - Text', 'nasa-core'),
                'param_name' => 'alt',
                'admin_label' => true,
                'value' => '',
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('URL', 'nasa-core'),
                'param_name' => 'link_text',
                'admin_label' => true,
                'value' => '',
            ),
            array(
                "type" => "dropdown",
                "heading" => esc_html__('Target', 'nasa-core'),
                "param_name" => 'link_target',
                "value" => array(
                    esc_html__('Default', 'nasa-core') => '',
                    esc_html__('Blank', 'nasa-core') => '_blank'
                ),
                'std' => ''
            ),
            array(
                'type' => 'attach_image',
                'heading' => esc_html__('Image', 'nasa-core'),
                'param_name' => 'image',
                'value' => '',
                'admin_label' => true,
                'description' => esc_html__('Select images from media library.', 'nasa-core')
            ),
            array(
                "type" => "dropdown",
                "heading" => esc_html__('Hide in Mobile - Mobile Layout', 'nasa-core'),
                "param_name" => 'hide_in_m',
                "value" => array(
                    esc_html__('No, Thanks!', 'nasa-core') => '',
                    esc_html__('Yes, Please!', 'nasa-core') => '1'
                ),
                'std' => ''
            ),
            array(
                "type" => "textfield",
                "heading" => esc_html__("Extra class name", 'nasa-core'),
                "param_name" => "el_class",
                "description" => esc_html__("If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.", 'nasa-core')
            )
        )
    );
    vc_map($params);
    
    $params = array(
        "name" => esc_html__("Grid Images Box", 'nasa-core'),
        "base" => "nasa_image_box_grid",
        'icon' => 'icon-wpb-nasatheme',
        'description' => esc_html__("Grid Image Box", 'nasa-core'),
        "content_element" => true,
        "as_parent" => array('only' => 'nasa_image_box'),
        // "as_parent" => array('except' => 'nasa_image_box'),
        // "is_container" => true,
        'category' => 'Nasa Core',
        "params" => array(
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Title', 'nasa-core'),
                'param_name' => 'title',
                'admin_label' => true,
                'value' => '',
            ),
            array(
                "type" => "dropdown",
                "heading" => esc_html__('Title Font Size', 'nasa-core'),
                "param_name" => 'title_font_size',
                "value" => array(
                    esc_html__('X-Large', 'nasa-core') => 'xl',
                    esc_html__('Large', 'nasa-core') => 'l',
                    esc_html__('Medium', 'nasa-core') => 'm',
                    esc_html__('Small', 'nasa-core') => 's',
                    esc_html__('Tiny', 'nasa-core') => 't'
                ),
                "std" => 's'
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Global URL', 'nasa-core'),
                'param_name' => 'glb_link',
                'admin_label' => true,
                'value' => '',
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Global URL Text', 'nasa-core'),
                'param_name' => 'glb_link_text',
                'value' => 'See All&nbsp;<i class="fa fa-arrow-circle-right primary-color"></i>',
            ),
            array(
                "type" => "dropdown",
                "heading" => esc_html__('Columns Number', 'nasa-core'),
                "param_name" => "column_number",
                "value" => array(6, 5, 4, 3, 2, 1),
                "std" => 5,
            ),
            
            array(
                "type" => "dropdown",
                "heading" => esc_html__('Columns Number Small', 'nasa-core'),
                "param_name" => "column_number_small",
                "value" => array(3, 2, 1),
                "std" => 2,
            ),
            
            array(
                "type" => "dropdown",
                "heading" => esc_html__('Columns Number Tablet', 'nasa-core'),
                "param_name" => "column_number_tablet",
                "value" => array(5, 4, 3, 2, 1),
                "std" => 4,
            ),

            array(
                "type" => "textfield",
                "heading" => esc_html__("Extra class name", 'nasa-core'),
                "param_name" => "el_class",
                "description" => esc_html__("If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.", 'nasa-core')
            )
        ),
        "js_view" => 'VcColumnView'
    );

    vc_map($params);
}

<?php
/**
 * Widget for Elementor
 */
class Nasa_Rev_Slider_WGSC extends Nasa_Elementor_Widget {

    /**
     * 
     * Contructor
     */
    function __construct() {
        $this->shortcode = 'nasa_rev_slider';
        $this->widget_cssclass = 'nasa_rev_slider_wgsc';
        $this->widget_description = esc_html__('Display Revolution Slider', 'nasa-core');
        $this->widget_id = 'nasa_rev_slider_sc';
        $this->widget_name = esc_html__('Nasa - Revo Slider', 'nasa-core');
        $this->settings = array(
            'alias' => array(
                'type' => 'revslider',
                'std' => '',
                'label' => esc_html__('RevSlider Item', 'nasa-core')
            ),
            
            'alias_m' => array(
                'type' => 'revslider',
                'std' => '',
                'label' => esc_html__('RevSlider Item - Mobile Layout', 'nasa-core')
            ),
            
            'el_class' => array(
                'type' => 'text',
                'std' => '',
                'label' => esc_html__('Extra class name', 'nasa-core')
            )
        );

        parent::__construct();
    }
}

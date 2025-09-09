<?php
/**
 * Widget for Elementor
 */
class Nasa_Client_WGSC extends Nasa_Elementor_Widget {

    /**
     * 
     * Contructor
     */
    function __construct() {
        $this->shortcode = 'nasa_client';
        $this->widget_cssclass = 'nasa_client_wgsc';
        $this->widget_description = esc_html__('Display Testimonials', 'nasa-core');
        $this->widget_id = 'nasa_client_sc';
        $this->widget_name = esc_html__('Nasa Testimonials', 'nasa-core');
        $this->settings = array(
            'img_src' => array(
                'type' => 'attach_image',
                'std' => '',
                'label' => esc_html__('Avatar', 'nasa-core')
            ),
            
            'name' => array(
                'type' => 'text',
                'std' => '',
                'label' => esc_html__('Name', 'nasa-core')
            ),
            
            'style' => array(
                'type' => 'select',
                'std' => 'full',
                'label' => esc_html__('Style', 'nasa-core'),
                'options' => array(
                    'full' => esc_html__('Full', 'nasa-core'),
                    'simple' => esc_html__('Simple', 'nasa-core')
                )
            ),
            
            'company' => array(
                'type' => 'text',
                'std' => '',
                'label' => esc_html__('Job (Style => Full)', 'nasa-core')
            ),
            
            'content' => array(
                'type' => 'textarea',
                'std' => 'Some promo text',
                'label' => esc_html__('Testimonials Content Say', 'nasa-core')
            ),
            
            'text_align' => array(
                'type' => 'select',
                'std' => 'center',
                'label' => esc_html__('Align (Style => Full)', 'nasa-core'),
                'options' => array(
                    'center' => esc_html__('Center', 'nasa-core'),
                    'left' => esc_html__('Left', 'nasa-core'),
                    'right' => esc_html__('Right', 'nasa-core'),
                    'justify' => esc_html__('Justify', 'nasa-core'),
                )
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

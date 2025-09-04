<?php
namespace Nasa_Core\Nasa_Widgets;

use Elementor\Plugin;
use Elementor\Controls_Manager;
use Nasa_Core\Nasa_ELM_Widgets_Abs;

class Nasa_Pin_Products_Banner_Elm extends Nasa_ELM_Widgets_Abs {

    /**
     * @return string Shortcode name.
     */
    protected function _shortcode() {
        return 'nasa_pin_products_banner';
    }

    /**
     * Retrieve the widget name.
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'nasa-pin-products-banner';
    }

    /**
     * Retrieve the widget title.
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Nasa Pin Products Banner', 'nasa-core');
    }
    
    /**
     * Register controls.
     *
     * @access protected
     */
    protected function register_controls() {
        if (!NASA_CORE_IN_ADMIN) {
            return;
        }
        
        $this->start_controls_section(
            'section_menu',
            [
                'label' => __('Settings', 'nasa-core'),
            ]
        );
        
        $this->add_control(
            'pin_slug',
            [
                'label'   => __('Pin Selected', 'nasa-core'),
                'type'    => Controls_Manager::SELECT2,
                'default' => '',
                'options' => nasa_get_pin_arrays('nasa_pin_pb', true),
            ]
        );
        
        $this->add_control(
            'marker_style',
            [
                'label'   => __('Marker Style', 'nasa-core'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'price',
                'options' => [
                    'price' => __('Price icon', 'nasa-core'),
                    'plus' => __('Plus icon', 'nasa-core')
                ],
            ]
        );
        
        $this->add_control(
            'full_price_icon',
            [
                'label'        => __('Full Price', 'nasa-core'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'nasa-core'),
                'label_off'    => __('No', 'nasa-core'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );
        
        $this->add_control(
            'price_rounding',
            [
                'label'        => __('Price Rounding', 'nasa-core'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'nasa-core'),
                'label_off'    => __('No', 'nasa-core'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        
        $this->add_control(
            'show_img',
            [
                'label'        => __('Show Image', 'nasa-core'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'nasa-core'),
                'label_off'    => __('No', 'nasa-core'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );
        
        $this->add_control(
            'show_price',
            [
                'label'        => __('Show Price', 'nasa-core'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'nasa-core'),
                'label_off'    => __('No', 'nasa-core'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );
        
        $this->add_control(
            'pin_effect',
            [
                'label'   => __('Effect icons', 'nasa-core'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'no',
                'options' => [
                    'default' => __('Default', 'nasa-core'),
                    'yes' => __('Yes', 'nasa-core'),
                    'no' => __('No', 'nasa-core')
                ],
            ]
        );
        
        $this->add_control(
            'el_class',
            [
                'label'   => __('Extra class name', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     * 
     * @access protected
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $atts = [
            'pin_slug' => isset($settings['pin_slug']) ? $settings['pin_slug'] : '',
            'marker_style' => isset($settings['marker_style']) ? $settings['marker_style'] : 'price',
            'full_price_icon' => isset($settings['full_price_icon']) ? $settings['full_price_icon'] : '',
            'price_rounding' => isset($settings['price_rounding']) ? $settings['price_rounding'] : 'yes',
            'show_img' => isset($settings['show_img']) ? $settings['show_img'] : '',
            'show_price' => isset($settings['show_price']) ? $settings['show_price'] : '',
            'pin_effect' => isset($settings['pin_effect']) ? $settings['pin_effect'] : 'no',
            'el_class' => isset($settings['el_class']) ? $settings['el_class'] : '',
        ];
        
        $this->render_shortcode_text($atts);
    }
}

// Register Widgets.
Plugin::instance()->widgets_manager->register(new Nasa_Pin_Products_Banner_Elm());

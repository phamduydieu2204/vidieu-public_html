<?php
namespace Nasa_Core\Nasa_Widgets;

use Elementor\Plugin;
use Elementor\Controls_Manager;
use Nasa_Core\Nasa_ELM_Widgets_Abs;

/**
 * Nasa Share
 */
class Nasa_Share_Elm extends Nasa_ELM_Widgets_Abs {

    /**
     * @return string Shortcode name.
     */
    protected function _shortcode() {
        return 'nasa_share';
    }

    /**
     * Retrieve the widget name.
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'nasa-share';
    }

    /**
     * Retrieve the widget title.
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Nasa Share', 'nasa-core');
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
            'title',
            [
                'label'   => __('Title', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
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
            'title'     => isset($settings['title']) ? $settings['title'] : '',
            'el_class'  => isset($settings['el_class']) ? $settings['el_class'] : '',
        ];
        
        $this->render_shortcode_text($atts);
    }
}

/**
 * Nasa Follow
 */
class Nasa_Follow_Elm extends Nasa_ELM_Widgets_Abs {

    /**
     * @return string Shortcode name.
     */
    protected function _shortcode() {
        return 'nasa_follow';
    }

    /**
     * Retrieve the widget name.
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'nasa-follow';
    }

    /**
     * Retrieve the widget title.
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Nasa Follow', 'nasa-core');
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
            'title',
            [
                'label'   => __('Title', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'twitter',
            [
                'label'   => __('Twitter', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'facebook',
            [
                'label'   => __('Facebook', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'pinterest',
            [
                'label'   => __('Pinterest', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'email',
            [
                'label'   => __('Email', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'instagram',
            [
                'label'   => __('Instagram', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'rss',
            [
                'label'   => __('RSS', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'linkedin',
            [
                'label'   => __('Linkedin', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'youtube',
            [
                'label'   => __('Youtube', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'flickr',
            [
                'label'   => __('Flickr', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'telegram',
            [
                'label'   => __('Telegram', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'whatsapp',
            [
                'label'   => __('Whatsapp', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'amazon',
            [
                'label'   => __('Amazon', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'tumblr',
            [
                'label'   => __('Tumblr', 'nasa-core'),
                'type'    => Controls_Manager::TEXT,
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
            'title'         => isset($settings['title']) ? $settings['title'] : '',
            
            'twitter'       => isset($settings['twitter']) ? $settings['twitter'] : '',
            'facebook'      => isset($settings['facebook']) ? $settings['facebook'] : '',
            'pinterest'     => isset($settings['pinterest']) ? $settings['pinterest'] : '',
            'email'         => isset($settings['email']) ? $settings['email'] : '',
            'instagram'     => isset($settings['instagram']) ? $settings['instagram'] : '',
            'rss'           => isset($settings['rss']) ? $settings['rss'] : '',
            'linkedin'      => isset($settings['linkedin']) ? $settings['linkedin'] : '',
            'youtube'       => isset($settings['youtube']) ? $settings['youtube'] : '',
            'flickr'        => isset($settings['flickr']) ? $settings['flickr'] : '',
            'telegram'      => isset($settings['telegram']) ? $settings['telegram'] : '',
            'whatsapp'      => isset($settings['whatsapp']) ? $settings['whatsapp'] : '',
            'amazon'        => isset($settings['amazon']) ? $settings['amazon'] : '',
            'tumblr'        => isset($settings['tumblr']) ? $settings['tumblr'] : '',
            
            'el_class'      => isset($settings['el_class']) ? $settings['el_class'] : '',
        ];
        
        $this->render_shortcode_text($atts);
    }
}

// Register Widgets.
Plugin::instance()->widgets_manager->register(new Nasa_Share_Elm());
Plugin::instance()->widgets_manager->register(new Nasa_Follow_Elm());

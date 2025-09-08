<?php
/**
 * Re:plain Live Chat Integration
 *
 * @package VidieuHomeSections
 * @since 1.7.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Replain_Chat class
 */
class VD_Replain_Chat {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Re:plain widget ID
     */
    const REPLAIN_ID = 'd4320c9c-f3b8-4e8f-bf80-aaf648924d5c';
    
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
        // Check if enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Don't load in admin
        if (is_admin()) {
            return;
        }
        
        // Hooks
        add_action('wp_footer', array($this, 'render_replain_script'), 100);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Check if Re:plain is enabled
     */
    private function is_enabled() {
        // Check constant first
        if (defined('VIDIEU_REPLAIN_ENABLE')) {
            return VIDIEU_REPLAIN_ENABLE;
        }
        
        // Check filter
        return apply_filters('vidieu_replain_enable', true);
    }
    
    /**
     * Check if should hide on mobile
     */
    private function should_hide_on_mobile() {
        // Check constant first
        if (defined('VIDIEU_REPLAIN_HIDE_ON_MOBILE')) {
            return VIDIEU_REPLAIN_HIDE_ON_MOBILE;
        }
        
        // Check filter
        return apply_filters('vidieu_replain_hide_on_mobile', true);
    }
    
    
    /**
     * Render Re:plain script
     */
    public function render_replain_script() {
        // Don't render in admin
        if (is_admin()) {
            return;
        }
        
        $hide_on_mobile = $this->should_hide_on_mobile();
        
        ?>
        <!-- Re:plain Live Chat -->
        <script>
        window.replainSettings = { 
            id: '<?php echo esc_js(self::REPLAIN_ID); ?>'
        };
        
        // Vidieu Re:plain helper
        window.VidieuReplain = {
            isLoaded: false,
            isLoading: false,
            callbacks: [],
            
            // Main open method
            open: function() {
                var self = this;
                
                // If already loaded, try to open
                if (this.isLoaded) {
                    this.tryOpen();
                    return;
                }
                
                // If loading, add to callbacks
                if (this.isLoading) {
                    this.callbacks.push(function() {
                        self.tryOpen();
                    });
                    return;
                }
                
                // Load Re:plain on demand
                this.loadReplain(function() {
                    self.tryOpen();
                });
            },
            
            // Try different methods to open Re:plain
            tryOpen: function() {
                // Try common Re:plain API methods
                if (typeof window.Replain !== 'undefined') {
                    if (typeof window.Replain.open === 'function') {
                        window.Replain.open();
                        return;
                    }
                    if (typeof window.Replain.show === 'function') {
                        window.Replain.show();
                        return;
                    }
                }
                
                // Try lowercase variant
                if (typeof window.replain !== 'undefined') {
                    if (typeof window.replain.open === 'function') {
                        window.replain.open();
                        return;
                    }
                    if (typeof window.replain.show === 'function') {
                        window.replain.show();
                        return;
                    }
                }
                
                // Try to find and click the widget
                var widget = document.querySelector('.replain-widget, #replain-widget, [class*="replain"]');
                if (widget) {
                    widget.style.display = 'block';
                    widget.style.visibility = 'visible';
                    
                    // Try to find and click the button
                    var button = widget.querySelector('button, [role="button"], .replain-button');
                    if (button) {
                        button.click();
                    }
                }
                
                // Log if nothing worked
                if (window.console && window.console.warn) {
                    console.warn('VidieuReplain: Could not find Re:plain API or widget');
                }
            },
            
            // Load Re:plain script dynamically
            loadReplain: function(callback) {
                var self = this;
                this.isLoading = true;
                
                if (callback) {
                    this.callbacks.push(callback);
                }
                
                var script = document.createElement('script');
                script.async = true;
                script.src = 'https://widget.replain.cc/dist/client.js';
                
                script.onload = function() {
                    self.isLoaded = true;
                    self.isLoading = false;
                    
                    // Add class to body for CSS targeting
                    document.body.classList.add('replain-loaded');
                    
                    // Wait a bit for Re:plain to initialize
                    setTimeout(function() {
                        // Execute all callbacks
                        for (var i = 0; i < self.callbacks.length; i++) {
                            self.callbacks[i]();
                        }
                        self.callbacks = [];
                    }, 500);
                };
                
                script.onerror = function() {
                    self.isLoading = false;
                    if (window.console && window.console.error) {
                        console.error('VidieuReplain: Failed to load Re:plain script');
                    }
                };
                
                var x = document.getElementsByTagName('script')[0];
                x.parentNode.insertBefore(script, x);
            }
        };
        
        // Always load Re:plain on both desktop and mobile
        (function(u){
            var s=document.createElement('script');
            s.async=true;
            s.src=u;
            var x=document.getElementsByTagName('script')[0];
            x.parentNode.insertBefore(s,x);
            
            // Mark as loaded when script loads
            s.onload = function() {
                window.VidieuReplain.isLoaded = true;
                // Add class to body for CSS targeting
                document.body.classList.add('replain-loaded');
            };
        })('https://widget.replain.cc/dist/client.js');
        </script>
        <!-- End Re:plain -->
        <?php
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        // Don't enqueue in admin
        if (is_admin()) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'vd-replain-chat',
            VD_HOME_PLUGIN_URL . 'assets/css/replain-chat.css',
            array(),
            VD_HOME_VERSION
        );
        
        // Enqueue JS for mobile integration
        if (wp_is_mobile() && $this->should_hide_on_mobile()) {
            wp_enqueue_script(
                'vd-replain-mobile',
                VD_HOME_PLUGIN_URL . 'assets/js/replain-mobile.js',
                array('jquery'),
                VD_HOME_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('vd-replain-mobile', 'vd_replain', array(
                'i18n' => array(
                    'chat_label' => __('Chat trực tiếp (Re:plain)', VD_HOME_TEXT_DOMAIN),
                    'chat_description' => __('Hỗ trợ trực tuyến', VD_HOME_TEXT_DOMAIN)
                )
            ));
        }
    }
}

// Initialize
VD_Replain_Chat::get_instance();
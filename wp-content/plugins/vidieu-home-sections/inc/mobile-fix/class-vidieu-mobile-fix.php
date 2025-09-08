<?php
/**
 * Fix mobile display issues with NASA theme
 * 
 * @package VidieuHomeSections
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vidieu Mobile Fix Class
 */
class Vidieu_Mobile_Fix {
    
    /**
     * Instance
     */
    private static $instance = null;
    
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
        // Force load mobile CSS for mobile viewports
        add_action('wp_enqueue_scripts', array($this, 'force_mobile_css'), 999);
        
        // Add body class for mobile detection
        add_filter('body_class', array($this, 'add_mobile_body_class'));
        
        // Add inline CSS to handle Chrome DevTools mobile mode
        add_action('wp_head', array($this, 'add_viewport_css'), 999);
    }
    
    /**
     * Force load mobile CSS when needed
     */
    public function force_mobile_css() {
        // Get theme URI
        $theme_uri = defined('ELESSI_THEME_URI') ? ELESSI_THEME_URI : get_template_directory_uri();
        
        // Check if file exists
        $mobile_css_path = get_template_directory() . '/assets/css/style-mobile.css';
        if (!file_exists($mobile_css_path)) {
            return;
        }
        
        // Force enqueue mobile CSS
        wp_enqueue_style(
            'elessi-style-mobile-force',
            $theme_uri . '/assets/css/style-mobile.css',
            array(),
            null
        );
    }
    
    /**
     * Add mobile body class based on viewport
     */
    public function add_mobile_body_class($classes) {
        // Add a JavaScript-detectable class
        $classes[] = 'vidieu-mobile-check';
        
        return $classes;
    }
    
    /**
     * Add viewport-based CSS
     */
    public function add_viewport_css() {
        ?>
        <style id="vidieu-mobile-viewport-fix">
            /* Force NASA mobile classes for mobile viewports */
            @media only screen and (max-width: 768px) {
                body.vidieu-mobile-check {
                    /* Simulate nasa-in-mobile class behavior */
                }
                
                /* Ensure mobile menu displays correctly */
                body.vidieu-mobile-check .nasa-menu-for-mobile {
                    display: block !important;
                }
                
                /* Fix font sizes and element scaling */
                body.vidieu-mobile-check {
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                    text-size-adjust: 100%;
                }
                
                /* Fix button and link sizes */
                body.vidieu-mobile-check a,
                body.vidieu-mobile-check button,
                body.vidieu-mobile-check input[type="button"],
                body.vidieu-mobile-check input[type="submit"] {
                    min-height: 44px;
                    min-width: 44px;
                    touch-action: manipulation;
                }
            }
        </style>
        
        <script id="vidieu-mobile-detection">
            // Enhanced mobile detection for Chrome DevTools
            (function() {
                function checkMobileViewport() {
                    var isMobileViewport = window.innerWidth <= 768;
                    var body = document.body;
                    
                    if (isMobileViewport) {
                        // Add mobile class
                        if (!body.classList.contains('nasa-in-mobile')) {
                            body.classList.add('nasa-in-mobile');
                        }
                        
                        // Trigger NASA theme mobile initialization if needed
                        if (typeof jQuery !== 'undefined' && jQuery(body).length) {
                            jQuery(body).trigger('nasa_mobile_mode_init');
                        }
                    } else {
                        // Remove mobile class for desktop
                        body.classList.remove('nasa-in-mobile');
                    }
                }
                
                // Check on load
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', checkMobileViewport);
                } else {
                    checkMobileViewport();
                }
                
                // Check on resize
                var resizeTimer;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(checkMobileViewport, 250);
                });
                
                // Force mobile CSS load if in mobile viewport
                if (window.innerWidth <= 768) {
                    // Create style element for mobile CSS if not already loaded
                    if (!document.getElementById('elessi-style-mobile-css') && !document.getElementById('elessi-style-mobile-force-css')) {
                        var link = document.createElement('link');
                        link.id = 'elessi-style-mobile-force-css';
                        link.rel = 'stylesheet';
                        link.type = 'text/css';
                        link.href = '<?php echo defined('ELESSI_THEME_URI') ? ELESSI_THEME_URI : get_template_directory_uri(); ?>/assets/css/style-mobile.css';
                        document.head.appendChild(link);
                    }
                }
            })();
        </script>
        <?php
    }
}

// Initialize
Vidieu_Mobile_Fix::get_instance();
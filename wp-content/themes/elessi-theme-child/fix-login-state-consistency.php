<?php
/**
 * Fix Login State Consistency
 * Ensures login state is consistent across popup login and my-account page
 * 
 * @package Elessi-theme-child
 * @since 2025-08-31
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Force reload after AJAX login to ensure session is properly set
 */
add_action('wp_footer', 'vidieu_fix_ajax_login_redirect');
function vidieu_fix_ajax_login_redirect() {
    if (!is_user_logged_in()) {
        ?>
        <script>
        (function($) {
            'use strict';
            
            
            if (typeof $ === 'undefined') return;
            
            // Listen for NASA theme login success
            $(document).on('nasa_login_success', function(e, data) {
                // Force page reload to sync session
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            });
            
            // Also listen for WooCommerce login success
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url && settings.url.includes('admin-ajax.php')) {
                    if (settings.data && (settings.data.includes('nasa_login_ajax') || settings.data.includes('woocommerce_login'))) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success || response.loggedin) {
                                // Login successful, reload page
                                setTimeout(function() {
                                    window.location.reload();
                                }, 500);
                            }
                        } catch (e) {
                        }
                    }
                }
            });
            
        })(jQuery);
        </script>
        <?php
    }
}

/**
 * Redirect logged-in users from login page to account dashboard
 */
add_action('template_redirect', 'vidieu_redirect_logged_in_users');
function vidieu_redirect_logged_in_users() {
    if (is_user_logged_in() && is_account_page()) {
        // Check if user is on login/register form
        global $wp;
        
        
        if (empty($wp->query_vars)) {
            // User is logged in but on login form, redirect to dashboard
            wp_safe_redirect(wc_get_account_endpoint_url('dashboard'));
            exit;
        }
    }
}

/**
 * Fix NASA theme login/logout link text
 */
add_filter('wp_nav_menu_items', 'vidieu_fix_login_menu_text', 999, 2);
function vidieu_fix_login_menu_text($items, $args) {
    if (is_user_logged_in()) {
        // Replace Login/Register with My Account for logged in users
        $items = str_replace(
            array('Login/Register', 'Login / Register', 'Đăng nhập/Đăng ký'),
            'My Account',
            $items
        );
    }
    return $items;
}

/**
 * Ensure proper nonce verification for AJAX requests
 */
add_action('init', 'vidieu_fix_ajax_nonce_verification', 1);
function vidieu_fix_ajax_nonce_verification() {
    // Regenerate nonce if user state changed
    if (is_user_logged_in() && !wp_verify_nonce(wp_create_nonce('nasa-ajax-nonce'), 'nasa-ajax-nonce')) {
        // Force nonce refresh
        add_action('wp_footer', function() {
            ?>
            <script>
            if (typeof nasa_ajax_params !== 'undefined') {
                // Update nonce for NASA theme
                nasa_ajax_params.ajax_nonce = '<?php echo wp_create_nonce('nasa-ajax-nonce'); ?>';
            }
            </script>
            <?php
        });
    }
}

/**
 * Add body class for login state
 */
add_filter('body_class', 'vidieu_login_state_body_class');
function vidieu_login_state_body_class($classes) {
    if (is_user_logged_in()) {
        $classes[] = 'vidieu-user-logged-in';
    } else {
        $classes[] = 'vidieu-user-logged-out';
    }
    return $classes;
}

/**
 * Fix my account page content for logged in users
 */
add_action('woocommerce_before_account_navigation', 'vidieu_check_login_on_account_page', 5);
function vidieu_check_login_on_account_page() {
    if (!is_user_logged_in()) {
        // User not logged in, let WooCommerce handle it
        return;
    }
    
    // User is logged in, ensure we're not showing login form
    global $wp;
    if (empty($wp->query_vars) || (isset($wp->query_vars['pagename']) && $wp->query_vars['pagename'] == 'my-account')) {
        // Force display of account dashboard
        ?>
        <script>
        // Hide login form if visible
        document.addEventListener('DOMContentLoaded', function() {
            var loginForm = document.querySelector('.woocommerce-form-login');
            if (loginForm) {
                loginForm.style.display = 'none';
            }
            
            // Show account navigation if hidden
            var accountNav = document.querySelector('.woocommerce-MyAccount-navigation');
            if (accountNav) {
                accountNav.style.display = 'block';
            }
        });
        </script>
        <?php
    }
}

/**
 * Clear all sessions and cookies on logout to prevent issues
 */
add_action('wp_logout', 'vidieu_clear_sessions_on_logout');
function vidieu_clear_sessions_on_logout() {
    // Clear WooCommerce session
    if (class_exists('WC_Session_Handler')) {
        $session = new WC_Session_Handler();
        $session->destroy_session();
    }
    
    // Clear WordPress auth cookies
    wp_clear_auth_cookie();
}

/**
 * Add JavaScript to handle login state sync
 */
add_action('wp_footer', 'vidieu_sync_login_state_js');
function vidieu_sync_login_state_js() {
    ?>
    <script>
    (function() {
        'use strict';
        
        // Check if user is logged in via body class
        var isLoggedIn = document.body.classList.contains('logged-in');
        
        // Update login/register link text based on state
        function updateLoginLinkText() {
            
            // Find all my-account links and NASA login links
            var loginLinks = document.querySelectorAll('a[href*="my-account"], .nasa-login-register-ajax');
            
            
            loginLinks.forEach(function(link, index) {
                var linkText = link.textContent.trim();
                
                if (isLoggedIn) {
                    if (linkText.includes('Login') || linkText.includes('Đăng nhập')) {
                        link.textContent = 'My Account';
                    }
                } else {
                    if (linkText === 'My Account' || linkText === 'Tài khoản') {
                        link.textContent = 'Login/Register';
                    }
                }
            });
        }
        
        // Run on page load
        updateLoginLinkText();
        
        // Also run after AJAX requests
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxComplete(function() {
                setTimeout(updateLoginLinkText, 100);
            });
        }
        
        // Fix my account page display
        if (window.location.href.includes('/my-account')) {
            var loginForm = document.querySelector('.woocommerce-form-login');
            var registerForm = document.querySelector('.woocommerce-form-register');
            var accountContent = document.querySelector('.woocommerce-MyAccount-content');
            var accountNav = document.querySelector('.woocommerce-MyAccount-navigation');
            
            if (isLoggedIn) {
                // Hide login form and show dashboard
                if (loginForm) {
                    loginForm.style.display = 'none';
                }
                if (registerForm) {
                    registerForm.style.display = 'none';
                }
                if (accountContent) {
                    accountContent.style.display = 'block';
                }
                if (accountNav) {
                    accountNav.style.display = 'block';
                }
            }
        }
    })();
    </script>
    <?php
}

/**
 * Force correct template on my-account page for logged in users
 */
add_action('template_redirect', 'vidieu_force_account_dashboard_template', 5);
function vidieu_force_account_dashboard_template() {
    if (is_account_page() && is_user_logged_in()) {
        // Remove login form hooks
        remove_action('woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10);
        remove_action('woocommerce_login_form_start', 'woocommerce_output_all_notices', 10);
        
        // Add filter to skip login form
        add_filter('woocommerce_account_menu_items', function($items) {
            // Ensure dashboard is first item
            if (!isset($items['dashboard'])) {
                $items = array('dashboard' => __('Dashboard', 'woocommerce')) + $items;
            }
            return $items;
        }, 5);
    }
}
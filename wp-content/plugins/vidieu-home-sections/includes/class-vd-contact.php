<?php
/**
 * Contact form and information class
 *
 * @package VidieuHomeSections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VD_Contact class
 */
class VD_Contact {
    
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
        add_action('init', array($this, 'register_shortcode'));
        add_action('wp_ajax_vd_contact_submit', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_vd_contact_submit', array($this, 'handle_form_submission'));
    }
    
    /**
     * Register shortcode
     */
    public function register_shortcode() {
        add_shortcode('vd_contact', array($this, 'render_contact_section'));
    }
    
    /**
     * Render contact section
     */
    public function render_contact_section($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Liên hệ với chúng tôi',
            'show_map' => 'yes',
            'map_height' => '400',
            'company_name' => 'Vidieu.vn',
            'address' => 'Hà Nội, Việt Nam',
            'lat' => '21.0285',
            'lng' => '105.8542'
        ), $atts, 'vd_contact');
        
        // Get current user info if logged in
        $current_user = wp_get_current_user();
        $user_name = '';
        $user_email = '';
        $user_phone = '';
        
        if ($current_user->ID > 0) {
            // User is logged in
            $user_name = $current_user->display_name;
            $user_email = $current_user->user_email;
            
            // Get phone from user meta (billing phone from WooCommerce)
            $user_phone = get_user_meta($current_user->ID, 'billing_phone', true);
            
            // If display name is empty or just username, try to get full name from billing
            if (empty($user_name) || $user_name == $current_user->user_login) {
                $first_name = get_user_meta($current_user->ID, 'billing_first_name', true);
                $last_name = get_user_meta($current_user->ID, 'billing_last_name', true);
                if ($first_name || $last_name) {
                    $user_name = trim($first_name . ' ' . $last_name);
                }
            }
        } else {
            // Check if user has filled checkout form before (via cookies/session)
            // WooCommerce stores this in session
            if (class_exists('WC') && WC()->session) {
                $customer = WC()->session->get('customer');
                if ($customer) {
                    $user_name = isset($customer['first_name']) && isset($customer['last_name']) 
                        ? trim($customer['first_name'] . ' ' . $customer['last_name']) 
                        : '';
                    $user_email = isset($customer['email']) ? $customer['email'] : '';
                    $user_phone = isset($customer['phone']) ? $customer['phone'] : '';
                }
            }
        }
        
        ob_start();
        ?>
        <div class="vd-contact-section">
            <div class="vd-container">
                <?php if (!empty($atts['title'])) : ?>
                    <h2 class="vd-contact-title"><?php echo esc_html($atts['title']); ?></h2>
                <?php endif; ?>
                
                <div class="vd-contact-content">
                    <div class="vd-contact-form-wrapper">
                        <h3 class="vd-contact-subtitle">Gửi tin nhắn cho chúng tôi</h3>
                        <form class="vd-contact-form" id="vd-contact-form">
                            <div class="vd-form-group">
                                <label for="vd_contact_name">Họ và tên <span class="required">*</span></label>
                                <input type="text" id="vd_contact_name" name="name" value="<?php echo esc_attr($user_name); ?>" required>
                            </div>
                            
                            <div class="vd-form-group">
                                <label for="vd_contact_email">Email <span class="required">*</span></label>
                                <input type="email" id="vd_contact_email" name="email" value="<?php echo esc_attr($user_email); ?>" required>
                            </div>
                            
                            <div class="vd-form-group">
                                <label for="vd_contact_phone">Số điện thoại</label>
                                <input type="tel" id="vd_contact_phone" name="phone" value="<?php echo esc_attr($user_phone); ?>">
                            </div>
                            
                            <div class="vd-form-group">
                                <label for="vd_contact_message">Nội dung <span class="required">*</span></label>
                                <textarea id="vd_contact_message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="vd-form-group">
                                <button type="submit" class="vd-contact-submit">
                                    <span class="vd-btn-text">Gửi tin nhắn</span>
                                    <span class="vd-btn-loading" style="display:none;">
                                        <svg class="vd-spinner" width="20" height="20" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="50.265" stroke-dashoffset="37.699" />
                                        </svg>
                                    </span>
                                </button>
                                <?php wp_nonce_field('vd_contact_form', 'vd_contact_nonce'); ?>
                            </div>
                            
                            <div class="vd-form-message"></div>
                        </form>
                    </div>
                    
                    <div class="vd-contact-info-wrapper">
                        <h3 class="vd-contact-subtitle">Thông tin liên hệ</h3>
                        <div class="vd-contact-info">
                            <a href="tel:0988691196" class="vd-contact-item vd-contact-link">
                                <div class="vd-contact-icon vd-contact-phone">
                                    <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
                                        <g transform="translate(0 -1028.4)">
                                            <path d="m23.015 1046.8c0 0.3-0.052 0.6-0.156 1.1-0.105 0.4-0.214 0.8-0.329 1-0.219 0.6-0.855 1.1-1.907 1.7-0.98 0.5-1.95 0.8-2.909 0.8h-0.828c-0.261-0.1-0.558-0.2-0.892-0.2-0.333-0.1-0.583-0.2-0.75-0.3-0.156 0-0.443-0.1-0.86-0.3s-0.672-0.2-0.766-0.3c-1.022-0.3-1.934-0.8-2.736-1.3-1.3346-0.8-2.7157-1.9-4.1438-3.3-1.4176-1.5-2.5381-2.9-3.3616-4.2-0.5003-0.8-0.9329-1.7-1.2977-2.7-0.0313-0.1-0.1251-0.4-0.2815-0.8-0.1563-0.4-0.2658-0.7-0.3283-0.9-0.0522-0.1-0.1251-0.4-0.2189-0.7s-0.1616-0.6-0.2033-0.9c-0.0313-0.3-0.0469-0.5-0.0469-0.8 0-1 0.2658-2 0.7974-2.9 0.5837-1.1 1.1362-1.7 1.6574-2 0.2606-0.1 0.615-0.2 1.0632-0.3 0.4586-0.1 0.8287-0.1 1.1101-0.1h0.3284c0.1876 0.1 0.4638 0.5 0.8287 1.2 0.1146 0.2 0.271 0.5 0.469 0.8 0.1981 0.4 0.3805 0.7 0.5473 1 0.1667 0.3 0.3283 0.6 0.4847 0.9 0.0312 0 0.1198 0.1 0.2658 0.4 0.1563 0.2 0.271 0.4 0.344 0.5 0.0729 0.2 0.1094 0.3 0.1094 0.5s-0.1511 0.4-0.4534 0.8c-0.2919 0.3-0.615 0.6-0.9694 0.8-0.344 0.3-0.6672 0.5-0.9694 0.8-0.2919 0.3-0.4378 0.6-0.4378 0.8 0 0.1 0.026 0.2 0.0781 0.3 0.0522 0.2 0.0939 0.3 0.1251 0.3 0.0417 0.1 0.1147 0.2 0.2189 0.4 0.1147 0.2 0.1772 0.3 0.1877 0.3 0.7922 1.4 1.699 2.7 2.7205 3.7 1.0213 1 2.2463 1.9 3.6743 2.7 0.021 0 0.12 0.1 0.297 0.2s0.302 0.2 0.375 0.2 0.178 0.1 0.313 0.1c0.146 0.1 0.266 0.1 0.36 0.1 0.187 0 0.427-0.1 0.719-0.4s0.568-0.6 0.829-1c0.26-0.3 0.547-0.7 0.86-1 0.312-0.3 0.573-0.4 0.781-0.4 0.146 0 0.292 0 0.438 0.1 0.157 0.1 0.344 0.2 0.563 0.3 0.219 0.2 0.349 0.3 0.391 0.3 0.261 0.2 0.537 0.3 0.829 0.5 0.302 0.2 0.635 0.3 1 0.5s0.647 0.4 0.845 0.5c0.729 0.4 1.125 0.7 1.188 0.8 0.031 0.1 0.047 0.2 0.047 0.4" fill="#fff"/>
                                            <path d="m1.2188 4.75c-0.1453 0.5076-0.2188 1.0294-0.2188 1.5312 0 0.282 0.0312 0.5414 0.0625 0.8126 0.0417 0.2608 0.0937 0.572 0.1875 0.9062 0.0938 0.3337 0.1666 0.5829 0.2188 0.75 0.0625 0.1564 0.1873 0.4575 0.3437 0.875 0.1564 0.417 0.25 0.656 0.2813 0.75 0.3648 1.023 0.7809 1.946 1.2812 2.75 0.8235 1.336 1.9574 2.695 3.375 4.125 1.428 1.419 2.7908 2.55 4.125 3.375 0.803 0.501 1.728 0.947 2.75 1.313 0.094 0.031 0.333 0.124 0.75 0.281 0.417 0.156 0.719 0.25 0.875 0.312 0.167 0.052 0.416 0.125 0.75 0.219s0.614 0.177 0.875 0.219c0.271 0.031 0.562 0.031 0.844 0.031 0.959 0 1.926-0.249 2.906-0.781 1.053-0.585 1.687-1.135 1.906-1.657 0.115-0.26 0.208-0.613 0.313-1.062 0.104-0.459 0.156-0.844 0.156-1.125 0-0.146 0-0.271-0.031-0.344-0.037-0.111-0.227-0.263-0.5-0.437-0.253 0.494-0.853 1.012-1.844 1.562-0.98 0.532-1.947 0.813-2.906 0.813-0.282 0-0.573-0.031-0.844-0.063-0.261-0.042-0.541-0.093-0.875-0.187s-0.583-0.167-0.75-0.219c-0.156-0.063-0.458-0.187-0.875-0.344-0.417-0.156-0.656-0.25-0.75-0.281-1.022-0.365-1.947-0.78-2.75-1.281-1.3342-0.825-2.697-1.956-4.125-3.375-1.4176-1.43-2.5515-2.821-3.375-4.157-0.5003-0.8032-0.9164-1.6954-1.2812-2.7182-0.0313-0.094-0.1249-0.3639-0.2813-0.7813-0.1564-0.4175-0.2812-0.6873-0.3437-0.8437-0.0522-0.1672-0.125-0.4164-0.2188-0.75-0.0224-0.0796-0.0119-0.1433-0.0312-0.2188z" transform="translate(0 1028.4)" fill="#c0392b"/>
                                        </g>
                                    </svg>
                                </div>
                                <div class="vd-contact-details">
                                    <span class="vd-contact-primary">0988 691 196</span>
                                    <span class="vd-contact-desc">Hỗ trợ gấp</span>
                                </div>
                            </a>
                            
                            <a href="mailto:admin@vidieu.vn" class="vd-contact-item vd-contact-link">
                                <div class="vd-contact-icon vd-contact-email">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 8L10.8906 13.2604C11.5624 13.7083 12.4376 13.7083 13.1094 13.2604L21 8M5 19H19C20.1046 19 21 18.1046 21 17V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V17C3 18.1046 3.89543 19 5 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="vd-contact-details">
                                    <span class="vd-contact-primary">admin@vidieu.vn</span>
                                    <span class="vd-contact-desc">Phản hồi trong 24h</span>
                                </div>
                            </a>
                            
                            <a href="https://zalo.me/g/hwcfvo585" target="_blank" rel="noopener" class="vd-contact-item vd-contact-link">
                                <div class="vd-contact-icon vd-contact-zalo">
                                    <svg viewBox="0 0 161.5 161.5" width="24" height="24" aria-hidden="true">
                                        <!-- viền/nền -->
                                        <path class="bg" fill="#0068FF" d="M73.29,0.54h14.31c19.66,0,31.15,2.89,41.35,8.36a56.65,56.65,0,0,1,23.65,23.65c5.47,10.2,8.36,21.69,8.36,41.35V88.15c0,19.66-2.89,31.15-8.36,41.35a56.65,56.65,0,0,1-23.65,23.65c-10.2,5.47-21.69,8.36-41.35,8.36H73.35c-19.66,0-31.15-2.89-41.35-8.36a56.65,56.65,0,0,1-23.65-23.65c-5.47-10.2-8.36-21.69-8.36-41.35V73.89c0-19.66,2.89-31.15,8.36-41.35A56.65,56.65,0,0,1,32,8.89C42.14,3.43,53.69,0.54,73.29,0.54Z"></path>
                                        <path class="shadow" fill="#0055d4" opacity="0.3" d="M160.96,85.75v2.35c0,19.66-2.89,31.15-8.35,41.35a56.65,56.65,0,0,1-23.65,23.65c-10.2,5.47-21.69,8.36-41.35,8.36H73.35c-16.09,0-26.7-1.93-35.62-5.63L23.04,140.75Z"></path>
                                        <path class="inner" fill="#fff" d="M24.67,141.26c7.53.83,16.94-1.31,23.62-4.56,29,16,74.38,15.27,101.84-2.3q1.6-2.4,3-5c5.49-10.24,8.39-21.77,8.39-41.5v-14.3c0-19.73-2.9-31.26-8.39-41.5a56.86,56.86,0,0,0-23.74-23.74c-10.24-5.49-21.77-8.39-41.5-8.39H73.51c-16.8,0-27.71,2.12-36.88,6.15q-.75.67-1.47,1.37c-26.89,25.92-28.93,82.11-6.13,112.64l.08.14c3.51,5.18.12,14.24-5.18,19.55C23.07,140.64,23.38,141.14,24.67,141.26Z"></path>
                                        <!-- chữ/biểu tượng -->
                                        <path class="fg" fill="#0068FF" d="M66.1,55.09H34.59v6.76h21.87l-21.56,26.72a6.06,6.06,0,0,0-1.17,4v1.72h29.73a2.73,2.73,0,0,0,2.7-2.7v-3.62h-23l20.27-25.43,1.11-1.35.12-.18a8,8,0,0,0,1.41-5Z"></path>
                                        <path class="fg" fill="#0068FF" d="M106.22,94.29H110.75v-39.2h-6.76v36.92A2.27,2.27,0,0,0,106.22,94.29Z"></path>
                                        <path class="fg" fill="#0068FF" d="M83.12,63.82a15.36,15.36,0,1,0,15.36,15.36A15.36,15.36,0,0,0,83.12,63.82Zm0,24.39a9,9,0,1,1,9-9A9,9,0,0,1,83.12,88.21Z"></path>
                                        <path class="fg" fill="#0068FF" d="M130.67,63.57A15.48,15.48,0,1,0,146.15,79.05,15.5,15.5,0,0,0,130.67,63.57Zm0,24.64a9.09,9.09,0,1,1,9.09-9.09A9.07,9.07,0,0,1,130.67,88.21Z"></path>
                                        <path class="fg" fill="#0068FF" d="M94.92,94.29h3.62V64.68h-6.33v27A2.72,2.72,0,0,0,94.92,94.29Z"></path>
                                    </svg>
                                </div>
                                <div class="vd-contact-details">
                                    <span class="vd-contact-primary">Nhóm Zalo</span>
                                    <span class="vd-contact-desc">Voucher nội bộ</span>
                                </div>
                            </a>
                            
                            <a href="https://m.me/vidieuvn.muatoolAmazon" target="_blank" rel="noopener" class="vd-contact-item vd-contact-link">
                                <div class="vd-contact-icon vd-contact-messenger">
                                    <svg viewBox="0 0 800 800" width="24" height="24" aria-hidden="true">
                                        <radialGradient id="msgGrad" cx="101.9" cy="809" r="1.1" gradientTransform="matrix(800 0 0 -800 -81386 648000)" gradientUnits="userSpaceOnUse">
                                            <stop offset="0" stop-color="#09f"></stop>
                                            <stop offset=".6" stop-color="#a033ff"></stop>
                                            <stop offset=".9" stop-color="#ff5280"></stop>
                                            <stop offset="1" stop-color="#ff7061"></stop>
                                        </radialGradient>
                                        <path fill="url(#msgGrad)" d="M400 0C174.7 0 0 165.1 0 388c0 116.6 47.8 217.4 125.6 287 6.5 5.8 10.5 14 10.7 22.8l2.2 71.2a32 32 0 0 0 44.9 28.3l79.4-35c6.7-3 14.3-3.5 21.4-1.6 36.5 10 75.3 15.4 115.8 15.4 225.3 0 400-165.1 400-388S625.3 0 400 0z"></path>
                                        <path fill="#fff" d="m159.8 501.5 117.5-186.4a60 60 0 0 1 86.8-16l93.5 70.1a24 24 0 0 0 28.9-.1l126.2-95.8c16.8-12.8 38.8 7.4 27.6 25.3L522.7 484.9a60 60 0 0 1-86.8 16l-93.5-70.1a24 24 0 0 0-28.9.1l-126.2 95.8c-16.8 12.8-38.8-7.3-27.5-25.2z"></path>
                                    </svg>
                                </div>
                                <div class="vd-contact-details">
                                    <span class="vd-contact-primary">Facebook Messenger</span>
                                    <span class="vd-contact-desc">Phản hồi nhanh nhất</span>
                                </div>
                            </a>
                        </div>
                        
                        <!-- Working hours section -->
                        <div class="vd-contact-hours">
                            <h3 class="vd-hours-title">Thời gian làm việc</h3>
                            <div class="vd-hours-content">
                                <div class="vd-hours-item">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="#3498db" stroke-width="2"/>
                                        <path d="M12 6V12L16 14" stroke="#3498db" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span><strong>8:00 - 21:00</strong> (Thứ 2 - Thứ 7)</span>
                                </div>
                                <div class="vd-hours-item">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#e74c3c" stroke-width="2"/>
                                        <path d="M3 9H21" stroke="#e74c3c" stroke-width="2"/>
                                        <path d="M8 13H8.01M12 13H12.01M16 13H16.01M8 17H8.01M12 17H12.01M16 17H16.01" stroke="#e74c3c" stroke-width="3" stroke-linecap="round"/>
                                    </svg>
                                    <span><strong>Nghỉ Chủ Nhật</strong></span>
                                </div>
                                <div class="vd-hours-note">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" fill="#27ae60"/>
                                        <path d="M12 8V12M12 16H12.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span>Đơn hàng được xử lý tự động 24/7</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($atts['show_map'] === 'yes') : ?>
                <div class="vd-contact-map" style="height: <?php echo absint($atts['map_height']); ?>px;">
                    <iframe
                        width="100%"
                        height="100%"
                        frameborder="0"
                        style="border:0"
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?php echo urlencode($atts['company_name'] . ' ' . $atts['address']); ?>&zoom=15"
                        allowfullscreen>
                    </iframe>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vd_contact_form')) {
            wp_send_json_error(array('message' => 'Token bảo mật không hợp lệ'));
        }
        
        // Validate required fields
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (empty($name) || empty($email) || empty($message)) {
            wp_send_json_error(array('message' => 'Vui lòng điền đầy đủ các trường bắt buộc'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Vui lòng nhập địa chỉ email hợp lệ'));
        }
        
        // Prepare email - send to admin@vidieu.vn instead of default admin email
        $to = 'admin@vidieu.vn';
        $subject = sprintf('Liên hệ mới từ %s', $name);
        
        $body = sprintf(
            "Bạn vừa nhận được một liên hệ mới từ form liên hệ:\n\n" .
            "Họ tên: %s\n" .
            "Email: %s\n" .
            "Số điện thoại: %s\n\n" .
            "Nội dung:\n%s\n\n" .
            "---\n" .
            "Tin nhắn này được gửi từ form liên hệ trên %s",
            $name,
            $email,
            $phone ?: 'Không cung cấp',
            $message,
            get_bloginfo('name')
        );
        
        $headers = array(
            'From: ' . $name . ' <' . $email . '>',
            'Reply-To: ' . $email,
            'Content-Type: text/plain; charset=UTF-8'
        );
        
        // Send email
        $sent = wp_mail($to, $subject, $body, $headers);
        
        if ($sent) {
            wp_send_json_success(array(
                'message' => 'Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất có thể!'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Xin lỗi, đã có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại sau.'
            ));
        }
    }
}
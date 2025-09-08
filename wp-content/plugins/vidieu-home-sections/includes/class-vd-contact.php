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
            'title' => __('Contact Us', VD_HOME_TEXT_DOMAIN),
            'show_map' => 'yes',
            'map_height' => '400',
            'company_name' => 'Vidieu.vn',
            'address' => 'Hà Nội, Việt Nam',
            'lat' => '21.0285',
            'lng' => '105.8542'
        ), $atts, 'vd_contact');
        
        ob_start();
        ?>
        <div class="vd-contact-section">
            <div class="vd-container">
                <?php if (!empty($atts['title'])) : ?>
                    <h2 class="vd-contact-title"><?php echo esc_html($atts['title']); ?></h2>
                <?php endif; ?>
                
                <div class="vd-contact-content">
                    <div class="vd-contact-form-wrapper">
                        <h3 class="vd-contact-subtitle"><?php _e('Send us a message', VD_HOME_TEXT_DOMAIN); ?></h3>
                        <form class="vd-contact-form" id="vd-contact-form">
                            <div class="vd-form-group">
                                <label for="vd_contact_name"><?php _e('Full Name', VD_HOME_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                                <input type="text" id="vd_contact_name" name="name" required>
                            </div>
                            
                            <div class="vd-form-group">
                                <label for="vd_contact_email"><?php _e('Email', VD_HOME_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                                <input type="email" id="vd_contact_email" name="email" required>
                            </div>
                            
                            <div class="vd-form-group">
                                <label for="vd_contact_phone"><?php _e('Phone Number', VD_HOME_TEXT_DOMAIN); ?></label>
                                <input type="tel" id="vd_contact_phone" name="phone">
                            </div>
                            
                            <div class="vd-form-group">
                                <label for="vd_contact_message"><?php _e('Message', VD_HOME_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                                <textarea id="vd_contact_message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="vd-form-group">
                                <button type="submit" class="vd-contact-submit">
                                    <span class="vd-btn-text"><?php _e('Send Message', VD_HOME_TEXT_DOMAIN); ?></span>
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
                        <h3 class="vd-contact-subtitle"><?php _e('Get in touch', VD_HOME_TEXT_DOMAIN); ?></h3>
                        <div class="vd-contact-info">
                            <div class="vd-contact-item">
                                <div class="vd-contact-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="vd-contact-details">
                                    <h4><?php _e('Hotline', VD_HOME_TEXT_DOMAIN); ?></h4>
                                    <a href="tel:0988691196">0988 691 196</a>
                                </div>
                            </div>
                            
                            <div class="vd-contact-item">
                                <div class="vd-contact-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 8L10.8906 13.2604C11.5624 13.7083 12.4376 13.7083 13.1094 13.2604L21 8M5 19H19C20.1046 19 21 18.1046 21 17V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V17C3 18.1046 3.89543 19 5 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="vd-contact-details">
                                    <h4><?php _e('Email', VD_HOME_TEXT_DOMAIN); ?></h4>
                                    <a href="mailto:support@vidieu.vn">support@vidieu.vn</a>
                                </div>
                            </div>
                            
                            <div class="vd-contact-item">
                                <div class="vd-contact-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22H16C16.55 22 17 21.55 17 21C17 20.45 16.55 20 16 20H12C7.58 20 4 16.42 4 12C4 7.58 7.58 4 12 4C16.42 4 20 7.58 20 12V13.43C20 14.22 19.29 15 18.5 15C17.71 15 17 14.22 17 13.43V12C17 9.24 14.76 7 12 7C9.24 7 7 9.24 7 12C7 14.76 9.24 17 12 17C13.38 17 14.64 16.44 15.54 15.53C16.19 16.42 17.31 17 18.5 17C20.47 17 22 15.4 22 13.43V12C22 6.48 17.52 2 12 2ZM12 15C10.34 15 9 13.66 9 12C9 10.34 10.34 9 12 9C13.66 9 15 10.34 15 12C15 13.66 13.66 15 12 15Z" fill="currentColor"/>
                                    </svg>
                                </div>
                                <div class="vd-contact-details">
                                    <h4><?php _e('Zalo', VD_HOME_TEXT_DOMAIN); ?></h4>
                                    <a href="https://zalo.me/g/hwcfvo585" target="_blank" rel="noopener">Zalo Group</a>
                                </div>
                            </div>
                            
                            <div class="vd-contact-item">
                                <div class="vd-contact-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M24 12C24 5.373 18.627 0 12 0C5.373 0 0 5.373 0 12C0 17.989 4.388 22.954 10.125 23.854V15.469H7.078V12H10.125V9.356C10.125 6.349 11.917 4.688 14.657 4.688C15.97 4.688 17.344 4.922 17.344 4.922V7.875H15.83C14.34 7.875 13.875 8.8 13.875 9.75V12H17.203L16.671 15.469H13.875V23.854C19.612 22.954 24 17.989 24 12Z" fill="currentColor"/>
                                    </svg>
                                </div>
                                <div class="vd-contact-details">
                                    <h4><?php _e('Facebook', VD_HOME_TEXT_DOMAIN); ?></h4>
                                    <a href="https://m.me/vidieuvn.muatoolAmazon" target="_blank" rel="noopener">Messenger</a>
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
            wp_send_json_error(array('message' => __('Invalid security token', VD_HOME_TEXT_DOMAIN)));
        }
        
        // Validate required fields
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (empty($name) || empty($email) || empty($message)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields', VD_HOME_TEXT_DOMAIN)));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address', VD_HOME_TEXT_DOMAIN)));
        }
        
        // Prepare email
        $to = get_option('admin_email');
        $subject = sprintf(__('New Contact Form Submission from %s', VD_HOME_TEXT_DOMAIN), $name);
        
        $body = sprintf(
            __("You have received a new contact form submission:\n\n" .
               "Name: %s\n" .
               "Email: %s\n" .
               "Phone: %s\n\n" .
               "Message:\n%s\n\n" .
               "---\n" .
               "This message was sent from the contact form on %s", VD_HOME_TEXT_DOMAIN),
            $name,
            $email,
            $phone ?: __('Not provided', VD_HOME_TEXT_DOMAIN),
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
                'message' => __('Thank you for your message. We will get back to you soon!', VD_HOME_TEXT_DOMAIN)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sorry, there was an error sending your message. Please try again later.', VD_HOME_TEXT_DOMAIN)
            ));
        }
    }
}
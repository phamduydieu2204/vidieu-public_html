<?php
/**
 * Vietnamese Translations for Elessi Theme
 * 
 * Tập trung tất cả các translations từ tiếng Anh sang tiếng Việt tại đây
 * để dễ quản lý và bảo trì.
 * 
 * @package Elessi-theme-child
 * @since 2025-09-04
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main translation filter for Elessi theme texts
 */
add_filter('gettext', 'elessi_child_vietnamese_translations', 20, 3);
function elessi_child_vietnamese_translations($translated_text, $text, $domain) {
    
    // Only translate texts from Elessi theme
    if ($domain === 'elessi-theme') {
        
        // Search translations
        switch ($text) {
            // Search placeholders
            case "I'm shopping for ...":
                return "Tìm sản phẩm/bài viết…";
                
            case "Start typing ...":
                return "Tìm kiếm…";
                
            // Add more search-related translations here
            case "Search":
                return "Tìm kiếm";
                
            case "Search Products":
                return "Tìm sản phẩm";
                
            // Category & Navigation
            case "Browse Categories":
                return "Danh mục sản phẩm";
                
            case "Categories":
                return "Danh mục";
                
            case "All Categories":
                return "Tất cả danh mục";
                
            // Shopping Cart
            case "Cart":
                return "Giỏ hàng";
                
            case "Shopping Cart":
                return "Giỏ hàng";
                
            case "Your cart is empty":
                return "Giỏ hàng của bạn đang trống";
                
            case "Add to cart":
                return "Thêm vào giỏ";
                
            // Checkout
            case "Checkout":
                return "Thanh toán";
                
            case "Proceed to checkout":
                return "Tiến hành thanh toán";
                
            // Account
            case "My Account":
                return "Tài khoản";
                
            case "Login":
                return "Đăng nhập";
                
            case "Register":
                return "Đăng ký";
                
            case "Logout":
                return "Đăng xuất";
                
            // Product
            case "Product":
                return "Sản phẩm";
                
            case "Products":
                return "Sản phẩm";
                
            case "Related products":
                return "Sản phẩm liên quan";
                
            case "New":
                return "Mới";
                
            case "Sale":
                return "Giảm giá";
                
            case "Out of stock":
                return "Hết hàng";
                
            case "In stock":
                return "Còn hàng";
                
            // Wishlist
            case "Wishlist":
                return "Yêu thích";
                
            case "Add to wishlist":
                return "Thêm vào yêu thích";
                
            // Compare
            case "Compare":
                return "So sánh";
                
            case "Add to compare":
                return "Thêm vào so sánh";
                
            // Quick View
            case "Quick View":
                return "Xem nhanh";
                
            // Sort & Filter
            case "Sort by":
                return "Sắp xếp theo";
                
            case "Default sorting":
                return "Mặc định";
                
            case "Sort by popularity":
                return "Phổ biến nhất";
                
            case "Sort by average rating":
                return "Đánh giá cao nhất";
                
            case "Sort by latest":
                return "Mới nhất";
                
            case "Sort by price: low to high":
                return "Giá: Thấp đến cao";
                
            case "Sort by price: high to low":
                return "Giá: Cao đến thấp";
                
            // Pagination
            case "Previous":
                return "Trước";
                
            case "Next":
                return "Sau";
                
            // Forms
            case "Name":
                return "Họ tên";
                
            case "Email":
                return "Email";
                
            case "Phone":
                return "Số điện thoại";
                
            case "Address":
                return "Địa chỉ";
                
            case "Submit":
                return "Gửi";
                
            // Messages
            case "Thank you":
                return "Cảm ơn bạn";
                
            case "Success":
                return "Thành công";
                
            case "Error":
                return "Lỗi";
                
            case "Loading...":
                return "Đang tải...";
                
            // Add more translations as needed...
        }
    }
    
    // WooCommerce translations
    if ($domain === 'woocommerce') {
        switch ($text) {
            case "Add to cart":
                return "Thêm vào giỏ";
                
            case "View cart":
                return "Xem giỏ hàng";
                
            case "Checkout":
                return "Thanh toán";
                
            // Add more WooCommerce translations here...
        }
    }
    
    // Contact Form 7 translations
    if ($domain === 'contact-form-7') {
        switch ($text) {
            // Form labels
            case "Your Name":
                return "Họ và tên";
                
            case "Your Email":
                return "Email của bạn";
                
            case "Subject":
                return "Chủ đề";
                
            case "Your Message":
                return "Nội dung tin nhắn";
                
            case "Send":
                return "Gửi";
                
            // Messages
            case "Thank you for your message. It has been sent.":
                return "Cảm ơn bạn! Tin nhắn của bạn đã được gửi thành công.";
                
            case "There was an error trying to send your message. Please try again later.":
                return "Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại sau.";
                
            case "One or more fields have an error. Please check and try again.":
                return "Một hoặc nhiều trường có lỗi. Vui lòng kiểm tra và thử lại.";
                
            case "Please fill in the required field.":
                return "Vui lòng điền vào trường bắt buộc.";
                
            case "The e-mail address entered is invalid.":
                return "Địa chỉ email không hợp lệ.";
                
            case "The telephone number is invalid.":
                return "Số điện thoại không hợp lệ.";
                
            case "You must accept the terms and conditions before sending your message.":
                return "Bạn phải chấp nhận điều khoản trước khi gửi.";
                
            // Add more Contact Form 7 translations here...
        }
    }
    
    return $translated_text;
}

/**
 * Translate text with context
 */
add_filter('gettext_with_context', 'elessi_child_vietnamese_translations_context', 20, 4);
function elessi_child_vietnamese_translations_context($translated_text, $text, $context, $domain) {
    
    if ($domain === 'elessi-theme' || $domain === 'woocommerce') {
        // Add context-specific translations here if needed
    }
    
    return $translated_text;
}

/**
 * Translate plural forms
 */
add_filter('ngettext', 'elessi_child_vietnamese_translations_plural', 20, 5);
function elessi_child_vietnamese_translations_plural($translation, $single, $plural, $number, $domain) {
    
    if ($domain === 'elessi-theme' || $domain === 'woocommerce') {
        // Add plural translations here
        if ($single === '%s product' && $plural === '%s products') {
            return $number === 1 ? '%s sản phẩm' : '%s sản phẩm';
        }
    }
    
    return $translation;
}

/**
 * JavaScript translations via localization
 */
add_action('wp_enqueue_scripts', 'elessi_child_localize_vietnamese_scripts', 100);
function elessi_child_localize_vietnamese_scripts() {
    
    // Enqueue Vietnamese search customization script
    wp_enqueue_script(
        'elessi-search-vietnamese',
        get_stylesheet_directory_uri() . '/assets/js/search-vietnamese.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Localize Vietnamese translations for JavaScript
    $vietnamese_translations = array(
        'search_suggestions' => 'Áo thun, Áo khoác, Quần jean...',
        'loading' => 'Đang tải...',
        'no_results' => 'Không tìm thấy kết quả',
        'view_all' => 'Xem tất cả',
        'close' => 'Đóng',
        'error' => 'Có lỗi xảy ra',
        'success' => 'Thành công',
        // Add more JS translations as needed
    );
    
    wp_localize_script('elessi-search-vietnamese', 'elessi_vietnamese', $vietnamese_translations);
}
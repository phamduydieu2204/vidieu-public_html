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
// Clear any translation cache on init
add_action('init', function() {
    if (isset($_GET['clear_translation_cache']) && current_user_can('administrator')) {
        // Clear object cache if exists
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear language cache directory
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/languages/';
        if (is_dir($cache_dir)) {
            array_map('unlink', glob($cache_dir . '*.json'));
        }
        
        wp_die('Translation cache cleared! <a href="' . remove_query_arg('clear_translation_cache') . '">Go back</a>');
    }
});

add_filter('gettext', 'elessi_child_vietnamese_translations', 999, 3);
function elessi_child_vietnamese_translations($translated_text, $text, $domain) {
    
    // Loại bỏ khoảng trắng thừa để so sánh chính xác
    $clean_text = trim($text);
    
    // Debug: Log texts that need translation on product pages
    if (is_product() && current_user_can('administrator') && isset($_GET['debug_translations'])) {
        $texts_to_check = array(
            'Size Guide', 'Delivery & Return', 'people are viewing this right now',
            'Share', 'Guaranteed Safe Checkout', 'Add to cart', 'Buy now',
            'Request a Call Back', 'Hurry up! Sale end in:'
        );
        
        if (stripos($clean_text, 'Size Guide') !== false || 
            stripos($clean_text, 'Delivery') !== false || 
            stripos($clean_text, 'people are viewing') !== false ||
            stripos($clean_text, 'Share') !== false ||
            stripos($clean_text, 'Guaranteed') !== false) {
            error_log("Translation Debug - Original: '$text', Clean: '$clean_text', Domain: '$domain'");
        }
    }
    
    // Dịch cho mọi text domain, không chỉ elessi-theme
    // Ưu tiên dịch các văn bản phổ biến trước
    
    // Common texts across all domains - Using clean text for comparison
    switch ($clean_text) {
        case "Size Guide":
            return "Hướng dẫn kích thước";
            
        case "Delivery & Return":
            return "Giao hàng & Đổi trả";
            
        case "people are viewing this right now":
            return "người đang xem sản phẩm này ngay bây giờ";
            
        case "Share":
            return "Chia sẻ";
            
        case "Guaranteed Safe Checkout":
            return "Thanh toán an toàn được đảm bảo";
    }
    
    // Kiểm tra cả với contains (cho trường hợp text có thêm HTML hoặc ký tự đặc biệt)
    if (stripos($clean_text, 'Size Guide') !== false) {
        return str_ireplace('Size Guide', 'Hướng dẫn kích thước', $text);
    }
    
    if (stripos($clean_text, 'Delivery & Return') !== false) {
        return str_ireplace('Delivery & Return', 'Giao hàng & Đổi trả', $text);
    }
    
    if (stripos($clean_text, 'people are viewing this right now') !== false) {
        return str_ireplace('people are viewing this right now', 'người đang xem sản phẩm này ngay bây giờ', $text);
    }
    
    if (stripos($clean_text, 'Guaranteed Safe Checkout') !== false) {
        return str_ireplace('Guaranteed Safe Checkout', 'Thanh toán an toàn được đảm bảo', $text);
    }
    
    // Share có thể xuất hiện nhiều nơi nên cần cẩn thận
    if ($clean_text === 'Share' || $clean_text === 'Share:') {
        return "Chia sẻ";
    }
    
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
            case "My account":
                return "Tài khoản của tôi";

            case "Thank you. Your order has been received.":
                return "Cảm ơn bạn. Đơn hàng của bạn đã được nhận.";

            case "Order Number":
                return "Mã đơn hàng";

            case "Date":
                return "Ngày";

            case "Total":
                return "Tổng cộng";

            case "Payment Method":
                return "Phương thức thanh toán";

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

            case "Manage Your Items List":
                return "Quản lý danh sách sản phẩm của bạn";

            case "Checkout Details":
                return "Chi tiết thanh toán";

            case "Checkout Your Items List":
                return "Thanh toán danh sách sản phẩm của bạn";

            case "Order Complete":
                return "Đơn hàng hoàn tất";

            case "Review Your Order":
                return "Xem lại đơn hàng của bạn";

            case "Have a coupon?":
                return "Có mã giảm giá?";

            case "Click here to enter your code":
                return "Nhấn vào đây để nhập mã";

            case "If you have a coupon code, please apply it below.":
                return "Nếu bạn có mã giảm giá, vui lòng nhập bên dưới.";

            case "Coupon code":
                return "Mã giảm giá";

            case "Apply Coupon":
                return "Áp dụng mã giảm giá";


            case "Login / Register":
                return "Đăng nhập/Đăng ký";

            case "Great to have you back!":
                return "Rất vui khi bạn quay lại!";

            case "Username or email":
                return "Tên đăng nhập hoặc email";

            case "Remember me":
                return "Ghi nhớ tôi";

            case "Password":
                return "Mật khẩu";

            case "Lost?":
                return "Quên mật khẩu?";

            case "Lost your password?":
                return "Quên mật khẩu?";

            case "SIGN IN TO YOUR ACCOUNT":
                return "Đăng nhập vào tài khoản của bạn";

            case "Not a member":
                return "Chưa có tài khoản";
                
            case "Not a member?":
                return "Chưa có tài khoản?";

            case "Create an account":
                return "Tạo tài khoản";
                
            case "Remember":
                return "Ghi nhớ";
                
            case "Great to see you here!":
                return "Rất vui được gặp bạn!";
                
            case "Email address":
                return "Địa chỉ email";
                
            case "SETUP YOUR ACCOUNT":
                return "TẠO TÀI KHOẢN";
                
            case "Already got an account?":
                return "Đã có tài khoản?";
                
            case "Sign in here":
                return "Đăng nhập tại đây";

            case "License keys":
                return "Khoá bản quyền";

                case "Welcome":
                return "Chào mừng";

            case "Hello":
                return "Xin chào";

            case "Size Guide":
                return "Hướng dẫn kích thước";

            case "Delivery & Return":
                return "Giao hàng & Đổi trả";

            case "people are viewing this right now":
                return "người đang xem sản phẩm này ngay bây giờ";

            case "Share":
                return "Chia sẻ";

            case "Guaranteed Safe Checkout":
                return "Thanh toán an toàn được đảm bảo";

            case "Hurry up! Sale end in:":
                return "Nhanh lên! Khuyến mãi kết thúc trong:";

            case "BUY NOW":
                return "MUA NGAY";

            case "Request a Call Back":
                return "Yêu cầu gọi lại";

            case "Country":
                return "Quốc gia";

            case "United States of America":
                return "Hoa Kỳ";

            case "United Kingdom":
                return "Vương quốc Anh";

            case "Japan":
                return "Nhật Bản";

            case "France":
                return "Pháp";

            case "India":
                return "Ấn Độ";

            case "Italy":
                return "Ý";

            case "United Arab Emirates":
                return "Các Tiểu vương quốc Ả Rập Thống nhất";

            case "Russian Federation":
                return "Liên bang Nga";

            case "Your Email (required)":
                return "Email của bạn (bắt buộc)";

            case "Phone Number (required)":
                return "Số điện thoại (bắt buộc)";

            case "Call":
                return "Gọi điện";

            case "SMS":
                return "Tin nhắn SMS";

            case "WhatsApp":
                return "WhatsApp";

            case "Send":
                return "Gửi";

            case "Size Guide":
                return "Hướng dẫn kích thước";

            case "DRESSES":
                return "ĐẦM";

            case "T-SHIRT":
                return "ÁO THUN";

            case "BOTTOMS":
                return "QUẦN";

            case "Size":
                return "Kích cỡ";

            case "Chest":
                return "Vòng ngực";

            case "Waist":
                return "Vòng eo";

            case "Hips":
                return "Vòng hông";

            case "All measurements are in INCHES":
                return "Tất cả số đo tính bằng INCH";

            case "and may vary a half inch in either direction.":
                return "và có thể sai lệch nửa inch theo cả hai hướng.";

            case "Delivery & Return":
                return "Giao hàng & Đổi trả";

            case "Delivery":
                return "Giao hàng";

            case "We ship to all 50 states, Washington DC.":
                return "Chúng tôi giao hàng đến tất cả 50 tiểu bang và Washington DC.";

            case "All orders are shipped with a UPS tracking number.":
                return "Tất cả đơn hàng được giao kèm mã theo dõi UPS.";

            case "Always free shipping for orders over US $200.":
                return "Luôn miễn phí vận chuyển cho đơn hàng trên 200 USD.";

            case "During sale periods and promotions the delivery time may be longer than normal.":
                return "Trong thời gian khuyến mãi, thời gian giao hàng có thể lâu hơn bình thường.";

            case "Return":
                return "Đổi trả";

            case "Help":
                return "Hỗ trợ";

            case "Give us a shout if you have any other questions and/or concerns.":
                return "Hãy liên hệ với chúng tôi nếu bạn có bất kỳ câu hỏi hoặc thắc mắc nào.";

            case "Share":
                return "Chia sẻ";

            case "Share on Twitter":
                return "Chia sẻ trên Twitter";

            case "Share on Facebook":
                return "Chia sẻ trên Facebook";

            case "Pin on Pinterest":
                return "Lưu lên Pinterest";

            case "Email to your friends":
                return "Gửi email cho bạn bè";

            case "Guaranteed Safe Checkout":
                return "Thanh toán an toàn được đảm bảo";

            case "people are viewing this right now":
                return "người đang xem sản phẩm này ngay bây giờ";

            case "are viewing this right now":
                return "đang xem sản phẩm này ngay bây giờ";

            case "people":
                return "người";

            case "Close":
                return "Đóng";

            case "My Cart":
                return "Giỏ hàng của tôi";

            case "No products in the cart.":
                return "Chưa có sản phẩm trong giỏ hàng.";

            case "RETURN TO SHOP":
                return "QUAY LẠI CỬA HÀNG";
            
            case "Related Products":
                return "Sản phẩm liên quan";

            case "Categories:":
                return "Danh mục:";

            case "Search":
                return "Tìm kiếm";

            case "Search here":
                return "Tìm kiếm tại đây";

            case "Close search":
                return "Đóng tìm kiếm";

            case "Recent":
                return "Mới nhất";

            case "Categories":
                return "Danh mục";

            case "Fashion":
                return "Thời trang";

            case "Food for thought":
                return "Suy ngẫm";

            case "Gaming":
                return "Trò chơi";

            case "Helium 10":
                return "Helium 10";

            case "Music":
                return "Âm nhạc";

            case "Archives":
                return "Lưu trữ";

            case "Meta":
                return "Meta";


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
    
    // Enqueue product page translations script
    wp_enqueue_script(
        'elessi-product-translations',
        get_stylesheet_directory_uri() . '/assets/js/product-translations.js',
        array('jquery'),
        '1.0.2',
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
        // Product page translations
        'size_guide' => 'Hướng dẫn kích thước',
        'delivery_return' => 'Giao hàng & Đổi trả',
        'people_viewing' => 'người đang xem sản phẩm này ngay bây giờ',
        'share' => 'Chia sẻ',
        'guaranteed_safe_checkout' => 'Thanh toán an toàn được đảm bảo',
        'add_to_cart' => 'Thêm vào giỏ',
        'buy_now' => 'Mua ngay',
        // Add more JS translations as needed
    );
    
    wp_localize_script('elessi-search-vietnamese', 'elessi_vietnamese', $vietnamese_translations);
}
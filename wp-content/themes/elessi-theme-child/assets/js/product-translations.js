/**
 * Product Page Vietnamese Translations
 * Dịch các văn bản động trên trang sản phẩm
 */
(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        translateProductTexts();
        
        // Re-run after AJAX loads
        $(document).ajaxComplete(function() {
            setTimeout(translateProductTexts, 100);
        });
        
        // Re-run on mutation observer for dynamic content
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function(mutations) {
                translateProductTexts();
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });
    
    function translateProductTexts() {
        // Translation map
        var translations = {
            'Size Guide': 'Hướng dẫn kích thước',
            'Delivery & Return': 'Giao hàng & Đổi trả',
            'people are viewing this right now': 'người đang xem sản phẩm này ngay bây giờ',
            'Share': 'Chia sẻ',
            'Guaranteed Safe Checkout': 'Thanh toán an toàn được đảm bảo',
            'Add to cart': 'Thêm vào giỏ',
            'Buy now': 'Mua ngay',
            'BUY NOW': 'MUA NGAY',
            'Request a Call Back': 'Yêu cầu gọi lại',
            'Hurry up! Sale end in:': 'Nhanh lên! Khuyến mãi kết thúc trong:',
            'Share on Twitter': 'Chia sẻ trên Twitter',
            'Share on Facebook': 'Chia sẻ trên Facebook',
            'Pin on Pinterest': 'Lưu lên Pinterest',
            'Email to your friends': 'Gửi email cho bạn bè',
            // Login/Register popup translations
            'Remember': 'Ghi nhớ',
            'Not a member?': 'Chưa có tài khoản?',
            'Great to see you here!': 'Rất vui được gặp bạn!',
            'Email address': 'Địa chỉ email',
            'SETUP YOUR ACCOUNT': 'TẠO TÀI KHOẢN',
            'Already got an account?': 'Đã có tài khoản?',
            'Sign in here': 'Đăng nhập tại đây'
        };
        
        // Translate text nodes
        $('*').contents().filter(function() {
            return this.nodeType === 3; // Text nodes only
        }).each(function() {
            var text = this.nodeValue;
            var trimmedText = text.trim();
            
            if (trimmedText && translations[trimmedText]) {
                this.nodeValue = text.replace(trimmedText, translations[trimmedText]);
            }
        });
        
        // Translate specific elements by class or ID
        $('.product-tabs a').each(function() {
            var text = $(this).text().trim();
            if (translations[text]) {
                $(this).text(translations[text]);
            }
        });
        
        // Translate buttons
        $('button, .button, .btn, input[type="submit"]').each(function() {
            var text = $(this).text().trim();
            if (translations[text]) {
                $(this).text(translations[text]);
            }
            
            // Also check value attribute for input buttons
            var value = $(this).val();
            if (value && translations[value.trim()]) {
                $(this).val(translations[value.trim()]);
            }
        });
        
        // Translate links
        $('a').each(function() {
            var text = $(this).text().trim();
            if (translations[text]) {
                $(this).text(translations[text]);
            }
        });
        
        // Translate placeholders
        $('[placeholder]').each(function() {
            var placeholder = $(this).attr('placeholder');
            if (placeholder && translations[placeholder.trim()]) {
                $(this).attr('placeholder', translations[placeholder.trim()]);
            }
        });
        
        // Translate title attributes
        $('[title]').each(function() {
            var title = $(this).attr('title');
            if (title && translations[title.trim()]) {
                $(this).attr('title', translations[title.trim()]);
            }
        });
        
        
        // NASA theme specific selectors
        $('.nasa-tabs-content .nasa-dsc-ul li a').each(function() {
            var text = $(this).text().trim();
            if (translations[text]) {
                $(this).text(translations[text]);
            }
        });
    }
    
})(jQuery);
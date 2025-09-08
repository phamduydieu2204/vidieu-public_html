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
                mutations.forEach(function(mutation) {
                    // Only re-translate if text content changed
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        // Specifically target the viewing counter
                        if ($(mutation.target).closest('#nasa-counter-viewing').length > 0 ||
                            $(mutation.target).is('#nasa-counter-viewing')) {
                            translateViewingCounter();
                        }
                    }
                });
                
                // Also run general translation
                translateProductTexts();
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                characterData: true
            });
        }
        
        // Specific interval for viewing counter (as it updates dynamically)
        setInterval(function() {
            translateViewingCounter();
        }, 500);
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
            'Email to your friends': 'Gửi email cho bạn bè'
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
        
        // Special case for viewing count (may contain number)
        $('.product-visitors, .nasa-viewing, #nasa-counter-viewing').each(function() {
            var $elem = $(this);
            var html = $elem.html();
            
            // Handle different HTML structures
            if (html) {
                // Case 1: <strong>people</strong> are viewing this right now
                html = html.replace(/<strong>people<\/strong>\s*are viewing this right now/gi, '<strong>người</strong> đang xem sản phẩm này ngay bây giờ');
                
                // Case 2: people are viewing this right now (no tags)
                html = html.replace(/people are viewing this right now/gi, 'người đang xem sản phẩm này ngay bây giờ');
                
                // Case 3: Just translate the word "people" in strong tags
                html = html.replace(/<strong>people<\/strong>/gi, '<strong>người</strong>');
                
                $elem.html(html);
            }
        });
        
        // Also handle text nodes within the viewing counter
        $('#nasa-counter-viewing strong').each(function() {
            if ($(this).text().trim() === 'people') {
                $(this).text('người');
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
    
    // Separate function for viewing counter translation
    function translateViewingCounter() {
        var $counter = $('#nasa-counter-viewing');
        if ($counter.length === 0) return;
        
        // Method 1: Replace the entire text content while preserving HTML structure
        $counter.contents().filter(function() {
            return this.nodeType === 3; // Text nodes
        }).each(function() {
            var text = this.nodeValue;
            if (text.indexOf('are viewing this right now') > -1) {
                this.nodeValue = text.replace('are viewing this right now', 'đang xem sản phẩm này ngay bây giờ');
            }
        });
        
        // Method 2: Replace strong tag content
        $counter.find('strong').each(function() {
            var text = $(this).text().trim();
            if (text === 'people') {
                $(this).text('người');
            }
        });
        
        // Method 3: If structure is completely dynamic, rebuild it
        var html = $counter.html();
        if (html && html.indexOf('people') > -1 && html.indexOf('người') === -1) {
            // Pattern: <strong>number</strong>&nbsp;<strong>people</strong>&nbsp;are viewing...
            html = html.replace(/<strong>people<\/strong>/gi, '<strong>người</strong>');
            html = html.replace(/are viewing this right now/gi, 'đang xem sản phẩm này ngay bây giờ');
            $counter.html(html);
        }
    }
    
})(jQuery);
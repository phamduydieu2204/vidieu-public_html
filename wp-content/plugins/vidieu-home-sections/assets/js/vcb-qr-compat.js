/**
 * VCB-MH QR Code Compatibility JavaScript
 * Ensures QR codes display properly on mobile devices
 * 
 * @package Vidieu_Home_Sections
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Configuration
    const config = window.vidieuVCBCompat || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        isOrderReceived: false,
        isCheckout: false,
        debug: false
    };

    // QR selectors
    const qrSelectors = [
        '.qrVietqr',
        '#qrVietqr',
        '.vcb-qr-code',
        '#vcb-qr-code',
        '[class*="vcb"][class*="qr"]',
        '[id*="vcb"][id*="qr"]',
        'img[src*="vietqr.io"]',
        'img[src*="qr_only.jpg"]'
    ];

    // Utility: Check if element is visible
    function isElementVisible(el) {
        if (!el) return false;
        
        const rect = el.getBoundingClientRect();
        const style = window.getComputedStyle(el);
        
        return rect.width > 0 && 
               rect.height > 0 &&
               style.display !== 'none' &&
               style.visibility !== 'hidden' &&
               style.opacity !== '0';
    }

    // Ensure QR code visibility
    function ensureQRVisibility() {
        const qrElements = $(qrSelectors.join(', '));
        
        qrElements.each(function() {
            const $qr = $(this);
            
            // Force visibility
            $qr.css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1',
                'position': 'relative',
                'z-index': '10'
            });
            
            // Handle images inside QR containers
            const $img = $qr.find('img').add($qr.filter('img'));
            if ($img.length) {
                $img.css({
                    'max-width': '100%',
                    'height': 'auto',
                    'display': 'block'
                });
                
                // Force image reload if needed
                $img.each(function() {
                    if (this.complete && this.naturalHeight === 0) {
                        const src = this.src;
                        this.src = '';
                        this.src = src;
                    }
                });
            }
            
            // Make parent containers visible
            $qr.parents().each(function() {
                const $parent = $(this);
                if ($parent.css('display') === 'none' || 
                    $parent.css('visibility') === 'hidden') {
                    $parent.css({
                        'display': 'block',
                        'visibility': 'visible'
                    });
                }
            });
        });
    }

    // Handle VCB-MH specific initialization
    function initVCBQR() {
        // Wait for VCB-MH to load its QR
        if (typeof window.vcbQRLoaded === 'undefined') {
            window.vcbQRLoaded = false;
        }
        
        // Check for VCB-MH payment info container
        const $paymentInfo = $('#payment-info');
        if ($paymentInfo.length) {
            // Ensure columns are visible on mobile
            if (window.innerWidth <= 768) {
                $('#left-col, #right-col').css({
                    'display': 'block',
                    'width': '100%',
                    'float': 'none'
                });
            }
            
            // Monitor for QR code insertion
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        ensureQRVisibility();
                    }
                });
            });
            
            observer.observe($paymentInfo[0], {
                childList: true,
                subtree: true
            });
            
            // Initial visibility check
            setTimeout(ensureQRVisibility, 100);
        }
    }

    // Handle SweetAlert2 QR display
    function handleSweetAlertQR() {
        // Monitor for SweetAlert2 popups
        if (typeof Swal !== 'undefined') {
            const originalFire = Swal.fire;
            
            Swal.fire = function() {
                const result = originalFire.apply(this, arguments);
                
                // Check for QR in popup after display
                setTimeout(function() {
                    const $swalQR = $('.swal2-popup').find(qrSelectors.join(', '));
                    if ($swalQR.length) {
                        ensureQRVisibility();
                        
                        // Mobile specific adjustments
                        if (window.innerWidth <= 768) {
                            $('.swal2-popup').css({
                                'width': '90%',
                                'max-width': '400px'
                            });
                            
                            $swalQR.css({
                                'max-width': '250px',
                                'margin': '15px auto'
                            });
                        }
                    }
                }, 50);
                
                return result;
            };
        }
    }

    // Handle responsive changes
    function handleResponsive() {
        let resizeTimer;
        
        $(window).on('resize orientationchange', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                ensureQRVisibility();
                
                // Re-apply mobile layout if needed
                if (window.innerWidth <= 768) {
                    $('#left-col, #right-col').css({
                        'display': 'block',
                        'width': '100%',
                        'float': 'none'
                    });
                }
            }, 250);
        });
    }

    // Monitor WooCommerce events
    function monitorWooCommerceEvents() {
        // Order received page updates
        $(document.body).on('updated_wc_div', function() {
            setTimeout(ensureQRVisibility, 100);
        });
        
        // Checkout updates
        $(document.body).on('updated_checkout', function() {
            setTimeout(ensureQRVisibility, 100);
        });
        
        // Fragment refreshes
        $(document.body).on('wc_fragments_refreshed', function() {
            setTimeout(ensureQRVisibility, 100);
        });
    }

    // Fallback image handler
    function addImageFallback() {
        $(document).on('error', 'img[src*="vietqr.io"]', function() {
            const $img = $(this);
            const $container = $img.closest('.qrVietqr');
            
            if ($container.length && !$container.hasClass('fallback-added')) {
                $container.addClass('fallback-added');
                
                // Add retry button
                const $retryBtn = $('<button>')
                    .text('Tải lại QR')
                    .addClass('button')
                    .css({
                        'margin-top': '10px',
                        'display': 'block',
                        'margin-left': 'auto',
                        'margin-right': 'auto'
                    })
                    .on('click', function() {
                        const src = $img.attr('src');
                        $img.attr('src', src + '?t=' + Date.now());
                        $(this).remove();
                    });
                
                $container.append($retryBtn);
            }
        });
    }

    // Debug logging
    function log(message, data) {
        if (config.debug && console && console.log) {
            console.log('[VCB QR Compat] ' + message, data || '');
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        log('Initializing VCB QR compatibility');
        
        // Only run on relevant pages
        if (!config.isOrderReceived && !config.isCheckout) {
            log('Not on checkout or order-received page, skipping');
            return;
        }
        
        // Initialize components
        initVCBQR();
        handleSweetAlertQR();
        handleResponsive();
        monitorWooCommerceEvents();
        addImageFallback();
        
        // Initial visibility check
        ensureQRVisibility();
        
        // Delayed check for late-loading content
        setTimeout(ensureQRVisibility, 1000);
        setTimeout(ensureQRVisibility, 3000);
        
        log('VCB QR compatibility initialized');
    });
    
    // Also check on window load
    $(window).on('load', function() {
        ensureQRVisibility();
    });

})(jQuery);
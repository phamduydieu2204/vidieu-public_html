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
    
    // Mobile section selectors (typo-friendly)
    const mobileSectionSelectors = ['.anMoblie', '.anMobile'];
    const desktopSectionSelector = '.anPc';

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

    // Extract payment data from page
    function extractPaymentData() {
        const data = {
            bin: '970436', // VCB default
            account: '',
            amount: 0,
            content: ''
        };
        
        // Try to extract BIN from bank logo
        const $bankLogo = $('img[src*="970436.svg"]');
        if ($bankLogo.length) {
            const match = $bankLogo.attr('src').match(/(\d{6})\.svg/);
            if (match) data.bin = match[1];
        }
        
        // Extract account number
        const accountText = $('#right-col').text();
        const accountMatch = accountText.match(/Tài Khoản:\s*([\d\s]+)/i);
        if (accountMatch) {
            data.account = accountMatch[1].replace(/\s/g, '');
        }
        
        // Extract amount
        const amountMatch = accountText.match(/Số Tiền:\s*([\d.,]+)/);
        if (amountMatch) {
            data.amount = parseInt(amountMatch[1].replace(/[.,đ\s]/g, ''));
        }
        
        // Extract content from various sources
        const $ndElement = $('#nd');
        const $noiDungElement = $('.noiDungCopy');
        if ($ndElement.length) {
            data.content = $ndElement.text().trim();
        } else if ($noiDungElement.length) {
            data.content = $noiDungElement.text().trim();
        } else {
            // Try to find in text
            const contentMatch = accountText.match(/Nội dung:\s*([A-Z0-9]+)/i);
            if (contentMatch) data.content = contentMatch[1];
        }
        
        log('Extracted payment data:', data);
        return data;
    }
    
    // Generate fallback QR
    function generateFallbackQR() {
        const data = extractPaymentData();
        
        if (!data.account || !data.amount || !data.content) {
            log('Insufficient data for fallback QR');
            return null;
        }
        
        // Build VietQR URL
        const qrUrl = `https://api.vietqr.io/${data.bin}/${data.account}/${data.amount}/${data.content}/qr_only.jpg`;
        
        // Create QR image element
        const $qrImg = $('<img>')
            .addClass('qrVietqr qr-fallback')
            .attr('src', qrUrl)
            .attr('alt', 'Mã QR thanh toán')
            .attr('data-fallback', '1');
        
        // Add error handling
        $qrImg.on('error', function() {
            log('Fallback QR failed to load');
            // Replace with link
            const $link = $('<div class="qr-error">')
                .append('<p>Không thể tải mã QR</p>')
                .append($('<a>')
                    .attr('href', qrUrl)
                    .attr('target', '_blank')
                    .addClass('button')
                    .text('Mở mã QR'));
            $(this).replaceWith($link);
        });
        
        return $qrImg;
    }
    
    // Clone QR to mobile section when needed
    function cloneQRToMobile() {
        // Check if we're on mobile viewport
        if (window.innerWidth > 768) {
            return false;
        }
        
        // Find QR in desktop section
        const $desktopQR = $(desktopSectionSelector + ' img.qrVietqr[src*="api.vietqr.io"]').first();
        
        if (!$desktopQR.length) {
            log('No QR found in desktop section, will try fallback');
            return false;
        }
        
        // Check if already cloned
        if ($desktopQR.attr('data-qr-cloned') === '1') {
            return true;
        }
        
        // Find or create mobile section
        let $mobileSection = null;
        for (let selector of mobileSectionSelectors) {
            $mobileSection = $(selector).first();
            if ($mobileSection.length) break;
        }
        
        if (!$mobileSection.length) {
            // Create mobile section if it doesn't exist
            $mobileSection = $('<div class="anMoblie"></div>');
            $('#right-col').prepend($mobileSection);
            log('Created mobile section');
        }
        
        // Check if mobile QR slot exists
        let $mobileQRSlot = $('#vcb-qr-mobile');
        if (!$mobileQRSlot.length) {
            $mobileQRSlot = $('<div id="vcb-qr-mobile" class="vcb-qr-mobile-slot"></div>');
            $mobileSection.append($mobileQRSlot);
        }
        
        // Clone the QR
        const $clonedQR = $desktopQR.clone();
        $clonedQR.removeAttr('style'); // Remove inline styles
        $clonedQR.addClass('qr-cloned-mobile');
        
        // Clear the slot and append cloned QR
        $mobileQRSlot.empty().append($clonedQR);
        
        // Mark as cloned
        $desktopQR.attr('data-qr-cloned', '1');
        
        // Hide loading spinner if exists
        $('.acb-gw-is-mb .momo-loading').hide();
        
        log('QR cloned to mobile section');
        return true;
    }
    
    // Ensure QR code visibility with fallback
    function ensureQRVisibility(useFallback = false) {
        // First try to clone to mobile if needed
        const cloneSuccess = cloneQRToMobile();
        
        // If clone failed and we're on mobile, try fallback
        if (!cloneSuccess && window.innerWidth <= 768 && (useFallback || !$('#vcb-qr-mobile img').length)) {
            log('Attempting fallback QR generation');
            
            const $fallbackQR = generateFallbackQR();
            if ($fallbackQR) {
                // Find or create mobile section and slot
                let $mobileSection = null;
                for (let selector of mobileSectionSelectors) {
                    $mobileSection = $(selector).first();
                    if ($mobileSection.length) break;
                }
                
                if (!$mobileSection.length) {
                    $mobileSection = $('<div class="anMoblie"></div>');
                    $('#right-col').prepend($mobileSection);
                }
                
                let $mobileQRSlot = $('#vcb-qr-mobile');
                if (!$mobileQRSlot.length) {
                    $mobileQRSlot = $('<div id="vcb-qr-mobile" class="vcb-qr-mobile-slot"></div>');
                    $mobileSection.append($mobileQRSlot);
                }
                
                $mobileQRSlot.empty().append($fallbackQR);
                
                // Hide spinner
                $('.acb-gw-is-mb .momo-loading').hide();
                
                log('Fallback QR generated and inserted');
            }
        }
        
        // Then ensure visibility of all QR elements
        const qrElements = $(qrSelectors.join(', '));
        
        qrElements.each(function() {
            const $qr = $(this);
            
            // Skip if in desktop section on mobile
            if (window.innerWidth <= 768 && $qr.closest(desktopSectionSelector).length) {
                return;
            }
            
            // Force visibility for mobile QR
            if ($qr.closest('.vcb-qr-mobile-slot').length || $qr.hasClass('qr-cloned-mobile') || $qr.hasClass('qr-fallback')) {
                $qr.css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
            
            // Handle images
            const $img = $qr.filter('img');
            if ($img.length) {
                $img.css({
                    'max-width': '100%',
                    'height': 'auto',
                    'display': 'block'
                });
                
                // Add load handler to hide spinner
                $img.on('load', function() {
                    $('.acb-gw-is-mb .momo-loading').hide();
                });
                
                // Force image reload if needed
                if ($img[0].complete && $img[0].naturalHeight === 0) {
                    const src = $img[0].src;
                    $img[0].src = '';
                    $img[0].src = src;
                }
            }
        });
    }

    // Handle VCB-MH specific initialization
    function initVCBQR() {
        // Wait for VCB-MH to load its QR
        if (typeof window.vcbQRLoaded === 'undefined') {
            window.vcbQRLoaded = false;
        }
        
        // Check for payment containers
        const $rightCol = $('#right-col');
        const $paymentInfo = $('#payment-info');
        
        if ($rightCol.length || $paymentInfo.length) {
            // Monitor for QR code insertion
            const targetNode = $rightCol.length ? $rightCol[0] : $paymentInfo[0];
            const observer = new MutationObserver(function(mutations) {
                let qrAdded = false;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Check if QR was added
                        $(mutation.addedNodes).each(function() {
                            if ($(this).find('img[src*="vietqr.io"]').length || 
                                $(this).is('img[src*="vietqr.io"]')) {
                                qrAdded = true;
                            }
                        });
                    }
                });
                
                if (qrAdded) {
                    log('QR detected via mutation observer');
                    setTimeout(ensureQRVisibility, 50);
                }
            });
            
            observer.observe(targetNode, {
                childList: true,
                subtree: true
            });
            
            // Initial visibility check
            setTimeout(ensureQRVisibility, 100);
            
            // Try with fallback after 3 seconds
            setTimeout(function() {
                if (window.innerWidth <= 768 && !$('#vcb-qr-mobile img').length) {
                    ensureQRVisibility(true); // Enable fallback
                }
            }, 3000);
            
            // Final fallback check after 8s
            setTimeout(function() {
                if (window.innerWidth <= 768 && !$('#vcb-qr-mobile img').length) {
                    const $fallbackMsg = $('<div class="qr-fallback-msg">' +
                        '<p>Đang tải mã QR...</p>' +
                        '<button class="button retry-qr-load">Tải lại</button>' +
                        '</div>');
                    
                    let $mobileQRSlot = $('#vcb-qr-mobile');
                    if (!$mobileQRSlot.length) {
                        const $mobileSection = $('.anMoblie, .anMobile').first();
                        if ($mobileSection.length) {
                            $mobileQRSlot = $('<div id="vcb-qr-mobile" class="vcb-qr-mobile-slot"></div>');
                            $mobileSection.append($mobileQRSlot);
                        }
                    }
                    
                    if ($mobileQRSlot.length) {
                        $mobileQRSlot.append($fallbackMsg);
                        
                        $('.retry-qr-load').on('click', function() {
                            location.reload();
                        });
                    }
                }
            }, 8000);
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
        if ((config.debug === 1 || config.debug === '1') && console && console.log) {
            console.info('[VCB QR Compat] ' + message, data || '');
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
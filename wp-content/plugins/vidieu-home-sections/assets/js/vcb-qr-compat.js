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
        
        return data;
    }
    
    // Generate fallback QR
    function generateFallbackQR() {
        const data = extractPaymentData();
        
        if (!data.account || !data.amount || !data.content) {
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
        
        // Find QR in desktop section or right column (VCB-MH uses both left/right)
        let $desktopQR = $(desktopSectionSelector + ' img.qrVietqr[src*="api.vietqr.io"]').first();
        
        // If not in desktop section, check right column
        if (!$desktopQR.length) {
            $desktopQR = $('#right-col img.qrVietqr[src*="api.vietqr.io"]').first();
        }
        
        // Also check left column if needed
        if (!$desktopQR.length) {
            $desktopQR = $('#left-col img.qrVietqr[src*="api.vietqr.io"]').first();
        }
        
        if (!$desktopQR.length) {
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
        hideSpinner();
        
        return true;
    }
    
    // Ensure QR code visibility with fallback
    function ensureQRVisibility(useFallback = false) {
        // First try to clone to mobile if needed
        const cloneSuccess = cloneQRToMobile();
        
        // If clone failed and we're on mobile, try fallback
        if (!cloneSuccess && window.innerWidth <= 768 && (useFallback || !$('#vcb-qr-mobile img').length)) {
            
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
                hideSpinner();
                
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
                    hideSpinner();
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
        // Prevent duplicate initialization
        if (window.vcbQRInitialized) {
            return;
        }
        window.vcbQRInitialized = true;
        
        
        // Check for payment containers
        const $rightCol = $('#right-col');
        const $paymentInfo = $('#payment-info');
        
        if ($rightCol.length || $paymentInfo.length) {
            // Monitor for QR code insertion
            const targetNode = $rightCol.length ? $rightCol[0] : $paymentInfo[0];
            // Debounce observer callbacks
            let observerTimeout;
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
                    // Debounce multiple mutations
                    clearTimeout(observerTimeout);
                    observerTimeout = setTimeout(function() {
                        ensureQRVisibility();
                    }, 100);
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
                    manageSpinner(); // Ensure spinner is visible
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

    // Debug logging - only active when VIDIEU_VCBQR_DEBUG is enabled
    function log(message, data) {
        // Only log when debug flag is explicitly enabled
        if ((config.debug === 1 || config.debug === '1' || window.VIDIEU_VCBQR_DEBUG) && console && console.log) {
            console.info('[VCB QR Compat] ' + message, data || '');
        }
    }

    // Manage spinner placement and visibility
    function manageSpinner() {
        // Create spinner slot if it doesn't exist
        let $spinnerSlot = $('#vcb-qr-spinner-slot');
        if (!$spinnerSlot.length) {
            // Find container with "Bước 1" text
            const $instructionContainer = $('.taiMaQrCode').length ? $('.taiMaQrCode') : 
                                        $('span:contains("Bước 1:")').closest('div');
            
            if ($instructionContainer.length) {
                $spinnerSlot = $('<div id="vcb-qr-spinner-slot" class="vcb-qr-spinner-slot" aria-hidden="true"></div>');
                $instructionContainer.before($spinnerSlot);
            }
        }
        
        // Move existing spinner to slot if needed
        const $existingSpinner = $('.acb-gw-is-mb .momo-loading');
        if ($existingSpinner.length && $spinnerSlot.length && !$existingSpinner.attr('data-spinner-mounted')) {
            $existingSpinner.attr('data-spinner-mounted', '1');
            $spinnerSlot.append($existingSpinner);
            
            // Normalize spinner attributes
            normalizeSpinnerElement($existingSpinner);
        }
        
        // Show spinner slot on mobile if no QR yet
        if (window.innerWidth <= 768 && $spinnerSlot.length) {
            const hasQR = $('#vcb-qr-mobile img').length || $('.qrVietqr:visible').length;
            if (!hasQR) {
                $spinnerSlot.show();
            }
        }
    }
    
    // Normalize spinner element - remove attributes that affect size
    function normalizeSpinnerElement($spinner) {
        if (!$spinner || !$spinner.length) return;
        
        $spinner.each(function() {
            const sp = this;
            if (sp.tagName === 'IMG') {
                sp.classList.add('vcb-qr-spinner--img');
                sp.removeAttribute('width');
                sp.removeAttribute('height');
                sp.style.width = '';
                sp.style.height = '';
            }
        });
    }
    
    // Ensure spinner is centered (fallback to absolute positioning if needed)
    function ensureSpinnerCentered() {
        const $slot = $('#vcb-qr-spinner-slot');
        if (!$slot.length) return;
        
        const $spinner = $slot.find('img.momo-loading').first();
        if (!$spinner.length) return;
        
        // Check if spinner is properly centered
        const slotRect = $slot[0].getBoundingClientRect();
        const spinnerRect = $spinner[0].getBoundingClientRect();
        
        const centerDiffX = Math.abs((slotRect.left + slotRect.width/2) - (spinnerRect.left + spinnerRect.width/2));
        const centerDiffY = Math.abs((slotRect.top + slotRect.height/2) - (spinnerRect.top + spinnerRect.height/2));
        
        // If offset is significant, use absolute positioning fallback
        if (centerDiffX > 5 || centerDiffY > 5) {
            $slot.addClass('is-abs');
        }
    }
    
    // Hide spinner when QR is ready
    function hideSpinner() {
        $('#vcb-qr-spinner-slot').hide();
        $('.momo-loading').hide();
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        
        // Remove our extra script tag from DOM
        $('#vidieu-vcb-qr-compat-js-extra').remove();
        
        // Only run on relevant pages
        if (!config.isOrderReceived && !config.isCheckout) {
            return;
        }
        
        // Initialize components
        initVCBQR();
        handleSweetAlertQR();
        handleResponsive();
        monitorWooCommerceEvents();
        addImageFallback();
        manageSpinner();
        
        // Monitor for dynamically inserted spinners
        observeSpinnerChanges();
        
        // Initial visibility check - removed duplicate calls
        ensureQRVisibility();
        
    });
    
    // Monitor for spinner changes/re-insertions
    function observeSpinnerChanges() {
        const targetNode = document.body;
        if (!targetNode) return;
        
        let spinnerObserverTimeout;
        const spinnerObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    $(mutation.addedNodes).each(function() {
                        const $addedSpinner = $(this).find('img.momo-loading').addBack('img.momo-loading');
                        if ($addedSpinner.length) {
                            clearTimeout(spinnerObserverTimeout);
                            spinnerObserverTimeout = setTimeout(function() {
                                // Re-normalize any newly added spinners
                                normalizeSpinnerElement($addedSpinner);
                                manageSpinner();
                            }, 100);
                        }
                    });
                }
            });
        });
        
        spinnerObserver.observe(targetNode, {
            childList: true,
            subtree: true
        });
    }
    
    // Also check on window load with debounce
    let loadTimeout;
    $(window).on('load', function() {
        clearTimeout(loadTimeout);
        loadTimeout = setTimeout(function() {
            ensureQRVisibility();
            ensureSpinnerCentered();
        }, 500);
    });

})(jQuery);
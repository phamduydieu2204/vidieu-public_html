/*
 * VD License Manager - Public JavaScript
 * Version: 1.0.0
 * For frontend portal functionality
 */

(function($) {
    'use strict';

    // Main public object
    window.VDLicensePortal = {
        init: function() {
            this.setupEventListeners();
            this.initializeComponents();
        },

        setupEventListeners: function() {
            // License authentication form
            $(document).on('submit', '.license-auth-form', this.handleLicenseAuth);

            // Copy buttons
            $(document).on('click', '.copy-btn', this.handleCopyClick);

            // Retry button
            $(document).on('click', '.retry-btn', this.retryAuthentication);

            // Tab switching (for future implementation)
            $(document).on('click', '.tab-button', this.switchTab);
        },

        initializeComponents: function() {
            // Auto-focus license key input
            $('.license-key-input').first().focus();

            // Format license key input
            this.formatLicenseKeyInput();

            // Initialize any existing portal content
            this.initializePortalContent();
        },

        handleLicenseAuth: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $licenseInput = $form.find('.license-key-input');
            var $submitButton = $form.find('.vd-btn');
            var $btnText = $submitButton.find('.btn-text');
            var $btnLoading = $submitButton.find('.btn-loading');
            var $errorContainer = $('.auth-errors');

            var licenseKey = $licenseInput.val().trim();

            // Basic validation
            if (!licenseKey) {
                VDLicensePortal.showError('Please enter a license key');
                return;
            }

            // Show loading state
            $submitButton.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            $errorContainer.hide();

            // For Sprint 1, we'll just show a demo response
            // In Sprint 4, this will connect to the actual API
            setTimeout(function() {
                // Reset form state
                $submitButton.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();

                // Demo validation - accept any key that looks valid
                if (VDLicensePortal.isValidLicenseKeyFormat(licenseKey)) {
                    VDLicensePortal.showPortalContent({
                        license_key: licenseKey,
                        status: 'active',
                        product_name: 'Demo Product',
                        expires_at: '2024-12-31 23:59:59'
                    });
                } else {
                    VDLicensePortal.showError('Invalid license key format');
                }
            }, 1500); // Simulate API delay
        },

        isValidLicenseKeyFormat: function(licenseKey) {
            // Basic format validation
            if (!licenseKey || typeof licenseKey !== 'string') {
                return false;
            }

            // Must be between 8 and 64 characters
            if (licenseKey.length < 8 || licenseKey.length > 64) {
                return false;
            }

            // Should contain only alphanumeric characters and dashes
            return /^[A-Za-z0-9\-]+$/.test(licenseKey);
        },

        showPortalContent: function(licenseData) {
            // Hide auth form
            $('.vd-portal-auth').hide();

            // Show portal content
            var $portalContent = $('.vd-portal-content');
            if ($portalContent.length === 0) {
                // Create portal content structure
                $portalContent = VDLicensePortal.createPortalStructure();
                $('.vd-license-portal').append($portalContent);
            }

            // Populate with license data
            VDLicensePortal.populatePortalData(licenseData);

            // Show the portal
            $portalContent.fadeIn();
        },

        createPortalStructure: function() {
            var portalHTML = `
                <div class="vd-portal-content">
                    <div class="portal-header">
                        <div class="license-info">
                            <h3 class="license-title"></h3>
                            <div class="license-key-display">
                                <span class="key-label">${vd_public_ajax.strings.license_key || 'License Key'}:</span>
                                <code class="license-key"></code>
                                <button class="copy-btn" type="button">Copy</button>
                            </div>
                        </div>
                        <div class="license-status">
                            <span class="status-badge"></span>
                            <div class="expiration-info">
                                <span class="expires-label">${vd_public_ajax.strings.expires || 'Expires'}:</span>
                                <time class="expires-date"></time>
                            </div>
                        </div>
                    </div>

                    <div class="portal-tabs">
                        <ul class="tab-list" role="tablist">
                            <li class="tab-item">
                                <button class="tab-button active" role="tab" data-tab="info" id="tab-info">
                                    <span class="tab-icon">ℹ️</span>
                                    <span class="tab-label">${vd_public_ajax.strings.info || 'Information'}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="portal-content-area">
                        <div class="tab-pane active" role="tabpanel" data-tab="info" id="pane-info">
                            <div class="info-content">
                                <h4>${vd_public_ajax.strings.license_info || 'License Information'}</h4>
                                <p>${vd_public_ajax.strings.sprint1_message || 'This is Sprint 1 - Plugin Foundation. Full portal functionality will be implemented in Sprint 7.'}</p>
                                <div class="demo-status">
                                    <p><strong>${vd_public_ajax.strings.current_status || 'Current Status'}:</strong> Foundation Complete ✅</p>
                                    <p><strong>${vd_public_ajax.strings.next_step || 'Next Step'}:</strong> Database Layer Implementation</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            return $(portalHTML);
        },

        populatePortalData: function(data) {
            $('.license-title').text((data.product_name || 'License') + ' Portal');
            $('.license-key').text(data.license_key);
            $('.status-badge').text(data.status || 'active').addClass('status-' + (data.status || 'active'));
            $('.expires-date').text(data.expires_at || 'Never');

            // Set copy button data
            $('.copy-btn').data('copy-text', data.license_key);
        },

        initializePortalContent: function() {
            // Initialize any portal content that's already visible
            if ($('.vd-portal-content').is(':visible')) {
                // Setup tab functionality, etc.
                this.initializeTabs();
            }
        },

        initializeTabs: function() {
            // Tab switching functionality (basic for Sprint 1)
            $('.tab-button').removeClass('active');
            $('.tab-pane').removeClass('active');

            // Show first tab
            $('.tab-button').first().addClass('active');
            $('.tab-pane').first().addClass('active');
        },

        switchTab: function(e) {
            e.preventDefault();

            var $button = $(this);
            var targetTab = $button.data('tab');

            // Remove active states
            $('.tab-button').removeClass('active');
            $('.tab-pane').removeClass('active');

            // Add active state to clicked tab
            $button.addClass('active');
            $('#pane-' + targetTab).addClass('active');
        },

        formatLicenseKeyInput: function() {
            $('.license-key-input').on('input', function() {
                var $input = $(this);
                var value = $input.val().toUpperCase();

                // Remove any invalid characters
                value = value.replace(/[^A-Z0-9\-]/g, '');

                // Update input value
                $input.val(value);
            });

            // Handle paste events
            $('.license-key-input').on('paste', function(e) {
                var $input = $(this);

                setTimeout(function() {
                    var value = $input.val().toUpperCase();
                    value = value.replace(/[^A-Z0-9\-]/g, '');
                    $input.val(value);
                }, 10);
            });
        },

        handleCopyClick: function(e) {
            e.preventDefault();

            var $button = $(this);
            var textToCopy = $button.data('copy-text') || $button.siblings('input, code').text() || $button.siblings('input, code').val();

            if (!textToCopy) {
                return;
            }

            // Use modern clipboard API if available
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    VDLicensePortal.showCopyFeedback($button);
                }).catch(function() {
                    VDLicensePortal.fallbackCopy(textToCopy, $button);
                });
            } else {
                VDLicensePortal.fallbackCopy(textToCopy, $button);
            }
        },

        fallbackCopy: function(text, $button) {
            // Fallback copy method for older browsers
            var $temp = $('<textarea>');
            $temp.val(text).css({
                position: 'fixed',
                left: '-999999px',
                top: '-999999px'
            });

            $('body').append($temp);
            $temp.select();

            try {
                document.execCommand('copy');
                VDLicensePortal.showCopyFeedback($button);
            } catch (err) {
                console.error('Copy failed:', err);
                VDLicensePortal.showError('Copy failed. Please copy manually.');
            }

            $temp.remove();
        },

        showCopyFeedback: function($button) {
            var originalText = $button.text();
            var originalClass = $button.attr('class');

            $button.text('Copied!').addClass('copied');

            setTimeout(function() {
                $button.text(originalText).attr('class', originalClass);
            }, 2000);
        },

        showError: function(message) {
            var $errorContainer = $('.auth-errors');
            var $errorMessage = $errorContainer.find('.error-message');

            if ($errorContainer.length === 0) {
                $errorContainer = $('<div class="auth-errors"><div class="error-message"></div><div class="error-actions"><button type="button" class="retry-btn">Try Again</button></div></div>');
                $('.license-auth-form').after($errorContainer);
                $errorMessage = $errorContainer.find('.error-message');
            }

            $errorMessage.text(message);
            $errorContainer.show();

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $errorContainer.fadeOut();
            }, 5000);
        },

        retryAuthentication: function(e) {
            e.preventDefault();

            // Hide error and clear form
            $('.auth-errors').hide();
            $('.license-key-input').val('').focus();

            // Show auth form if it's hidden
            $('.vd-portal-auth').show();
            $('.vd-portal-content').hide();
        },

        // Utility functions
        generateDeviceFingerprint: function() {
            // Basic device fingerprinting for Sprint 1
            var components = [
                navigator.userAgent,
                screen.width + 'x' + screen.height,
                navigator.language,
                new Date().getTimezoneOffset()
            ];

            var fingerprint = components.join('|');

            // Simple hash function (will be replaced with crypto.subtle.digest in Sprint 4)
            var hash = 0;
            for (var i = 0; i < fingerprint.length; i++) {
                var char = fingerprint.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32-bit integer
            }

            // Convert to hex and pad to 64 characters (simulated SHA-256)
            return Math.abs(hash).toString(16).padStart(64, '0');
        },

        validateLicenseKey: function(licenseKey) {
            // For Sprint 1, just do basic format validation
            // Real validation will be implemented in Sprint 4
            return VDLicensePortal.isValidLicenseKeyFormat(licenseKey);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on a page with the portal
        if ($('.vd-license-portal').length > 0) {
            VDLicensePortal.init();
        }
    });

})(jQuery);
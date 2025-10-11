/**
 * VD License Manager - Public JavaScript
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/public/js
 * @since      1.0.0
 */

(function($) {
    'use strict';

    /**
     * VD Public Object
     */
    var VDPublic = {

        /**
         * Initialize public functionality
         */
        init: function() {
            this.bindEvents();
            console.log('VD License Manager Public initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // License form submission
            $(document).on('submit', '.vd-license-form', this.handleLicenseSubmit);

            // Copy to clipboard functionality
            $(document).on('click', '.vd-copy-btn', this.copyToClipboard);
            $(document).on('click', '.vd-copy-all-btn', this.copyAllCredentials);

            // License key input formatting
            $(document).on('input', '.vd-license-input', this.formatLicenseKey);

            // Device rotation request
            $(document).on('click', '.vd-rotate-device-btn', this.requestDeviceRotation);
        },

        /**
         * Handle license form submission
         */
        handleLicenseSubmit: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('.vd-form-button');
            var $licenseInput = $form.find('.vd-license-input');
            var licenseKey = $licenseInput.val().trim();

            // Validate license key format
            if (!VDPublic.isValidLicenseKey(licenseKey)) {
                VDPublic.showMessage(vd_public_ajax.strings.invalid_key, 'error');
                return;
            }

            // Show loading state
            var originalText = $submitBtn.text();
            $submitBtn.text(vd_public_ajax.strings.loading).prop('disabled', true);

            // Make API call
            $.ajax({
                url: vd_public_ajax.api_base + '/license/resolve-info',
                type: 'GET',
                data: {
                    license_key: licenseKey,
                    device_id: VDPublic.getDeviceId()
                },
                success: function(response) {
                    if (response.success) {
                        VDPublic.displayLicenseInfo(response.data);
                        VDPublic.showMessage('License information loaded successfully!', 'success');
                    } else {
                        VDPublic.showMessage(response.data.message || 'Failed to load license information', 'error');
                    }
                },
                error: function(xhr) {
                    var message = vd_public_ajax.strings.error;

                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    } else if (xhr.status === 401) {
                        message = vd_public_ajax.strings.access_denied;
                    }

                    VDPublic.showMessage(message, 'error');
                },
                complete: function() {
                    $submitBtn.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $item = $btn.closest('.vd-credential-item');
            var $value = $item.find('.vd-credential-value');
            var text = $value.text().trim();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                // Modern clipboard API
                navigator.clipboard.writeText(text).then(function() {
                    VDPublic.showCopyFeedback($btn);
                }).catch(function() {
                    VDPublic.fallbackCopy(text, $btn);
                });
            } else {
                // Fallback for older browsers
                VDPublic.fallbackCopy(text, $btn);
            }
        },

        /**
         * Copy all credentials to clipboard
         */
        copyAllCredentials: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $credentials = $btn.closest('.vd-credentials');
            var credentials = [];

            $credentials.find('.vd-credential-item').each(function() {
                var label = $(this).find('.vd-credential-label').text().trim();
                var value = $(this).find('.vd-credential-value').text().trim();
                credentials.push(label + ': ' + value);
            });

            var text = credentials.join('\n');

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    VDPublic.showCopyFeedback($btn, 'All credentials copied!');
                }).catch(function() {
                    VDPublic.fallbackCopy(text, $btn);
                });
            } else {
                VDPublic.fallbackCopy(text, $btn);
            }
        },

        /**
         * Format license key input
         */
        formatLicenseKey: function(e) {
            var $input = $(this);
            var value = $input.val().toUpperCase();

            // Remove any non-alphanumeric characters except hyphens
            value = value.replace(/[^A-Z0-9-]/g, '');

            // Format as XXXX-XXXX-XXXX-XXXX
            if (value.length > 0) {
                value = value.replace(/-/g, ''); // Remove existing hyphens
                value = value.match(/.{1,4}/g).join('-'); // Add hyphens every 4 characters
            }

            $input.val(value);
        },

        /**
         * Request device rotation
         */
        requestDeviceRotation: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to rotate your device? This will require you to re-authenticate.')) {
                return;
            }

            var $btn = $(this);
            var licenseKey = $btn.data('license-key');
            var originalText = $btn.text();

            $btn.text(vd_public_ajax.strings.loading).prop('disabled', true);

            $.ajax({
                url: vd_public_ajax.api_base + '/device/rotate',
                type: 'POST',
                data: {
                    license_key: licenseKey,
                    device_id: VDPublic.getDeviceId()
                },
                success: function(response) {
                    if (response.success) {
                        VDPublic.showMessage('Device rotation requested successfully!', 'success');
                        // Refresh the page after a short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        VDPublic.showMessage(response.data.message || 'Failed to rotate device', 'error');
                    }
                },
                error: function(xhr) {
                    var message = 'Failed to rotate device';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    VDPublic.showMessage(message, 'error');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Display license information
         */
        displayLicenseInfo: function(data) {
            // This will be implemented in future phases
            // For now, just show the data structure
            console.log('License Data:', data);

            // Create or update license info display
            var $container = $('.vd-license-results');
            if (!$container.length) {
                $container = $('<div class="vd-license-results"></div>');
                $('.vd-license-form').after($container);
            }

            var html = VDPublic.buildLicenseInfoHTML(data);
            $container.html(html);
        },

        /**
         * Build license information HTML
         */
        buildLicenseInfoHTML: function(data) {
            var html = '';

            // License basic info
            html += '<div class="vd-license-info">';
            html += '<h3>License Information</h3>';
            html += '<p><strong>License Key:</strong> ' + data.license_key + '</p>';
            html += '<p><strong>Status:</strong> <span class="vd-license-status ' + data.status + '">' + data.status + '</span></p>';
            html += '<p><strong>Product:</strong> ' + data.product_name + '</p>';
            html += '<p><strong>Valid Until:</strong> ' + data.valid_until + '</p>';
            html += '</div>';

            // Account credentials
            if (data.credentials) {
                html += '<div class="vd-credentials">';
                html += '<div class="vd-credentials-header">';
                html += '<h4 class="vd-credentials-title">Account Credentials</h4>';
                html += '<button type="button" class="vd-copy-all-btn">Copy All</button>';
                html += '</div>';

                $.each(data.credentials, function(key, value) {
                    html += '<div class="vd-credential-item">';
                    html += '<span class="vd-credential-label">' + VDPublic.formatFieldName(key) + ':</span>';
                    html += '<span class="vd-credential-value">' + value + '</span>';
                    html += '<button type="button" class="vd-copy-btn">Copy</button>';
                    html += '</div>';
                });

                html += '</div>';
            }

            // Device information
            if (data.devices) {
                html += '<div class="vd-device-info">';
                html += '<h4>Your Devices</h4>';
                html += '<ul class="vd-device-list">';

                $.each(data.devices, function(index, device) {
                    html += '<li class="vd-device-item">';
                    html += '<span class="vd-device-name">' + device.device_name + '</span>';
                    html += '<span class="vd-device-status">' + device.status + '</span>';
                    html += '</li>';
                });

                html += '</ul>';
                html += '<button type="button" class="vd-rotate-device-btn" data-license-key="' + data.license_key + '">Request Device Rotation</button>';
                html += '</div>';
            }

            return html;
        },

        /**
         * Validate license key format
         */
        isValidLicenseKey: function(key) {
            // Basic validation: XXXX-XXXX-XXXX-XXXX format
            var pattern = /^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/;
            return pattern.test(key);
        },

        /**
         * Get device ID (simplified fingerprinting)
         */
        getDeviceId: function() {
            // This is a simplified device ID - real implementation will be more comprehensive
            var deviceId = localStorage.getItem('vd_device_id');

            if (!deviceId) {
                // Generate a simple device ID based on browser characteristics
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                ctx.textBaseline = 'top';
                ctx.font = '14px Arial';
                ctx.fillText('Device fingerprint', 2, 2);

                deviceId = btoa(
                    navigator.userAgent +
                    screen.width + 'x' + screen.height +
                    new Date().getTimezoneOffset() +
                    navigator.language +
                    canvas.toDataURL()
                ).replace(/[^A-Za-z0-9]/g, '').substr(0, 32);

                localStorage.setItem('vd_device_id', deviceId);
            }

            return deviceId;
        },

        /**
         * Show copy feedback
         */
        showCopyFeedback: function($btn, message) {
            message = message || 'Copied!';
            var originalText = $btn.text();

            $btn.text(message);
            setTimeout(function() {
                $btn.text(originalText);
            }, 1500);
        },

        /**
         * Fallback copy method for older browsers
         */
        fallbackCopy: function(text, $btn) {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();

            try {
                document.execCommand('copy');
                VDPublic.showCopyFeedback($btn);
            } catch (err) {
                VDPublic.showMessage('Copy failed. Please copy manually.', 'warning');
            }

            $temp.remove();
        },

        /**
         * Format field names for display
         */
        formatFieldName: function(fieldName) {
            return fieldName.replace(/_/g, ' ').replace(/\b\w/g, function(l) {
                return l.toUpperCase();
            });
        },

        /**
         * Show message to user
         */
        showMessage: function(message, type) {
            type = type || 'info';

            var $message = $('<div class="vd-message ' + type + '">' + message + '</div>');

            // Insert at top of portal
            var $portal = $('.vd-license-portal');
            if ($portal.length) {
                $portal.prepend($message);
            } else {
                $('body').prepend($message);
            }

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 5000);
        },

        /**
         * Utility: Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        VDPublic.init();
    });

    /**
     * Make VDPublic globally available
     */
    window.VDPublic = VDPublic;

})(jQuery);
/**
 * VD License Manager - Admin JavaScript
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/admin/js
 * @since      1.0.0
 */

(function($) {
    'use strict';

    /**
     * VD Admin Object
     */
    var VDAdmin = {

        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            console.log('VD License Manager Admin initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Confirmation dialogs for delete actions
            $(document).on('click', '.vd-delete-confirm', this.confirmDelete);

            // Test buttons
            $(document).on('click', '.vd-test-encryption', this.testEncryption);
            $(document).on('click', '.vd-test-email', this.testEmail);

            // Refresh actions
            $(document).on('click', '.vd-refresh-stats', this.refreshStats);

            // Placeholder button clicks (for future implementation)
            $(document).on('click', 'button[disabled]', this.placeholderClick);
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Add tooltips to status indicators
            $('.vd-status-ok').attr('title', vd_admin_ajax.strings.status_ok || 'Status: OK');
            $('.vd-status-warning').attr('title', vd_admin_ajax.strings.status_warning || 'Status: Warning');
            $('.vd-status-error').attr('title', vd_admin_ajax.strings.status_error || 'Status: Error');
        },

        /**
         * Confirm delete actions
         */
        confirmDelete: function(e) {
            e.preventDefault();

            if (!confirm(vd_admin_ajax.strings.confirm_delete)) {
                return false;
            }

            // Proceed with delete action (future implementation)
            var $button = $(this);
            var actionUrl = $button.attr('href') || $button.data('action-url');

            if (actionUrl) {
                window.location.href = actionUrl;
            }
        },

        /**
         * Test encryption functionality
         */
        testEncryption: function(e) {
            e.preventDefault();

            var $button = $(this);
            var originalText = $button.text();

            $button.text(vd_admin_ajax.strings.loading).prop('disabled', true);

            $.ajax({
                url: vd_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vd_test_encryption',
                    nonce: vd_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VDAdmin.showNotice('Encryption test passed!', 'success');
                    } else {
                        VDAdmin.showNotice('Encryption test failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    VDAdmin.showNotice(vd_admin_ajax.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Test email functionality
         */
        testEmail: function(e) {
            e.preventDefault();

            var $button = $(this);
            var originalText = $button.text();
            var email = prompt('Enter email address to send test email to:', '');

            if (!email) {
                return;
            }

            $button.text(vd_admin_ajax.strings.loading).prop('disabled', true);

            $.ajax({
                url: vd_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vd_test_email',
                    nonce: vd_admin_ajax.nonce,
                    email: email
                },
                success: function(response) {
                    if (response.success) {
                        VDAdmin.showNotice('Test email sent successfully!', 'success');
                    } else {
                        VDAdmin.showNotice('Failed to send test email: ' + response.data, 'error');
                    }
                },
                error: function() {
                    VDAdmin.showNotice(vd_admin_ajax.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Refresh dashboard statistics
         */
        refreshStats: function(e) {
            e.preventDefault();

            var $button = $(this);
            var originalText = $button.text();

            $button.text(vd_admin_ajax.strings.loading).prop('disabled', true);

            $.ajax({
                url: vd_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vd_refresh_stats',
                    nonce: vd_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update stats on page
                        if (response.data.stats) {
                            VDAdmin.updateStats(response.data.stats);
                        }
                        VDAdmin.showNotice('Statistics refreshed!', 'success');
                    } else {
                        VDAdmin.showNotice('Failed to refresh statistics: ' + response.data, 'error');
                    }
                },
                error: function() {
                    VDAdmin.showNotice(vd_admin_ajax.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Handle placeholder button clicks
         */
        placeholderClick: function(e) {
            e.preventDefault();

            var $button = $(this);
            var feature = $button.text();

            VDAdmin.showNotice('Feature "' + feature + '" is coming soon!', 'info');
        },

        /**
         * Update dashboard statistics
         */
        updateStats: function(stats) {
            $.each(stats, function(key, value) {
                $('.vd-stat-card[data-stat="' + key + '"] .stat-number').text(value);
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';

            var noticeClass = 'notice-' + type;
            if (type === 'success') noticeClass = 'notice-success';
            if (type === 'error') noticeClass = 'notice-error';
            if (type === 'warning') noticeClass = 'notice-warning';

            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            // Insert after h1 or at top of content
            var $target = $('.vd-admin-wrap h1').first();
            if ($target.length) {
                $target.after($notice);
            } else {
                $('.vd-admin-wrap').prepend($notice);
            }

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);

            // Make dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            });
        },

        /**
         * Utility: Format numbers
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        /**
         * Utility: Validate email
         */
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Utility: Show loading state
         */
        showLoading: function($element) {
            var $loader = $('<span class="vd-loading"><span class="spinner is-active"></span></span>');
            $element.append($loader);
        },

        /**
         * Utility: Hide loading state
         */
        hideLoading: function($element) {
            $element.find('.vd-loading').remove();
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        VDAdmin.init();
    });

    /**
     * Make VDAdmin globally available
     */
    window.VDAdmin = VDAdmin;

})(jQuery);
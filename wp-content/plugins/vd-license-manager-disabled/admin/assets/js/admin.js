/*
 * VD License Manager - Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Main admin object
    window.VDLicenseAdmin = {
        init: function() {
            this.setupEventListeners();
            this.initializeComponents();
            this.handleFormSubmissions();
        },

        setupEventListeners: function() {
            // Test connection button
            $(document).on('click', '.vd-test-connection', this.testConnection);

            // Confirmation dialogs
            $(document).on('click', '.vd-confirm-action', this.confirmAction);

            // Form changes tracking
            $(document).on('change', '.vd-form input, .vd-form select, .vd-form textarea', this.trackFormChanges);

            // Auto-save functionality
            $(document).on('blur', '.vd-auto-save', this.autoSave);
        },

        initializeComponents: function() {
            // Initialize tooltips
            this.initTooltips();

            // Initialize status indicators
            this.updateStatusIndicators();

            // Initialize copy buttons
            this.initCopyButtons();

            // Check for notifications
            this.checkNotifications();
        },

        handleFormSubmissions: function() {
            $('.vd-form').on('submit', function(e) {
                var $form = $(this);
                var $submitButton = $form.find('input[type="submit"], button[type="submit"]');

                // Disable submit button to prevent double submission
                $submitButton.prop('disabled', true).addClass('updating-message');

                // Re-enable after a delay if no page reload
                setTimeout(function() {
                    $submitButton.prop('disabled', false).removeClass('updating-message');
                }, 3000);
            });
        },

        testConnection: function(e) {
            e.preventDefault();

            var $button = $(this);
            var $spinner = $button.find('.spinner');
            var originalText = $button.text();

            // Show loading state
            $button.prop('disabled', true).text(vd_admin_ajax.strings.saving);
            if ($spinner.length === 0) {
                $button.append('<span class="spinner is-active" style="float: none; margin-left: 5px;"></span>');
            } else {
                $spinner.addClass('is-active');
            }

            // Make AJAX request
            $.ajax({
                url: vd_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vd_test_connection',
                    nonce: vd_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VDLicenseAdmin.showNotification('success', 'Connection test successful');
                    } else {
                        VDLicenseAdmin.showNotification('error', response.data.message || 'Connection test failed');
                    }
                },
                error: function() {
                    VDLicenseAdmin.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    // Reset button state
                    $button.prop('disabled', false).text(originalText);
                    $button.find('.spinner').removeClass('is-active');
                }
            });
        },

        confirmAction: function(e) {
            var message = $(this).data('confirm') || vd_admin_ajax.strings.confirm_delete;

            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },

        trackFormChanges: function() {
            var $form = $(this).closest('form');
            $form.addClass('has-changes');

            // Show unsaved changes warning
            if (!$form.hasClass('warned')) {
                $form.addClass('warned');
                VDLicenseAdmin.showNotification('warning', 'You have unsaved changes');
            }
        },

        autoSave: function() {
            var $field = $(this);
            var fieldName = $field.attr('name');
            var fieldValue = $field.val();

            if (!fieldName || fieldValue === $field.data('original-value')) {
                return;
            }

            // Show saving indicator
            var $indicator = $field.siblings('.auto-save-indicator');
            if ($indicator.length === 0) {
                $indicator = $('<span class="auto-save-indicator">Saving...</span>');
                $field.after($indicator);
            }

            $indicator.text('Saving...').show();

            // Make AJAX request to save
            $.ajax({
                url: vd_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vd_auto_save_field',
                    field_name: fieldName,
                    field_value: fieldValue,
                    nonce: vd_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $indicator.text('Saved').fadeOut(2000);
                        $field.data('original-value', fieldValue);
                    } else {
                        $indicator.text('Save failed').addClass('error');
                    }
                },
                error: function() {
                    $indicator.text('Save failed').addClass('error');
                }
            });
        },

        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltip = $element.data('tooltip');

                $element.on('mouseenter', function() {
                    var $tooltip = $('<div class="vd-tooltip">' + tooltip + '</div>');
                    $('body').append($tooltip);

                    var offset = $element.offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 5,
                        left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    });
                });

                $element.on('mouseleave', function() {
                    $('.vd-tooltip').remove();
                });
            });
        },

        updateStatusIndicators: function() {
            $('.status-indicator[data-status-url]').each(function() {
                var $indicator = $(this);
                var statusUrl = $indicator.data('status-url');

                $.get(statusUrl, function(response) {
                    if (response.success) {
                        $indicator.removeClass('status-error status-warning status-inactive')
                                 .addClass('status-active')
                                 .text(response.data.status);
                    } else {
                        $indicator.removeClass('status-active')
                                 .addClass('status-error')
                                 .text('Error');
                    }
                });
            });
        },

        initCopyButtons: function() {
            $('.vd-copy-button').on('click', function(e) {
                e.preventDefault();

                var $button = $(this);
                var textToCopy = $button.data('copy-text') || $button.siblings('input, textarea').val();

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(textToCopy).then(function() {
                        VDLicenseAdmin.showCopyFeedback($button);
                    });
                } else {
                    // Fallback for older browsers
                    var $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(textToCopy).select();
                    document.execCommand('copy');
                    $temp.remove();
                    VDLicenseAdmin.showCopyFeedback($button);
                }
            });
        },

        showCopyFeedback: function($button) {
            var originalText = $button.text();
            $button.text('Copied!').addClass('copied');

            setTimeout(function() {
                $button.text(originalText).removeClass('copied');
            }, 2000);
        },

        checkNotifications: function() {
            // Check for server-side notifications
            $.get(vd_admin_ajax.ajax_url, {
                action: 'vd_get_notifications',
                nonce: vd_admin_ajax.nonce
            }, function(response) {
                if (response.success && response.data.notifications) {
                    response.data.notifications.forEach(function(notification) {
                        VDLicenseAdmin.showNotification(notification.type, notification.message);
                    });
                }
            });
        },

        showNotification: function(type, message, persistent) {
            var $notification = $('<div class="notice notice-' + type + ' is-dismissible vd-notification">')
                .append('<p>' + message + '</p>');

            if (!persistent) {
                $notification.append('<button type="button" class="notice-dismiss"></button>');
            }

            $('.wrap').prepend($notification);

            // Auto-dismiss after 5 seconds for non-persistent notifications
            if (!persistent) {
                setTimeout(function() {
                    $notification.fadeOut(function() {
                        $notification.remove();
                    });
                }, 5000);
            }

            // Handle dismiss button
            $notification.find('.notice-dismiss').on('click', function() {
                $notification.fadeOut(function() {
                    $notification.remove();
                });
            });
        },

        // Utility functions
        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';

            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];

            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

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

    // Initialize when document is ready
    $(document).ready(function() {
        VDLicenseAdmin.init();
    });

    // Handle page unload warnings for unsaved changes
    $(window).on('beforeunload', function() {
        if ($('.has-changes').length > 0) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    // Remove warning when form is submitted
    $('.vd-form').on('submit', function() {
        $(this).removeClass('has-changes warned');
    });

})(jQuery);
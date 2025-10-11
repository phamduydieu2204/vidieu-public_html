/**
 * Account Form JavaScript
 *
 * Handles form interactions, validation, and dynamic functionality
 * for the provider account add/edit form.
 *
 * @package    VD_License_Manager
 * @subpackage Admin/JavaScript
 * @since      1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        VDAccountForm.init();
    });

    /**
     * Main Account Form Handler
     */
    const VDAccountForm = {

        /**
         * Custom field counter
         */
        customFieldIndex: 0,

        /**
         * Initialize all form functionality
         */
        init: function() {
            this.initializePostboxes();
            this.initializePasswordToggles();
            this.initializeCustomFields();
            this.initializeFormValidation();
            this.initializeAutoFill();
            this.initializeEncryptedFields();
        },

        /**
         * Initialize collapsible postboxes
         */
        initializePostboxes: function() {
            $('.postbox .handlediv').on('click', function(e) {
                e.preventDefault();

                const $postbox = $(this).closest('.postbox');
                const $inside = $postbox.find('.inside');
                const $button = $(this);
                const isVisible = $inside.is(':visible');

                $inside.toggle();
                $button.attr('aria-expanded', !isVisible);

                // Save state in localStorage
                const postboxId = $postbox.attr('id') || $postbox.find('.hndle').text().trim();
                if (postboxId) {
                    localStorage.setItem('vd_postbox_' + postboxId, !isVisible ? 'open' : 'closed');
                }
            });

            // Restore postbox states
            $('.postbox').each(function() {
                const $postbox = $(this);
                const postboxId = $postbox.attr('id') || $postbox.find('.hndle').text().trim();
                const state = localStorage.getItem('vd_postbox_' + postboxId);

                if (state === 'closed') {
                    $postbox.find('.inside').hide();
                    $postbox.find('.handlediv').attr('aria-expanded', 'false');
                }
            });
        },

        /**
         * Initialize password toggle functionality
         */
        initializePasswordToggles: function() {
            $('.vd-toggle-password').on('click', function(e) {
                e.preventDefault();

                const target = $(this).data('target');
                const $field = $('#' + target);
                const $button = $(this);

                if ($field.length === 0) {
                    console.warn('Password field not found:', target);
                    return;
                }

                const isPassword = $field.attr('type') === 'password';

                // Toggle field type
                $field.attr('type', isPassword ? 'text' : 'password');

                // Update button text and icon
                if (isPassword) {
                    $button.html('🙈 ' + vdAccountFormL10n.hide);
                } else {
                    $button.html('👁️ ' + vdAccountFormL10n.show);
                }

                // Add visual feedback
                $button.addClass('vd-button-clicked');
                setTimeout(() => {
                    $button.removeClass('vd-button-clicked');
                }, 150);
            });
        },

        /**
         * Initialize custom fields management
         */
        initializeCustomFields: function() {
            // Set initial counter
            this.customFieldIndex = $('#custom-fields-container .vd-custom-field-row').length;

            // Add custom field button
            $('#add-custom-field').on('click', (e) => {
                e.preventDefault();
                this.addCustomField();
            });

            // Remove custom field (delegated event)
            $(document).on('click', '.vd-remove-field', function(e) {
                e.preventDefault();

                const $row = $(this).closest('.vd-custom-field-row');
                $row.fadeOut(300, function() {
                    $row.remove();
                });
            });

            // Handle type change for custom fields
            $(document).on('change', 'select[name="custom_field_type[]"]', function() {
                const type = $(this).val();
                const $row = $(this).closest('.vd-custom-field-row');
                const $valueInput = $row.find('input[name="custom_field_value[]"], textarea[name="custom_field_value[]"]');
                const currentValue = $valueInput.val();

                // Replace with appropriate input type
                let newInput;
                if (type === 'textarea') {
                    newInput = $('<textarea></textarea>')
                        .attr('name', 'custom_field_value[]')
                        .attr('rows', '3')
                        .addClass('large-text')
                        .attr('placeholder', vdAccountFormL10n.fieldValue)
                        .val(currentValue);
                } else {
                    newInput = $('<input>')
                        .attr('type', type)
                        .attr('name', 'custom_field_value[]')
                        .attr('placeholder', vdAccountFormL10n.fieldValue)
                        .addClass('large-text')
                        .val(currentValue);
                }

                $valueInput.replaceWith(newInput);
            });
        },

        /**
         * Add new custom field row
         */
        addCustomField: function() {
            const fieldHtml = `
                <div class="vd-custom-field-row" style="display: none;">
                    <div class="vd-grid-4">
                        <input type="text"
                               name="custom_field_key[]"
                               placeholder="${vdAccountFormL10n.fieldKey}"
                               class="regular-text"
                               required>
                        <input type="text"
                               name="custom_field_label[]"
                               placeholder="${vdAccountFormL10n.fieldLabel}"
                               class="regular-text"
                               required>
                        <select name="custom_field_type[]" class="regular-text">
                            <option value="text">${vdAccountFormL10n.fieldTypes.text}</option>
                            <option value="email">${vdAccountFormL10n.fieldTypes.email}</option>
                            <option value="url">${vdAccountFormL10n.fieldTypes.url}</option>
                            <option value="tel">${vdAccountFormL10n.fieldTypes.tel}</option>
                            <option value="password">${vdAccountFormL10n.fieldTypes.password}</option>
                            <option value="textarea">${vdAccountFormL10n.fieldTypes.textarea}</option>
                        </select>
                        <button type="button"
                                class="button button-small vd-remove-field">
                            ${vdAccountFormL10n.remove}
                        </button>
                    </div>
                    <input type="text"
                           name="custom_field_value[]"
                           placeholder="${vdAccountFormL10n.fieldValue}"
                           class="large-text"
                           style="margin-top: 5px;">
                </div>
            `;

            const $newField = $(fieldHtml);
            $('#custom-fields-container').append($newField);
            $newField.fadeIn(300);

            // Focus on the first input
            $newField.find('input[name="custom_field_key[]"]').focus();

            this.customFieldIndex++;
        },

        /**
         * Initialize form validation
         */
        initializeFormValidation: function() {
            $('.vd-account-form').on('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                    return false;
                }
            });

            // Real-time validation
            $('#provider').on('blur', () => this.validateField('provider'));
            $('#account_login').on('blur', () => this.validateField('account_login'));
            $('#capacity').on('blur', () => this.validateField('capacity'));

            // Only validate password on create
            if (vdAccountFormL10n.isEdit === '0') {
                $('#account_password').on('blur', () => this.validateField('account_password'));
            }
        },

        /**
         * Validate entire form
         */
        validateForm: function() {
            let isValid = true;
            const errors = [];

            // Clear previous errors
            $('.vd-form-row').removeClass('error');
            $('.vd-error-message').remove();

            // Validate required fields
            const validations = [
                { field: 'provider', message: vdAccountFormL10n.errors.providerRequired },
                { field: 'account_login', message: vdAccountFormL10n.errors.loginRequired },
                { field: 'capacity', message: vdAccountFormL10n.errors.capacityInvalid, validator: this.validateCapacity }
            ];

            // Add password validation for new accounts
            if (vdAccountFormL10n.isEdit === '0') {
                validations.push({
                    field: 'account_password',
                    message: vdAccountFormL10n.errors.passwordRequired
                });
            }

            validations.forEach(validation => {
                const value = $('#' + validation.field).val();
                let fieldValid = true;

                if (validation.validator) {
                    fieldValid = validation.validator(value);
                } else {
                    fieldValid = value && value.trim() !== '';
                }

                if (!fieldValid) {
                    this.showFieldError(validation.field, validation.message);
                    errors.push(validation.message);
                    isValid = false;
                }
            });

            // Show summary if there are errors
            if (!isValid) {
                this.showErrorSummary(errors);
            }

            return isValid;
        },

        /**
         * Validate individual field
         */
        validateField: function(fieldName) {
            const $field = $('#' + fieldName);
            const value = $field.val();
            let isValid = true;
            let message = '';

            switch (fieldName) {
                case 'provider':
                case 'account_login':
                    isValid = value && value.trim() !== '';
                    message = fieldName === 'provider' ?
                        vdAccountFormL10n.errors.providerRequired :
                        vdAccountFormL10n.errors.loginRequired;
                    break;

                case 'capacity':
                    isValid = this.validateCapacity(value);
                    message = vdAccountFormL10n.errors.capacityInvalid;
                    break;

                case 'account_password':
                    if (vdAccountFormL10n.isEdit === '0') {
                        isValid = value && value.trim() !== '';
                        message = vdAccountFormL10n.errors.passwordRequired;
                    }
                    break;
            }

            if (isValid) {
                this.clearFieldError(fieldName);
            } else {
                this.showFieldError(fieldName, message);
            }

            return isValid;
        },

        /**
         * Validate capacity value
         */
        validateCapacity: function(value) {
            const capacity = parseInt(value);
            return !isNaN(capacity) && capacity >= 1 && capacity <= 100;
        },

        /**
         * Show field error
         */
        showFieldError: function(fieldName, message) {
            const $field = $('#' + fieldName);
            const $row = $field.closest('.vd-form-row');

            $row.addClass('error');

            // Remove existing error message
            $row.find('.vd-error-message').remove();

            // Add new error message
            $field.after(`<span class="vd-error-message">${message}</span>`);

            // Scroll to first error
            if ($('.error').length === 1) {
                $('html, body').animate({
                    scrollTop: $row.offset().top - 100
                }, 300);
            }
        },

        /**
         * Clear field error
         */
        clearFieldError: function(fieldName) {
            const $field = $('#' + fieldName);
            const $row = $field.closest('.vd-form-row');

            $row.removeClass('error');
            $row.find('.vd-error-message').remove();
        },

        /**
         * Show error summary
         */
        showErrorSummary: function(errors) {
            const $form = $('.vd-account-form');

            // Remove existing summary
            $('.vd-error-summary').remove();

            const errorHtml = `
                <div class="notice notice-error vd-error-summary">
                    <p><strong>${vdAccountFormL10n.errors.formErrors}</strong></p>
                    <ul>
                        ${errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            `;

            $form.before(errorHtml);

            // Scroll to summary
            $('html, body').animate({
                scrollTop: $('.vd-error-summary').offset().top - 100
            }, 300);
        },

        /**
         * Initialize auto-fill functionality
         */
        initializeAutoFill: function() {
            // Auto-fill display name from account login
            $('#account_login').on('blur', function() {
                const accountLogin = $(this).val();
                const $displayName = $('#display_name');

                if (accountLogin && !$displayName.val()) {
                    // Extract name from email or use full login
                    let displayName = accountLogin;
                    if (accountLogin.includes('@')) {
                        displayName = accountLogin.split('@')[0];
                    }

                    // Capitalize first letter
                    displayName = displayName.charAt(0).toUpperCase() + displayName.slice(1);

                    $displayName.val(displayName);
                }
            });

            // Auto-suggest provider names
            const commonProviders = [
                'Netflix', 'Spotify', 'Helium10', 'ChatGPT', 'YouTube Premium',
                'Amazon Prime', 'Disney+', 'Hulu', 'HBO Max', 'Apple Music'
            ];

            $('#provider').on('input', function() {
                const value = $(this).val().toLowerCase();

                if (value.length >= 2) {
                    const suggestions = commonProviders.filter(provider =>
                        provider.toLowerCase().includes(value)
                    );

                    // Simple autocomplete (could be enhanced with a proper dropdown)
                    if (suggestions.length === 1 &&
                        suggestions[0].toLowerCase().startsWith(value)) {
                        const suggestion = suggestions[0];
                        $(this).val(suggestion);

                        // Select the auto-completed part
                        if (this.setSelectionRange) {
                            this.setSelectionRange(value.length, suggestion.length);
                        }
                    }
                }
            });
        },

        /**
         * Initialize encrypted fields handling
         */
        initializeEncryptedFields: function() {
            if (vdAccountFormL10n.isEdit === '1') {
                // Handle encrypted fields that show placeholder values
                const encryptedFields = [
                    'cookies', 'phone_recovery', 'email_recovery',
                    'security_answer', 'backup_codes', 'two_factor_secret',
                    'api_key', 'secret_key', 'api_token'
                ];

                encryptedFields.forEach(fieldName => {
                    const $field = $('#' + fieldName);

                    if ($field.length > 0) {
                        // Clear placeholder on focus
                        $field.on('focus', function() {
                            if ($(this).val() === '••••••••••••••••') {
                                $(this).val('');
                                $(this).addClass('vd-field-editing');
                            }
                        });

                        // Restore placeholder if empty on blur
                        $field.on('blur', function() {
                            if ($(this).val() === '') {
                                $(this).val('••••••••••••••••');
                                $(this).removeClass('vd-field-editing');
                            }
                        });

                        // Handle form submission
                        $('.vd-account-form').on('submit', function() {
                            encryptedFields.forEach(fname => {
                                const $f = $('#' + fname);
                                if ($f.val() === '••••••••••••••••') {
                                    $f.val(''); // Don't submit placeholder
                                }
                            });
                        });
                    }
                });
            }
        }
    };

    // Make VDAccountForm globally available for debugging
    window.VDAccountForm = VDAccountForm;

})(jQuery);
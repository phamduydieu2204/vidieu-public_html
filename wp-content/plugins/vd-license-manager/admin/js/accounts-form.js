/**
 * Account Form JavaScript - Enhanced Version
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

    console.log('🚀 VD Account Form JS Loading...');

    $(document).ready(function() {
        console.log('✅ VD Account Form Ready');

        // ==============================================
        // PASSWORD SHOW/HIDE TOGGLE
        // ==============================================

        function initPasswordToggles() {
            console.log('🔐 Initializing password toggles...');

            // Method 1: Class-based delegation (most reliable)
            $(document).off('click.vdPassword').on('click.vdPassword', '.vd-toggle-password', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $button = $(this);
                const targetId = $button.attr('data-target');

                console.log('🔘 Toggle clicked for:', targetId);

                if (!targetId) {
                    console.error('❌ No data-target attribute');
                    return;
                }

                const $input = $('#' + targetId);

                if (!$input.length) {
                    console.error('❌ Input not found:', targetId);
                    return;
                }

                const currentType = $input.attr('type');
                console.log('Current type:', currentType);

                if (currentType === 'password') {
                    // Show password
                    $input.attr('type', 'text');
                    $button.find('.dashicons')
                        .removeClass('dashicons-visibility')
                        .addClass('dashicons-hidden');
                    $button.find('.toggle-text').text('Hide');
                    $button.addClass('active');
                    console.log('✅ Password shown');
                } else {
                    // Hide password
                    $input.attr('type', 'password');
                    $button.find('.dashicons')
                        .removeClass('dashicons-hidden')
                        .addClass('dashicons-visibility');
                    $button.find('.toggle-text').text('Show');
                    $button.removeClass('active');
                    console.log('✅ Password hidden');
                }
            });

            console.log('✅ Password toggles initialized');
        }

        // Initialize immediately
        initPasswordToggles();


        // ==============================================
        // CUSTOM FIELDS MANAGEMENT
        // ==============================================

        let customFieldIndex = $('#vd-custom-fields-container .vd-custom-field-row').length || 0;

        // Add Custom Field
        $('#vd-add-custom-field').off('click').on('click', function(e) {
            e.preventDefault();

            console.log('➕ Adding custom field...');

            const fieldHtml = `
                <div class="vd-custom-field-row" data-index="${customFieldIndex}">
                    <div class="vd-custom-field-inputs">
                        <input type="text"
                               name="custom_field_key[]"
                               placeholder="field_key (e.g., profile_id)"
                               class="regular-text"
                               required>

                        <input type="text"
                               name="custom_field_label[]"
                               placeholder="Field Label"
                               class="regular-text"
                               required>

                        <select name="custom_field_type[]" class="regular-text">
                            <option value="text">Text</option>
                            <option value="email">Email</option>
                            <option value="url">URL</option>
                            <option value="tel">Phone</option>
                            <option value="number">Number</option>
                            <option value="password">Password (encrypted)</option>
                            <option value="textarea">Long Text</option>
                        </select>

                        <button type="button"
                                class="button button-small vd-remove-custom-field"
                                title="Remove this field">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>

                    <div class="vd-custom-field-value">
                        <input type="text"
                               name="custom_field_value[]"
                               placeholder="Field value"
                               class="large-text">
                    </div>
                </div>
            `;

            $('#vd-custom-fields-container').append(fieldHtml);
            customFieldIndex++;

            console.log('✅ Custom field added. Total:', customFieldIndex);
        });

        // Remove Custom Field
        $(document).off('click.removeField').on('click.removeField', '.vd-remove-custom-field', function(e) {
            e.preventDefault();
            const $row = $(this).closest('.vd-custom-field-row');
            $row.fadeOut(300, function() {
                $(this).remove();
                console.log('✅ Custom field removed');
            });
        });

        // Change field type
        $(document).off('change.fieldType').on('change.fieldType', 'select[name="custom_field_type[]"]', function() {
            const type = $(this).val();
            const $row = $(this).closest('.vd-custom-field-row');
            const $valueContainer = $row.find('.vd-custom-field-value');
            const currentValue = $valueContainer.find('input, textarea').val() || '';

            let newInput;
            if (type === 'textarea') {
                newInput = `<textarea name="custom_field_value[]" rows="3" class="large-text" placeholder="Field value">${currentValue}</textarea>`;
            } else {
                newInput = `<input type="${type}" name="custom_field_value[]" class="large-text" placeholder="Field value" value="${currentValue}">`;
            }

            $valueContainer.html(newInput);
            console.log('✅ Field type changed to:', type);
        });


        // ==============================================
        // FORM SUBMISSION WITH VALIDATION
        // ==============================================

        $('.vd-account-form').on('submit', function(e) {
            console.log('📝 Form submitting...');

            // Clear previous errors
            $('.vd-field-error').remove();
            $('.vd-error-field').removeClass('vd-error-field');

            // Basic validation
            const provider = $('input[name="provider"]').val().trim();
            const login = $('input[name="account_login"]').val().trim();
            const password = $('input[name="account_password"]').val().trim();
            const isEdit = $(this).data('edit-mode');

            let hasError = false;

            if (!provider) {
                showFieldError('provider', 'Provider is required');
                hasError = true;
            }

            if (!login) {
                showFieldError('account_login', 'Account Login is required');
                hasError = true;
            }

            if (!password && !isEdit) {
                showFieldError('account_password', 'Password is required');
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.vd-error-field').first().offset().top - 100
                }, 500);
                return false;
            }

            // Show loading
            const $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Creating Account...');
        });

        function showFieldError(fieldName, message) {
            const $field = $(`[name="${fieldName}"]`);
            $field.addClass('vd-error-field');
            $field.after(`<p class="vd-field-error">${message}</p>`);
            console.log('❌ Validation error:', fieldName, message);
        }

    // ==========================================
    // POSTBOX COLLAPSING
    // ==========================================

    $('.postbox .handlediv').on('click', function() {
        const $postbox = $(this).closest('.postbox');
        const $inside = $postbox.find('.inside');
        const $button = $(this);

        $inside.toggle();
        $button.attr('aria-expanded', $inside.is(':visible'));

        // Save state in localStorage
        const postboxId = $postbox.attr('id') || $postbox.find('.hndle').text().trim();
        if (postboxId) {
            localStorage.setItem('vd_postbox_' + postboxId, $inside.is(':visible') ? 'open' : 'closed');
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

    // ==========================================
    // AUTO-FILL FUNCTIONALITY
    // ==========================================

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

    // ==========================================
    // ENCRYPTED FIELDS HANDLING
    // ==========================================

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
        }
    });

    // Handle form submission for encrypted fields
    $('.vd-account-form').on('submit', function() {
        encryptedFields.forEach(fname => {
            const $f = $('#' + fname);
            if ($f.val() === '••••••••••••••••') {
                $f.val(''); // Don't submit placeholder
            }
        });
    });


        console.log('✅ VD Account Form Initialized Successfully');
    });

})(jQuery);
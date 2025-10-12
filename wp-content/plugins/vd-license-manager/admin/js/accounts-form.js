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

jQuery(document).ready(function($) {
    'use strict';

    console.log('VD Account Form JS Loaded'); // Debug log

    // ==========================================
    // PASSWORD SHOW/HIDE TOGGLE
    // ==========================================

    // Method 1: Direct event (preferred)
    $('.vd-toggle-password').on('click', function(e) {
        e.preventDefault();

        const $button = $(this);
        const targetId = $button.data('target');
        const $input = $('#' + targetId);

        console.log('Toggle clicked for:', targetId); // Debug

        if (!$input.length) {
            console.error('Input not found:', targetId);
            return;
        }

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $button.html('<span class="dashicons dashicons-hidden"></span> Hide');
            $button.addClass('active');
        } else {
            $input.attr('type', 'password');
            $button.html('<span class="dashicons dashicons-visibility"></span> Show');
            $button.removeClass('active');
        }
    });

    // Method 2: Fallback with class-based toggle
    $(document).on('click', 'button[data-target]', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const targetId = $btn.data('target');
        const $field = $('#' + targetId);

        console.log('Fallback toggle for:', targetId); // Debug

        if ($field.length) {
            const isPassword = $field.attr('type') === 'password';
            $field.attr('type', isPassword ? 'text' : 'password');
            $btn.find('.dashicons').toggleClass('dashicons-visibility dashicons-hidden');

            // Update text while preserving emoji
            const textNode = $btn.contents().filter(function() {
                return this.nodeType === 3; // Text node
            });
            if (textNode.length) {
                textNode[0].nodeValue = isPassword ? ' Hide' : ' Show';
            }
        }
    });

    // ==========================================
    // CUSTOM FIELDS MANAGEMENT
    // ==========================================

    let customFieldIndex = $('#custom-fields-container .vd-custom-field-row').length || 0;

    // Add Custom Field - Multiple selectors for compatibility
    $('#add-custom-field, #vd-add-custom-field').on('click', function(e) {
        e.preventDefault();

        console.log('Add custom field clicked'); // Debug

        const fieldHtml = `
            <div class="vd-custom-field-row" data-index="${customFieldIndex}">
                <div class="vd-custom-field-inputs vd-grid-4">
                    <input type="text"
                           name="custom_field_key[]"
                           placeholder="field_key"
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
                            class="button vd-remove-custom-field vd-remove-field"
                            title="Remove field">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>

                <div class="vd-custom-field-value">
                    <input type="text"
                           name="custom_field_value[]"
                           placeholder="Field value"
                           class="large-text"
                           style="margin-top: 5px;">
                </div>
            </div>
        `;

        $('#custom-fields-container').append(fieldHtml);
        customFieldIndex++;

        console.log('Custom field added, total:', customFieldIndex);
    });

    // Remove Custom Field - Multiple selectors
    $(document).on('click', '.vd-remove-custom-field, .vd-remove-field', function(e) {
        e.preventDefault();
        console.log('Remove custom field clicked'); // Debug

        $(this).closest('.vd-custom-field-row').fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Change field type
    $(document).on('change', 'select[name="custom_field_type[]"]', function() {
        const type = $(this).val();
        const $row = $(this).closest('.vd-custom-field-row');
        const $valueContainer = $row.find('.vd-custom-field-value');
        const currentValue = $valueContainer.find('input, textarea').val() || '';

        let newInput;
        if (type === 'textarea') {
            newInput = `<textarea name="custom_field_value[]" rows="3" class="large-text" placeholder="Field value" style="margin-top: 5px;">${currentValue}</textarea>`;
        } else {
            newInput = `<input type="${type}" name="custom_field_value[]" class="large-text" placeholder="Field value" value="${currentValue}" style="margin-top: 5px;">`;
        }

        $valueContainer.html(newInput);
    });

    // ==========================================
    // FORM VALIDATION
    // ==========================================

    $('.vd-account-form').on('submit', function(e) {
        const provider = $('input[name="provider"]').val().trim();
        const login = $('input[name="account_login"]').val().trim();
        const password = $('input[name="account_password"]').val().trim();
        const isEditMode = $(this).data('edit-mode') || $('input[name="action"]').val() === 'update';

        console.log('Form validation:', { provider, login, password, isEditMode }); // Debug

        if (!provider || !login || (!password && !isEditMode)) {
            e.preventDefault();
            alert('Provider, Account Login, and Password are required.');
            return false;
        }

        // Show loading
        $(this).find('button[type="submit"]').prop('disabled', true).text('Saving...');
    });

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

    console.log('VD Account Form initialization complete');
});
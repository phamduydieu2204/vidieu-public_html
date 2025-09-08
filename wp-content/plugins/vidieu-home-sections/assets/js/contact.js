/**
 * Contact form JavaScript
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Auto-fill form from localStorage if available and fields are empty
        var savedInfo = localStorage.getItem('vd_contact_info');
        if (savedInfo) {
            try {
                var info = JSON.parse(savedInfo);
                // Check if info is not too old (7 days)
                if (info.timestamp && (Date.now() - info.timestamp) < 7 * 24 * 60 * 60 * 1000) {
                    // Only fill if fields are empty (no server-side values)
                    if (!$('#vd_contact_name').val() && info.name) {
                        $('#vd_contact_name').val(info.name);
                    }
                    if (!$('#vd_contact_email').val() && info.email) {
                        $('#vd_contact_email').val(info.email);
                    }
                    if (!$('#vd_contact_phone').val() && info.phone) {
                        $('#vd_contact_phone').val(info.phone);
                    }
                } else {
                    // Remove old data
                    localStorage.removeItem('vd_contact_info');
                }
            } catch(e) {
                // Invalid JSON, remove it
                localStorage.removeItem('vd_contact_info');
            }
        }
        
        // Handle contact form submission
        $('#vd-contact-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('.vd-contact-submit');
            var $btnText = $submitBtn.find('.vd-btn-text');
            var $btnLoading = $submitBtn.find('.vd-btn-loading');
            var $message = $form.find('.vd-form-message');
            
            // Clear previous messages
            $message.removeClass('success error').html('').hide();
            
            // Disable submit button and show loading
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            
            // Collect form data
            var formData = {
                action: 'vd_contact_submit',
                nonce: $form.find('#vd_contact_nonce').val(),
                name: $form.find('#vd_contact_name').val(),
                email: $form.find('#vd_contact_email').val(),
                phone: $form.find('#vd_contact_phone').val(),
                message: $form.find('#vd_contact_message').val()
            };
            
            // Save user info to localStorage for next time (if not logged in)
            if (formData.name || formData.email || formData.phone) {
                localStorage.setItem('vd_contact_info', JSON.stringify({
                    name: formData.name,
                    email: formData.email,
                    phone: formData.phone,
                    timestamp: Date.now()
                }));
            }
            
            // Submit form via AJAX
            $.post(vd_ajax_object.ajax_url, formData, function(response) {
                if (response.success) {
                    $message.addClass('success').html(response.data.message).show();
                    $form[0].reset(); // Clear form
                } else {
                    $message.addClass('error').html(response.data.message).show();
                }
            })
            .fail(function() {
                $message.addClass('error').html(vd_ajax_object.error_message || 'An error occurred. Please try again.').show();
            })
            .always(function() {
                // Re-enable submit button and hide loading
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();
            });
        });
        
        // Optional: Add form validation
        $('#vd-contact-form input[required], #vd-contact-form textarea[required]').on('blur', function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (!value) {
                $field.addClass('error');
            } else {
                $field.removeClass('error');
            }
        });
        
        // Clear error state on focus
        $('#vd-contact-form input, #vd-contact-form textarea').on('focus', function() {
            $(this).removeClass('error');
        });
        
        // Optional: Phone number formatting
        $('#vd_contact_phone').on('input', function() {
            var value = $(this).val();
            // Remove non-numeric characters except + and -
            value = value.replace(/[^\d\+\-\s]/g, '');
            $(this).val(value);
        });
    });
})(jQuery);
/* Document ready */
jQuery(document).ready(function($) {
"use strict";

$('body').on('ns_btns_fixed', function() {
    if ($('.ns_btn-fixed').length <= 0) {
        $('body').append('<div class="ns_btn-fixed nasa-flex"></div>');
    }

    if ($('.nasa-single-product-in-mobile .single_add_to_cart_button').length) {
        if ($('.ns_btn-fixed').find('.single_add_to_cart_button').length <= 0) {
            var _text = $('.nasa-single-product-in-mobile .single_add_to_cart_button').text();
            $('.ns_btn-fixed').append('<a href="javascript:void(0);" class="single_add_to_cart_button button">' + _text + '</a>');
        }
    }

    if ($('.nasa-single-product-in-mobile .nasa-buy-now').length) {
        if ($('.ns_btn-fixed').find('.nasa-buy-now').length <= 0) {
            var _text = $('.nasa-single-product-in-mobile .nasa-buy-now').text();
            $('.ns_btn-fixed').append('<a href="javascript:void(0);" class="nasa-buy-now button">' + _text + '</a>');
        }
    }
    
}).trigger('ns_btns_fixed');

$('body').on('ns_hide_simple_cart_form', function() {
    if ($('.nasa-single-product-in-mobile form.cart').length && $('.nasa-single-product-in-mobile form.cart').find('input[name="data-type"]').length) {
        if ($('.nasa-single-product-in-mobile form.cart').find('input[name="data-type"]').val() === 'simple') {
            $('.nasa-single-product-in-mobile form.cart').hide();
        }
    }
}).trigger('ns_hide_simple_cart_form');

$('body').on('nasa_after_loaded_ajax_complete', function() {
    $('body').trigger('ns_hide_simple_cart_form');
});

$('body').on('ns_variation_form_fixed', function() {
    if ($('.nasa-single-product-in-mobile form.cart.variations_form').length) {
        
        $('body').trigger('ns_before_variation_form_fixed');
        
        var _form = $('.nasa-single-product-in-mobile form.cart.variations_form');
        
        if ($(_form).find('.ns-form-info').length) {
            $(_form).find('.ns-form-info').remove();
        }
        
        if ($(_form).find('.ns-form-close').length <= 0) {
            $(_form).append('<a class="ns-form-close nasa-stclose" href="javascript:void(0);"></a>');
        }
        
        var _img = null,
            _price = null;
        
        if ($('.nasa-single-product-in-mobile .main-images .nasa-item-main-image-wrap[data-key="0"] img').length) {
            _img = $('.nasa-single-product-in-mobile .main-images .nasa-item-main-image-wrap[data-key="0"] img').clone();
        }
        
        if ($('.nasa-single-product-in-mobile .ns-begin-wrap .nasa-single-product-price').length) {
            _price = $('.nasa-single-product-in-mobile .ns-begin-wrap .nasa-single-product-price').clone();
        }
        
        if (_img || _price) {
            if ($(_form).find('.ns-form-info').length <= 0) {
                $(_form).prepend('<div class="ns-form-info nasa-flex align-start margin-bottom-20"></div>');
            }
            
            if (_img) {
                $(_form).find('.ns-form-info').append(_img);
            }
            
            if (_price) {
                $(_form).find('.ns-form-info').append(_price);
                
                if ($('.nasa-single-product-in-mobile .ns-begin-wrap .nasa-bulk-price').length) {
                    var _price_bulk = $('.nasa-single-product-in-mobile .ns-begin-wrap .nasa-bulk-price').clone();
                    
                    if ($(_form).find('.ns-form-info .nasa-bulk-price').length) {
                        $(_form).find('.ns-form-info .nasa-bulk-price').replaceWith(_price_bulk);
                    } else {
                        $(_form).find('.ns-form-info').append(_price_bulk);
                    }
                }
            }
        }
        
        /**
         * Move Btns
         */
        if ($(_form).find('.ns-info-btns .variations_button').length <= 0) {
            if ($(_form).find('.ns-info-btns').length <= 0) {
                $(_form).append('<div class="ns-info-btns"></div>');
            }
            
            var _btn = $(_form).find('.variations_button');
            
            if ($(_btn).length) {
                $(_form).find('.ns-info-btns').append(_btn);
            }
        }
        
        /**
         * Choose Variations
         */
        if ($('.nasa-single-product-in-mobile .ns-variants-clone').length <= 0) {
            var _choose = '';
            
            $(_form).find('.variations tr:first-child').each(function() {
                var _this = $(this);

                var _label = $(_this).find('th.label').length ? $(_this).find('th.label').clone() : null;
                
                _choose += _label !== null ? '<div class="ns-variant-lbl">' + $(_label).html() + '</div>' : '';

                var _value = $(_this).find('td.value').length ? $(_this).find('td.value').clone() : null;
                
                if (_value !== null) {
                    if ($(_value).find('.reset_variations').length) {
                        $(_value).find('.reset_variations').remove();
                    }
                    
                    if ($(_value).find('[id]').length) {
                        $(_value).find('[id]').removeAttr('id');
                    }
                    
                    _choose += '<div class="ns-variant-val">' + $(_value).html() + '</div>';
                }
            });

            if (_choose !== '') {
                $(_form).before('<div class="ns-begin-wrap ns-variants-first"><div class="ns-variants-clone"><a href="javascript:void(0);" class="ns-open-var-form"></a>' + _choose + '</div></div>');
            }
        }
        
        $('body').trigger('ns_after_variation_form_fixed');
    }
}).trigger('ns_variation_form_fixed');

$('body').on('nasa_changed_gallery_variable_single nasa_after_changed_src_main_img', function() {
    $('body').trigger('ns_variation_form_fixed');
});

$('body').on('click', '.ns-form-close', function() {
    var _form = $(this).parents('form');
    $(_form).removeClass('ns-show');
    $('.transparent-window').fadeOut(1000);
});

$('body').on('click', '.ns_btn-fixed .single_add_to_cart_button, .ns-open-var-form', function() {
    if ($('.nasa-single-product-in-mobile form.cart').length) {
        var _form = $('.nasa-single-product-in-mobile form.cart');
        var _btn = $(_form).find('.single_add_to_cart_button');
        
        if ($(_form).hasClass('variations_form')) {
            if (!$(_form).hasClass('ns-show')) {
                $(_form).addClass('ns-show');
                
                $('.transparent-window').fadeIn(200);
            }
        } else {
            if ($(_btn).length) {
                $(_btn).trigger('click');
            }
        }
    }
});

$('body').on('click', '.ns_btn-fixed .nasa-buy-now', function() {
    if ($('.nasa-single-product-in-mobile form.cart').length) {
        var _form = $('.nasa-single-product-in-mobile form.cart');
        var _btn = $(_form).find('.nasa-buy-now');
        
        if ($(_form).hasClass('variations_form')) {
            if (!$(_form).hasClass('ns-show')) {
                $(_form).addClass('ns-show');
                
                $('.transparent-window').fadeIn(200);
            }
        } else {
            if ($(_btn).length) {
                $(_btn).trigger('click');
            }
        }
    }
});

$('body').on('added_to_cart', function() {
    if ($('form.cart .ns-form-close').length) {
        $('form.cart .ns-form-close').trigger('click');
    }
});

});
/* End Document Ready */

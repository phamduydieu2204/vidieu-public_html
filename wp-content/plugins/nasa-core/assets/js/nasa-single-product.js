/**
 * Document nasa-core ready
 */
jQuery(document).ready(function($) {
"use strict";

/**
 * 360 Degree Popup
 */
$('body').on('nasa_before_popup_360_degree', function() {
    $.magnificPopup.close();

    $.magnificPopup.open({
        mainClass: 'my-mfp-zoom-in',
        items: {
            src: '<div class="nasa-product-360-degree"></div>',
            type: 'inline'
        },
        closeMarkup: '<button title="' + $('input[name="nasa-close-string"]').val() + '" type="button" class="mfp-close nasa-stclose-360">×</button>',
        callbacks: {
            beforeClose: function() {
                this.st.removalDelay = 350;
            },
            afterClose: function() {

            }
        }
    });
});

/**
 * Check accessories product
 */
$('body').on('change', '.nasa-check-accessories-product', function () {
    var _urlAjax = null;
    if (
        typeof nasa_ajax_params !== 'undefined' &&
        typeof nasa_ajax_params.wc_ajax_url !== 'undefined'
    ) {
        _urlAjax = nasa_ajax_params.wc_ajax_url.toString().replace('%%endpoint%%', 'nasa_refresh_accessories_price');
    }

    if (_urlAjax) {
        var _this = $(this);

        var _wrap = $(_this).parents('.nasa-accessories-check');

        var _id = $(_this).val();
        var _isChecked = $(_this).is(':checked');

        var _price = $(_wrap).find('.nasa-check-main-product').length ? parseInt($(_wrap).find('.nasa-check-main-product').attr('data-price')) : 0;
        if ($(_wrap).find('.nasa-check-accessories-product').length) {
            $(_wrap).find('.nasa-check-accessories-product').each(function() {
                if ($(this).is(':checked')) {
                    _price += parseInt($(this).attr('data-price'));
                }
            });
        }

        $.ajax({
            url: _urlAjax,
            type: 'post',
            dataType: 'json',
            cache: false,
            data: {
                total_price: _price
            },
            beforeSend: function () {
                $(_wrap).append('<div class="nasa-disable-wrap"></div><div class="nasa-loader"></div>');
            },
            success: function (res) {
                if (typeof res.total_price !== 'undefined') {
                    $('.nasa-accessories-total-price .price').html(res.total_price);

                    if (!_isChecked) {
                        $('.nasa-accessories-' + _id).fadeOut(200);
                    } else {
                        $('.nasa-accessories-' + _id).fadeIn(200);
                    }
                }

                $(_wrap).find('.nasa-loader, .nasa-disable-wrap').remove();
            },
            error: function () {

            }
        });
    }
});

/**
 * Add To cart accessories
 */
$('body').on('click', '.add_to_cart_accessories', function() {
    if (
        typeof nasa_ajax_params !== 'undefined' &&
        typeof nasa_ajax_params.wc_ajax_url !== 'undefined'
    ) {
        var _urlAjax = nasa_ajax_params.wc_ajax_url.toString().replace('%%endpoint%%', 'nasa_add_to_cart_accessories');
        var _this = $(this);

        var _wrap = $(_this).parents('.nasa-bought-together-wrap');
        if ($(_wrap).length) {
            var _wrapCheck = $(_wrap).find('.nasa-accessories-check');

            if ($(_wrapCheck).length) {
                var _pid = [];

                // nasa-check-main-product
                if ($(_wrapCheck).find('.nasa-check-main-product').length) {
                    _pid.push($(_wrapCheck).find('.nasa-check-main-product').val());
                }

                // nasa-check-accessories-product
                if ($(_wrapCheck).find('.nasa-check-accessories-product').length) {
                    $(_wrapCheck).find('.nasa-check-accessories-product').each(function() {
                        if ($(this).is(':checked')) {
                            _pid.push($(this).val());
                        }
                    });
                }

                if (_pid.length) {
                    $.ajax({
                        url: _urlAjax,
                        type: 'post',
                        dataType: 'json',
                        cache: false,
                        data: {
                            product_ids: _pid
                        },
                        beforeSend: function () {
                            $('.nasa-message-error').hide();
                            $(_wrap).append('<div class="nasa-disable-wrap"></div><div class="nasa-loader"></div>');
                        },
                        success: function (data) {
                            if (data && data.fragments) {
                                $.each(data.fragments, function(key, value) {
                                    $(key).replaceWith(value);
                                });

                                if ($('.cart-link').length) {
                                    $('.cart-link').trigger('click');
                                }
                            } else {
                                if (data && data.error && $('.nasa-message-error').length) {
                                    $('.nasa-message-error').html(data.message);
                                    $('.nasa-message-error').show();
                                }
                            }

                            $(_wrap).find('.nasa-loader, .nasa-disable-wrap').remove();
                        },
                        error: function () {
                            $(_wrap).find('.nasa-loader, .nasa-disable-wrap').remove();
                        }
                    });
                }
            }
        }
    }

    return false;
});

/**
 * Nodes Popup
 */
$('body').on('click', '.nasa-node-popup', function() {
    var _target = $(this).attr('data-target');
    
    if ($(_target).length) {
    
        if (!$('body').hasClass('nasa-mobile-app')) {

            $('body').trigger('nasa_popup_content_contact');

            /**
             * Close old Magnific
             */
            $.magnificPopup.close();

            /**
             * Open current Magnific
             */
            $.magnificPopup.open({
                mainClass: 'my-mfp-slide-bottom nasa-mfp-max-width',

                items: {
                    src: _target,
                    type: 'inline'
                },

                closeMarkup: '<a class="nasa-mfp-close nasa-stclose" href="javascript:void(0);" title="' + $('input[name="nasa-close-string"]').val() + '"></a>',

                callbacks: {
                    beforeClose: function() {
                        this.st.removalDelay = 350;
                    },
                    afterClose: function() {

                    }
                }
            });

            $('body').trigger('init_nasa_tabs_not_set');
        } else {
            if ($(_target).find('.nasa-stclose').length <= 0) {
                $(_target).prepend('<a class="ns-node-close nasa-stclose" href="javascript:void(0);"></a>');
            }
            
            if (!$(_target).hasClass('ns-actived')) {
                $(_target).addClass('ns-actived');
            }
        }
    }
});

/**
 * Close Node Mobile App
 */
$('body').on('click', '.ns-node-close', function() {
    var _target = $(this).parents('.nasa-node-content');
    
    if ($(_target).length) {
        $(_target).removeClass('ns-actived');
    }
});

/**
 * Adding info product to contact form 7 ask a question
 * 
 * @type type
 */
$('body').on('nasa_popup_content_contact', function() {
    if ($('.nasa-popup-content-contact').length) {
        $('.nasa-popup-content-contact').each(function() {
            var _this = $(this);
            
            if (!$(_this).hasClass('nasa-inited')) {
                $(_this).addClass('nasa-inited');
                
                var _form = $(_this).find('form.wpcf7-form');

                if ($(_form).length) {

                    if ($(_this).find('.nasa-info-add-form').length) {
                        $(_form).prepend($(_this).find('.nasa-info-add-form').html());
                        $(_this).find('.nasa-info-add-form').remove();
                    }

                    if ($(_form).find('input[type="text"], input[type="email"], input[type="number"], textarea').length) {
                        $(_form).find('input[type="text"], input[type="email"], input[type="number"], textarea').each(function() {
                            var _input = $(this);
                            var _label = $(_input).parents('label');
                            if ($(_label).length) {
                                var _text = ($(_label).text()).trim();
                                $(_input).attr('placeholder', _text);
                                $(_label).addClass('hide-text');
                            }
                        });
                    }
                }
            }
        });
    }
});

/**
 * Single Attributes Brands
 */
if ($('.single-product .nasa-sa-brands').length) {
    if ($('.single-product .woocommerce-product-rating').length) {
        $('.single-product .woocommerce-product-rating').addClass('nasa-has-sa-brands');
    } else {
        $('.single-product .nasa-sa-brands').addClass('margin-top-10');
    }
    
    $('.single-product .nasa-sa-brands').addClass('nasa-inited');
}

/**
 * init Variations forms
 * 
 * @returns {undefined}
 */
setTimeout(function() {
    $('body').trigger('nasa_init_ux_variation_form');
}, 10);

/**
 * Load single product image
 */
load_slick_single_product($);

/**
 * Load single product image
 */
$('body').on('nasa_load_single_product_slide', function() {
    load_slick_single_product($);
});

/**
 * Re-Load single product image
 */
$('body').on('nasa_reload_single_product_slide', function() {
    load_slick_single_product($, true);
});

/**
 * Change Countdown for variation - Quick view
 */
$('body').on('nasa_reload_slick_slider', function() {
    load_slick_single_product($, true);
});

/**
 * Viewing
 * 
 * @type Number|_min|_others
 */
var _current = 0,
    _change_counter;
$('body').on('nasa_counter_viewing', function() {
    if ($('#nasa-counter-viewing').length) {
        var _min = parseInt($('#nasa-counter-viewing').attr('data-min'));
        var _max = parseInt($('#nasa-counter-viewing').attr('data-max'));
        var _delay = parseInt($('#nasa-counter-viewing').attr('data-delay'));
        var _change = parseInt($('#nasa-counter-viewing').attr('data-change'));
        var _id = $('#nasa-counter-viewing').attr('data-id');
        
        if (!$('#nasa-counter-viewing').hasClass('inited')) {
            if (typeof _change_counter !== 'undefined' && _change_counter) {
                clearInterval(_change_counter);
            }
            
            _current = $.cookie('nasa_cv_' + _id);
            
            if (typeof _current === 'undefined' || !_current) {
                // get Random from min - max
                _current = Math.floor(Math.random() * _max) + _min;
            }
            
            $('#nasa-counter-viewing').addClass('inited');
            
            $.cookie('nasa_cv_' + _id, _current, {expires: 1 / 24, path: '/'}); // Live in 1 hours
            
            $('#nasa-counter-viewing .nasa-count').html(_current);
        }
        
        _change_counter = setInterval(function() {
            _current = parseInt($('#nasa-counter-viewing .nasa-count').text());
            
            if (!_current) {
                _current = _min;
            }
            
            var _pm = Math.floor(Math.random() * 2);
            var _others = Math.floor(Math.random() * _change + 1);
            _current = (_pm < 1 && _current > _others) ? _current - _others : _current + _others;
            $.cookie('nasa_cv_' + _id, _current, {expires: 1 / 24, path: '/'}); // Live in 1 hours
            
            $('#nasa-counter-viewing .nasa-count').html(_current);
            
        }, _delay);
    }
}).trigger('nasa_counter_viewing');

/**
 * After load ajax compalete
 */
$('body').on('nasa_after_loaded_ajax_complete', function() {
    if ($('.single-product .nasa-sa-brands').length) {
        if ($('.single-product .woocommerce-product-rating').length) {
            $('.single-product .woocommerce-product-rating').addClass('nasa-has-sa-brands');
        } else {
            $('.single-product .nasa-sa-brands').addClass('margin-top-10');
        }

        $('.single-product .nasa-sa-brands').addClass('nasa-inited');
    }
    
    $('body').trigger('nasa_counter_viewing');
    
    if ($('form.cart input[name="quantity"]').length && $('.ev-dsc-qty').length) {
        $('form.cart input[name="quantity"]').trigger('change');
    }
});

/**
 * Bulk Discount
 * 
 * @param {type} $
 * @param {type} restart
 * @returns {undefined}
 */
if ($('form.cart input[name="quantity"]').length && $('.ev-dsc-qty').length) {
    $('form.cart input[name="quantity"]').trigger('change');
}

/**
 * Bulk Discount - Mobile App
 */
$('body').on('ns_after_variation_form_fixed', function() {
    var _form = $('.nasa-single-product-in-mobile form.cart.variations_form');
    
    if (
        $(_form).length &&
        $('.nasa-mobile-app .nasa-single-product-in-mobile .nasa-variation-bulk-dsct').length &&
        $(_form).find('.nasa-variation-bulk-dsct').length <= 0
    ) {
        $(_form).find('.single_variation_wrap').before($('.nasa-mobile-app .nasa-single-product-in-mobile .nasa-variation-bulk-dsct'));
    }
});
});

/**
 * Single slick images
 * 
 * @param {type} $
 * @param {type} restart
 * @returns {undefined}
 */
function load_slick_single_product($, restart) {
    if ($('.nasa-single-product-slide .nasa-single-product-main-image').length) {
        var _root_wrap = $('.nasa-single-product-slide');
        if ($(_root_wrap).length) {
            var _restart = typeof restart === 'undefined' ? false : true;
            var _rtl = $('body').hasClass('nasa-rtl') ? true : false;
            var _main = $(_root_wrap).find('.nasa-single-product-main-image'),
                _thumb = $(_root_wrap).find('.nasa-single-product-thumbnails').length ? $(_root_wrap).find('.nasa-single-product-thumbnails') : null,

                _autoplay = $(_root_wrap).attr('data-autoplay') === 'true' ? true : false,
                _infinite = $(_root_wrap).attr('data-infinite') === 'true' ? true : false,
                _speed = parseInt($(_root_wrap).attr('data-speed')),
                _delay = parseInt($(_root_wrap).attr('data-delay')),
                _dots = $(_root_wrap).attr('data-dots') === 'true' ? true : false,
                _num_main = parseInt($(_root_wrap).attr('data-num_main'));

            _speed = !_speed ? 600 : _speed;
            _delay = !_delay ? 6000 : _delay;
            _num_main = !_num_main ? 1 : _num_main;

            if (_restart) {
                if ($(_main).length && $(_main).hasClass('slick-initialized')) {
                    $(_main).slick('unslick');
                }

                if ($(_thumb).length && $(_thumb).hasClass('slick-initialized')) {
                    $(_thumb).slick('unslick');
                }
            }
            
            var _padding = $(_root_wrap).attr('data-padding');
            
            var _main_params = {
                rtl: _rtl,
                slidesToShow: _num_main,
                slidesToScroll: _num_main,
                autoplay: _autoplay,
                autoplaySpeed: _delay,
                speed: _speed,
                arrows: true,
                dots: _dots,
                infinite: _infinite,
                adaptiveHeight: true,
                asNavFor: _thumb,
                responsive: [
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    }
                ]
            };
            
            if (_padding) {
                _main_params.centerMode = true;
                _main_params.centerPadding = _padding;
                _main_params.infinite = true;
                
                if (!$(_root_wrap).hasClass('nasa-center-mode')) {
                    $(_root_wrap).addClass('nasa-center-mode');
                }
                
                if (!$(_main).hasClass('no-easyzoom')) {
                    $(_main).addClass('no-easyzoom');
                }
                
                var _padding_small = $(_root_wrap).attr('data-padding_small');
                
                if (_padding_small) {
                    _main_params.responsive = [
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1,
                                centerPadding: _padding_small
                            }
                        }
                    ];
                }
            }

            var _interval = setInterval(function() {
                if ($(_main).find('#nasa-main-image-0 img').height()) {
                    if (!$(_main).hasClass('slick-initialized')) {
                        $(_main).slick(_main_params);
                    }

                    if (_thumb && !$(_thumb).hasClass('slick-initialized')) {
                        var _num_ver = parseInt($(_root_wrap).attr('data-num_thumb'));
                        _num_ver = !_num_ver ? 4 : _num_ver;

                        var _vertical = true;
                        var wrapThumb = $(_thumb).parents('.nasa-thumb-wrap');

                        if ($(wrapThumb).length && $(wrapThumb).hasClass('nasa-thumbnail-hoz')) {
                            _vertical = false;
                            _num_ver = 4;
                        }

                        var _setting = {
                            vertical: _vertical,
                            slidesToShow: _num_ver,
                            slidesToScroll: 1,
                            dots: false,
                            arrows: true,
                            infinite: false
                        };

                        if (!_vertical) {
                            _setting.rtl = _rtl;
                        } else {
                            _setting.verticalSwiping = true;
                        }

                        _setting.asNavFor = _main;
                        _setting.centerMode = false;
                        _setting.centerPadding = '0';
                        _setting.focusOnSelect = true;

                        $(_thumb).slick(_setting);
                        $(_thumb).attr('data-speed', _speed);
                    }

                    clearInterval(_interval);
                    
                    if ($(_main).find('.slick-slide').length <= _num_main) {
                        $(_main).addClass('nasa-no-slide');
                    }

                    $('body').trigger('nasa_after_single_product_slick_inited', [_thumb, _num_ver]);
                }
            }, 100);

            setTimeout(function() {
                if ($('.nasa-single-product-slide .nasa-single-product-main-image .slick-list').length <= 0 || $('.nasa-single-product-slide .nasa-single-product-main-image .slick-list').height() < 2) {
                    load_slick_single_product($, true);
                }
            }, 500);
        }
    }
}

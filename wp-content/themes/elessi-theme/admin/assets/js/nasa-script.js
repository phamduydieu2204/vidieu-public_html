var top_bar_left_df = '';
var content_custom_df = '';

jQuery(document).ready(function($) {
    "use strict";
    loadListIcons($);
    
    var text_now = $('textarea#topbar_left').val();
    $('body').on('click', '.reset_topbar_left', function() {
        if ($('textarea#topbar_left').val() !== top_bar_left_df) {
            var _confirm = confirm('Are you sure to reset top bar left ?');

            if (_confirm) {
                $('textarea#topbar_left').val(top_bar_left_df);
            }
        }
        
        return false;
    });
    
    $('body').on('click', '.restore_topbar_left', function() {
        if (text_now !== $('textarea#topbar_left').val()) {
            var _confirm = confirm('Are you sure to restore top bar left ?');

            if (_confirm) {
                $('textarea#topbar_left').val(text_now);
            }
        }
        
        return false;
    });
    
    var text_content_now = $('textarea#content_custom').val();
    $('body').on('click', '.reset_content_custom', function() {
        if ($('textarea#content_custom').val() !== content_custom_df) {
            var _confirm = confirm('Are you sure to reset your content custom ?');

            if (_confirm) {
                $('textarea#content_custom').val(content_custom_df);
            }
        }
        
        return false;
    });
    
    $('body').on('click', '.restore_content_custom', function() {
        if (text_content_now !== $('textarea#content_custom').val()) {
            var _confirm = confirm('Are you sure to restore your content custom ?');

            if (_confirm) {
                $('textarea#content_custom').val(text_content_now);
            }
        }
        
        return false;
    });
    
    $('body').on('click', '.toggle-choose-icon-btn', function() {
        $(this).parents('.widget-content').find('.toggle-choose-icon').toggleClass('hidden-tag');
    });
    
    $('body').on('click', '.nasa-chosen-icon', function() {
        var _fill = $(this).attr('data-fill');
        if (_fill) {
            if ($('.nasa-list-icons-select').length < 1) {
                $.ajax({
                    url: ajaxurl,
                    type: 'get',
                    dataType: 'html',
                    data: {
                        action: 'nasa_list_fonts_admin',
                        fill: _fill
                    },
                    success: function(res) {
                        $('body').append(res);
                        $('body').append('<div class="nasa-tranparent" />');
                        $('.nasa-list-icons-select').animate({right: 0}, 300);
                    }
                });
            } else {
                $('body').append('<div class="nasa-tranparent" />');
                $('.nasa-list-icons-select').attr('data-fill', _fill);
                $('.nasa-list-icons-select').animate({right: 0}, 300);
            }
        }
        
        return false;
    });
    
    $('body').on('click', '.nasa-tranparent', function() {
        if ($('.nasa-list-icons-select').length) {
            $('.nasa-list-icons-select').animate({right: '-500px'}, 300);
        }
        $(this).remove();
    });
    
    // Search icons
    $('body').on('keyup', '.nasa-input-search-icon', function() {
        searchIcons($);
    });
    
    $('body').on('click', '.nasa-fill-icon', function() {
        var _val = $(this).attr('data-val');
        var _fill = $(this).parent().attr('data-fill');
        
        if ($('#'+_fill).length) {
            $('#'+_fill).val(_val);
        }
        
        if ($('input[name="'+_fill+'"]').length) {
            $('input[name="'+_fill+'"]').val(_val);
        }
        
        if ($('#ico-'+_fill).length) {
            $('#ico-'+_fill).html('<i class="' + _val + '"></i><a href="javascript:void(0);" class="nasa-remove-icon" data-id="' + _fill + '"><i class="fa fa-remove"></i></a>');
        }
        
        $('.nasa-tranparent').click();
    });
    
    $('body').on('click', '.nasa-remove-icon', function() {
        var _fill = $(this).attr('data-id');
        
        if ($('#'+_fill).length) {
            $('#'+_fill).val('');
        }
        
        if ($('input[name="'+_fill+'"]').length) {
            $('input[name="'+_fill+'"]').val('');
        }
        
        if ($('#ico-'+_fill).length) {
            $('#ico-'+_fill).html('');
        }
    });
    
    loadColorPicker($);
    $('.widget-control-save').ajaxComplete(function() {
        loadColorPicker($);
    });
    
    $(document).ajaxComplete(function() {
        if ($('input[name="section_nasa_icon"]').length) {
            $('input[name="section_nasa_icon"]').attr('readonly', true);
        }
        
        if ($('.vc_ui-panel-window select[name="i_type"]').length) {
            var _change = false;
            $('.vc_ui-panel-window select[name="i_type"] option').each(function() {
                if ('fontawesome' !== $(this).attr('value')) {
                    $(this).remove();
                    
                    _change = true;
                }
            });
            
            if (_change) {
                $('.vc_ui-panel-window select[name="i_type"]').val('fontawesome').trigger('change');
            }
        }
    });
    
    $('body').on('change', '.nasa-select-attr', function() {
        var _warp = $(this).parents('.widget-content');
        if ($(_warp).find('.nasa-vari-type').val() === '1') {
            var taxonomy = $(this).val(),
                num = $(this).attr('data-num'),
                instance = $(_warp).find('.nasa-widget-instance').attr('data-instance');
            loadColorDefault($, _warp, taxonomy, num, instance, false);
        }
        
        return true;
    });
    
    $('body').on('change', '.nasa-vari-type', function() {
        var _warp = $(this).parents('.widget-content'),
            taxonomy = $(_warp).find('.nasa-select-attr').val(),
            num = $(_warp).find('.nasa-select-attr').attr('data-num'),
            instance = $(_warp).find('.nasa-widget-instance').attr('data-instance');
        if ($(this).val() === '1') {  
            loadColorDefault($, _warp, taxonomy, num, instance, true);
        } else {
            unloadColor($, _warp);
        }
        
        return true;
    });
    
    // Option Breadcrumb
    if ($('.nasa-breadcrumb-flag-option input[type="checkbox"]').is(':checked')) {
	$('.nasa-breadcrumb-type-option').show();
        $('.nasa-breadcrumb-align-option').show();
	if ($('.nasa-breadcrumb-type-option').find('select').val() === 'has-background') {
	    $('.nasa-breadcrumb-bg-option').show();
            // $('.nasa-breadcrumb-bg-lax').show();
	    loadImgOpBreadcrumb($);
	}
    }
    
    $('body').on('change', '.nasa-breadcrumb-flag-option input[type="checkbox"]', function() {
	if ($(this).is(':checked')) {
	    $('.nasa-breadcrumb-type-option').fadeIn(200);
            $('.nasa-breadcrumb-align-option').fadeIn(200);
	    if ($('.nasa-breadcrumb-type-option').find('select').val() === 'has-background') {
		$('.nasa-breadcrumb-bg-option').fadeIn(200);
                // $('.nasa-breadcrumb-bg-lax').fadeIn(200);
		loadImgOpBreadcrumb($);
	    }
	} else {
	    $('.nasa-breadcrumb-type-option').fadeOut(200);
	    $('.nasa-breadcrumb-bg-option').fadeOut(200);
            // $('.nasa-breadcrumb-bg-lax').fadeOut(200);
            $('.nasa-breadcrumb-align-option').fadeOut(200);
	}
    });
    
    $('body').on('change', '.nasa-breadcrumb-type-option select', function() {
	if ($(this).val() === 'has-background') {
	    $('.nasa-breadcrumb-bg-option').fadeIn(200);
	    $('.nasa-breadcrumb-color-option').fadeIn(200);
            // $('.nasa-breadcrumb-bg-lax').fadeIn(200);
	    $('.nasa-breadcrumb-height-option').fadeIn(200);
            $('.nasa-breadcrumb-text-option').fadeIn(200);
	    loadImgOpBreadcrumb($);
	} else {
	    $('.nasa-breadcrumb-bg-option').fadeOut(200);
	    $('.nasa-breadcrumb-color-option').fadeOut(200);
            // $('.nasa-breadcrumb-bg-lax').fadeOut(200);
	    $('.nasa-breadcrumb-height-option').fadeOut(200);
	    $('.nasa-breadcrumb-text-option').fadeOut(200);
	}
    });
    
    if ($('.type_promotion select').length) {
        var val_promotion = $('.type_promotion select').val();
        if (val_promotion === 'custom') {
            $('.nasa-custom_content').show();
        } else if (val_promotion === 'list-posts') {
            $('.nasa-list_post').show();
        }
        $('body').on('change', '.type_promotion select', function() {
            var val_promotion = $(this).val();
            if (val_promotion === 'custom') {
                $('.nasa-custom_content').fadeIn(200);
                $('.nasa-list_post').fadeOut(200);
            } else if (val_promotion === 'list-posts') {
                $('.nasa-custom_content').fadeOut(200);
                $('.nasa-list_post').fadeIn(200);
            }
        });
    }
    
    if ($('.nasa-header-type-select input[type="radio"][name="header-type"]').length > 0) {
        var _val_header = $('.nasa-header-type-select input[type="radio"][name="header-type"]:checked').val();
        $('.nasa-header-type-select-' + _val_header).slideDown(200);
        
        $('body').on('click', '.nasa-header-type-select img.of-radio-img-img', function() {
            var _val_header = $('.nasa-header-type-select input[type="radio"][name="header-type"]:checked').val();
            $('.nasa-header-type-select-' + _val_header).slideDown(200);
            $('.nasa-header-type-child').each(function() {
                if (!$(this).hasClass('nasa-header-type-select-' + _val_header)) {
                    $(this).slideUp(200);
                }
            });
        });
    }
    
    if ($('.nasa-type-font select').length) {
        var _val_font = $('.nasa-type-font select').val();
        $('.nasa-type-font-' + _val_font).slideDown(200);
        
        $('body').on('change', '.nasa-type-font select', function() {
            var _val_font = $(this).val();
            $('.nasa-type-font-glb').slideUp(200);
            $('.nasa-type-font-' + _val_font).slideDown(200);
        });
    }
    
    $('.nasa-theme-option-parent select').each(function() {
        var _val = $(this).val();
        var _id = $(this).attr('id');
        $('.nasa-' + _id + '.nasa-theme-option-child').hide();
        $('.nasa-' + _id + '-' + _val + '.nasa-theme-option-child').show();
    });
    
    $('body').on('change', '.nasa-theme-option-parent select', function() {
        var _val = $(this).val();
        var _id = $(this).attr('id');
        
        $('.nasa-' + _id + '.nasa-theme-option-child').slideUp(200);
        $('.nasa-' + _id + '-' + _val + '.nasa-theme-option-child').slideDown(200);
    });
    
    if ($('.nasa-theme-option-parent input[type="radio"]:checked').length) {
        $('.nasa-theme-option-parent input[type="radio"]:checked').each(function() {
            var _this = $(this);
            var _val = $(_this).val();
            var _id = $(_this).attr('name');

            $('.nasa-' + _id + '.nasa-theme-option-child').hide();
            $('.nasa-' + _id + '-' + _val + '.nasa-theme-option-child').show();
        });
    }
    
    $('body').on('click', '.nasa-theme-option-parent img.of-radio-img-img', function() {
        var _this = $(this);
        var _parents = $(_this).parents('.nasa-theme-option-parent');
        var _val = $(_parents).find('input[type="radio"]:checked').val();
        var _id = $(_parents).find('input[type="radio"]:checked').attr('name');

        $('.nasa-' + _id + '.nasa-theme-option-child').slideUp(200);
        $('.nasa-' + _id + '-' + _val + '.nasa-theme-option-child').slideDown(200);
    });
    
    if ($('.nasa-topbar_toggle input[type="checkbox"]').is(':checked')) {
	$('.nasa-topbar_df-show').show();
    }
    
    $('body').on('change', '.nasa-topbar_toggle input[type="checkbox"]', function() {
	if ($(this).is(':checked')) {
	    $('.nasa-topbar_df-show').slideDown(200);
	} else {
	    $('.nasa-topbar_df-show').slideUp(200);
	}
    });
    
    /**
     * Ajax field
     * 
     * @param {type} $
     * @returns {undefined}
     */
    $('body').on('click', '.nasa-init-ajax', function() {
        var _wrap = $(this).parents('.nasa-opt-ajax-wrap');
        $(_wrap).find('.nasa-info-ajax').hide();
        $(_wrap).find('.nasa-do-ajax').show();
    });
    
    $('body').on('click', '.nasa-cancel-ajax', function() {
        var _wrap = $(this).parents('.nasa-opt-ajax-wrap');
        $(_wrap).find('.nasa-info-ajax').show();
        $(_wrap).find('.nasa-do-ajax').hide();
    });
    
    $('body').on('click', '.nasa-apply-ajax', function() {
        if (!_disable_save) {
            _disable_save = true;

            var _this = $(this);
            var _wrap = $(_this).parents('.nasa-opt-ajax-wrap');
            var _action = $(_this).attr('data-action');
            var _old_val = $(_wrap).find('input.nasa-org-input').val();
            var _value = $(_wrap).find('input.nasa-do-ajax-input').val();

            if (_value && _value !== _old_val) {
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    dataType: 'json',
                    cache: false,
                    data: {
                        action: _action,
                        data_value: _value
                    },
                    beforeSend: function() {
                        $(_wrap).addClass('nasa-loading');
                    },
                    success: function(res) {
                        if (res.success === 'ok') {
                            $(_wrap).find('.value-show').html(res.result);

                            $(_wrap).find('input.nasa-do-ajax-input').val(res.result).trigger('change');
                            $(_wrap).find('input.nasa-org-input').val(res.result).trigger('change');

                            $(_wrap).find('.nasa-ajax-mess').html(res.mess);
                            $(_wrap).find('.nasa-ajax-mess').show();
                            
                            $(_wrap).removeClass('nasa-loading');
                            
                            $(_wrap).find('.nasa-cancel-ajax').trigger('click');

                            setTimeout(function() {
                                _disable_save = false;
                            }, 100);
                            
                            setTimeout(function() {
                                $(_wrap).find('.nasa-ajax-mess').fadeOut(300);
                            }, 3000);
                        } else {
                            $(_wrap).find('.nasa-ajax-mess').html(res.mess);
                            $(_wrap).find('.nasa-ajax-mess').show();
                            
                            $(_wrap).removeClass('nasa-loading');

                            setTimeout(function() {
                                _disable_save = false;
                            }, 100);
                            
                            setTimeout(function() {
                                $(_wrap).find('.nasa-ajax-mess').fadeOut(300);
                            }, 3000);
                        }
                    },
                    error: function() {
                        $(_wrap).removeClass('nasa-loading');
                        
                        setTimeout(function() {
                            _disable_save = false;
                        }, 100);
                        
                        setTimeout(function() {
                            $(_wrap).find('.nasa-ajax-mess').fadeOut(300);
                        }, 3000);
                    }
                });
            } else {
                _disable_save = false;
            }
        }
    });
    
    $('body').on('change', '#white_lbl', function() {
        if ($(this).is(':checked')) {
            $('.nasa-online-doc').hide();
        } else {
            $('.nasa-online-doc').show();
        }
    });

    $('body').on('focus input', '.fake_purchases_input .user_name', function() {
        var name = $(this).val();
        var _wrap = $(this).parents('.fake_pc_wrap');
        $(_wrap).find('.fake_purchases_demo .wrapper-theme .noti-title .nameuser').text(name);
    });
    
    $('body').on('focus input', '.fake_purchases_input .datetime', function() {
        var day = $(this).val();
        var _wrap = $(this).parents('.fake_pc_wrap');
        $(_wrap).find('.fake_purchases_demo .wrapper-theme .noti-time .time_purchased').text(day);
    });

    $('body').on('focus', '.fake_purchases_input .ns_search', function() {
        $(this).parents('.fake_purchases_input').find('.ns_browsers').show();
    });

    $('body').on('input', '.fake_purchases_input .ns_search', function() {
        var s = $(this).val();
        var str = ''; 
        var _wrap = $(this).parents('.fake_pc_wrap');
        
        if( s.trim().length >= 3) {

            if($(_wrap).find('.fake_purchases_demo').hasClass('hidden-tag')) {

                $(_wrap).find('.fake_purchases_input .hidden-tag').removeClass('hidden-tag');
                $(_wrap).find('.fake_purchases_demo').removeClass('hidden-tag');

            }

            $.ajax({
                url: ns_admin_url_search,
                type: 'GET',
                data: {
                    term: s,
                    security: ns_admin_search_nonce,
                    rule_opt: 'elessi-options'
                },
                success: function(data) {
                    var results = data.imgs;
                    
                    for (var id in results) {
                        str +='<li class="product_item" permalink="' + (results[id])['permalink'] + '" img_url="' + (results[id])['img_url'] + '">' + (results[id])['title'] +  '</li>';
                    }
                    
                    $(_wrap).find('.ns_browsers').html(str);
                    
                    return {
                        results: results
                    };
                }
            });

        }
    });

    $('body').on('click', '.fake_purchases_input .product_item', function(e) {
        e.preventDefault();
        
        var _this = $(this);
        var _section = $(_this).parents('.section-fake_purchases');
        var _input_wrap = $(_section).find('.fake_purchases_input');
        
        $(_section).find('.fake_purchases_demo .nameproduct').text($(_this).html());
        $(_section).find('.nameproduct').attr('href',$(_this).attr('permalink'));
        $(_section).find('.nameproduct').attr('title',$(_this).html());
        $(_input_wrap).find('.ns_search').val($(_this).html());
        $(_section).find('.fake_purchases_demo .tmop-product-image').attr('src', $(_this).attr('img_url'));
        $(_input_wrap).find('.ns_browsers').hide();
    });

    $('body').on('click', '.fake_purchases_input .add-list', function(e) {
        e.preventDefault();
        
        var _wrap = $(this).parents('.fake_pc_wrap');
        var _item = $(_wrap).find('.product-item-tmpl').html();
        var _src_img = $(_wrap).find('.fake_purchases_demo .tmop-product-image').attr('src');
        var _customer = $(_wrap).find('.fake_purchases_demo .nameuser').html();
        var _p_url = $(_wrap).find('.fake_purchases_demo .nameproduct').attr('href');
        var _p_name = $(_wrap).find('.fake_purchases_demo .nameproduct').html();
        var _time_purchase = $(_wrap).find('.fake_purchases_demo .time_purchased').html();
        
        _item = _item.replace(/{{src_img}}/g, _src_img);
        _item = _item.replace(/{{customer}}/g, _customer);
        _item = _item.replace(/{{p_url}}/g, _p_url);
        _item = _item.replace(/{{p_name}}/g, _p_name);
        _item = _item.replace(/{{time_purchase}}/g, _time_purchase);
        
        $('.fake_purchases_list').append(_item);
        
        ns_add_to_list($, _wrap);
    });

    $('body').on('click', '.fake_purchases_list .delete_btn', function(e) {
        e.preventDefault();
        
        var _wrap = $(this).parents('.fake_pc_wrap');

        if (confirm("Are you sure?")) {
            $(this).parents('.product_list_item').remove();
            ns_add_to_list($, _wrap);
        }
    
    });    

    $('body').on('click', '.fake_purchases_list .change_btn', function(e) {
        e.preventDefault();
        
        $(this).parents('.product_list_item').find('.user_name_change, .datetime_change').attr('type', 'text');
        $(this).parents('.product_list_item').find('.btn_wrap').removeClass('hidden-tag');
    });

    $('body').on('click', '.product_list_item .apply_change', function(e) {
        e.preventDefault();
        
        var _wrap = $(this).parents('.fake_pc_wrap');
        var par = $(this).parents('.product_list_item');
        
        var name = $(par).find('.user_name_change').val();
        var day = $(par).find('.datetime_change').val();
        
        if(name.trim().length > 0){
            $(par).find('.nameuser').text(name);
        }
        
        if (day.trim().length > 0){
            $(par).find('.noti-time .time_purchased').text(day);
        }
        
        ns_add_to_list($, _wrap);
        
        $(par).find('.close_change').trigger('click');
    }); 

    $('body').on('click', '.close_change', function(e) {
        e.preventDefault();
        
        $(this).parents('.product_list_item').find('.user_name_change, .datetime_change').attr('type', 'hidden');
        
        if (!$(this).parents('.product_list_item').find('.btn_wrap').hasClass('hidden-tag')) {
            $(this).parents('.product_list_item').find('.btn_wrap').addClass('hidden-tag');
        }
    }); 

    $('body').on('click', function(e) {
        if(e.target.className !== "of-input ns_search" && e.target.className !== "ns_browsers") {
            $('.ns_browsers').css('display', 'none');
        }
    });
    
    /**
     * 
     * @param {type} $
     * @param {type} _wrap
     * Render Available Face Purchase Item
     */
    if ($('.input_list_purchased').length) {
        $('.input_list_purchased').each(function() {
            var _wrap = $(this).parents('.fake_pc_wrap');
            
            var _val = $(this).val();
            
            if (_val !== '') {
                var _data = JSON.parse(_val);
                var _count = _data.length;
                var _temp = $(_wrap).find('.product-item-tmpl').html();
                
                for (var i=0; i<_count; i++) {
                    var _src_img = _data[i]['img_url'];
                    var _customer = _data[i]['name'];
                    var _p_url = _data[i]['pro_href'];
                    var _p_name = _data[i]['pro_name'];
                    var _time_purchase = _data[i]['day'];
                    
                    var _item = _temp;
                    _item = _item.replace(/{{src_img}}/g, _src_img);
                    _item = _item.replace(/{{customer}}/g, _customer);
                    _item = _item.replace(/{{p_url}}/g, _p_url);
                    _item = _item.replace(/{{p_name}}/g, _p_name);
                    _item = _item.replace(/{{time_purchase}}/g, _time_purchase);
                    
                    $(_wrap).find('.fake_purchases_list').append(_item);
                }
            }
        });
    }
        
    /* =============== End document ready !!! ================== */
});


function ns_add_to_list($, _wrap){
    var array = [];
    
    if ($(_wrap).find('.fake_purchases_list .product_list_item').length) {
        $(_wrap).find('.fake_purchases_list .product_list_item').each(function() {
            var _this = $(this);
            
            var img_url     = $(_this).find('.product-image .tmop-product-image').attr('src').trim();
            var name        = $(_this).find('.wrapper-theme .noti-title .nameuser').text().trim();
            var pro_name    = $(_this).find('.wrapper-theme .noti-body .nameproduct').text().trim();
            var pro_href    = $(_this).find('.wrapper-theme .noti-body .nameproduct').attr('href').trim();
            var day         = $(_this).find('.wrapper-theme .noti-time .time_purchased').text().trim();
            array.push({img_url,name,pro_name,pro_href,day});
        });
    }
    
    var json = array.length ? JSON.stringify(array) : '';
    
    $(_wrap).find('.input_list_purchased').val(json);
}

function loadImgOpBreadcrumb($) {
    if ($('.nasa-breadcrumb-bg-option .screenshot').length && $('.nasa-breadcrumb-bg-option #breadcrumb_bg_upload').val() !== '') {
	if ($('.nasa-breadcrumb-bg-option .screenshot').html() === '') {
	    $('.nasa-breadcrumb-bg-option .screenshot').html('<img class="of-option-image" src="' + $('.nasa-breadcrumb-bg-option #breadcrumb_bg_upload').val() + '" />');
	    $('.upload_button_div .remove-image').removeClass('hide').show();
	}
    }
}

function loadColorDefault($, _warp, _taxonomy, _num, _instance, _check) {
    if (_check && $(_warp).find('.nasa_p_color').length) {
        var _this = $(_warp).find('.nasa_p_color');
        $(_this).find('input').prop('disabled', false);
        $(_this).show();
    }else{
        _instance = _instance.toLocaleString();
        $.ajax({
	    url: ajaxurl,
	    type: 'post',
	    dataType: 'html',
	    data: {
		action: 'nasa_list_colors_admin',
                taxonomy: _taxonomy,
		num: _num,
                instance: _instance
	    },
	    success: function(res) {
                $(_warp).find('.nasa_p_color').remove();
		$(_warp).append(res);
                loadColorPicker($);
	    }
	});
    }
}

function unloadColor($, _warp) {
    var _this = $(_warp).find('.nasa_p_color');
    $(_this).find('input').prop('disabled', true);
    $(_this).hide();
}

function loadColorPicker($) {
    $('.nasa-color-field').each(function() {
        if ($(this).parents('.wp-picker-container').length < 1) {
            $(this).wpColorPicker();
        }
    });
};

function loadListIcons($) {
    if ($('.nasa-list-icons-select').length < 1) {
	$.ajax({
	    url: ajaxurl,
	    type: 'get',
	    dataType: 'html',
	    data: {
		action: 'nasa_list_fonts_admin',
		fill: ''
	    },
	    success: function(res) {
		$('body').append(res);
	    }
	});
    }
};

function searchIcons($) {
    var _textsearch = $.trim($('.nasa-input-search-icon').val());
    if (_textsearch === '') {
        $('.nasa-font-icons').fadeIn(200);
    } else {
        var patt = new RegExp(_textsearch);
        $('.nasa-font-icons').each(function() {
            var _sstext = $(this).attr('data-text');
            if (patt.test(_sstext)) {
                $(this).fadeIn(200);
            } else {
                $(this).fadeOut(200);
            }
        });
    }
}

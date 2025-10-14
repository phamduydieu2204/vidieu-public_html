/**
 * Page Mappings Admin JavaScript
 */
(function($) {
    'use strict';
    
    var VDPageMappingsAdmin = {
        init: function() {
            this.bindEvents();
            this.loadMappings();
        },
        
        bindEvents: function() {
            $('#vd-sidebar-type').on('change', this.handleSidebarTypeChange);
            $('#vd-save-mapping').on('click', this.saveMapping);
            $(document).on('click', '.vd-delete-mapping', this.deleteMapping);
        },
        
        handleSidebarTypeChange: function() {
            var $taxonomyRow = $('#vd-taxonomy-row');
            if ($(this).val() === 'custom_taxonomy') {
                $taxonomyRow.show();
            } else {
                $taxonomyRow.hide();
                $('#vd-taxonomy-select').val('');
            }
        },
        
        saveMapping: function(e) {
            e.preventDefault();
            
            var pageId = $('#vd-page-select').val();
            var sidebarType = $('#vd-sidebar-type').val();
            var sidebarConfig = '';
            var shortcodeConfig = $('#vd-shortcode-config').val();
            var perPage = $('#vd-per-page').val() || 16;
            
            if (!pageId || !sidebarType) {
                alert('Please select both a page and sidebar type.');
                return;
            }
            
            if (sidebarType === 'custom_taxonomy') {
                sidebarConfig = $('#vd-taxonomy-select').val();
                if (!sidebarConfig) {
                    alert('Please select a taxonomy.');
                    return;
                }
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: vdPageMappings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vd_save_mapping',
                    nonce: vdPageMappings.nonce,
                    page_id: pageId,
                    sidebar_type: sidebarType,
                    sidebar_config: sidebarConfig,
                    shortcode_config: shortcodeConfig,
                    per_page: perPage
                },
                success: function(response) {
                    if (response.success) {
                        alert(vdPageMappings.strings.saved);
                        VDPageMappingsAdmin.loadMappings();
                        VDPageMappingsAdmin.resetForm();
                    } else {
                        alert(response.data.message || vdPageMappings.strings.error);
                    }
                },
                error: function() {
                    alert(vdPageMappings.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save Mapping');
                }
            });
        },
        
        deleteMapping: function(e) {
            e.preventDefault();
            
            if (!confirm(vdPageMappings.strings.confirmDelete)) {
                return;
            }
            
            var $button = $(this);
            var id = $button.data('id');
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: vdPageMappings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vd_delete_mapping',
                    nonce: vdPageMappings.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        alert(vdPageMappings.strings.deleted);
                        VDPageMappingsAdmin.loadMappings();
                    } else {
                        alert(vdPageMappings.strings.error);
                    }
                },
                error: function() {
                    alert(vdPageMappings.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },
        
        loadMappings: function() {
            var $tbody = $('#vd-mappings-tbody');
            $tbody.html('<tr><td colspan="4">Loading...</td></tr>');
            
            $.ajax({
                url: vdPageMappings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vd_get_mappings',
                    nonce: vdPageMappings.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        if (response.data.length > 0) {
                            var html = '';
                            $.each(response.data, function(index, mapping) {
                                html += '<tr>';
                                html += '<td>' + mapping.page_title + '</td>';
                                html += '<td>' + mapping.sidebar_type_label + '</td>';
                                html += '<td>' + mapping.config_label + '</td>';
                                html += '<td>';
                                html += '<button type="button" class="button button-small vd-delete-mapping" data-id="' + mapping.id + '">Delete</button>';
                                html += '</td>';
                                html += '</tr>';
                            });
                            $tbody.html(html);
                        } else {
                            $tbody.html('<tr><td colspan="4">No mappings found.</td></tr>');
                        }
                    } else {
                        $tbody.html('<tr><td colspan="4">Error loading mappings.</td></tr>');
                    }
                },
                error: function() {
                    $tbody.html('<tr><td colspan="4">Error loading mappings.</td></tr>');
                }
            });
        },
        
        resetForm: function() {
            $('#vd-page-select').val('');
            $('#vd-sidebar-type').val('');
            $('#vd-taxonomy-select').val('');
            $('#vd-per-page').val('16');
            $('#vd-shortcode-config').val('columns="4" title="SẢN PHẨM THEO NHU CẦU"');
            $('#vd-taxonomy-row').hide();
        }
    };
    
    $(document).ready(function() {
        VDPageMappingsAdmin.init();
    });
    
})(jQuery);
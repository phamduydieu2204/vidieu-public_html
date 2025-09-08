/**
 * Re:plain Mobile Integration
 * Adds Re:plain to mobile chat popup
 * 
 * @package VidieuHomeSections
 * @version 1.7.0
 */
(function($) {
    'use strict';
    
    if (typeof $ === 'undefined') {
        return;
    }
    
    var ReplainMobileIntegration = {
        initialized: false,
        
        init: function() {
            if (this.initialized) {
                return;
            }
            
            this.initialized = true;
            this.addToChatPopup();
            this.observePopupChanges();
        },
        
        /**
         * Add Re:plain item to mobile chat popup
         */
        addToChatPopup: function() {
            var self = this;
            
            // Wait for popup to be available
            $(document).ready(function() {
                // Try immediately
                self.insertReplainItem();
                
                // Also try after a delay (for dynamic content)
                setTimeout(function() {
                    self.insertReplainItem();
                }, 1000);
            });
            
            // Listen for popup open events
            $(document).on('click', '.nasa-support-chat, [href="#nasa-support-chat"]', function() {
                setTimeout(function() {
                    self.insertReplainItem();
                }, 100);
            });
        },
        
        /**
         * Insert Re:plain item into chat popup
         */
        insertReplainItem: function() {
            // Check if already added
            if ($('.vd-replain-chat-item').length > 0) {
                return;
            }
            
            // Find the chat popup container
            var $chatPopup = $('#nasa-support-chat, .nasa-support-chat-popup');
            if (!$chatPopup.length) {
                $chatPopup = $('.nasa-support-chat-wrap');
            }
            
            if (!$chatPopup.length) {
                return;
            }
            
            // Find where to insert (after YouTube, before work hours)
            var $insertPoint = null;
            
            // Try to find YouTube item
            var $youtubeItem = $chatPopup.find('a[href*="youtube"]').closest('.chat-item, li, div[class*="item"]');
            if ($youtubeItem.length) {
                $insertPoint = $youtubeItem;
            } else {
                // Find work hours section
                var $workHours = $chatPopup.find('.work-hours, .nasa-work-hours, [class*="work-hours"]');
                if ($workHours.length) {
                    $insertPoint = $workHours.prev();
                } else {
                    // Just find the last item before any footer
                    $insertPoint = $chatPopup.find('a[href^="tel:"], a[href*="zalo"], a[href*="messenger"]').last().closest('.chat-item, li, div[class*="item"]');
                }
            }
            
            // Create Re:plain item HTML
            var replainHtml = this.createReplainItem();
            
            // Insert the item
            if ($insertPoint && $insertPoint.length) {
                $(replainHtml).insertAfter($insertPoint);
            } else {
                // Fallback: append to the end of items list
                var $itemsContainer = $chatPopup.find('.chat-items, .nasa-support-items, ul, .items-wrap').first();
                if ($itemsContainer.length) {
                    $itemsContainer.append(replainHtml);
                } else {
                    $chatPopup.append(replainHtml);
                }
            }
            
            // Bind click event
            this.bindClickEvent();
        },
        
        /**
         * Create Re:plain item HTML
         */
        createReplainItem: function() {
            var label = (typeof vd_replain !== 'undefined' && vd_replain.i18n) 
                ? vd_replain.i18n.chat_label 
                : 'Chat trực tiếp (Re:plain)';
                
            var description = (typeof vd_replain !== 'undefined' && vd_replain.i18n) 
                ? vd_replain.i18n.chat_description 
                : 'Hỗ trợ trực tuyến';
            
            // Match the structure of existing items in the popup
            var html = '<div class="vd-replain-chat-item chat-item">' +
                '<a href="#" class="vd-replain-trigger" data-action="open-replain">' +
                    '<span class="icon">' +
                        '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
                            '<path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3 .97 4.29L2 22l5.71-.97A9.93 9.93 0 0012 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.41 0-2.73-.38-3.88-1.03l-.28-.17-2.9.49.49-2.9-.17-.28A7.887 7.887 0 014 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/>' +
                            '<path d="M7 11h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2z"/>' +
                        '</svg>' +
                    '</span>' +
                    '<span class="content">' +
                        '<span class="title">' + label + '</span>' +
                        '<span class="description">' + description + '</span>' +
                    '</span>' +
                '</a>' +
            '</div>';
            
            return html;
        },
        
        /**
         * Bind click event to Re:plain trigger
         */
        bindClickEvent: function() {
            $(document).off('click.replain').on('click.replain', '.vd-replain-trigger', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close the popup
                var $popup = $(this).closest('#nasa-support-chat, .nasa-support-chat-popup');
                if ($popup.length) {
                    $popup.removeClass('active open').fadeOut(200);
                    $('.transparent-window, .nasa-transparent-window').fadeOut(200);
                }
                
                // Open Re:plain
                if (window.VidieuReplain && typeof window.VidieuReplain.open === 'function') {
                    // Add class to show Re:plain on mobile
                    setTimeout(function() {
                        $('#replain-widget, .replain-widget, [id*="replain"], [class*="replain-messenger"]').addClass('replain-opened');
                    }, 100);
                    
                    window.VidieuReplain.open();
                } else {
                    console.warn('VidieuReplain.open() is not available');
                }
                
                return false;
            });
        },
        
        /**
         * Observe DOM changes to re-add Re:plain item if needed
         */
        observePopupChanges: function() {
            var self = this;
            
            // Use MutationObserver if available
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length) {
                            // Check if chat popup was added
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    var $node = $(node);
                                    if ($node.is('#nasa-support-chat, .nasa-support-chat-popup') || 
                                        $node.find('#nasa-support-chat, .nasa-support-chat-popup').length) {
                                        setTimeout(function() {
                                            self.insertReplainItem();
                                        }, 100);
                                    }
                                }
                            });
                        }
                    });
                });
                
                // Start observing
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        ReplainMobileIntegration.init();
    });
    
    // Also initialize on window load
    $(window).on('load', function() {
        ReplainMobileIntegration.init();
    });
    
})(jQuery);
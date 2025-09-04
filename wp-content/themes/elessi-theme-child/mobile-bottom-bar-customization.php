<?php
/**
 * Mobile Bottom Bar Customization
 * Remove Filter and Search buttons, add Chat button
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modify mobile bottom bar via JavaScript
 */
add_action('wp_footer', 'vidieu_customize_mobile_bottom_bar', 999);

function vidieu_customize_mobile_bottom_bar() {
    // No need to check for mobile here as NASA theme already handles this
    ?>
    <style>
        /* Hide filter button on shop page and product pages */
        body.post-type-archive-product .nasa-bot-item-sidebar,
        body.tax-product_cat .nasa-bot-item-sidebar,
        body.tax-product_tag .nasa-bot-item-sidebar,
        body.woocommerce-shop .nasa-bot-item-sidebar {
            display: none !important;
        }
        
        /* Hide search button on home page, checkout and order-received pages */
        body.home .nasa-bot-item-search,
        body.page-template-default .nasa-bot-item-search,
        body.woocommerce-checkout .nasa-bot-item-search,
        body.woocommerce-order-received .nasa-bot-item-search,
        body.page-checkout .nasa-bot-item-search,
        .woocommerce-checkout .nasa-bot-item-search {
            display: none !important;
        }
        
        /* Always ensure bottom bar is visible on mobile */
        @media (max-width: 767px) {
            .nasa-bottom-bar {
                display: block !important;
                visibility: visible !important;
            }
        }
        
        /* Remove custom styling for chat icon - let it inherit from theme */
        /* This ensures chat icon matches other icons exactly */
        
        /* Adjust columns when items are hidden */
        body.home .nasa-bottom-bar-icons.nasa-4-columns,
        body.post-type-archive-product .nasa-bottom-bar-icons.nasa-4-columns,
        body.tax-product_cat .nasa-bottom-bar-icons.nasa-4-columns,
        body.tax-product_tag .nasa-bottom-bar-icons.nasa-4-columns {
            grid-template-columns: repeat(4, 1fr);
        }
        
        /* Ensure proper spacing */
        .nasa-bottom-bar .nasa-bottom-bar-icons {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        
        .nasa-bottom-bar .nasa-bot-item {
            flex: 1;
            text-align: center;
        }
        
        /* Chat icon animation */
        @keyframes chatPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .nasa-bot-icon-chat:hover i {
            animation: chatPulse 0.5s ease;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Make showChatOptions globally accessible
        window.showChatOptions = function() {
            
            // Check if popup already exists
            if ($('#vidieu-chat-popup').length > 0) {
                if ($('#vidieu-chat-popup').hasClass('active')) {
                    hidePopup();
                } else {
                    showPopup();
                }
                return;
            }
            
            
            // Create overlay
            var overlayHtml = '<div class="vidieu-popup-overlay"></div>';
            
            // Create popup HTML
            var popupHtml = `
                <div id="vidieu-chat-popup" class="vidieu-chat-popup">
                    <div class="vidieu-popup-arrow"></div>
                    <div class="vidieu-popup-content">
                        <a href="tel:0988691196" class="vidieu-chat-option phone-option">
                            <div class="chat-icon">
                                <svg viewBox="0 0 24 24" width="35" height="35">
                                    <g transform="translate(0 -1028.4)">
                                        <path d="m23.015 1046.8c0 0.3-0.052 0.6-0.156 1.1-0.105 0.4-0.214 0.8-0.329 1-0.219 0.6-0.855 1.1-1.907 1.7-0.98 0.5-1.95 0.8-2.909 0.8h-0.828c-0.261-0.1-0.558-0.2-0.892-0.2-0.333-0.1-0.583-0.2-0.75-0.3-0.156 0-0.443-0.1-0.86-0.3s-0.672-0.2-0.766-0.3c-1.022-0.3-1.934-0.8-2.736-1.3-1.3346-0.8-2.7157-1.9-4.1438-3.3-1.4176-1.5-2.5381-2.9-3.3616-4.2-0.5003-0.8-0.9329-1.7-1.2977-2.7-0.0313-0.1-0.1251-0.4-0.2815-0.8-0.1563-0.4-0.2658-0.7-0.3283-0.9-0.0522-0.1-0.1251-0.4-0.2189-0.7s-0.1616-0.6-0.2033-0.9c-0.0313-0.3-0.0469-0.5-0.0469-0.8 0-1 0.2658-2 0.7974-2.9 0.5837-1.1 1.1362-1.7 1.6574-2 0.2606-0.1 0.615-0.2 1.0632-0.3 0.4586-0.1 0.8287-0.1 1.1101-0.1h0.3284c0.1876 0.1 0.4638 0.5 0.8287 1.2 0.1146 0.2 0.271 0.5 0.469 0.8 0.1981 0.4 0.3805 0.7 0.5473 1 0.1667 0.3 0.3283 0.6 0.4847 0.9 0.0312 0 0.1198 0.1 0.2658 0.4 0.1563 0.2 0.271 0.4 0.344 0.5 0.0729 0.2 0.1094 0.3 0.1094 0.5s-0.1511 0.4-0.4534 0.8c-0.2919 0.3-0.615 0.6-0.9694 0.8-0.344 0.3-0.6672 0.5-0.9694 0.8-0.2919 0.3-0.4378 0.6-0.4378 0.8 0 0.1 0.026 0.2 0.0781 0.3 0.0522 0.2 0.0939 0.3 0.1251 0.3 0.0417 0.1 0.1147 0.2 0.2189 0.4 0.1147 0.2 0.1772 0.3 0.1877 0.3 0.7922 1.4 1.699 2.7 2.7205 3.7 1.0213 1 2.2463 1.9 3.6743 2.7 0.021 0 0.12 0.1 0.297 0.2s0.302 0.2 0.375 0.2 0.178 0.1 0.313 0.1c0.146 0.1 0.266 0.1 0.36 0.1 0.187 0 0.427-0.1 0.719-0.4s0.568-0.6 0.829-1c0.26-0.3 0.547-0.7 0.86-1 0.312-0.3 0.573-0.4 0.781-0.4 0.146 0 0.292 0 0.438 0.1 0.157 0.1 0.344 0.2 0.563 0.3 0.219 0.2 0.349 0.3 0.391 0.3 0.261 0.2 0.537 0.3 0.829 0.5 0.302 0.2 0.635 0.3 1 0.5s0.647 0.4 0.845 0.5c0.729 0.4 1.125 0.7 1.188 0.8 0.031 0.1 0.047 0.2 0.047 0.4" fill="#e74c3c"/>
                                        <path d="m1.2188 4.75c-0.1453 0.5076-0.2188 1.0294-0.2188 1.5312 0 0.282 0.0312 0.5414 0.0625 0.8126 0.0417 0.2608 0.0937 0.572 0.1875 0.9062 0.0938 0.3337 0.1666 0.5829 0.2188 0.75 0.0625 0.1564 0.1873 0.4575 0.3437 0.875 0.1564 0.417 0.25 0.656 0.2813 0.75 0.3648 1.023 0.7809 1.946 1.2812 2.75 0.8235 1.336 1.9574 2.695 3.375 4.125 1.428 1.419 2.7908 2.55 4.125 3.375 0.803 0.501 1.728 0.947 2.75 1.313 0.094 0.031 0.333 0.124 0.75 0.281 0.417 0.156 0.719 0.25 0.875 0.312 0.167 0.052 0.416 0.125 0.75 0.219s0.614 0.177 0.875 0.219c0.271 0.031 0.562 0.031 0.844 0.031 0.959 0 1.926-0.249 2.906-0.781 1.053-0.585 1.687-1.135 1.906-1.657 0.115-0.26 0.208-0.613 0.313-1.062 0.104-0.459 0.156-0.844 0.156-1.125 0-0.146 0-0.271-0.031-0.344-0.037-0.111-0.227-0.263-0.5-0.437-0.253 0.494-0.853 1.012-1.844 1.562-0.98 0.532-1.947 0.813-2.906 0.813-0.282 0-0.573-0.031-0.844-0.063-0.261-0.042-0.541-0.093-0.875-0.187s-0.583-0.167-0.75-0.219c-0.156-0.063-0.458-0.187-0.875-0.344-0.417-0.156-0.656-0.25-0.75-0.281-1.022-0.365-1.947-0.78-2.75-1.281-1.3342-0.825-2.697-1.956-4.125-3.375-1.4176-1.43-2.5515-2.821-3.375-4.157-0.5003-0.8032-0.9164-1.6954-1.2812-2.7182-0.0313-0.094-0.1249-0.3639-0.2813-0.7813-0.1564-0.4175-0.2812-0.6873-0.3437-0.8437-0.0522-0.1672-0.125-0.4164-0.2188-0.75-0.0224-0.0796-0.0119-0.1433-0.0312-0.2188z" transform="translate(0 1028.4)" fill="#c0392b"/>
                                    </g>
                                </svg>
                            </div>
                            <div class="chat-text">
                                <span class="chat-title">Gọi điện ngay</span>
                                <span class="chat-subtitle">0988 691 196</span>
                            </div>
                        </a>
                        <a href="https://m.me/vidieuvn.muatoolAmazon" target="_blank" class="vidieu-chat-option messenger-option">
                            <div class="chat-icon">
                                <svg viewBox="0 0 800 800" width="35" height="35">
                                    <defs>
                                        <radialGradient id="msgGrad3" cx="101.9" cy="809" r="1.1" gradientTransform="matrix(800 0 0 -800 -81386 648000)" gradientUnits="userSpaceOnUse">
                                            <stop offset="0" stop-color="#09f"/>
                                            <stop offset=".6" stop-color="#a033ff"/>
                                            <stop offset=".9" stop-color="#ff5280"/>
                                            <stop offset="1" stop-color="#ff7061"/>
                                        </radialGradient>
                                    </defs>
                                    <path fill="url(#msgGrad3)" d="M400 0C174.7 0 0 165.1 0 388c0 116.6 47.8 217.4 125.6 287 6.5 5.8 10.5 14 10.7 22.8l2.2 71.2a32 32 0 0 0 44.9 28.3l79.4-35c6.7-3 14.3-3.5 21.4-1.6 36.5 10 75.3 15.4 115.8 15.4 225.3 0 400-165.1 400-388S625.3 0 400 0z"/>
                                    <path fill="#fff" d="m159.8 501.5 117.5-186.4a60 60 0 0 1 86.8-16l93.5 70.1a24 24 0 0 0 28.9-.1l126.2-95.8c16.8-12.8 38.8 7.4 27.6 25.3L522.7 484.9a60 60 0 0 1-86.8 16l-93.5-70.1a24 24 0 0 0-28.9.1l-126.2 95.8c-16.8 12.8-38.8-7.3-27.5-25.2z"/>
                                </svg>
                            </div>
                            <div class="chat-text">
                                <span class="chat-title">Chat Facebook</span>
                                <span class="chat-subtitle">Phản hồi nhanh nhất</span>
                            </div>
                        </a>
                        <a href="https://zalo.me/g/hwcfvo585" target="_blank" class="vidieu-chat-option zalo-option">
                            <div class="chat-icon">
                                <svg viewBox="0 0 161.5 161.5" width="35" height="35">
                                    <!-- viền/nền -->
                                    <path class="bg" fill="#0068FF" d="M73.29,0.54h14.31c19.66,0,31.15,2.89,41.35,8.36a56.65,56.65,0,0,1,23.65,23.65c5.47,10.2,8.36,21.69,8.36,41.35V88.15c0,19.66-2.89,31.15-8.36,41.35a56.65,56.65,0,0,1-23.65,23.65c-10.2,5.47-21.69,8.36-41.35,8.36H73.35c-19.66,0-31.15-2.89-41.35-8.36a56.65,56.65,0,0,1-23.65-23.65c-5.47-10.2-8.36-21.69-8.36-41.35V73.89c0-19.66,2.89-31.15,8.36-41.35A56.65,56.65,0,0,1,32,8.89C42.14,3.43,53.69,0.54,73.29,0.54Z"/>
                                    <path class="shadow" fill="#0055d4" opacity="0.3" d="M160.96,85.75v2.35c0,19.66-2.89,31.15-8.35,41.35a56.65,56.65,0,0,1-23.65,23.65c-10.2,5.47-21.69,8.36-41.35,8.36H73.35c-16.09,0-26.7-1.93-35.62-5.63L23.04,140.75Z"/>
                                    <path class="inner" fill="#fff" d="M24.67,141.26c7.53.83,16.94-1.31,23.62-4.56,29,16,74.38,15.27,101.84-2.3q1.6-2.4,3-5c5.49-10.24,8.39-21.77,8.39-41.5v-14.3c0-19.73-2.9-31.26-8.39-41.5a56.86,56.86,0,0,0-23.74-23.74c-10.24-5.49-21.77-8.39-41.5-8.39H73.51c-16.8,0-27.71,2.12-36.88,6.15q-.75.67-1.47,1.37c-26.89,25.92-28.93,82.11-6.13,112.64l.08.14c3.51,5.18.12,14.24-5.18,19.55C23.07,140.64,23.38,141.14,24.67,141.26Z"/>
                                    <!-- chữ/biểu tượng -->
                                    <path class="fg" fill="#0068FF" d="M66.1,55.09H34.59v6.76h21.87l-21.56,26.72a6.06,6.06,0,0,0-1.17,4v1.72h29.73a2.73,2.73,0,0,0,2.7-2.7v-3.62h-23l20.27-25.43,1.11-1.35.12-.18a8,8,0,0,0,1.41-5Z"/>
                                    <path class="fg" fill="#0068FF" d="M106.22,94.29H110.75v-39.2h-6.76v36.92A2.27,2.27,0,0,0,106.22,94.29Z"/>
                                    <path class="fg" fill="#0068FF" d="M83.12,63.82a15.36,15.36,0,1,0,15.36,15.36A15.36,15.36,0,0,0,83.12,63.82Zm0,24.39a9,9,0,1,1,9-9A9,9,0,0,1,83.12,88.21Z"/>
                                    <path class="fg" fill="#0068FF" d="M130.67,63.57A15.48,15.48,0,1,0,146.15,79.05,15.5,15.5,0,0,0,130.67,63.57Zm0,24.64a9.09,9.09,0,1,1,9.09-9.09A9.07,9.07,0,0,1,130.67,88.21Z"/>
                                    <path class="fg" fill="#0068FF" d="M94.92,94.29h3.62V64.68h-6.33v27A2.72,2.72,0,0,0,94.92,94.29Z"/>
                                </svg>
                            </div>
                            <div class="chat-text">
                                <span class="chat-title">Nhóm Zalo</span>
                                <span class="chat-subtitle">Voucher nội bộ</span>
                            </div>
                        </a>
                        <a href="https://t.me/+ZanU07t-Vgc3OWJl" target="_blank" class="vidieu-chat-option telegram-option">
                            <div class="chat-icon">
                                <svg viewBox="0 0 32 32" width="35" height="35" fill="none">
                                    <circle cx="16" cy="16" r="14" fill="url(#telegramGradMobile)"/>
                                    <path d="M22.9866 10.2088C23.1112 9.40332 22.3454 8.76755 21.6292 9.082L7.36482 15.3448C6.85123 15.5703 6.8888 16.3483 7.42147 16.5179L10.3631 17.4547C10.9246 17.6335 11.5325 17.541 12.0228 17.2023L18.655 12.6203C18.855 12.4821 19.073 12.7665 18.9021 12.9426L14.1281 17.8646C13.665 18.3421 13.7569 19.1512 14.314 19.5005L19.659 22.8523C20.2585 23.2282 21.0297 22.8506 21.1418 22.1261L22.9866 10.2088Z" fill="white"/>
                                    <defs>
                                        <linearGradient id="telegramGradMobile" x1="16" y1="2" x2="16" y2="30" gradientUnits="userSpaceOnUse">
                                            <stop stop-color="#37BBFE"/>
                                            <stop offset="1" stop-color="#007DBB"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                            </div>
                            <div class="chat-text">
                                <span class="chat-title">Nhóm Telegram</span>
                                <span class="chat-subtitle">Voucher nội bộ</span>
                            </div>
                        </a>
                        <a href="https://www.youtube.com/@phamduydieu" target="_blank" class="vidieu-chat-option youtube-option">
                            <div class="chat-icon">
                                <svg viewBox="0 0 48 48" width="35" height="35" fill="none">
                                    <circle cx="24" cy="24" r="20" fill="#FF0000"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M35.3005 16.3781C35.6996 16.7772 35.9872 17.2739 36.1346 17.8187C36.9835 21.2357 36.7873 26.6324 36.1511 30.1813C36.0037 30.7261 35.7161 31.2228 35.317 31.6219C34.9179 32.021 34.4212 32.3086 33.8764 32.456C31.8819 33 23.8544 33 23.8544 33C23.8544 33 15.8269 33 13.8324 32.456C13.2876 32.3086 12.7909 32.021 12.3918 31.6219C11.9927 31.2228 11.7051 30.7261 11.5577 30.1813C10.7038 26.7791 10.9379 21.3791 11.5412 17.8352C11.6886 17.2903 11.9762 16.7936 12.3753 16.3945C12.7744 15.9954 13.2711 15.7079 13.8159 15.5604C15.8104 15.0165 23.8379 15 23.8379 15C23.8379 15 31.8654 15 33.8599 15.544C34.4047 15.6914 34.9014 15.979 35.3005 16.3781ZM27.9423 24L21.283 27.8571V20.1428L27.9423 24Z" fill="white"/>
                                </svg>
                            </div>
                            <div class="chat-text">
                                <span class="chat-title">Kênh YouTube</span>
                                <span class="chat-subtitle">Hướng dẫn sử dụng</span>
                            </div>
                        </a>
                        <div class="vidieu-work-hours">
                            <span>Thời gian làm việc: 08:00 - 21:00</span>
                        </div>
                    </div>
                </div>
            `;
            
            // Add overlay and popup to body
            $('body').append(overlayHtml + popupHtml);
            
            // Add popup styles if not exists
            if ($('#vidieu-chat-popup-styles').length === 0) {
                var popupStyles = `
                    <style id="vidieu-chat-popup-styles">
                        /* Chat popup container */
                        .vidieu-chat-popup {
                            position: fixed;
                            bottom: 70px;
                            background: #fff;
                            border-radius: 12px;
                            box-shadow: 0 2px 20px rgba(0,0,0,0.15);
                            z-index: 99999;
                            display: none;
                            min-width: 220px;
                            overflow: hidden;
                            transform-origin: bottom center;
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        }
                        
                        .vidieu-chat-popup.active {
                            display: block;
                            animation: slideUpFade 0.3s ease-out;
                        }
                        
                        /* Arrow pointing down */
                        .vidieu-popup-arrow {
                            position: absolute;
                            bottom: -8px;
                            width: 0;
                            height: 0;
                            border-left: 8px solid transparent;
                            border-right: 8px solid transparent;
                            border-top: 8px solid #fff;
                            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
                        }
                        
                        /* Popup content */
                        .vidieu-popup-content {
                            display: flex;
                            flex-direction: column;
                            padding: 8px 0;
                        }
                        
                        /* Chat options */
                        .vidieu-chat-option {
                            display: flex;
                            align-items: center;
                            gap: 12px;
                            padding: 12px 20px;
                            text-decoration: none !important;
                            color: #333;
                            transition: all 0.2s ease;
                            border-bottom: 1px solid #f0f0f0;
                            min-height: 48px;
                        }
                        
                        .vidieu-chat-option:last-child {
                            border-bottom: none;
                        }
                        
                        .vidieu-chat-option:hover {
                            background: #f8f8f8;
                            color: #000;
                        }
                        
                        .vidieu-chat-option:active {
                            background: #eee;
                        }
                        
                        .chat-icon {
                            flex-shrink: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 35px;
                            height: 35px;
                        }
                        
                        .chat-text {
                            display: flex;
                            flex-direction: column;
                            align-items: flex-start;
                            gap: 2px;
                        }
                        
                        .chat-title {
                            font-size: 14px;
                            font-weight: 600;
                            color: #333;
                            line-height: 1.2;
                        }
                        
                        .chat-subtitle {
                            font-size: 12px;
                            font-weight: 400;
                            color: #666;
                            line-height: 1.2;
                        }
                        
                        .vidieu-work-hours {
                            padding: 10px 20px;
                            text-align: center;
                            border-top: 1px solid #f0f0f0;
                            background: #f8f8f8;
                        }
                        
                        .vidieu-work-hours span {
                            font-size: 11px;
                            color: #888;
                            font-weight: 400;
                        }
                        
                        /* Animation */
                        @keyframes slideUpFade {
                            from { 
                                transform: translateY(20px) scale(0.9);
                                opacity: 0;
                            }
                            to { 
                                transform: translateY(0) scale(1);
                                opacity: 1;
                            }
                        }
                        
                        /* Overlay for closing popup */
                        .vidieu-popup-overlay {
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            z-index: 99998;
                            display: none;
                        }
                        
                        .vidieu-popup-overlay.active {
                            display: block;
                        }
                    </style>
                `;
                $('head').append(popupStyles);
            }
            
            // Position popup and show it
            positionPopup();
            showPopup();
        };
        
        // Show popup function
        function showPopup() {
            $('#vidieu-chat-popup').addClass('active');
            $('.vidieu-popup-overlay').addClass('active');
            
            // Close when clicking/touching overlay
            $('.vidieu-popup-overlay').off('click touchstart').on('click touchstart', function(e) {
                e.preventDefault();
                hidePopup();
            });
            
            // Add delay before enabling outside click/touch to prevent immediate close
            setTimeout(function() {
                // Close on swipe/touch outside popup
                $(document).off('touchstart.popup').on('touchstart.popup', function(e) {
                    var $popup = $('#vidieu-chat-popup');
                    var $chatBtn = $('.nasa-bot-icon-chat');
                    
                    // Check if touch is outside popup and chat button
                    if (!$popup.is(e.target) && 
                        $popup.has(e.target).length === 0 &&
                        !$chatBtn.is(e.target) && 
                        $chatBtn.has(e.target).length === 0) {
                        hidePopup();
                    }
                });
                
                // Also handle click for desktop
                $(document).off('click.popup').on('click.popup', function(e) {
                    var $popup = $('#vidieu-chat-popup');
                    var $chatBtn = $('.nasa-bot-icon-chat');
                    
                    // Check if click is outside popup and chat button
                    if (!$popup.is(e.target) && 
                        $popup.has(e.target).length === 0 &&
                        !$chatBtn.is(e.target) && 
                        $chatBtn.has(e.target).length === 0) {
                        hidePopup();
                    }
                });
            }, 300); // 300ms delay
        }
        
        // Hide popup function
        function hidePopup() {
            $('#vidieu-chat-popup').removeClass('active');
            $('.vidieu-popup-overlay').removeClass('active');
            
            // Remove event listeners
            $(document).off('touchstart.popup');
            $(document).off('click.popup');
            $('.vidieu-popup-overlay').off('click touchstart');
        }
        
        // Position popup above chat button
        function positionPopup() {
            var $chatBtn = $('.nasa-bot-icon-chat');
            var $popup = $('#vidieu-chat-popup');
            
            if ($chatBtn.length && $popup.length) {
                // Get chat button position and dimensions
                var btnOffset = $chatBtn.offset();
                var btnWidth = $chatBtn.outerWidth();
                var btnHeight = $chatBtn.outerHeight();
                
                // Get popup dimensions
                var popupWidth = $popup.outerWidth();
                var popupHeight = $popup.outerHeight();
                
                // Calculate popup position
                var left = btnOffset.left + (btnWidth / 2) - (popupWidth / 2);
                var windowWidth = $(window).width();
                
                // Ensure popup doesn't go off-screen
                if (left < 10) {
                    left = 10;
                } else if (left + popupWidth > windowWidth - 10) {
                    left = windowWidth - popupWidth - 10;
                }
                
                // Position arrow relative to button
                var arrowLeft = btnOffset.left + (btnWidth / 2) - left - 8; // 8 is half arrow width
                
                // Apply positioning
                $popup.css({
                    'left': left + 'px',
                    'right': 'auto'
                });
                
                // Position arrow
                $('.vidieu-popup-arrow').css({
                    'left': arrowLeft + 'px',
                    'right': 'auto'
                });
            }
        }
        
        // Function to add chat button
        function addChatButton() {
            var $bottomBar = $('.nasa-bottom-bar-icons');
            if ($bottomBar.length === 0) return;
            
            // Check if chat button already exists
            if ($('.nasa-bot-icon-chat').length > 0) return;
            
            // Create chat button HTML with same structure as other icons
            var chatButtonHtml = `
                <li class="nasa-bot-item">
                    <a class="nasa-bot-icons nasa-bot-icon-chat botbar-chat-link" href="javascript:void(0);" title="Chat" rel="nofollow">
                        <i class="nasa-icon pe-7s-comment"></i>
                        Chat
                    </a>
                </li>
            `;
            
            // Add chat button at the end
            $bottomBar.append(chatButtonHtml);
        }
        
        // Bind click event globally for chat button
        $(document).on('click', '.nasa-bot-icon-chat, .botbar-chat-link', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Show chat options
            window.showChatOptions();
            return false;
        });
        
        // Close popup when clicking chat options (after opening in new tab)
        $(document).on('click', '.vidieu-chat-option', function() {
            setTimeout(function() {
                hidePopup();
            }, 100);
        });
        
        // Function to modify shop page bottom bar
        function modifyShopPageBottomBar() {
            if (!$('body').hasClass('post-type-archive-product') && 
                !$('body').hasClass('tax-product_cat') && 
                !$('body').hasClass('tax-product_tag')) {
                return;
            }
            
            var $shopIcon = $('.nasa-bot-icon-shop');
            if ($shopIcon.length > 0) {
                // Change shop icon to home icon
                $shopIcon.attr('href', '<?php echo home_url(); ?>')
                         .attr('title', 'Home')
                         .html('<i class="nasa-icon pe-7s-home"></i>Home');
                $shopIcon.parent().removeClass('nasa-bot-item-shop').addClass('nasa-bot-item-home');
            }
        }
        
        // Initialize
        function initMobileBottomBar() {
            addChatButton();
            modifyShopPageBottomBar();
        }
        
        // Run on page load
        initMobileBottomBar();
        
        // Run after AJAX navigation
        $(document).on('nasa_after_load_ajax nasa_complete_update_quickview', function() {
            setTimeout(initMobileBottomBar, 100);
        });
        
        // Run on window load to ensure all elements are ready
        $(window).on('load', function() {
            setTimeout(initMobileBottomBar, 500);
        });
        
        // Observer for dynamic content
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            var $node = $(node);
                            if ($node.hasClass('nasa-bottom-bar') || $node.find('.nasa-bottom-bar').length > 0) {
                                console.log('Bottom bar detected in mutation, reinitializing');
                                setTimeout(initMobileBottomBar, 100);
                            }
                        }
                    });
                }
            });
        });
        
        // Observe body for changes
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        // Fallback: Check periodically if chat button exists
        var checkInterval = setInterval(function() {
            if ($('.nasa-bottom-bar-icons').length > 0 && $('.nasa-bot-icon-chat').length === 0) {
                console.log('Bottom bar found but no chat button, adding it');
                initMobileBottomBar();
            }
        }, 1000);
        
        // Clear interval after 10 seconds to prevent continuous checking
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 10000);
    });
    </script>
    <?php
}

/**
 * Add filter to modify bottom bar items server-side (if possible)
 */
add_filter('nasa_mobile_bottom_bar_items', 'vidieu_modify_bottom_bar_items', 999);

function vidieu_modify_bottom_bar_items($items) {
    // Remove filter button on shop pages
    if (is_shop() || is_product_category() || is_product_tag()) {
        unset($items['filter']);
    }
    
    // Remove search button on home page, checkout and order-received pages
    if (is_front_page() || is_home() || is_checkout() || is_wc_endpoint_url('order-received')) {
        unset($items['search']);
    }
    
    // Add chat button
    $items['chat'] = array(
        'icon' => 'pe-7s-comment',
        'title' => 'Chat',
        'url' => 'javascript:void(0);',
        'class' => 'nasa-bot-icon-chat'
    );
    
    return $items;
}

/**
 * Force add chat button using NASA theme hooks
 */
add_action('nasa_mobile_bottom_bar_icons', 'vidieu_add_chat_to_bottom_bar', 99);

function vidieu_add_chat_to_bottom_bar() {
    ?>
    <li class="nasa-bot-item">
        <a class="nasa-bot-icons nasa-bot-icon-chat botbar-chat-link" href="javascript:void(0);" title="Chat" rel="nofollow">
            <i class="nasa-icon pe-7s-comment"></i>
            Chat
        </a>
    </li>
    <?php
}

/**
 * Alternative method using output buffer
 */
add_action('nasa_bottom_bar_menu', 'vidieu_modify_bottom_menu_output', 1);

function vidieu_modify_bottom_menu_output() {
    ob_start('vidieu_filter_bottom_bar_output');
}

add_action('nasa_after_bottom_bar_menu', 'vidieu_end_bottom_menu_output', 999);

function vidieu_end_bottom_menu_output() {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}

function vidieu_filter_bottom_bar_output($content) {
    // Add chat button if not already present
    if (strpos($content, 'nasa-bot-icon-chat') === false) {
        $chat_button = '<li class="nasa-bot-item">
            <a class="nasa-bot-icons nasa-bot-icon-chat botbar-chat-link" href="javascript:void(0);" title="Chat" rel="nofollow">
                <i class="nasa-icon pe-7s-comment"></i>
                Chat
            </a>
        </li>';
        
        // Add before closing </ul>
        $content = str_replace('</ul>', $chat_button . '</ul>', $content);
    }
    
    return $content;
}
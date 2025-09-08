<?php
/**
 * Floating Contact Widget for Facebook and Zalo
 * Displays floating contact buttons with SVG icons
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add floating contact widget to frontend
 */
add_action('wp_footer', 'vidieu_floating_contact_widget');

function vidieu_floating_contact_widget() {
    // Don't show on checkout or order received pages
    if (is_checkout() || is_wc_endpoint_url('order-received')) {
        return;
    }
    
    // Don't show on mobile - users should use bottom bar instead
    if (wp_is_mobile()) {
        return;
    }
    ?>
    <div class="vd-floating-contact-widget">
        <!-- Phone button -->
        <div class="vd-floating-item vd-phone-item">
            <div class="vd-slide-content">
                <div class="vd-slide-line1">Gọi điện ngay</div>
                <div class="vd-slide-line2">0988 691 196</div>
            </div>
            <a href="tel:0988691196" 
               class="vd-floating-btn vd-phone-btn"
               title="Gọi điện cho Vidieu.vn">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <g transform="translate(0 -1028.4)">
                        <path d="m23.015 1046.8c0 0.3-0.052 0.6-0.156 1.1-0.105 0.4-0.214 0.8-0.329 1-0.219 0.6-0.855 1.1-1.907 1.7-0.98 0.5-1.95 0.8-2.909 0.8h-0.828c-0.261-0.1-0.558-0.2-0.892-0.2-0.333-0.1-0.583-0.2-0.75-0.3-0.156 0-0.443-0.1-0.86-0.3s-0.672-0.2-0.766-0.3c-1.022-0.3-1.934-0.8-2.736-1.3-1.3346-0.8-2.7157-1.9-4.1438-3.3-1.4176-1.5-2.5381-2.9-3.3616-4.2-0.5003-0.8-0.9329-1.7-1.2977-2.7-0.0313-0.1-0.1251-0.4-0.2815-0.8-0.1563-0.4-0.2658-0.7-0.3283-0.9-0.0522-0.1-0.1251-0.4-0.2189-0.7s-0.1616-0.6-0.2033-0.9c-0.0313-0.3-0.0469-0.5-0.0469-0.8 0-1 0.2658-2 0.7974-2.9 0.5837-1.1 1.1362-1.7 1.6574-2 0.2606-0.1 0.615-0.2 1.0632-0.3 0.4586-0.1 0.8287-0.1 1.1101-0.1h0.3284c0.1876 0.1 0.4638 0.5 0.8287 1.2 0.1146 0.2 0.271 0.5 0.469 0.8 0.1981 0.4 0.3805 0.7 0.5473 1 0.1667 0.3 0.3283 0.6 0.4847 0.9 0.0312 0 0.1198 0.1 0.2658 0.4 0.1563 0.2 0.271 0.4 0.344 0.5 0.0729 0.2 0.1094 0.3 0.1094 0.5s-0.1511 0.4-0.4534 0.8c-0.2919 0.3-0.615 0.6-0.9694 0.8-0.344 0.3-0.6672 0.5-0.9694 0.8-0.2919 0.3-0.4378 0.6-0.4378 0.8 0 0.1 0.026 0.2 0.0781 0.3 0.0522 0.2 0.0939 0.3 0.1251 0.3 0.0417 0.1 0.1147 0.2 0.2189 0.4 0.1147 0.2 0.1772 0.3 0.1877 0.3 0.7922 1.4 1.699 2.7 2.7205 3.7 1.0213 1 2.2463 1.9 3.6743 2.7 0.021 0 0.12 0.1 0.297 0.2s0.302 0.2 0.375 0.2 0.178 0.1 0.313 0.1c0.146 0.1 0.266 0.1 0.36 0.1 0.187 0 0.427-0.1 0.719-0.4s0.568-0.6 0.829-1c0.26-0.3 0.547-0.7 0.86-1 0.312-0.3 0.573-0.4 0.781-0.4 0.146 0 0.292 0 0.438 0.1 0.157 0.1 0.344 0.2 0.563 0.3 0.219 0.2 0.349 0.3 0.391 0.3 0.261 0.2 0.537 0.3 0.829 0.5 0.302 0.2 0.635 0.3 1 0.5s0.647 0.4 0.845 0.5c0.729 0.4 1.125 0.7 1.188 0.8 0.031 0.1 0.047 0.2 0.047 0.4" fill="#e74c3c"/>
                        <path d="m1.2188 4.75c-0.1453 0.5076-0.2188 1.0294-0.2188 1.5312 0 0.282 0.0312 0.5414 0.0625 0.8126 0.0417 0.2608 0.0937 0.572 0.1875 0.9062 0.0938 0.3337 0.1666 0.5829 0.2188 0.75 0.0625 0.1564 0.1873 0.4575 0.3437 0.875 0.1564 0.417 0.25 0.656 0.2813 0.75 0.3648 1.023 0.7809 1.946 1.2812 2.75 0.8235 1.336 1.9574 2.695 3.375 4.125 1.428 1.419 2.7908 2.55 4.125 3.375 0.803 0.501 1.728 0.947 2.75 1.313 0.094 0.031 0.333 0.124 0.75 0.281 0.417 0.156 0.719 0.25 0.875 0.312 0.167 0.052 0.416 0.125 0.75 0.219s0.614 0.177 0.875 0.219c0.271 0.031 0.562 0.031 0.844 0.031 0.959 0 1.926-0.249 2.906-0.781 1.053-0.585 1.687-1.135 1.906-1.657 0.115-0.26 0.208-0.613 0.313-1.062 0.104-0.459 0.156-0.844 0.156-1.125 0-0.146 0-0.271-0.031-0.344-0.037-0.111-0.227-0.263-0.5-0.437-0.253 0.494-0.853 1.012-1.844 1.562-0.98 0.532-1.947 0.813-2.906 0.813-0.282 0-0.573-0.031-0.844-0.063-0.261-0.042-0.541-0.093-0.875-0.187s-0.583-0.167-0.75-0.219c-0.156-0.063-0.458-0.187-0.875-0.344-0.417-0.156-0.656-0.25-0.75-0.281-1.022-0.365-1.947-0.78-2.75-1.281-1.3342-0.825-2.697-1.956-4.125-3.375-1.4176-1.43-2.5515-2.821-3.375-4.157-0.5003-0.8032-0.9164-1.6954-1.2812-2.7182-0.0313-0.094-0.1249-0.3639-0.2813-0.7813-0.1564-0.4175-0.2812-0.6873-0.3437-0.8437-0.0522-0.1672-0.125-0.4164-0.2188-0.75-0.0224-0.0796-0.0119-0.1433-0.0312-0.2188z" transform="translate(0 1028.4)" fill="#c0392b"/>
                    </g>
                </svg>
            </a>
        </div>
        
        <!-- Facebook Messenger button -->
        <div class="vd-floating-item vd-messenger-item">
            <div class="vd-slide-content">
                <div class="vd-slide-line1">Chat Facebook</div>
                <div class="vd-slide-line2">Phản hồi nhanh nhất</div>
            </div>
            <a href="https://m.me/vidieuvn.muatoolAmazon" 
               target="_blank" 
               class="vd-floating-btn vd-messenger-btn"
               title="Chat Facebook với Vidieu.vn">
                <svg viewBox="0 0 800 800" aria-hidden="true">
                    <radialGradient id="msgGrad" cx="101.9" cy="809" r="1.1"
                        gradientTransform="matrix(800 0 0 -800 -81386 648000)" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="#09f"/>
                        <stop offset=".6" stop-color="#a033ff"/>
                        <stop offset=".9" stop-color="#ff5280"/>
                        <stop offset="1" stop-color="#ff7061"/>
                    </radialGradient>
                    <path fill="url(#msgGrad)"
                          d="M400 0C174.7 0 0 165.1 0 388c0 116.6 47.8 217.4 125.6 287 6.5 5.8 10.5 14 10.7 22.8l2.2 71.2a32 32 0 0 0 44.9 28.3l79.4-35c6.7-3 14.3-3.5 21.4-1.6 36.5 10 75.3 15.4 115.8 15.4 225.3 0 400-165.1 400-388S625.3 0 400 0z"/>
                    <path fill="#fff"
                          d="m159.8 501.5 117.5-186.4a60 60 0 0 1 86.8-16l93.5 70.1a24 24 0 0 0 28.9-.1l126.2-95.8c16.8-12.8 38.8 7.4 27.6 25.3L522.7 484.9a60 60 0 0 1-86.8 16l-93.5-70.1a24 24 0 0 0-28.9.1l-126.2 95.8c-16.8 12.8-38.8-7.3-27.5-25.2z"/>
                </svg>
            </a>
        </div>
        
        <!-- Zalo button -->
        <div class="vd-floating-item vd-zalo-item">
            <div class="vd-slide-content">
                <div class="vd-slide-line1">Nhóm Zalo</div>
                <div class="vd-slide-line2">Voucher nội bộ</div>
            </div>
            <a href="https://zalo.me/g/hwcfvo585" 
               target="_blank" 
               class="vd-floating-btn vd-zalo-btn"
               title="Chat Zalo với Vidieu.vn">
                <svg viewBox="0 0 161.5 161.5" aria-hidden="true">
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
            </a>
        </div>
        
        <!-- Telegram button -->
        <div class="vd-floating-item vd-telegram-item">
            <div class="vd-slide-content">
                <div class="vd-slide-line1">Nhóm Telegram</div>
                <div class="vd-slide-line2">Voucher nội bộ</div>
            </div>
            <a href="https://t.me/+ZanU07t-Vgc3OWJl" 
               target="_blank" 
               class="vd-floating-btn vd-telegram-btn"
               title="Tham gia nhóm Telegram Vidieu.vn">
                <svg viewBox="0 0 32 32" aria-hidden="true" fill="none">
                    <circle cx="16" cy="16" r="14" fill="url(#telegramGrad)"/>
                    <path d="M22.9866 10.2088C23.1112 9.40332 22.3454 8.76755 21.6292 9.082L7.36482 15.3448C6.85123 15.5703 6.8888 16.3483 7.42147 16.5179L10.3631 17.4547C10.9246 17.6335 11.5325 17.541 12.0228 17.2023L18.655 12.6203C18.855 12.4821 19.073 12.7665 18.9021 12.9426L14.1281 17.8646C13.665 18.3421 13.7569 19.1512 14.314 19.5005L19.659 22.8523C20.2585 23.2282 21.0297 22.8506 21.1418 22.1261L22.9866 10.2088Z" fill="white"/>
                    <defs>
                        <linearGradient id="telegramGrad" x1="16" y1="2" x2="16" y2="30" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#37BBFE"/>
                            <stop offset="1" stop-color="#007DBB"/>
                        </linearGradient>
                    </defs>
                </svg>
            </a>
        </div>
        
        <!-- YouTube button -->
        <div class="vd-floating-item vd-youtube-item">
            <div class="vd-slide-content">
                <div class="vd-slide-line1">Kênh YouTube</div>
                <div class="vd-slide-line2">Hướng dẫn sử dụng</div>
            </div>
            <a href="https://www.youtube.com/@phamduydieu" 
               target="_blank" 
               class="vd-floating-btn vd-youtube-btn"
               title="Xem kênh YouTube Vidieu.vn">
                <svg viewBox="0 0 48 48" aria-hidden="true" fill="none">
                    <circle cx="24" cy="24" r="20" fill="#FF0000"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M35.3005 16.3781C35.6996 16.7772 35.9872 17.2739 36.1346 17.8187C36.9835 21.2357 36.7873 26.6324 36.1511 30.1813C36.0037 30.7261 35.7161 31.2228 35.317 31.6219C34.9179 32.021 34.4212 32.3086 33.8764 32.456C31.8819 33 23.8544 33 23.8544 33C23.8544 33 15.8269 33 13.8324 32.456C13.2876 32.3086 12.7909 32.021 12.3918 31.6219C11.9927 31.2228 11.7051 30.7261 11.5577 30.1813C10.7038 26.7791 10.9379 21.3791 11.5412 17.8352C11.6886 17.2903 11.9762 16.7936 12.3753 16.3945C12.7744 15.9954 13.2711 15.7079 13.8159 15.5604C15.8104 15.0165 23.8379 15 23.8379 15C23.8379 15 31.8654 15 33.8599 15.544C34.4047 15.6914 34.9014 15.979 35.3005 16.3781ZM27.9423 24L21.283 27.8571V20.1428L27.9423 24Z" fill="white"/>
                </svg>
            </a>
        </div>
    </div>
    
    <style>
        /* Floating contact widget container - positioned at left bottom */
        .vd-floating-contact-widget {
            position: fixed;
            bottom: 10px;
            left: 25px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
        
        /* Floating item wrapper */
        .vd-floating-item {
            display: flex;
            align-items: center;
            position: relative;
        }
        
        /* Floating buttons - match NASA button size */
        .vd-floating-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none !important;
            transition: all 0.3s ease;
            cursor: pointer;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            width: 50px;
            height: 50px;
            padding: 0;
            position: relative;
            overflow: visible; /* Changed to visible for pulse effect */
        }
        
        /* Individual button backgrounds with lighter tones */
        .vd-zalo-btn {
            background: rgba(0, 104, 255, 0.08); /* Very light Zalo blue */
        }
        
        .vd-zalo-btn:hover {
            background: rgba(0, 104, 255, 0.15);
        }
        
        .vd-messenger-btn {
            background: linear-gradient(135deg, rgba(0, 153, 255, 0.08), rgba(160, 51, 255, 0.08)); /* Light gradient for Messenger */
        }
        
        .vd-messenger-btn:hover {
            background: linear-gradient(135deg, rgba(0, 153, 255, 0.15), rgba(160, 51, 255, 0.15));
        }
        
        .vd-youtube-btn {
            background: rgba(255, 0, 0, 0.1); /* Light red for YouTube */
        }
        
        .vd-youtube-btn:hover {
            background: rgba(255, 0, 0, 0.2);
        }
        
        .vd-phone-btn {
            background: rgba(231, 76, 60, 0.1); /* Light red for Phone */
        }
        
        .vd-phone-btn:hover {
            background: rgba(231, 76, 60, 0.2);
        }
        
        .vd-telegram-btn {
            background: rgba(0, 136, 204, 0.1); /* Light Telegram blue */
        }
        
        .vd-telegram-btn:hover {
            background: rgba(0, 136, 204, 0.2);
        }
        
        .vd-floating-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        /* SVG icon styling - YouTube size as standard */
        .vd-floating-btn svg {
            width: 35px;
            height: 35px;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }
        
        .vd-floating-btn:hover svg {
            transform: scale(1.1);
        }
        
        /* Zalo specific styling */
        .vd-zalo-btn .bg {
            fill: #0068FF;
        }
        
        .vd-zalo-btn .shadow {
            fill: #0055d4;
            opacity: 0.3;
        }
        
        .vd-zalo-btn .inner {
            fill: #fff;
        }
        
        .vd-zalo-btn .fg {
            fill: #0068FF;
        }
        
        /* Slide content styling */
        .vd-slide-content {
            position: absolute;
            left: 60px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 12px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transform: translateX(-20px);
            transition: all 0.3s ease;
            pointer-events: none;
            min-width: 160px;
        }
        
        .vd-slide-content:after {
            content: '';
            position: absolute;
            left: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-right: 8px solid rgba(255, 255, 255, 0.95);
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
        }
        
        .vd-slide-line1 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .vd-slide-line2 {
            font-size: 12px;
            color: #666;
        }
        
        /* Hover effect for desktop */
        @media (min-width: 768px) {
            .vd-floating-item:hover .vd-slide-content {
                opacity: 1;
                visibility: visible;
                transform: translateX(0);
                pointer-events: auto;
            }
            
            .vd-floating-item:hover .vd-floating-btn {
                transform: scale(1.1);
                box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            }
        }
        
        /* Make slide content clickable */
        .vd-slide-content {
            cursor: pointer;
        }
        
        /* Zalo specific colors on hover */
        .vd-zalo-item:hover .vd-slide-line1 {
            color: #0068FF;
        }
        
        /* Messenger specific colors on hover */
        .vd-messenger-item:hover .vd-slide-line1 {
            color: #0084FF;
        }
        
        /* YouTube specific colors on hover */
        .vd-youtube-item:hover .vd-slide-line1 {
            color: #FF0000;
        }
        
        /* Phone specific colors on hover */
        .vd-phone-item:hover .vd-slide-line1 {
            color: #e74c3c;
        }
        
        /* Telegram specific colors on hover */
        .vd-telegram-item:hover .vd-slide-line1 {
            color: #0088cc;
        }
        
        /* Animation on page load */
        @keyframes slideInLeft {
            from {
                transform: translateX(-100px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .vd-floating-item {
            animation: slideInLeft 0.5s ease-out backwards;
        }
        
        .vd-floating-item:nth-child(1) {
            animation-delay: 0s;
        }
        
        .vd-floating-item:nth-child(2) {
            animation-delay: 0.1s;
        }
        
        .vd-floating-item:nth-child(3) {
            animation-delay: 0.2s;
        }
        
        .vd-floating-item:nth-child(4) {
            animation-delay: 0.3s;
        }
        
        .vd-floating-item:nth-child(5) {
            animation-delay: 0.4s;
        }
        
        /* Wiggle animation - lắc lư mạnh quanh tâm */
        @keyframes wiggle {
            0%, 100% {
                transform: rotate(0deg);
            }
            15% {
                transform: rotate(-20deg);
            }
            30% {
                transform: rotate(18deg);
            }
            45% {
                transform: rotate(-15deg);
            }
            60% {
                transform: rotate(12deg);
            }
            75% {
                transform: rotate(-8deg);
            }
            90% {
                transform: rotate(5deg);
            }
        }
        
        /* Apply wiggle animation when class is added */
        .vd-floating-btn.shake-animation {
            animation: wiggle 1s ease-in-out;
            transform-origin: center center;
        }
        
        /* Pulse effect */
        @keyframes pulse-ring {
            0% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0;
            }
            50% {
                opacity: 0.3;
            }
            100% {
                transform: translate(-50%, -50%) scale(2);
                opacity: 0;
            }
        }
        
        .vd-floating-item {
            position: relative;
        }
        
        /* Pulse ring - positioned relative to button */
        .vd-floating-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            background: currentColor;
            opacity: 0;
            animation: pulse-ring 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            pointer-events: none;
            z-index: -1;
        }
        
        /* Different pulse colors and delays for each button */
        .vd-phone-item {
            color: rgba(255, 59, 48, 0.35);
        }
        
        .vd-phone-btn::before {
            animation-delay: 0s;
        }
        
        .vd-messenger-item {
            color: rgba(0, 132, 255, 0.35);
        }
        
        .vd-messenger-btn::before {
            animation-delay: 0.6s;
        }
        
        .vd-zalo-item {
            color: rgba(0, 104, 255, 0.35);
        }
        
        .vd-zalo-btn::before {
            animation-delay: 1.2s;
        }
        
        .vd-telegram-item {
            color: rgba(34, 158, 217, 0.35);
        }
        
        .vd-telegram-btn::before {
            animation-delay: 1.8s;
        }
        
        .vd-youtube-item {
            color: rgba(255, 0, 51, 0.35);
        }
        
        .vd-youtube-btn::before {
            animation-delay: 2.4s;
        }
        
        /* Ripple effect container - create wrapper */
        .vd-floating-btn {
            position: relative;
        }
        
        /* Ripple wrapper to contain effect */
        .vd-ripple-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 50%;
            overflow: hidden;
            pointer-events: none;
        }
        
        /* Ripple animation */
        @keyframes ripple {
            0% {
                transform: translate(-50%, -50%) scale(0);
                opacity: 0.5;
            }
            100% {
                transform: translate(-50%, -50%) scale(4);
                opacity: 0;
            }
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            width: 100px;
            height: 100px;
            pointer-events: none;
            animation: ripple 0.6s ease-out;
        }
        
        /* Smooth transition for hover effects */
        .vd-floating-btn {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        /* Hide entire floating widget on mobile - use bottom bar instead */
        @media (max-width: 767px) {
            .vd-floating-contact-widget {
                display: none !important;
                visibility: hidden !important;
            }
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .vd-floating-btn {
                border: 2px solid #000;
            }
        }
        
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .vd-floating-btn,
            .vd-floating-btn.shake-animation {
                animation: none;
                transition: none;
            }
            
            .vd-floating-btn:hover {
                transform: none;
            }
            
            .vd-floating-btn:hover svg {
                transform: none;
            }
            
        }
        
        /* Print styles - hide widget when printing */
        @media print {
            .vd-floating-contact-widget {
                display: none !important;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Add ripple wrapper to each button
        $('.vd-floating-btn').each(function() {
            $(this).append('<div class="vd-ripple-wrapper"></div>');
        });
        
        // Sequential shake animation
        function startShakeSequence() {
            var $buttons = $('.vd-floating-btn');
            
            $buttons.each(function(index) {
                var $btn = $(this);
                
                // Initial shake after page load
                setTimeout(function() {
                    $btn.addClass('shake-animation');
                    setTimeout(function() {
                        $btn.removeClass('shake-animation');
                    }, 1000);
                }, 2000 + (index * 1200));
            });
            
            // Repeat shake sequence every 12 seconds
            setInterval(function() {
                $buttons.each(function(index) {
                    var $btn = $(this);
                    setTimeout(function() {
                        $btn.addClass('shake-animation');
                        setTimeout(function() {
                            $btn.removeClass('shake-animation');
                        }, 1000);
                    }, index * 1200);
                });
            }, 15000);
        }
        
        // Start shake sequence
        startShakeSequence();
        
        // Ripple effect function
        function createRipple(e) {
            var $this = $(this);
            var $wrapper = $this.find('.vd-ripple-wrapper');
            var $ripple = $('<span class="ripple"></span>');
            
            // Get click position relative to button
            var offset = $this.offset();
            var x = e.pageX - offset.left;
            var y = e.pageY - offset.top;
            
            // Set ripple position
            $ripple.css({
                left: x + 'px',
                top: y + 'px'
            });
            
            // Add ripple to wrapper
            $wrapper.append($ripple);
            
            // Remove ripple after animation
            setTimeout(function() {
                $ripple.remove();
            }, 600);
        }
        
        // Track clicks on floating buttons with ripple
        $('.vd-floating-btn').on('click', function(e) {
            // Create ripple effect
            createRipple.call(this, e);
            
            var platform = '';
            if ($(this).hasClass('vd-phone-btn')) platform = 'Phone';
            else if ($(this).hasClass('vd-messenger-btn')) platform = 'Messenger';
            else if ($(this).hasClass('vd-zalo-btn')) platform = 'Zalo';
            else if ($(this).hasClass('vd-telegram-btn')) platform = 'Telegram';
            else if ($(this).hasClass('vd-youtube-btn')) platform = 'YouTube';
            
            
            // Optional: Send tracking event to Google Analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'contact_click', {
                    'contact_method': platform,
                    'contact_location': 'floating_widget'
                });
            }
        });
        
        // Make slide content clickable
        $('.vd-slide-content').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this).siblings('.vd-floating-btn');
            var url = $btn.attr('href');
            if (url) {
                window.open(url, '_blank');
            }
        });
        
        // Add hover effect on mobile (touch devices)
        if ('ontouchstart' in window) {
            $('.vd-floating-btn').on('touchstart', function() {
                $(this).addClass('hover');
            }).on('touchend', function() {
                $(this).removeClass('hover');
            });
        }
    });
    </script>
    <?php
}

/**
 * Add option to admin to enable/disable floating widget
 */
add_action('admin_init', 'vidieu_floating_widget_settings');

function vidieu_floating_widget_settings() {
    add_settings_section(
        'vidieu_floating_widget_section',
        'Floating Contact Widget',
        null,
        'general'
    );
    
    register_setting('general', 'vidieu_floating_widget_enabled');
    
    add_settings_field(
        'vidieu_floating_widget_enabled',
        'Enable Floating Contact Widget',
        'vidieu_floating_widget_field_callback',
        'general',
        'vidieu_floating_widget_section'
    );
}

function vidieu_floating_widget_field_callback() {
    $enabled = get_option('vidieu_floating_widget_enabled', '1');
    ?>
    <label>
        <input type="checkbox" name="vidieu_floating_widget_enabled" value="1" <?php checked($enabled, '1'); ?>>
        Show floating Zalo and Facebook contact buttons
    </label>
    <?php
}

// Modify the display function to check if enabled
add_filter('wp_footer', 'vidieu_check_floating_widget_enabled', 9);

function vidieu_check_floating_widget_enabled() {
    $enabled = get_option('vidieu_floating_widget_enabled', '1');
    if ($enabled !== '1') {
        remove_action('wp_footer', 'vidieu_floating_contact_widget');
    }
}
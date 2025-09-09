<?php
/**
 * Plugin Name: VCB-MH CSS Layout Fix
 * Description: Fix CSS conflicts và layout issues cho VCB-MH display
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add CSS fixes for VCB-MH layout
 */
add_action('wp_head', function() {
    // Only on order-received page
    if (!is_order_received_page()) {
        return;
    }
    ?>
    <style>
    /* Reset conflicting styles from theme */
    #vcb-gateway,
    #vcb-gateway * {
        box-sizing: border-box !important;
    }
    
    /* Main container */
    #vcb-gateway {
        display: block !important;
        visibility: visible !important;
        position: relative !important;
        z-index: 1 !important;
        margin: 20px 0 !important;
        padding: 20px !important;
        background: #fff !important;
        border: 1px solid #ddd !important;
        border-radius: 8px !important;
        clear: both !important;
    }
    
    /* Payment info container */
    #payment-info {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 20px !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Left column */
    #left-col {
        flex: 1 1 300px !important;
        min-width: 280px !important;
        text-align: center !important;
        padding: 20px !important;
        border: 1px solid #eee !important;
        border-radius: 8px !important;
        background: #f9f9f9 !important;
    }
    
    /* Right column */
    #right-col {
        flex: 1 1 400px !important;
        min-width: 300px !important;
        padding: 20px !important;
    }
    
    /* QR Code image */
    .qrVietqr,
    #vcb-gateway img[src*="api.vietqr.io"] {
        display: block !important;
        max-width: 280px !important;
        height: auto !important;
        margin: 20px auto !important;
        border: 2px solid #ddd !important;
        padding: 10px !important;
        background: #fff !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
    }
    
    /* Download button */
    .download-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        margin: 10px auto !important;
        padding: 10px 20px !important;
        background: #0066cc !important;
        color: #fff !important;
        border: none !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        font-size: 14px !important;
        text-decoration: none !important;
        transition: background 0.3s !important;
    }
    
    .download-btn:hover {
        background: #0052a3 !important;
    }
    
    .download-btn img {
        width: 20px !important;
        height: 20px !important;
        filter: brightness(0) invert(1) !important;
    }
    
    /* Copy buttons */
    .copy-btn {
        display: inline-block !important;
        padding: 5px 10px !important;
        background: #f0f0f0 !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        margin-left: 10px !important;
        transition: all 0.3s !important;
    }
    
    .copy-btn:hover {
        background: #e0e0e0 !important;
    }
    
    .copy-btn img {
        width: 20px !important;
        height: 20px !important;
        vertical-align: middle !important;
    }
    
    /* Table styling */
    #vcb-gateway table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 20px 0 !important;
    }
    
    #vcb-gateway table th {
        padding: 10px !important;
        text-align: left !important;
        border-bottom: 1px solid #eee !important;
        font-weight: normal !important;
        color: #666 !important;
    }
    
    #vcb-gateway table th:last-child {
        text-align: right !important;
        font-weight: bold !important;
        color: #333 !important;
    }
    
    /* Steps */
    #vcb-gateway span b {
        color: #0066cc !important;
        font-weight: 600 !important;
    }
    
    .flex-center-box {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        margin: 10px 0 !important;
    }
    
    .flex-center-box img {
        width: 30px !important;
        height: 30px !important;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        #payment-info {
            flex-direction: column !important;
        }
        
        #left-col,
        #right-col {
            min-width: 100% !important;
        }
        
        .anMoblie {
            display: block !important;
        }
        
        .anPc {
            display: none !important;
        }
        
        .qrVietqr {
            max-width: 100% !important;
            width: 280px !important;
        }
    }
    
    @media (min-width: 769px) {
        .anMoblie {
            display: none !important;
        }
        
        .anPc {
            display: block !important;
        }
    }
    
    /* Success animation */
    .vcb-gateway-result {
        text-align: center !important;
        padding: 40px !important;
    }
    
    .success-animation {
        margin: 0 auto 20px !important;
    }
    
    /* Fix z-index issues */
    .header-wrapper,
    .nasa-header-sticky,
    #nasa-init-breadcrumb {
        z-index: 99 !important;
    }
    
    /* Ensure VCB-MH content is not hidden */
    #vcb-gateway,
    #vcb-gateway * {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Override theme's max-width constraints */
    .nasa-checkout-wrap #vcb-gateway,
    .woocommerce-checkout #vcb-gateway {
        max-width: none !important;
        width: 100% !important;
    }
    
    /* Fix potential float issues */
    .woocommerce-order-received #vcb-gateway:before,
    .woocommerce-order-received #vcb-gateway:after {
        content: "" !important;
        display: table !important;
        clear: both !important;
    }
    
    /* Force display of sweetalert */
    .swal2-container {
        z-index: 999999 !important;
    }
    
    /* Hide any duplicate or conflicting elements */
    .woocommerce-thankyou-order-received + #vcb-gateway {
        margin-top: 30px !important;
    }
    
    /* Ensure scripts load */
    script[src*="sweetalert"],
    link[href*="sweetalert"] {
        display: none !important; /* Scripts don't need display */
    }
    
    /* Force VCB-MH images to load */
    img[src*="wp-content/plugins/vcb-mh"] {
        display: inline-block !important;
    }
    </style>
    <?php
}, 100); // High priority to override other styles

/**
 * Ensure VCB-MH scripts load properly
 */
add_action('wp_footer', function() {
    if (!is_order_received_page()) {
        return;
    }
    ?>
    <script>
    // Force reload images if they fail
    document.addEventListener('DOMContentLoaded', function() {
        const vcbImages = document.querySelectorAll('#vcb-gateway img');
        vcbImages.forEach(img => {
            // If image fails to load, retry
            img.onerror = function() {
                console.log('VCB-MH image failed to load:', this.src);
                // Retry once
                setTimeout(() => {
                    const src = this.src;
                    this.src = '';
                    this.src = src;
                }, 1000);
            };
        });
        
        // Ensure QR code is visible
        const qrImages = document.querySelectorAll('.qrVietqr');
        qrImages.forEach(img => {
            if (img.src && img.src.includes('api.vietqr.io')) {
                console.log('VCB-MH QR found:', img.src);
                img.style.display = 'block';
                img.style.visibility = 'visible';
            }
        });
        
        // Fix layout if needed
        const paymentInfo = document.getElementById('payment-info');
        if (paymentInfo && !paymentInfo.querySelector('img.qrVietqr')) {
            console.error('VCB-MH QR code not found in payment-info!');
        }
    });
    </script>
    <?php
}, 999);
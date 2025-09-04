<?php
/**
 * VietQR Integration for WooCommerce BACS Payment
 * 
 * Displays QR code on order-received page for bank transfer orders
 * Reorganizes bank details layout with QR in column 2
 * 
 * @package Elessi-theme-child
 * @since 2025-08-30
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle AJAX request to confirm transfer
 */
add_action('wp_ajax_vidieu_confirm_transfer', 'vidieu_handle_confirm_transfer');
add_action('wp_ajax_nopriv_vidieu_confirm_transfer', 'vidieu_handle_confirm_transfer');

function vidieu_handle_confirm_transfer() {
    // Get order ID from request
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
        return;
    }
    
    // Get order object
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Order not found');
        return;
    }
    
    // Check if payment method is BACS
    if ($order->get_payment_method() !== 'bacs') {
        wp_send_json_error('Invalid payment method');
        return;
    }
    
    // Check current status - only update if pending payment
    $current_status = $order->get_status();
    if ($current_status !== 'pending') {
        wp_send_json_error('Order is not in pending status');
        return;
    }
    
    // Update order status to processing
    $order->update_status('processing', __('Khách hàng đã xác nhận chuyển khoản', 'woocommerce'));
    
    // Add order note
    $order->add_order_note(sprintf(
        __('Khách hàng đã xác nhận chuyển khoản lúc %s', 'woocommerce'),
        current_time('mysql')
    ));
    
    // Save order
    $order->save();
    
    // Send success response
    wp_send_json_success(array(
        'message' => 'Order status updated successfully',
        'new_status' => $order->get_status()
    ));
}

/**
 * Reorganize BACS bank details and add QR code
 */
add_action('wp_footer', 'vidieu_reorganize_bacs_with_qr');

function vidieu_reorganize_bacs_with_qr() {
    // Only run on order-received page
    if (!is_order_received_page()) {
        return;
    }
    
    // Get order ID from query
    $order_id = get_query_var('order-received');
    if (!$order_id) {
        return;
    }
    
    // Get order object
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    // Check if payment method is BACS
    if ($order->get_payment_method() !== 'bacs') {
        return;
    }
    
    // Get order total (integer, no decimals for VND)
    $amount = intval($order->get_total());
    
    // Create transfer content - no # character
    $addInfo = 'Vidieuvn ' . $order_id;
    $addInfo_encoded = urlencode($addInfo);
    
    // Bank details
    $bank_id = 'vietcombank';
    $account_number = '0821000013390';
    $account_name = 'PHAM%20DUY%20DIEU'; // Already URL-encoded
    
    // Build VietQR URL
    $qr_url = sprintf(
        'https://img.vietqr.io/image/%s-%s-qr_only.png?amount=%d&addInfo=%s&accountName=%s',
        $bank_id,
        $account_number,
        $amount,
        $addInfo_encoded,
        $account_name
    );
    
    ?>
    <style>
        /* Hide original QR if it was added by previous version */
        .vd-vietqr {
            display: none !important;
        }
        
        /* Hide original bank details section content */
        .woocommerce-bacs-bank-details h3,
        .woocommerce-bacs-bank-details > ul {
            display: none !important;
        }
        
        /* Main container for bank transfer info */
        .vietqr-bank-transfer-container {
            background: transparent;
            border: none;
            padding: 20px 0;
            margin: 20px 0;
        }
        
        .vietqr-bank-transfer-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        
        /* Table layout for bank details */
        .vietqr-bank-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .vietqr-bank-row {
            
        }
        
        .vietqr-bank-cell {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }
        
        .vietqr-bank-row:last-child .vietqr-bank-cell {
            border-bottom: none;
        }
        
        .vietqr-bank-label {
            color: #6c757d;
            font-weight: normal;
            min-width: 120px;
        }
        
        .vietqr-bank-value {
            font-weight: bold;
            color: #333;
        }
        
        /* QR column - now on the left */
        .vietqr-qr-cell {
            vertical-align: middle;
            text-align: center;
            padding: 15px 25px 15px 0;
            width: 280px;
            border-right: 1px solid #dee2e6;
        }
        
        .vietqr-qr-cell img {
            max-width: 220px;
            height: auto;
            margin: 0 auto 20px;
            display: block;
            border: 1px solid #ddd;
            padding: 10px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .vietqr-info {
            font-size: 14px;
            color: #333;
            margin-top: 10px;
        }
        
        .vietqr-info strong {
            color: #0066cc;
            font-size: 16px;
            display: block;
            margin-top: 5px;
        }
        
        .vietqr-copy-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        
        .vietqr-copy-btn:hover {
            background: #218838;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .vietqr-copy-btn.copied {
            background: #17a2b8;
        }
        
        /* Confirm button and home link */
        .vietqr-confirm-cell {
            padding-top: 20px !important;
        }
        
        .vietqr-confirm-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #0066cc;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        
        .vietqr-confirm-btn:hover {
            background: #0052a3;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .vietqr-confirm-btn.confirmed {
            background: #28a745;
            cursor: default;
        }
        
        .vietqr-home-link {
            margin-top: 10px;
            text-align: center;
        }
        
        .vietqr-home-link a {
            color: #0066cc;
            text-decoration: underline;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .vietqr-home-link a:hover {
            color: #0052a3;
        }
        
        /* Align buttons */
        .vietqr-qr-cell,
        .vietqr-confirm-cell {
            vertical-align: middle;
        }
        
        /* Row with confirm button */
        .vietqr-bank-row:last-child .vietqr-bank-cell {
            text-align: center;
            padding: 15px;
        }
        
        /* Align both buttons horizontally */
        .vietqr-bank-row:nth-child(4) {
            vertical-align: top;
        }
        
        .vietqr-bank-row:nth-child(4) .vietqr-bank-cell {
            padding-top: 12px;
        }
        
        /* Email notice styling */
        .vietqr-email-notice {
            margin-top: 30px;
            text-align: center;
        }
        
        .vietqr-email-notice-text {
            font-size: 15px;
            font-weight: 600;
            color: #5a67d8;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .vietqr-contact-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .vietqr-contact-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .vietqr-contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .vietqr-contact-btn.zalo {
            background: #0068ff;
            color: #fff !important;
        }
        
        .vietqr-contact-btn.zalo:hover {
            background: #0056d6;
        }
        
        .vietqr-contact-btn.facebook {
            background: #1877f2;
            color: #fff !important;
        }
        
        .vietqr-contact-btn.facebook:hover {
            background: #1564c9;
        }
        
        .vietqr-contact-btn svg {
            width: 20px;
            height: 20px;
        }
        
        @media (max-width: 767px) {
            .vietqr-email-notice {
                padding: 20px;
            }
            
            .vietqr-contact-buttons {
                flex-direction: column;
            }
            
            .vietqr-contact-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Mobile layout */
        @media (max-width: 767px) {
            .vietqr-bank-table tbody {
                display: block;
            }
            
            .vietqr-bank-table tr {
                display: block;
                margin-bottom: 5px;
            }
            
            .vietqr-bank-cell {
                display: block;
                padding: 8px 0;
                border-bottom: none;
            }
            
            .vietqr-bank-label {
                display: inline-block;
                min-width: 110px;
            }
            
            .vietqr-qr-cell {
                display: block !important;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #dee2e6;
                margin-bottom: 15px;
                padding-bottom: 20px;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Find the bank details section
        var $bankSection = $('.woocommerce-bacs-bank-details');
        if ($bankSection.length === 0) return;
        
        // Create new bank transfer container with QR on left, info on right
        var bankTransferHtml = `
            <div class="vietqr-bank-transfer-container">
                <h3 class="vietqr-bank-transfer-title">Thông tin chuyển khoản ngân hàng:</h3>
                <table class="vietqr-bank-table">
                    <tr class="vietqr-bank-row">
                        <td class="vietqr-qr-cell" rowspan="4">
                            <img src="<?php echo esc_url($qr_url); ?>" 
                                 alt="QR Code chuyển khoản" 
                                 title="Quét mã để chuyển khoản">
                            <button type="button" 
                                    class="vietqr-copy-btn" 
                                    onclick="vidieu_copyToClipboard('<?php echo esc_attr($addInfo); ?>', this)">
                                Sao chép nội dung CK
                            </button>
                            <div class="vietqr-info">
                                Nội dung: <strong><?php echo esc_html($addInfo); ?></strong>
                            </div>
                        </td>
                        <td class="vietqr-bank-cell">
                            <span class="vietqr-bank-label">Ngân hàng:</span>
                            <span class="vietqr-bank-value">Ngân hàng Vietcombank</span>
                        </td>
                    </tr>
                    <tr class="vietqr-bank-row">
                        <td class="vietqr-bank-cell">
                            <span class="vietqr-bank-label">Số tài khoản:</span>
                            <span class="vietqr-bank-value">0821000013390</span>
                        </td>
                    </tr>
                    <tr class="vietqr-bank-row">
                        <td class="vietqr-bank-cell">
                            <span class="vietqr-bank-label">Chủ tài khoản:</span>
                            <span class="vietqr-bank-value">Phạm Duy Diệu</span>
                        </td>
                    </tr>
                    <tr class="vietqr-bank-row">
                        <td class="vietqr-bank-cell vietqr-confirm-cell">
                            <button type="button" class="vietqr-confirm-btn" onclick="vidieu_confirmTransfer(this)">
                                Tôi đã chuyển khoản
                            </button>
                            <div class="vietqr-home-link">
                                <a href="<?php echo home_url(); ?>">Trở về Trang chủ</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="vietqr-email-notice">
                <p class="vietqr-email-notice-text">
                    📧 Email từ Vidieu.vn sẽ gửi đến Inbox/Spam/Quảng cáo. Nếu quá 15 phút chưa nhận được, vui lòng liên hệ:
                </p>
                <div class="vietqr-contact-buttons">
                    <a href="https://zalo.me/g/hwcfvo585" target="_blank" class="vietqr-contact-btn zalo">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                        </svg>
                        Nhắn tin Zalo
                    </a>
                    <a href="https://www.facebook.com/vidieuvn.muatoolAmazon" target="_blank" class="vietqr-contact-btn facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10 10 0 0 0 22 12.06C22 6.53 17.5 2.04 12 2.04Z"/>
                        </svg>
                        Chat Facebook
                    </a>
                </div>
            </div>
        `;
        
        // Append new container to bank section
        $bankSection.append(bankTransferHtml);
    });
    
    function vidieu_copyToClipboard(text, btn) {
        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                vidieu_showCopied(btn);
            }).catch(function() {
                vidieu_fallbackCopy(text, btn);
            });
        } else {
            vidieu_fallbackCopy(text, btn);
        }
    }
    
    function vidieu_fallbackCopy(text, btn) {
        // Fallback for older browsers
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            vidieu_showCopied(btn);
        } catch (err) {
            console.error('Copy failed:', err);
            alert('Không thể sao chép. Vui lòng chọn và sao chép thủ công: ' + text);
        }
        
        document.body.removeChild(textArea);
    }
    
    function vidieu_showCopied(btn) {
        var originalText = btn.textContent;
        btn.textContent = 'Đã sao chép!';
        btn.classList.add('copied');
        
        setTimeout(function() {
            btn.textContent = originalText;
            btn.classList.remove('copied');
        }, 2000);
    }
    
    function vidieu_confirmTransfer(btn) {
        // Get order ID
        var orderId = <?php echo $order_id; ?>;
        
        // Update button state
        btn.textContent = 'Đã xác nhận chuyển khoản';
        btn.classList.add('confirmed');
        btn.disabled = true;
        
        // Optional: Send AJAX to mark order as payment confirmed
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'vidieu_confirm_transfer',
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    console.log('Transfer confirmed for order:', orderId);
                    console.log('New order status:', response.data.new_status);
                } else {
                    console.error('Error updating order:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
        
        // Show success message
        alert('Cảm ơn bạn đã xác nhận chuyển khoản. Chúng tôi sẽ kiểm tra và xử lý đơn hàng của bạn sớm nhất.');
    }
    </script>
    <?php
}
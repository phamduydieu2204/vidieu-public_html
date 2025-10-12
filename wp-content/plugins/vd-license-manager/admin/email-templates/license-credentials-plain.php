<?php
/**
 * Email Template: License Credentials (Plain Text)
 *
 * Plain text version for email clients that don't support HTML
 * Contains account login credentials and usage instructions
 *
 * Available variables:
 * @var string $customer_name Customer's full name
 * @var string $customer_email Customer's email
 * @var string $product_name Product name
 * @var string $license_key License key from LMfWC
 * @var string $account_login Account username/email
 * @var string $account_password Decrypted account password
 * @var int $max_devices Maximum devices allowed
 * @var int $validity_days License validity in days
 * @var string $expiry_date License expiration date (formatted)
 * @var int $max_requests_per_day API rate limit
 * @var string $order_id WooCommerce order ID
 * @var string $site_name WordPress site name
 * @var string $site_url WordPress site URL
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;
?>
=== YOUR <?php echo strtoupper(esc_html($product_name)); ?> ACCOUNT IS READY! ===

Hi <?php echo esc_html($customer_name); ?>,

Thank you for your purchase! Your <?php echo esc_html($product_name); ?> account has been activated and is ready to use.

=== YOUR ACCOUNT CREDENTIALS ===

License Key: <?php echo esc_html($license_key); ?>

Account Login: <?php echo esc_html($account_login); ?>

Account Password: <?php echo esc_html($account_password); ?>


=== ACCOUNT DETAILS ===

* Maximum Devices: <?php echo esc_html($max_devices); ?> device<?php echo $max_devices > 1 ? 's' : ''; ?>

<?php if ($validity_days > 0): ?>
* Valid Until: <?php echo esc_html($expiry_date); ?> (<?php echo esc_html($validity_days); ?> days)
<?php else: ?>
* Validity: Lifetime
<?php endif; ?>
* Daily API Limit: <?php echo number_format($max_requests_per_day); ?> requests per day
* Order ID: #<?php echo esc_html($order_id); ?>


=== IMPORTANT WARNING ===

Please keep these credentials safe and do not share them with anyone. You are responsible for all activity under this account.

=== GETTING STARTED ===

1. Use the credentials above to log in to your <?php echo esc_html($product_name); ?> account
2. You can use this account on up to <?php echo esc_html($max_devices); ?> device<?php echo $max_devices > 1 ? 's' : ''; ?> simultaneously
3. If you encounter any issues, please contact our support team

If you have any questions or need assistance, please contact our support team at:
<?php echo esc_url($site_url); ?>/contact

Best regards,
The <?php echo esc_html($site_name); ?> Team

---
This email was sent to <?php echo esc_html($customer_email); ?>

<?php echo esc_html($site_name); ?>: <?php echo esc_url($site_url); ?>
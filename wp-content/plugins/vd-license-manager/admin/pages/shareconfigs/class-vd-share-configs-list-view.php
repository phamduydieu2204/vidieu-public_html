<?php
/**
 * Share Configs List View
 *
 * Renders the share configs list page HTML
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Share Configs List View Class
 *
 * Handles rendering of share configs list page
 *
 * @package VD_License_Manager
 * @since 1.0.1
 */
class VD_Share_Configs_List_View {

	/**
	 * Render the page
	 *
	 * @since 1.0.1
	 */
	public function render() {
		$list_table = new VD_Share_Configs_List_Table();
		$list_table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php _e('Cấu Hình Chia Sẻ', 'vd-license-manager'); ?>
			</h1>

			<a href="<?php echo esc_url(admin_url('admin.php?page=vd-share-configs&action=add')); ?>" class="page-title-action">
				<?php _e('Thêm Mới', 'vd-license-manager'); ?>
			</a>

			<hr class="wp-header-end">

			<?php $this->display_messages(); ?>

			<?php $this->display_stats(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
				<?php
				$list_table->search_box(__('Tìm kiếm sản phẩm...', 'vd-license-manager'), 'config');
				?>
			</form>

			<form method="post">
				<?php
				wp_nonce_field('bulk-configs', '_wpnonce');
				$list_table->display();
				?>
			</form>

			<?php $this->add_custom_styles(); ?>

			<?php $this->display_help_section(); ?>
		</div>
		<?php
	}

	/**
	 * Display admin messages
	 *
	 * @since 1.0.1
	 */
	private function display_messages() {
		if (isset($_GET['message'])) {
			$message = urldecode(sanitize_text_field($_GET['message']));
			$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';

			// Ensure valid message type
			$allowed_types = array('success', 'error', 'warning', 'info');
			if (!in_array($type, $allowed_types)) {
				$type = 'info';
			}

			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr($type),
				esc_html($message)
			);
		}
	}

	/**
	 * Display share configs statistics
	 *
	 * @since 1.0.1
	 */
	private function display_stats() {
		global $wpdb;

		// Get config counts
		$configs_table = 'bz_vd_product_share_configs';

		$total_configs = $wpdb->get_var("SELECT COUNT(*) FROM {$configs_table}");
		$auto_rotate_enabled = $wpdb->get_var("SELECT COUNT(*) FROM {$configs_table} WHERE auto_rotate = 1");
		$products_configured = $wpdb->get_var("SELECT COUNT(DISTINCT product_id) FROM {$configs_table}");

		// Get products without configs
		$products_without_configs = $wpdb->get_var("
			SELECT COUNT(*) FROM {$wpdb->posts} p
			LEFT JOIN {$configs_table} c ON p.ID = c.product_id
			WHERE p.post_type = 'product'
			  AND p.post_status = 'publish'
			  AND c.id IS NULL
		");

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in share config stats - ' . $wpdb->last_error);
			return;
		}

		?>
		<div class="config-stats-container" style="margin: 15px 0;">
			<div class="config-stats-grid">
				<div class="config-stat-box">
					<div class="stat-number"><?php echo intval($total_configs); ?></div>
					<div class="stat-label"><?php _e('Tổng Cấu Hình', 'vd-license-manager'); ?></div>
				</div>
				<div class="config-stat-box auto-rotate">
					<div class="stat-number"><?php echo intval($auto_rotate_enabled); ?></div>
					<div class="stat-label"><?php _e('Tự Động Xoay', 'vd-license-manager'); ?></div>
				</div>
				<div class="config-stat-box configured">
					<div class="stat-number"><?php echo intval($products_configured); ?></div>
					<div class="stat-label"><?php _e('Sản Phẩm Đã Cấu Hình', 'vd-license-manager'); ?></div>
				</div>
				<div class="config-stat-box unconfigured">
					<div class="stat-number"><?php echo intval($products_without_configs); ?></div>
					<div class="stat-label"><?php _e('Sản Phẩm Chưa Cấu Hình', 'vd-license-manager'); ?></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display help section
	 *
	 * @since 1.0.1
	 */
	private function display_help_section() {
		?>
		<div class="config-help-section" style="margin-top: 30px;">
			<h3><?php _e('💡 Cấu Hình Chia Sẻ là gì?', 'vd-license-manager'); ?></h3>
			<div class="help-grid">
				<div class="help-card">
					<h4><?php _e('🎯 Mục đích', 'vd-license-manager'); ?></h4>
					<p><?php _e('Định nghĩa cách các tài khoản Provider được chia sẻ cho từng sản phẩm WooCommerce.', 'vd-license-manager'); ?></p>
				</div>
				<div class="help-card">
					<h4><?php _e('👥 Max Profiles', 'vd-license-manager'); ?></h4>
					<p><?php _e('Số lượng khách hàng có thể chia sẻ một tài khoản (VD: Netflix cho phép 4-5 profile).', 'vd-license-manager'); ?></p>
				</div>
				<div class="help-card">
					<h4><?php _e('📱 Max Devices', 'vd-license-manager'); ?></h4>
					<p><?php _e('Số thiết bị mỗi khách hàng có thể sử dụng đồng thời cho một profile.', 'vd-license-manager'); ?></p>
				</div>
				<div class="help-card">
					<h4><?php _e('⏰ Thời Hạn', 'vd-license-manager'); ?></h4>
					<p><?php _e('Số ngày khách hàng có thể sử dụng tài khoản trước khi hết hạn.', 'vd-license-manager'); ?></p>
				</div>
				<div class="help-card">
					<h4><?php _e('🔄 Tự Động Xoay', 'vd-license-manager'); ?></h4>
					<p><?php _e('Tự động chuyển đổi tài khoản sau một khoảng thời gian để tránh lạm dụng.', 'vd-license-manager'); ?></p>
				</div>
				<div class="help-card">
					<h4><?php _e('📺 Luồng Đồng Thời', 'vd-license-manager'); ?></h4>
					<p><?php _e('Số luồng phát có thể xem cùng lúc (VD: Netflix cho phép 1-4 luồng tùy gói).', 'vd-license-manager'); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add custom styles for stats and help
	 *
	 * @since 1.0.1
	 */
	private function add_custom_styles() {
		?>
		<style>
		.config-stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 15px;
			margin-bottom: 20px;
		}

		.config-stat-box {
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 6px;
			padding: 20px;
			text-align: center;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}

		.config-stat-box .stat-number {
			font-size: 2.5em;
			font-weight: bold;
			color: #333;
			margin-bottom: 5px;
		}

		.config-stat-box .stat-label {
			font-size: 13px;
			color: #666;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.config-stat-box.auto-rotate .stat-number {
			color: #00a32a;
		}

		.config-stat-box.configured .stat-number {
			color: #007cba;
		}

		.config-stat-box.unconfigured .stat-number {
			color: #dc3232;
		}

		.help-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
			margin-top: 15px;
		}

		.help-card {
			background: #f9f9f9;
			border: 1px solid #e0e0e0;
			border-radius: 6px;
			padding: 20px;
		}

		.help-card h4 {
			margin: 0 0 10px 0;
			color: #333;
			font-size: 14px;
		}

		.help-card p {
			margin: 0;
			font-size: 13px;
			line-height: 1.5;
			color: #666;
		}

		.badge {
			display: inline-block;
			padding: 2px 8px;
			border-radius: 3px;
			font-size: 12px;
			font-weight: bold;
		}
		</style>
		<?php
	}
}
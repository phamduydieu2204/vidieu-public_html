<?php
/**
 * Product Pools List View
 *
 * Renders the pools list page HTML
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Pools List View Class
 *
 * Handles rendering of pools list page
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Pools_List_View {

	/**
	 * Render the page
	 *
	 * @since 1.0.0
	 */
	public function render() {
		// Load list table
		require_once VD_PLUGIN_PATH . 'admin/pages/pools/class-vd-pools-list-table.php';

		$list_table = new VD_Pools_List_Table();
		$list_table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php _e('Product Pools', 'vd-license-manager'); ?>
			</h1>

			<a href="<?php echo esc_url(admin_url('admin.php?page=vd-product-pools&action=add')); ?>" class="page-title-action">
				<?php _e('Thêm Pool', 'vd-license-manager'); ?>
			</a>

			<hr class="wp-header-end">

			<?php $this->display_messages(); ?>

			<?php $this->display_stats(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
				<?php
				$list_table->search_box(__('Tìm kiếm pools...', 'vd-license-manager'), 'pool');
				?>
			</form>

			<form method="post">
				<?php
				wp_nonce_field('bulk-pools', '_wpnonce');
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
	 * @since 1.0.0
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
	 * Display pool statistics
	 *
	 * @since 1.0.0
	 */
	private function display_stats() {
		global $wpdb;

		// Get pool counts
		$pools_table = 'bz_vd_product_pools';

		$total_pools = $wpdb->get_var("SELECT COUNT(*) FROM {$pools_table}");
		$active_pools = $wpdb->get_var("SELECT COUNT(*) FROM {$pools_table} WHERE is_active = 1");
		$inactive_pools = $total_pools - $active_pools;

		// Get unique products and accounts count
		$unique_products = $wpdb->get_var("SELECT COUNT(DISTINCT product_id) FROM {$pools_table}");
		$unique_accounts = $wpdb->get_var("SELECT COUNT(DISTINCT account_id) FROM {$pools_table}");

		if ($wpdb->last_error) {
			error_log('VD License Manager: Database error in pool stats - ' . $wpdb->last_error);
			return;
		}

		?>
		<div class="pool-stats-container" style="margin: 15px 0;">
			<div class="pool-stats-grid">
				<div class="pool-stat-box">
					<div class="stat-number"><?php echo intval($total_pools); ?></div>
					<div class="stat-label"><?php _e('Tổng Pools', 'vd-license-manager'); ?></div>
				</div>
				<div class="pool-stat-box active">
					<div class="stat-number"><?php echo intval($active_pools); ?></div>
					<div class="stat-label"><?php _e('Hoạt động', 'vd-license-manager'); ?></div>
				</div>
				<div class="pool-stat-box inactive">
					<div class="stat-number"><?php echo intval($inactive_pools); ?></div>
					<div class="stat-label"><?php _e('Ngừng', 'vd-license-manager'); ?></div>
				</div>
				<div class="pool-stat-box">
					<div class="stat-number"><?php echo intval($unique_products); ?></div>
					<div class="stat-label"><?php _e('Sản phẩm', 'vd-license-manager'); ?></div>
				</div>
				<div class="pool-stat-box">
					<div class="stat-number"><?php echo intval($unique_accounts); ?></div>
					<div class="stat-label"><?php _e('Tài khoản', 'vd-license-manager'); ?></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display help section
	 *
	 * @since 1.0.0
	 */
	private function display_help_section() {
		?>
		<div class="pool-help-section" style="margin-top: 30px;">
			<h3><?php _e('💡 Product Pools là gì?', 'vd-license-manager'); ?></h3>
			<div class="help-grid">
				<div class="help-card">
					<h4><?php _e('🎯 Mục đích', 'vd-license-manager'); ?></h4>
					<p><?php _e('Gán các tài khoản Provider vào sản phẩm WooCommerce để tự động cấp phát khi khách hàng mua.', 'vd-license-manager'); ?></p>
				</div>
				<div class="help-card">
					<h4><?php _e('⚡ Priority', 'vd-license-manager'); ?></h4>
					<p><?php _e('Số nhỏ = ưu tiên cao. Tài khoản có priority 0 sẽ được chọn trước priority 1.', 'vd-license-manager'); ?></p>
				</div>
				<div class="help-card">
					<h4><?php _e('🔄 Trạng thái', 'vd-license-manager'); ?></h4>
					<p><?php _e('Chỉ pool "Hoạt động" mới được sử dụng. Pool "Ngừng" sẽ bị bỏ qua khi cấp phát.', 'vd-license-manager'); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add custom CSS styles
	 *
	 * @since 1.0.0
	 */
	private function add_custom_styles() {
		?>
		<style>
			/* Status badges */
			.status-badge {
				display: inline-block;
				padding: 4px 10px;
				border-radius: 12px;
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
			}

			.status-active {
				background: #d1e7dd;
				color: #0f5132;
				border: 1px solid #a3cfbb;
			}

			.status-inactive {
				background: #f8d7da;
				color: #58151c;
				border: 1px solid #f1aeb5;
			}

			/* Priority badges */
			.priority-badge {
				display: inline-block;
				color: white;
				padding: 3px 8px;
				border-radius: 10px;
				font-size: 11px;
				font-weight: 700;
				min-width: 20px;
				text-align: center;
			}

			/* Column widths */
			.wp-list-table .column-id {
				width: 60px;
			}

			.wp-list-table .column-priority {
				width: 100px;
				text-align: center;
			}

			.wp-list-table .column-is_active {
				width: 110px;
			}

			.wp-list-table .column-created_at {
				width: 140px;
			}

			/* Stats container */
			.pool-stats-grid {
				display: flex;
				gap: 15px;
				margin-bottom: 20px;
				flex-wrap: wrap;
			}

			.pool-stat-box {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 15px 20px;
				text-align: center;
				min-width: 120px;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}

			.pool-stat-box.active {
				border-color: #00a32a;
				background: #f6fff6;
			}

			.pool-stat-box.inactive {
				border-color: #d63638;
				background: #fef7f7;
			}

			.stat-number {
				font-size: 24px;
				font-weight: 600;
				color: #1d2327;
				line-height: 1;
			}

			.stat-label {
				font-size: 13px;
				color: #646970;
				margin-top: 5px;
			}

			/* Help section */
			.pool-help-section {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
				margin-top: 30px;
			}

			.pool-help-section h3 {
				margin-top: 0;
				color: #1d2327;
				font-size: 16px;
			}

			.help-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 20px;
				margin-top: 15px;
			}

			.help-card {
				background: #f6f7f7;
				border: 1px solid #dcdcde;
				border-radius: 4px;
				padding: 15px;
			}

			.help-card h4 {
				margin: 0 0 8px 0;
				font-size: 14px;
				color: #1d2327;
			}

			.help-card p {
				margin: 0;
				font-size: 13px;
				color: #646970;
				line-height: 1.4;
			}

			/* Responsive */
			@media (max-width: 768px) {
				.pool-stats-grid {
					justify-content: center;
				}

				.pool-stat-box {
					flex: 1;
					min-width: 100px;
				}

				.help-grid {
					grid-template-columns: 1fr;
				}
			}
		</style>
		<?php
	}
}
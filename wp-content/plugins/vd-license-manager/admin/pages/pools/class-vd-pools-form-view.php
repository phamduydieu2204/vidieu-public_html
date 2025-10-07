<?php
/**
 * Product Pools Form View
 *
 * Renders the add/edit form for product pools
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
 * Pools Form View Class
 *
 * Handles rendering of add/edit form for product pools
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Pools_Form_View {

	/**
	 * Pool data (for edit mode)
	 *
	 * @since 1.0.0
	 * @var object|null
	 */
	private $pool = null;

	/**
	 * Form errors
	 *
	 * @since 1.0.0
	 * @var WP_Error|null
	 */
	private $errors = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param object|null   $pool   Pool data for edit mode
	 * @param WP_Error|null $errors Validation errors
	 */
	public function __construct($pool = null, $errors = null) {
		$this->pool = $pool;
		$this->errors = $errors;
	}

	/**
	 * Render the form
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$is_edit = !is_null($this->pool);
		$pool_id = $is_edit ? $this->pool->id : 0;

		// Get products and accounts for dropdowns
		$products = $this->get_products();
		$accounts = $this->get_accounts();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo $is_edit ? __('Sửa Pool', 'vd-license-manager') : __('Thêm Pool Mới', 'vd-license-manager'); ?>
			</h1>

			<a href="<?php echo admin_url('admin.php?page=vd-product-pools'); ?>" class="page-title-action">
				<?php _e('← Quay lại danh sách', 'vd-license-manager'); ?>
			</a>

			<hr class="wp-header-end">

			<?php $this->display_errors(); ?>

			<form method="post" action="" id="vd-pool-form">
				<?php wp_nonce_field('vd_save_pool', 'vd_pool_nonce'); ?>
				<input type="hidden" name="action" value="save_pool">
				<?php if ($is_edit): ?>
					<input type="hidden" name="pool_id" value="<?php echo esc_attr($pool_id); ?>">
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tbody>
						<!-- Product Selection -->
						<tr>
							<th scope="row">
								<label for="product_id">
									<?php _e('Sản phẩm', 'vd-license-manager'); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<select name="product_id" id="product_id" class="regular-text" required <?php echo $is_edit ? 'disabled' : ''; ?>>
									<option value=""><?php _e('-- Chọn sản phẩm --', 'vd-license-manager'); ?></option>
									<?php foreach ($products as $product): ?>
										<option value="<?php echo esc_attr($product->ID); ?>"
												<?php selected($is_edit ? $this->pool->product_id : 0, $product->ID); ?>>
											<?php echo esc_html($product->post_title); ?> (ID: <?php echo $product->ID; ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<?php if ($is_edit): ?>
									<input type="hidden" name="product_id" value="<?php echo esc_attr($this->pool->product_id); ?>">
									<p class="description">
										<?php _e('Không thể thay đổi sản phẩm khi sửa pool. Xóa và tạo mới nếu cần.', 'vd-license-manager'); ?>
									</p>
								<?php else: ?>
									<p class="description">
										<?php _e('Sản phẩm WooCommerce mà pool này sẽ cung cấp tài khoản.', 'vd-license-manager'); ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>

						<!-- Account Selection -->
						<tr>
							<th scope="row">
								<label for="account_id">
									<?php _e('Tài khoản', 'vd-license-manager'); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<select name="account_id" id="account_id" class="regular-text" required <?php echo $is_edit ? 'disabled' : ''; ?>>
									<option value=""><?php _e('-- Chọn tài khoản --', 'vd-license-manager'); ?></option>
									<?php foreach ($accounts as $account): ?>
										<option value="<?php echo esc_attr($account->id); ?>"
												<?php selected($is_edit ? $this->pool->account_id : 0, $account->id); ?>>
											<?php echo esc_html($account->display_name); ?>
											(<?php echo esc_html($account->provider); ?>, ID: <?php echo $account->id; ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<?php if ($is_edit): ?>
									<input type="hidden" name="account_id" value="<?php echo esc_attr($this->pool->account_id); ?>">
									<p class="description">
										<?php _e('Không thể thay đổi tài khoản khi sửa pool. Xóa và tạo mới nếu cần.', 'vd-license-manager'); ?>
									</p>
								<?php else: ?>
									<p class="description">
										<?php _e('Tài khoản Provider sẽ được sử dụng cho sản phẩm này.', 'vd-license-manager'); ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>

						<!-- Priority -->
						<tr>
							<th scope="row">
								<label for="priority">
									<?php _e('Độ ưu tiên', 'vd-license-manager'); ?>
								</label>
							</th>
							<td>
								<input type="number"
									   name="priority"
									   id="priority"
									   value="<?php echo esc_attr($is_edit ? $this->pool->priority : 0); ?>"
									   min="0"
									   max="999"
									   class="small-text">
								<p class="description">
									<?php _e('Số nhỏ = ưu tiên cao. Tài khoản có priority 0 sẽ được chọn trước priority 1.', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>

						<!-- Active Status -->
						<tr>
							<th scope="row">
								<label for="is_active">
									<?php _e('Trạng thái', 'vd-license-manager'); ?>
								</label>
							</th>
							<td>
								<label for="is_active">
									<input type="checkbox"
										   name="is_active"
										   id="is_active"
										   value="1"
										   <?php checked($is_edit ? $this->pool->is_active : 1, 1); ?>>
									<?php _e('Hoạt động', 'vd-license-manager'); ?>
								</label>
								<p class="description">
									<?php _e('Chỉ các pool đang hoạt động mới được sử dụng để cấp phát tài khoản.', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit"
						   name="submit"
						   id="submit"
						   class="button button-primary"
						   value="<?php echo $is_edit ? __('Cập nhật Pool', 'vd-license-manager') : __('Thêm Pool', 'vd-license-manager'); ?>">

					<a href="<?php echo admin_url('admin.php?page=vd-product-pools'); ?>"
					   class="button button-secondary">
						<?php _e('Hủy', 'vd-license-manager'); ?>
					</a>
				</p>
			</form>

			<?php $this->add_form_styles(); ?>
		</div>
		<?php
	}

	/**
	 * Get WooCommerce products
	 *
	 * @since 1.0.0
	 * @return array Products
	 */
	private function get_products() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT ID, post_title
			 FROM {$wpdb->posts}
			 WHERE post_type = 'product'
			   AND post_status = 'publish'
			 ORDER BY post_title ASC"
		);
	}

	/**
	 * Get provider accounts
	 *
	 * @since 1.0.0
	 * @return array Accounts
	 */
	private function get_accounts() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT id, display_name, provider
			 FROM bz_vd_provider_accounts
			 WHERE status = 'active'
			 ORDER BY display_name ASC"
		);
	}

	/**
	 * Display validation errors
	 *
	 * @since 1.0.0
	 */
	private function display_errors() {
		if (!$this->errors || !is_wp_error($this->errors)) {
			return;
		}

		echo '<div class="notice notice-error is-dismissible">';
		echo '<p><strong>' . __('Có lỗi xảy ra:', 'vd-license-manager') . '</strong></p>';
		echo '<ul style="list-style: disc; margin-left: 20px;">';
		foreach ($this->errors->get_error_messages() as $error) {
			echo '<li>' . esc_html($error) . '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Add form styles
	 *
	 * @since 1.0.0
	 */
	private function add_form_styles() {
		?>
		<style>
			.required {
				color: #d63638;
				font-weight: bold;
			}

			#vd-pool-form .form-table th {
				padding: 20px 10px 20px 0;
				width: 200px;
			}

			#vd-pool-form .form-table td {
				padding: 15px 10px;
			}

			#vd-pool-form select.regular-text {
				min-width: 400px;
			}

			#vd-pool-form input[type="number"] {
				width: 80px;
			}

			#vd-pool-form .description {
				margin-top: 8px;
				color: #646970;
			}

			#vd-pool-form .submit {
				padding-top: 10px;
			}

			#vd-pool-form .button-secondary {
				margin-left: 10px;
			}

			/* Disabled field styling */
			#vd-pool-form select:disabled {
				background-color: #f6f7f7;
				color: #50575e;
				cursor: not-allowed;
			}

			/* Help section styling */
			#vd-pool-form .description {
				font-style: italic;
			}
		</style>
		<?php
	}
}
<?php
/**
 * Share Configs Form View
 *
 * Renders the add/edit form for share configs
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
 * Share Configs Form View Class
 *
 * Handles rendering of add/edit form for share configs
 *
 * @package VD_License_Manager
 * @since 1.0.1
 */
class VD_Share_Configs_Form_View {

	/**
	 * Config data (for edit mode)
	 *
	 * @since 1.0.1
	 * @var object|null
	 */
	private $config = null;

	/**
	 * Form errors
	 *
	 * @since 1.0.1
	 * @var WP_Error|null
	 */
	private $errors = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.1
	 * @param object|null   $config Config data for edit mode
	 * @param WP_Error|null $errors Validation errors
	 */
	public function __construct($config = null, $errors = null) {
		$this->config = $config;
		$this->errors = $errors;
	}

	/**
	 * Render the form
	 *
	 * @since 1.0.1
	 */
	public function render() {
		$is_edit = !is_null($this->config);
		$config_id = $is_edit ? $this->config->id : 0;

		// Get products for dropdown (products without configs)
		$products = $is_edit ? $this->get_current_product() : $this->get_available_products();

		// Get form values (with defaults)
		$defaults = VD_Share_Config_Validator::get_defaults();
		$values = $this->get_form_values($defaults);

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo $is_edit ? __('Sửa Cấu Hình Chia Sẻ', 'vd-license-manager') : __('Thêm Cấu Hình Chia Sẻ', 'vd-license-manager'); ?>
			</h1>

			<a href="<?php echo admin_url('admin.php?page=vd-share-configs'); ?>" class="page-title-action">
				<?php _e('← Quay lại danh sách', 'vd-license-manager'); ?>
			</a>

			<hr class="wp-header-end">

			<?php $this->display_errors(); ?>

			<form method="post" action="" id="vd-config-form">
				<?php wp_nonce_field('vd_save_config', 'vd_config_nonce'); ?>
				<input type="hidden" name="action" value="save_config">
				<?php if ($is_edit): ?>
					<input type="hidden" name="config_id" value="<?php echo esc_attr($config_id); ?>">
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
								<?php if ($is_edit): ?>
									<input type="text"
										   value="<?php echo esc_attr($this->config->product_name ?: 'Sản phẩm không tồn tại (ID: ' . $this->config->product_id . ')'); ?>"
										   class="regular-text"
										   readonly>
									<input type="hidden" name="product_id" value="<?php echo esc_attr($this->config->product_id); ?>">
									<p class="description">
										<?php _e('Không thể thay đổi sản phẩm khi sửa cấu hình.', 'vd-license-manager'); ?>
									</p>
								<?php else: ?>
									<select name="product_id" id="product_id" class="regular-text" required>
										<option value=""><?php _e('-- Chọn sản phẩm --', 'vd-license-manager'); ?></option>
										<?php foreach ($products as $product): ?>
											<option value="<?php echo esc_attr($product->ID); ?>"
													<?php selected($values['product_id'], $product->ID); ?>>
												<?php echo esc_html($product->post_title); ?> (ID: <?php echo $product->ID; ?>)
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<?php _e('Sản phẩm WooCommerce cần cấu hình chia sẻ.', 'vd-license-manager'); ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>

						<!-- Max Profiles -->
						<tr>
							<th scope="row">
								<label for="max_profiles">
									<?php _e('Số Profile Tối Đa', 'vd-license-manager'); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<input type="number"
									   name="max_profiles"
									   id="max_profiles"
									   value="<?php echo esc_attr($values['max_profiles']); ?>"
									   min="1"
									   max="100"
									   class="small-text"
									   required>
								<p class="description">
									<?php _e('Số lượng khách hàng có thể chia sẻ một tài khoản (VD: Netflix cho phép 4-5 profile).', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>

						<!-- Max Devices per Profile -->
						<tr>
							<th scope="row">
								<label for="max_devices_per_profile">
									<?php _e('Số Thiết Bị Mỗi Profile', 'vd-license-manager'); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<input type="number"
									   name="max_devices_per_profile"
									   id="max_devices_per_profile"
									   value="<?php echo esc_attr($values['max_devices_per_profile']); ?>"
									   min="1"
									   max="10"
									   class="small-text"
									   required>
								<p class="description">
									<?php _e('Số thiết bị mỗi khách hàng có thể sử dụng đồng thời.', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>

						<!-- Sharing Duration -->
						<tr>
							<th scope="row">
								<label for="sharing_duration_days">
									<?php _e('Thời Hạn Chia Sẻ (ngày)', 'vd-license-manager'); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<input type="number"
									   name="sharing_duration_days"
									   id="sharing_duration_days"
									   value="<?php echo esc_attr($values['sharing_duration_days']); ?>"
									   min="1"
									   max="365"
									   class="small-text"
									   required>
								<p class="description">
									<?php _e('Số ngày khách hàng được sử dụng trước khi cần đổi tài khoản.', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>

						<!-- Auto Rotate -->
						<tr>
							<th scope="row">
								<label for="auto_rotate">
									<?php _e('Tự Động Đổi Tài Khoản', 'vd-license-manager'); ?>
								</label>
							</th>
							<td>
								<fieldset>
									<label for="auto_rotate">
										<input type="checkbox"
											   name="auto_rotate"
											   id="auto_rotate"
											   value="1"
											   <?php checked($values['auto_rotate'], 1); ?>>
										<?php _e('Tự động chuyển đổi tài khoản sau một khoảng thời gian', 'vd-license-manager'); ?>
									</label>
								</fieldset>
								<p class="description">
									<?php _e('Giúp tránh lạm dụng bằng cách tự động thay đổi thông tin tài khoản.', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>

						<!-- Rotation Interval -->
						<tr id="rotation_interval_row" style="<?php echo $values['auto_rotate'] ? '' : 'display: none;'; ?>">
							<th scope="row">
								<label for="rotation_interval_days">
									<?php _e('Chu Kỳ Đổi Tài Khoản (ngày)', 'vd-license-manager'); ?>
								</label>
							</th>
							<td>
								<input type="number"
									   name="rotation_interval_days"
									   id="rotation_interval_days"
									   value="<?php echo esc_attr($values['rotation_interval_days'] ?: 7); ?>"
									   min="1"
									   max="30"
									   class="small-text">
								<p class="description">
									<?php _e('Số ngày giữa các lần đổi tài khoản tự động.', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>

						<!-- Concurrent Streams -->
						<tr>
							<th scope="row">
								<label for="allow_concurrent_streams">
									<?php _e('Số Luồng Đồng Thời (tùy chọn)', 'vd-license-manager'); ?>
								</label>
							</th>
							<td>
								<input type="number"
									   name="allow_concurrent_streams"
									   id="allow_concurrent_streams"
									   value="<?php echo esc_attr($values['allow_concurrent_streams']); ?>"
									   min="1"
									   max="10"
									   class="small-text"
									   placeholder="<?php _e('Để trống = không giới hạn', 'vd-license-manager'); ?>">
								<p class="description">
									<?php _e('Số luồng phát có thể xem cùng lúc (VD: Netflix cho phép 1-4 luồng tùy gói).', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>

						<!-- Custom Rules -->
						<tr>
							<th scope="row">
								<label for="custom_rules">
									<?php _e('Quy Tắc Tùy Chỉnh (tùy chọn)', 'vd-license-manager'); ?>
								</label>
							</th>
							<td>
								<textarea name="custom_rules"
										  id="custom_rules"
										  rows="4"
										  cols="50"
										  class="large-text"
										  placeholder="<?php _e('Nhập các quy tắc đặc biệt cho sản phẩm này...', 'vd-license-manager'); ?>"><?php echo esc_textarea($values['custom_rules']); ?></textarea>
								<p class="description">
									<?php _e('Các quy tắc đặc biệt hoặc hướng dẫn cho sản phẩm này (văn bản thuần túy).', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(
					$is_edit ? __('Cập Nhật Cấu Hình', 'vd-license-manager') : __('Thêm Cấu Hình', 'vd-license-manager'),
					'primary',
					'submit',
					false
				); ?>

				<a href="<?php echo admin_url('admin.php?page=vd-share-configs'); ?>" class="button button-secondary">
					<?php _e('Hủy', 'vd-license-manager'); ?>
				</a>
			</form>

			<?php $this->add_form_javascript(); ?>
		</div>
		<?php
	}

	/**
	 * Display form errors
	 *
	 * @since 1.0.1
	 */
	private function display_errors() {
		if ($this->errors && $this->errors->has_errors()) {
			echo '<div class="notice notice-error"><ul>';
			foreach ($this->errors->get_error_messages() as $error) {
				echo '<li>' . esc_html($error) . '</li>';
			}
			echo '</ul></div>';
		}
	}

	/**
	 * Get available products (without existing configs)
	 *
	 * @since 1.0.1
	 * @return array Array of product objects
	 */
	private function get_available_products() {
		return VD_Share_Config_Repository::get_products_without_configs();
	}

	/**
	 * Get current product (for edit mode)
	 *
	 * @since 1.0.1
	 * @return array Array with current product
	 */
	private function get_current_product() {
		if (!$this->config) {
			return array();
		}

		$product = get_post($this->config->product_id);
		if ($product && $product->post_type === 'product') {
			// Add product_name to config if not already there
			if (!isset($this->config->product_name)) {
				$this->config->product_name = $product->post_title;
			}
			return array($product);
		}
		return array();
	}

	/**
	 * Get form values with fallbacks
	 *
	 * @since 1.0.1
	 * @param array $defaults Default values
	 * @return array Form values
	 */
	private function get_form_values($defaults) {
		$values = array();

		if ($this->config) {
			// Edit mode - use config data
			$values = array(
				'product_id'               => $this->config->product_id,
				'max_profiles'             => $this->config->max_profiles,
				'max_devices_per_profile'  => $this->config->max_devices_per_profile,
				'sharing_duration_days'    => $this->config->sharing_duration_days,
				'auto_rotate'              => $this->config->auto_rotate,
				'rotation_interval_days'   => $this->config->rotation_interval_days,
				'allow_concurrent_streams' => $this->config->allow_concurrent_streams,
				'custom_rules'             => $this->config->custom_rules
			);
		} else {
			// Add mode - use POST data if available, otherwise defaults
			$values = array(
				'product_id'               => isset($_POST['product_id']) ? intval($_POST['product_id']) : '',
				'max_profiles'             => isset($_POST['max_profiles']) ? intval($_POST['max_profiles']) : $defaults['max_profiles'],
				'max_devices_per_profile'  => isset($_POST['max_devices_per_profile']) ? intval($_POST['max_devices_per_profile']) : $defaults['max_devices_per_profile'],
				'sharing_duration_days'    => isset($_POST['sharing_duration_days']) ? intval($_POST['sharing_duration_days']) : $defaults['sharing_duration_days'],
				'auto_rotate'              => isset($_POST['auto_rotate']) ? 1 : $defaults['auto_rotate'],
				'rotation_interval_days'   => isset($_POST['rotation_interval_days']) ? intval($_POST['rotation_interval_days']) : $defaults['rotation_interval_days'],
				'allow_concurrent_streams' => isset($_POST['allow_concurrent_streams']) ? intval($_POST['allow_concurrent_streams']) : $defaults['allow_concurrent_streams'],
				'custom_rules'             => isset($_POST['custom_rules']) ? sanitize_textarea_field($_POST['custom_rules']) : $defaults['custom_rules']
			);
		}

		return $values;
	}

	/**
	 * Add form JavaScript for dynamic behavior
	 *
	 * @since 1.0.1
	 */
	private function add_form_javascript() {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Show/hide rotation interval based on auto rotate checkbox
			$('#auto_rotate').change(function() {
				if ($(this).is(':checked')) {
					$('#rotation_interval_row').show();
					$('#rotation_interval_days').prop('required', true);
				} else {
					$('#rotation_interval_row').hide();
					$('#rotation_interval_days').prop('required', false);
				}
			});

			// Client-side validation
			$('#vd-config-form').submit(function(e) {
				var maxProfiles = parseInt($('#max_profiles').val());
				var maxDevices = parseInt($('#max_devices_per_profile').val());
				var duration = parseInt($('#sharing_duration_days').val());
				var autoRotate = $('#auto_rotate').is(':checked');
				var rotationInterval = parseInt($('#rotation_interval_days').val());

				// Validate ranges
				if (maxProfiles < 1 || maxProfiles > 100) {
					alert('<?php _e("Số Profile phải từ 1 đến 100", "vd-license-manager"); ?>');
					$('#max_profiles').focus();
					return false;
				}

				if (maxDevices < 1 || maxDevices > 10) {
					alert('<?php _e("Số thiết bị phải từ 1 đến 10", "vd-license-manager"); ?>');
					$('#max_devices_per_profile').focus();
					return false;
				}

				if (duration < 1 || duration > 365) {
					alert('<?php _e("Thời hạn phải từ 1 đến 365 ngày", "vd-license-manager"); ?>');
					$('#sharing_duration_days').focus();
					return false;
				}

				// Auto rotate validation
				if (autoRotate) {
					if (!rotationInterval || rotationInterval < 1 || rotationInterval > 30) {
						alert('<?php _e("Chu kỳ xoay phải từ 1 đến 30 ngày", "vd-license-manager"); ?>');
						$('#rotation_interval_days').focus();
						return false;
					}

					if (rotationInterval > duration) {
						alert('<?php _e("Chu kỳ xoay không được lớn hơn thời hạn chia sẻ", "vd-license-manager"); ?>');
						$('#rotation_interval_days').focus();
						return false;
					}
				}

				return true;
			});
		});
		</script>

		<style>
		.required {
			color: #dc3232;
		}

		.form-table th {
			width: 200px;
		}

		.form-table input[type="number"] {
			width: 80px;
		}

		#custom_rules {
			width: 100%;
			max-width: 500px;
		}
		</style>
		<?php
	}
}
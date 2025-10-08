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

						<!-- Max Devices Total -->
						<tr>
							<th scope="row">
								<label for="max_devices">
									<?php _e('Tổng Số Thiết Bị Tối Đa', 'vd-license-manager'); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<input type="number"
									   name="max_devices"
									   id="max_devices"
									   value="<?php echo esc_attr($values['max_devices']); ?>"
									   min="1"
									   max="100"
									   class="small-text"
									   required>
								<p class="description">
									<?php _e('Tổng số thiết bị có thể kết nối cho tất cả các profile.', 'vd-license-manager'); ?>
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

						<!-- Last Update Date -->
						<tr>
							<th scope="row">
								<label for="last_update_date">
									<?php _e('Ngày Cập Nhật Cuối', 'vd-license-manager'); ?>
								</label>
							</th>
							<td>
								<input type="datetime-local"
									   name="last_update_date"
									   id="last_update_date"
									   value="<?php echo esc_attr($this->format_datetime_for_input($values['last_update_date'])); ?>"
									   class="regular-text">
								<p class="description">
									<?php _e('Lần cuối cùng thông tin tài khoản được cập nhật.', 'vd-license-manager'); ?>
								</p>
							</td>
						</tr>

						<!-- Next Update Date -->
						<tr>
							<th scope="row">
								<label for="next_update_date">
									<?php _e('Ngày Cập Nhật Tiếp Theo', 'vd-license-manager'); ?>
								</label>
							</th>
							<td>
								<input type="datetime-local"
									   name="next_update_date"
									   id="next_update_date"
									   value="<?php echo esc_attr($this->format_datetime_for_input($values['next_update_date'])); ?>"
									   class="regular-text">
								<p class="description">
									<?php _e('Ngày dự kiến cập nhật thông tin tài khoản tiếp theo.', 'vd-license-manager'); ?>
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
				'max_devices'              => isset($this->config->max_devices) ? $this->config->max_devices : $defaults['max_devices'],
				'sharing_duration_days'    => $this->config->sharing_duration_days,
				'last_update_date'         => isset($this->config->last_update_date) ? $this->config->last_update_date : $defaults['last_update_date'],
				'next_update_date'         => isset($this->config->next_update_date) ? $this->config->next_update_date : $defaults['next_update_date']
			);
		} else {
			// Add mode - use POST data if available, otherwise defaults
			$values = array(
				'product_id'               => isset($_POST['product_id']) ? intval($_POST['product_id']) : '',
				'max_profiles'             => isset($_POST['max_profiles']) ? intval($_POST['max_profiles']) : $defaults['max_profiles'],
				'max_devices_per_profile'  => isset($_POST['max_devices_per_profile']) ? intval($_POST['max_devices_per_profile']) : $defaults['max_devices_per_profile'],
				'max_devices'              => isset($_POST['max_devices']) ? intval($_POST['max_devices']) : $defaults['max_devices'],
				'sharing_duration_days'    => isset($_POST['sharing_duration_days']) ? intval($_POST['sharing_duration_days']) : $defaults['sharing_duration_days'],
				'last_update_date'         => isset($_POST['last_update_date']) ? sanitize_text_field($_POST['last_update_date']) : $defaults['last_update_date'],
				'next_update_date'         => isset($_POST['next_update_date']) ? sanitize_text_field($_POST['next_update_date']) : $defaults['next_update_date']
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
			// Auto-calculate max_devices when profiles or devices per profile changes
			$('#max_profiles, #max_devices_per_profile').on('change keyup', function() {
				var maxProfiles = parseInt($('#max_profiles').val()) || 1;
				var maxDevicesPerProfile = parseInt($('#max_devices_per_profile').val()) || 1;
				var suggestedMaxDevices = maxProfiles * maxDevicesPerProfile;

				$('#max_devices').attr('min', suggestedMaxDevices);
				if (parseInt($('#max_devices').val()) < suggestedMaxDevices) {
					$('#max_devices').val(suggestedMaxDevices);
				}
			});

			// Client-side validation
			$('#vd-config-form').submit(function(e) {
				var maxProfiles = parseInt($('#max_profiles').val());
				var maxDevicesPerProfile = parseInt($('#max_devices_per_profile').val());
				var maxDevicesTotal = parseInt($('#max_devices').val());
				var duration = parseInt($('#sharing_duration_days').val());
				var lastUpdate = $('#last_update_date').val();
				var nextUpdate = $('#next_update_date').val();

				// Validate ranges
				if (maxProfiles < 1 || maxProfiles > 100) {
					alert('<?php _e("Số Profile phải từ 1 đến 100", "vd-license-manager"); ?>');
					$('#max_profiles').focus();
					return false;
				}

				if (maxDevicesPerProfile < 1 || maxDevicesPerProfile > 20) {
					alert('<?php _e("Số thiết bị mỗi profile phải từ 1 đến 20", "vd-license-manager"); ?>');
					$('#max_devices_per_profile').focus();
					return false;
				}

				if (maxDevicesTotal < 1 || maxDevicesTotal > 100) {
					alert('<?php _e("Tổng số thiết bị phải từ 1 đến 100", "vd-license-manager"); ?>');
					$('#max_devices').focus();
					return false;
				}

				// Check device logic
				var calculatedTotal = maxProfiles * maxDevicesPerProfile;
				if (maxDevicesTotal < calculatedTotal) {
					alert('<?php printf(_e("Tổng số thiết bị (%s) phải ít nhất bằng số profile × thiết bị mỗi profile (%s)", "vd-license-manager"), "' + maxDevicesTotal + '", "' + calculatedTotal + '"); ?>');
					$('#max_devices').focus();
					return false;
				}

				if (duration < 1 || duration > 365) {
					alert('<?php _e("Thời hạn phải từ 1 đến 365 ngày", "vd-license-manager"); ?>');
					$('#sharing_duration_days').focus();
					return false;
				}

				// Date validation
				if (lastUpdate && nextUpdate) {
					var lastDate = new Date(lastUpdate);
					var nextDate = new Date(nextUpdate);

					if (nextDate <= lastDate) {
						alert('<?php _e("Ngày cập nhật tiếp theo phải sau ngày cập nhật cuối", "vd-license-manager"); ?>');
						$('#next_update_date').focus();
						return false;
					}
				}

				if (nextUpdate) {
					var nextDate = new Date(nextUpdate);
					var now = new Date();

					if (nextDate <= now) {
						alert('<?php _e("Ngày cập nhật tiếp theo phải trong tương lai", "vd-license-manager"); ?>');
						$('#next_update_date').focus();
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

	/**
	 * Format datetime for HTML datetime-local input
	 *
	 * @since 1.0.1
	 * @param string|null $datetime MySQL datetime string
	 * @return string Formatted datetime for input
	 */
	private function format_datetime_for_input($datetime) {
		if (empty($datetime)) {
			return '';
		}

		try {
			$date = new DateTime($datetime);
			return $date->format('Y-m-d\TH:i');
		} catch (Exception $e) {
			return '';
		}
	}
}
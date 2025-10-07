<?php
/**
 * Provider Accounts form view
 *
 * Renders add and edit forms for provider accounts
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check
if (!defined('ABSPATH')) {
	exit;
}

/**
 * VD Accounts Form View class
 *
 * Handles the rendering of add and edit forms for provider accounts
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Accounts_Form_View {

	/**
	 * Render add new account form
	 *
	 * @since 1.0.0
	 */
	public static function render_add() {
		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'vd-license-manager'));
		}

		// Render messages
		self::render_messages();

		// Render page
		?>
		<div class="wrap">
			<?php self::render_header('add'); ?>
			<?php self::render_add_form(); ?>
		</div>
		<?php
	}

	/**
	 * Render edit account form
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID to edit
	 */
	public static function render_edit($account_id) {
		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'vd-license-manager'));
		}

		// Get account data
		$account = VD_Accounts_Repository::get_account($account_id);
		if (!$account) {
			wp_die(__('Account not found.', 'vd-license-manager'));
		}

		// Render messages
		self::render_messages();

		// Render page
		?>
		<div class="wrap">
			<?php self::render_header('edit', $account); ?>
			<?php self::render_edit_form($account); ?>
		</div>
		<?php
	}

	/**
	 * Render page header
	 *
	 * @since 1.0.0
	 * @param string $mode    'add' or 'edit'
	 * @param object $account Account object (for edit mode)
	 */
	private static function render_header($mode, $account = null) {
		$title = ($mode === 'add') ? __('Add New Provider Account', 'vd-license-manager') : __('Edit Provider Account', 'vd-license-manager');
		$back_url = admin_url('admin.php?page=vd-provider-accounts');

		?>
		<h1 class="wp-heading-inline">
			<?php echo esc_html($title); ?>
		</h1>

		<a href="<?php echo esc_url($back_url); ?>" class="page-title-action">
			<?php echo esc_html__('Back to List', 'vd-license-manager'); ?>
		</a>

		<hr class="wp-header-end">
		<?php
	}

	/**
	 * Render add form
	 *
	 * @since 1.0.0
	 */
	private static function render_add_form() {
		?>
		<form method="post" action="<?php echo esc_url(admin_url('admin.php?page=vd-provider-accounts&action=save')); ?>">
			<?php wp_nonce_field('vd_save_account', 'vd_account_nonce'); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="provider"><?php _e('Provider', 'vd-license-manager'); ?> <span class="required">*</span></label>
						</th>
						<td>
							<select name="provider" id="provider" required>
								<option value=""><?php _e('Select Provider...', 'vd-license-manager'); ?></option>
								<option value="netflix">Netflix</option>
								<option value="spotify">Spotify</option>
								<option value="youtube">YouTube</option>
								<option value="disney">Disney+</option>
								<option value="hbo">HBO Max</option>
								<option value="amazon">Amazon Prime</option>
								<option value="hulu">Hulu</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="account_login"><?php _e('Account Login', 'vd-license-manager'); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" name="account_login" id="account_login" class="regular-text" required />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="display_name"><?php _e('Display Name', 'vd-license-manager'); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" name="display_name" id="display_name" class="regular-text" required />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="capacity"><?php _e('Capacity', 'vd-license-manager'); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="number" name="capacity" id="capacity" class="small-text" min="1" max="100" required />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="status"><?php _e('Status', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<select name="status" id="status">
								<option value="active"><?php _e('Active', 'vd-license-manager'); ?></option>
								<option value="suspended"><?php _e('Suspended', 'vd-license-manager'); ?></option>
								<option value="expired"><?php _e('Expired', 'vd-license-manager'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="cookie"><?php _e('Cookie', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<textarea name="cookie" id="cookie" rows="8" cols="50" class="large-text"></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="cookie_format"><?php _e('Cookie Format', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<select name="cookie_format" id="cookie_format">
								<option value="json">JSON</option>
								<option value="netscape">Netscape</option>
								<option value="headers">Headers</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="login_email"><?php _e('Login Email', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="email" name="login_email" id="login_email" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="login_password"><?php _e('Login Password', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="password" name="login_password" id="login_password" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="totp_secret"><?php _e('TOTP Secret', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="text" name="totp_secret" id="totp_secret" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="recovery_email"><?php _e('Recovery Email', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="email" name="recovery_email" id="recovery_email" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="recovery_phone"><?php _e('Recovery Phone', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="tel" name="recovery_phone" id="recovery_phone" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="notes"><?php _e('Notes', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<textarea name="notes" id="notes" rows="4" cols="50" class="large-text"></textarea>
						</td>
					</tr>

					<!-- Subscription Information Section -->
					<tr>
						<td colspan="2">
							<h3 style="margin-top: 20px; margin-bottom: 10px; cursor: pointer;" onclick="toggleSection('subscription-section')">
								<span class="dashicons dashicons-arrow-down-alt2"></span>
								<?php echo esc_html__('Subscription Information', 'vd-license-manager'); ?>
							</h3>
						</td>
					</tr>
				</tbody>

				<tbody id="subscription-section" style="display: none;">
					<!-- Subscription Start Date -->
					<tr>
						<th scope="row">
							<label for="subscription_start_date"><?php echo esc_html__('Subscription Start Date', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="date" name="subscription_start_date" id="subscription_start_date" value="">
							<p class="description"><?php echo esc_html__('Ngày bắt đầu đăng ký dịch vụ', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Subscription End Date -->
					<tr>
						<th scope="row">
							<label for="subscription_end_date"><?php echo esc_html__('Subscription End Date', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="date" name="subscription_end_date" id="subscription_end_date" value="">
							<p class="description"><?php echo esc_html__('Ngày hết hạn đăng ký', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Subscription Cost -->
					<tr>
						<th scope="row">
							<label for="subscription_cost"><?php echo esc_html__('Subscription Cost', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="number" step="0.01" name="subscription_cost" id="subscription_cost" class="small-text" value="0.00">
							<select name="currency" id="currency" style="margin-left: 10px;">
								<option value="USD">USD</option>
								<option value="VND">VND</option>
								<option value="EUR">EUR</option>
								<option value="GBP">GBP</option>
								<option value="JPY">JPY</option>
							</select>
							<p class="description"><?php echo esc_html__('Số tiền đăng ký và đơn vị tiền tệ', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Auto Renewal -->
					<tr>
						<th scope="row">
							<label for="auto_renewal"><?php echo esc_html__('Auto Renewal', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="auto_renewal" id="auto_renewal" value="1">
								<?php echo esc_html__('Tự động gia hạn khi hết hạn', 'vd-license-manager'); ?>
							</label>
						</td>
					</tr>
				</tbody>

				<tbody>
					<!-- Account Details Section -->
					<tr>
						<td colspan="2">
							<h3 style="margin-top: 20px; margin-bottom: 10px; cursor: pointer;" onclick="toggleSection('account-details-section')">
								<span class="dashicons dashicons-arrow-down-alt2"></span>
								<?php echo esc_html__('Account Details', 'vd-license-manager'); ?>
							</h3>
						</td>
					</tr>
				</tbody>

				<tbody id="account-details-section" style="display: none;">
					<!-- Plan Type -->
					<tr>
						<th scope="row">
							<label for="plan_type"><?php echo esc_html__('Plan Type', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<select name="plan_type" id="plan_type" class="regular-text">
								<option value=""><?php echo esc_html__('Select Plan', 'vd-license-manager'); ?></option>
								<option value="Premium">Premium</option>
								<option value="Basic">Basic</option>
								<option value="Family">Family</option>
								<option value="Student">Student</option>
								<option value="Standard">Standard</option>
							</select>
							<p class="description"><?php echo esc_html__('Loại gói dịch vụ', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Profile Limit -->
					<tr>
						<th scope="row">
							<label for="profile_limit"><?php echo esc_html__('Profile Limit', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="number" name="profile_limit" id="profile_limit" class="small-text" min="1" max="10" value="1">
							<p class="description"><?php echo esc_html__('Số lượng profile tối đa', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Video Quality -->
					<tr>
						<th scope="row">
							<label for="video_quality"><?php echo esc_html__('Video Quality', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<select name="video_quality" id="video_quality" class="regular-text">
								<option value=""><?php echo esc_html__('Select Quality', 'vd-license-manager'); ?></option>
								<option value="SD">SD (480p)</option>
								<option value="HD">HD (720p/1080p)</option>
								<option value="4K">4K (2160p)</option>
								<option value="8K">8K (4320p)</option>
							</select>
							<p class="description"><?php echo esc_html__('Chất lượng video tối đa', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Account Region -->
					<tr>
						<th scope="row">
							<label for="account_region"><?php echo esc_html__('Account Region', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<select name="account_region" id="account_region" class="regular-text">
								<option value=""><?php echo esc_html__('Select Region', 'vd-license-manager'); ?></option>
								<option value="US">🇺🇸 United States</option>
								<option value="UK">🇬🇧 United Kingdom</option>
								<option value="VN">🇻🇳 Vietnam</option>
								<option value="JP">🇯🇵 Japan</option>
								<option value="KR">🇰🇷 Korea</option>
								<option value="SG">🇸🇬 Singapore</option>
							</select>
							<p class="description"><?php echo esc_html__('Vùng/quốc gia của tài khoản', 'vd-license-manager'); ?></p>
						</td>
					</tr>
				</tbody>

				<tbody>
					<!-- Security Settings Section -->
					<tr>
						<td colspan="2">
							<h3 style="margin-top: 20px; margin-bottom: 10px; cursor: pointer;" onclick="toggleSection('security-section')">
								<span class="dashicons dashicons-arrow-down-alt2"></span>
								<?php echo esc_html__('Security Settings', 'vd-license-manager'); ?>
							</h3>
						</td>
					</tr>
				</tbody>

				<tbody id="security-section" style="display: none;">
					<!-- Last Password Changed -->
					<tr>
						<th scope="row">
							<label for="last_password_changed"><?php echo esc_html__('Last Password Changed', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="datetime-local" name="last_password_changed" id="last_password_changed" value="">
							<p class="description"><?php echo esc_html__('Lần đổi password gần nhất', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Has 2FA -->
					<tr>
						<th scope="row">
							<label for="has_2fa"><?php echo esc_html__('Two-Factor Authentication', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="has_2fa" id="has_2fa" value="1">
								<?php echo esc_html__('Tài khoản có bật 2FA', 'vd-license-manager'); ?>
							</label>
						</td>
					</tr>

					<!-- Security Level -->
					<tr>
						<th scope="row">
							<label for="security_level"><?php echo esc_html__('Security Level', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<select name="security_level" id="security_level" class="regular-text">
								<option value="low">🟡 Low</option>
								<option value="medium" selected>🟠 Medium</option>
								<option value="high">🔴 High</option>
							</select>
							<p class="description"><?php echo esc_html__('Mức độ bảo mật của tài khoản', 'vd-license-manager'); ?></p>
						</td>
					</tr>
				</tbody>

				<tbody>
					<!-- Business Metrics Section (Read-only) -->
					<tr>
						<td colspan="2">
							<h3 style="margin-top: 20px; margin-bottom: 10px; cursor: pointer;" onclick="toggleSection('business-section')">
								<span class="dashicons dashicons-arrow-down-alt2"></span>
								<?php echo esc_html__('Business Metrics', 'vd-license-manager'); ?>
								<small style="color: #999;">(<?php echo esc_html__('Auto-calculated', 'vd-license-manager'); ?>)</small>
							</h3>
						</td>
					</tr>
				</tbody>

				<tbody id="business-section" style="display: none;">
					<!-- Total Revenue -->
					<tr>
						<th scope="row">
							<label for="total_revenue"><?php echo esc_html__('Total Revenue', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="number" step="0.01" name="total_revenue" id="total_revenue" class="regular-text" readonly value="0.00" style="background: #f5f5f5;">
							<p class="description"><?php echo esc_html__('Tổng doanh thu đã tạo ra (tự động tính)', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Total Licenses Served -->
					<tr>
						<th scope="row">
							<label for="total_licenses_served"><?php echo esc_html__('Total Licenses Served', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="number" name="total_licenses_served" id="total_licenses_served" class="small-text" readonly value="0" style="background: #f5f5f5;">
							<p class="description"><?php echo esc_html__('Tổng số licenses đã phục vụ (tự động tính)', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Success Rate -->
					<tr>
						<th scope="row">
							<label for="success_rate"><?php echo esc_html__('Success Rate', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="number" step="0.01" name="success_rate" id="success_rate" class="small-text" readonly value="0.00" style="background: #f5f5f5;">
							<span>%</span>
							<p class="description"><?php echo esc_html__('Tỷ lệ thành công (tự động tính)', 'vd-license-manager'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<script>
			function toggleSection(sectionId) {
				var section = document.getElementById(sectionId);
				if (section) {
					section.style.display = section.style.display === 'none' ? 'table-row-group' : 'none';
				}
			}
			</script>

			<?php submit_button(__('Add Account', 'vd-license-manager')); ?>
		</form>
		<?php
	}

	/**
	 * Render edit form
	 *
	 * @since 1.0.0
	 * @param object $account Account object
	 */
	private static function render_edit_form($account) {
		// Note: Edit form will be implemented in Sprint 3
		// For now, show a message
		?>
		<div class="notice notice-info">
			<p><?php _e('Edit functionality will be implemented in Sprint 3.', 'vd-license-manager'); ?></p>
		</div>
		<?php
	}

	/**
	 * Render admin messages from URL parameters
	 *
	 * @since 1.0.0
	 */
	private static function render_messages() {
		// Check for message in URL
		if (!isset($_GET['message'])) {
			return;
		}

		$message = sanitize_text_field($_GET['message']);
		$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';

		// Determine notice class
		$notice_class = ($type === 'error') ? 'notice-error' : 'notice-success';

		?>
		<div class="notice <?php echo esc_attr($notice_class); ?> is-dismissible">
			<p><?php echo esc_html($message); ?></p>
		</div>
		<?php
	}
}
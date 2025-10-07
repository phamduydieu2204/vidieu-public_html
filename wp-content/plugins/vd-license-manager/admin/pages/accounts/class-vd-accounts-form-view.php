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
		<!-- Helper Info Box -->
		<div class="notice notice-info" style="margin: 20px 0; padding: 10px 15px;">
			<p><strong>💡 Giải thích một số trường quan trọng:</strong></p>
			<ul style="margin: 10px 0 10px 20px; list-style: disc;">
				<li>
					<strong>Account Login</strong> = Username/email CHÍNH để đăng nhập vào Netflix/Spotify<br>
					<strong>Login Email</strong> = Email PHỤ để recovery/2FA (có thể khác Account Login)
				</li>
				<li>
					<strong>Capacity</strong> = Số KHÁCH HÀNG bạn muốn chia sẻ tài khoản này (do bạn quyết định)<br>
					<strong>Profile Limit</strong> = Số PROFILE trong tài khoản (do Netflix/Spotify quy định)
				</li>
			</ul>
			<p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">
				<em>Di chuột vào từng trường để xem giải thích chi tiết bằng tiếng Việt.</em>
			</p>
		</div>

		<form method="post" action="<?php echo esc_url(admin_url('admin.php?page=vd-provider-accounts&action=save')); ?>">
			<?php wp_nonce_field('vd_save_account', 'vd_account_nonce'); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="provider"><?php _e('Provider', 'vd-license-manager'); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" name="provider" id="provider" class="regular-text" required
								   title="<?php echo esc_attr__('Tên nhà cung cấp dịch vụ streaming (Netflix, Spotify, YouTube Premium, v.v.). Bạn có thể nhập tự do.', 'vd-license-manager'); ?>"
								   placeholder="<?php _e('e.g., Netflix, Spotify, YouTube...', 'vd-license-manager'); ?>" />
							<p class="description"><?php _e('Nhà cung cấp dịch vụ (có thể nhập tự do)', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="account_login"><?php _e('Account Login', 'vd-license-manager'); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" name="account_login" id="account_login" class="regular-text" required
								   title="<?php echo esc_attr__('Username hoặc email CHÍNH để đăng nhập vào dịch vụ streaming. Ví dụ: demo@netflix.com hoặc john_smith. ĐÂY LÀ TÀI KHOẢN LOGIN THẬT của Netflix/Spotify.', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Username hoặc email CHÍNH để đăng nhập (ví dụ: demo@netflix.com). Đây là tài khoản LOGIN THẬT.', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="display_name"><?php _e('Display Name', 'vd-license-manager'); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" name="display_name" id="display_name" class="regular-text" required
								   title="<?php echo esc_attr__('Tên hiển thị thân thiện để dễ nhận biết trong danh sách. Ví dụ: \'Tài khoản Netflix Premium Việt Nam\' hoặc \'Spotify Family - Chính\'.', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Tên hiển thị để dễ nhận biết trong danh sách', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="capacity"><?php _e('Capacity', 'vd-license-manager'); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="number" name="capacity" id="capacity" class="small-text" min="1" required
								   title="<?php echo esc_attr__('Số lượng khách hàng/licenses tối đa có thể CHIA SẺ tài khoản này. Ví dụ: Capacity = 5 nghĩa là chia cho 5 khách hàng. KHÁC với Profile Limit (số profile trong tài khoản).', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Số khách hàng có thể sử dụng tài khoản này. Không có giới hạn tối đa. Khác với Profile Limit (số profile trong account).', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="status"><?php _e('Status', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<select name="status" id="status"
									title="<?php echo esc_attr__('Trạng thái hiện tại của tài khoản: Active (hoạt động), Suspended (tạm khóa), hoặc Expired (hết hạn).', 'vd-license-manager'); ?>">
								<option value="active"><?php _e('Active', 'vd-license-manager'); ?></option>
								<option value="suspended"><?php _e('Suspended', 'vd-license-manager'); ?></option>
								<option value="expired"><?php _e('Expired', 'vd-license-manager'); ?></option>
							</select>
							<p class="description"><?php echo esc_html__('Trạng thái hoạt động của tài khoản', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="cookie"><?php _e('Cookie', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<textarea name="cookie" id="cookie" rows="8" cols="50" class="large-text"
									  title="<?php echo esc_attr__('Cookie dạng JSON hoặc text từ trình duyệt. Dùng để tự động login mà không cần nhập password. Copy từ browser extension.', 'vd-license-manager'); ?>"></textarea>
							<p class="description"><?php echo esc_html__('Cookie dạng JSON để tự động đăng nhập', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="login_email"><?php _e('Login Email', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="email" name="login_email" id="login_email" class="regular-text"
								   title="<?php echo esc_attr__('Email PHỤ để nhận mã khôi phục hoặc xác thực 2FA. KHÁC với Account Login (username đăng nhập chính). Có thể để trống.', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Email PHỤ để recovery/2FA. Có thể khác với Account Login. Để trống nếu không có.', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="login_password"><?php _e('Login Password', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="text" name="login_password" id="login_password" class="regular-text"
								   title="<?php echo esc_attr__('Mật khẩu để đăng nhập vào tài khoản streaming. Hiển thị dạng text rõ ràng (không ẩn) để dễ copy/paste.', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Mật khẩu đăng nhập (hiển thị rõ để dễ copy)', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="totp_secret"><?php _e('TOTP Secret', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="text" name="totp_secret" id="totp_secret" class="regular-text"
								   title="<?php echo esc_attr__('Mã bí mật TOTP cho xác thực 2 bước (2FA). Dùng với Google Authenticator hoặc Authy. Ví dụ: JBSWY3DPEHPK3PXP.', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Mã bí mật TOTP cho 2FA (Google Authenticator)', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="recovery_email"><?php _e('Recovery Email', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="email" name="recovery_email" id="recovery_email" class="regular-text"
								   title="<?php echo esc_attr__('Email dự phòng để khôi phục tài khoản khi quên mật khẩu. Thường khác với Login Email.', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Email dự phòng để khôi phục tài khoản', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="recovery_phone"><?php _e('Recovery Phone', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="tel" name="recovery_phone" id="recovery_phone" class="regular-text"
								   title="<?php echo esc_attr__('Số điện thoại để nhận mã OTP khôi phục tài khoản. Định dạng: +84901234567 hoặc 0901234567.', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Số điện thoại nhận mã OTP khôi phục', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="notes"><?php _e('Notes', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<textarea name="notes" id="notes" rows="4" cols="50" class="large-text"
									  title="<?php echo esc_attr__('Ghi chú nội bộ về tài khoản này. Ví dụ: nguồn mua, lịch sử thay đổi, vấn đề gặp phải, v.v.', 'vd-license-manager'); ?>"></textarea>
							<p class="description"><?php echo esc_html__('Ghi chú nội bộ về tài khoản', 'vd-license-manager'); ?></p>
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
							<input type="date" name="subscription_start_date" id="subscription_start_date" value=""
								   title="<?php echo esc_attr__('Ngày bắt đầu gói đăng ký dịch vụ. Ví dụ: 2025-01-01. Để trống nếu không rõ.', 'vd-license-manager'); ?>">
							<p class="description"><?php echo esc_html__('Ngày bắt đầu đăng ký dịch vụ', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Subscription End Date -->
					<tr>
						<th scope="row">
							<label for="subscription_end_date"><?php echo esc_html__('Subscription End Date', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="date" name="subscription_end_date" id="subscription_end_date" value=""
								   title="<?php echo esc_attr__('Ngày hết hạn gói đăng ký. Ví dụ: 2025-12-31. Dùng để theo dõi khi nào cần gia hạn.', 'vd-license-manager'); ?>">
							<p class="description"><?php echo esc_html__('Ngày hết hạn đăng ký', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Subscription Cost -->
					<tr>
						<th scope="row">
							<label for="subscription_cost"><?php echo esc_html__('Subscription Cost', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="number" step="0.01" name="subscription_cost" id="subscription_cost" class="small-text" value="0.00"
								   title="<?php echo esc_attr__('Số tiền đã trả cho gói đăng ký này. Ví dụ: 15.99 USD hoặc 350000 VND. Dùng để tính lợi nhuận.', 'vd-license-manager'); ?>">
							<select name="currency" id="currency" style="margin-left: 10px;"
									title="<?php echo esc_attr__('Đơn vị tiền tệ của giá đăng ký: USD ($), VND (₫), EUR (€), GBP (£), JPY (¥).', 'vd-license-manager'); ?>">
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
							<label title="<?php echo esc_attr__('Đánh dấu nếu tài khoản tự động gia hạn khi hết hạn. Dùng để nhắc nhở thanh toán.', 'vd-license-manager'); ?>">
								<input type="checkbox" name="auto_renewal" id="auto_renewal" value="1">
								<?php echo esc_html__('Tự động gia hạn khi hết hạn', 'vd-license-manager'); ?>
							</label>
							<p class="description"><?php echo esc_html__('Tự động gia hạn đăng ký', 'vd-license-manager'); ?></p>
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
							<input type="text" name="plan_type" id="plan_type" class="regular-text"
								   placeholder="<?php echo esc_attr__('e.g., Premium, Family, Basic...', 'vd-license-manager'); ?>"
								   title="<?php echo esc_attr__('Loại gói dịch vụ: Premium, Basic, Family, Student, v.v. Bạn có thể nhập tự do theo tên gói thực tế.', 'vd-license-manager'); ?>" />
							<p class="description"><?php echo esc_html__('Loại gói dịch vụ (nhập tự do)', 'vd-license-manager'); ?></p>
							<p class="description"><?php echo esc_html__('Loại gói dịch vụ', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Profile Limit -->
					<tr>
						<th scope="row">
							<label for="profile_limit"><?php echo esc_html__('Profile Limit', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="number" name="profile_limit" id="profile_limit" class="small-text" min="1" value="1"
								   title="<?php echo esc_attr__('Số lượng profile tối đa mà dịch vụ CHO PHÉP tạo TRONG tài khoản. Ví dụ: Netflix Premium = 4 profiles. KHÁC với Capacity (số khách hàng chia sẻ).', 'vd-license-manager'); ?>">
							<p class="description"><?php echo esc_html__('Số profile tối đa do dịch vụ cho phép (ví dụ: Netflix Premium = 4). Khác với Capacity (số khách chia sẻ).', 'vd-license-manager'); ?></p>
							<p class="description"><?php echo esc_html__('Số lượng profile tối đa', 'vd-license-manager'); ?></p>
						</td>
					</tr>


					<!-- Account Region -->
					<tr>
						<th scope="row">
							<label for="account_region"><?php echo esc_html__('Account Region', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<select name="account_region" id="account_region" class="regular-text"
									title="<?php echo esc_attr__('Vùng/quốc gia đăng ký tài khoản. Ví dụ: US (Mỹ), VN (Việt Nam), JP (Nhật). Ảnh hưởng đến nội dung có sẵn.', 'vd-license-manager'); ?>">
								<option value=""><?php echo esc_html__('Select Region', 'vd-license-manager'); ?></option>
								<option value="US">🇺🇸 United States</option>
								<option value="UK">🇬🇧 United Kingdom</option>
								<option value="VN">🇻🇳 Vietnam</option>
								<option value="JP">🇯🇵 Japan</option>
								<option value="KR">🇰🇷 Korea</option>
								<option value="SG">🇸🇬 Singapore</option>
							</select>
							<p class="description"><?php echo esc_html__('Vùng/quốc gia đăng ký tài khoản', 'vd-license-manager'); ?></p>
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
							<input type="datetime-local" name="last_password_changed" id="last_password_changed" value=""
								   title="<?php echo esc_attr__('Thời gian thay đổi mật khẩu gần nhất. Dùng để theo dõi bảo mật. Ví dụ: 2025-01-06 14:30.', 'vd-license-manager'); ?>">
							<p class="description"><?php echo esc_html__('Lần đổi password cuối cùng', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Has 2FA -->
					<tr>
						<th scope="row">
							<label for="has_2fa"><?php echo esc_html__('Two-Factor Authentication', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<label title="<?php echo esc_attr__('Đánh dấu nếu tài khoản có BẬT xác thực 2 bước (2FA). Tăng cường bảo mật nhưng phức tạp hơn khi chia sẻ.', 'vd-license-manager'); ?>">
								<input type="checkbox" name="has_2fa" id="has_2fa" value="1">
								<?php echo esc_html__('Tài khoản có bật 2FA', 'vd-license-manager'); ?>
							</label>
							<p class="description"><?php echo esc_html__('Tài khoản có bật 2FA không', 'vd-license-manager'); ?></p>
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
							<input type="number" step="0.01" name="total_revenue" id="total_revenue" class="regular-text" readonly value="0.00" style="background: #f5f5f5;"
								   title="<?php echo esc_attr__('Tổng doanh thu đã tạo ra từ tài khoản này (tự động tính từ các đơn hàng). Chỉ xem, không chỉnh sỮa.', 'vd-license-manager'); ?>">
							<p class="description"><?php echo esc_html__('Tổng doanh thu đã tạo ra (tự động tính)', 'vd-license-manager'); ?></p>
						</td>
					</tr>

					<!-- Total Licenses Served -->
					<tr>
						<th scope="row">
							<label for="total_licenses_served"><?php echo esc_html__('Total Licenses Served', 'vd-license-manager'); ?></label>
						</th>
						<td>
							<input type="number" name="total_licenses_served" id="total_licenses_served" class="small-text" readonly value="0" style="background: #f5f5f5;"
								   title="<?php echo esc_attr__('Tổng số lần đã cấp license/chia sẻ tài khoản này cho khách hàng (tự động tính). Chỉ xem, không chỉnh sỮa.', 'vd-license-manager'); ?>">
							<p class="description"><?php echo esc_html__('Tổng số licenses đã phục vụ (tự động tính)', 'vd-license-manager'); ?></p>
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
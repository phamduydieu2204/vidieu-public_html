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
			<?php wp_nonce_field('vd_add_account', 'vd_account_nonce'); ?>

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
				</tbody>
			</table>

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
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
		<div class="notice notice-info">
			<p><strong><?php _e('Sprint 2 Notice:', 'vd-license-manager'); ?></strong>
			<?php _e('Add/Edit forms will be implemented in Sprint 3. This page serves as a placeholder for now.', 'vd-license-manager'); ?></p>
		</div>

		<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
			<h2><?php _e('Add New Provider Account', 'vd-license-manager'); ?></h2>
			<p><?php _e('This form will include the following fields:', 'vd-license-manager'); ?></p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php _e('Provider', 'vd-license-manager'); ?></th>
						<td>
							<select disabled>
								<option><?php _e('Select Provider...', 'vd-license-manager'); ?></option>
								<option>🎬 Netflix</option>
								<option>🎵 Spotify</option>
								<option>📺 YouTube</option>
								<option>🏰 Disney+</option>
								<option>📽️ HBO Max</option>
								<option>📦 Amazon Prime</option>
								<option>🟢 Hulu</option>
							</select>
							<p class="description"><?php _e('Select the streaming service provider', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Display Name', 'vd-license-manager'); ?></th>
						<td>
							<input type="text" class="regular-text" disabled placeholder="<?php esc_attr_e('e.g., Premium Netflix Account', 'vd-license-manager'); ?>" />
							<p class="description"><?php _e('Friendly name for this account', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Account Login', 'vd-license-manager'); ?></th>
						<td>
							<input type="email" class="regular-text" disabled placeholder="<?php esc_attr_e('account@example.com', 'vd-license-manager'); ?>" />
							<p class="description"><?php _e('Login email for the provider account', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Account Password', 'vd-license-manager'); ?></th>
						<td>
							<input type="password" class="regular-text" disabled />
							<p class="description"><?php _e('Password for the provider account', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Capacity', 'vd-license-manager'); ?></th>
						<td>
							<input type="number" class="small-text" disabled min="1" max="100" placeholder="5" />
							<p class="description"><?php _e('Maximum number of users that can use this account', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Status', 'vd-license-manager'); ?></th>
						<td>
							<select disabled>
								<option value="active"><?php _e('Active', 'vd-license-manager'); ?></option>
								<option value="suspended"><?php _e('Suspended', 'vd-license-manager'); ?></option>
								<option value="expired"><?php _e('Expired', 'vd-license-manager'); ?></option>
							</select>
							<p class="description"><?php _e('Current status of the account', 'vd-license-manager'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" disabled value="<?php esc_attr_e('Add Account', 'vd-license-manager'); ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=vd-provider-accounts')); ?>" class="button">
					<?php _e('Cancel', 'vd-license-manager'); ?>
				</a>
			</p>
		</div>

		<div class="notice notice-warning">
			<p><strong><?php _e('Coming in Sprint 3:', 'vd-license-manager'); ?></strong></p>
			<ul>
				<li><?php _e('✅ Form validation with VD_Account_Validator', 'vd-license-manager'); ?></li>
				<li><?php _e('✅ Data processing and saving', 'vd-license-manager'); ?></li>
				<li><?php _e('✅ Success/error message handling', 'vd-license-manager'); ?></li>
				<li><?php _e('✅ AJAX form submission', 'vd-license-manager'); ?></li>
				<li><?php _e('✅ Field-specific validation feedback', 'vd-license-manager'); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render edit form
	 *
	 * @since 1.0.0
	 * @param object $account Account object
	 */
	private static function render_edit_form($account) {
		?>
		<div class="notice notice-info">
			<p><strong><?php _e('Sprint 2 Notice:', 'vd-license-manager'); ?></strong>
			<?php _e('Add/Edit forms will be implemented in Sprint 3. This page serves as a placeholder for now.', 'vd-license-manager'); ?></p>
		</div>

		<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
			<h2><?php _e('Edit Provider Account', 'vd-license-manager'); ?></h2>
			<p><?php printf(__('Editing account: <strong>%s</strong> (ID: %d)', 'vd-license-manager'), esc_html($account->display_name), $account->id); ?></p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php _e('Provider', 'vd-license-manager'); ?></th>
						<td>
							<select disabled>
								<option selected><?php echo esc_html(ucfirst($account->provider)); ?></option>
							</select>
							<p class="description"><?php _e('Provider cannot be changed after creation', 'vd-license-manager'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Display Name', 'vd-license-manager'); ?></th>
						<td>
							<input type="text" class="regular-text" disabled value="<?php echo esc_attr($account->display_name); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Account Login', 'vd-license-manager'); ?></th>
						<td>
							<input type="email" class="regular-text" disabled value="<?php echo esc_attr($account->account_login); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Capacity', 'vd-license-manager'); ?></th>
						<td>
							<input type="number" class="small-text" disabled value="<?php echo esc_attr($account->capacity); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Status', 'vd-license-manager'); ?></th>
						<td>
							<select disabled>
								<option value="active" <?php selected($account->status, 'active'); ?>><?php _e('Active', 'vd-license-manager'); ?></option>
								<option value="suspended" <?php selected($account->status, 'suspended'); ?>><?php _e('Suspended', 'vd-license-manager'); ?></option>
								<option value="expired" <?php selected($account->status, 'expired'); ?>><?php _e('Expired', 'vd-license-manager'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Created', 'vd-license-manager'); ?></th>
						<td>
							<span><?php echo esc_html($account->created_at); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Last Updated', 'vd-license-manager'); ?></th>
						<td>
							<span><?php echo esc_html($account->updated_at); ?></span>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" disabled value="<?php esc_attr_e('Update Account', 'vd-license-manager'); ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=vd-provider-accounts')); ?>" class="button">
					<?php _e('Cancel', 'vd-license-manager'); ?>
				</a>
			</p>
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
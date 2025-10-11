<?php
/**
 * Accounts Add/Edit Form Template - UPDATED WITH ALL CREDENTIAL FIELDS
 *
 * Comprehensive form for provider account management with all credential types,
 * security fields, and proper encryption indicators.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/admin/partials
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variables available from controller:
// $account - account object (null for add, populated for edit)
// $providers - array of provider options
// $form_action - 'create' or 'update'

$is_edit = ( $form_action === 'update' && $account );
$page_title = $is_edit ? __( 'Edit Account', 'vd-license-manager' ) : __( 'Add New Account', 'vd-license-manager' );
$submit_text = $is_edit ? __( 'Update Account', 'vd-license-manager' ) : __( 'Create Account', 'vd-license-manager' );

// Get current values
$current_provider = $is_edit ? $account->provider : '';
$current_account_login = $is_edit ? $account->account_login : '';
$current_display_name = $is_edit ? $account->display_name : '';
$current_capacity = $is_edit ? $account->capacity : 1;
$current_status = $is_edit ? $account->status : 'active';
$current_expires_at = $is_edit && ! empty( $account->expires_at ) && $account->expires_at !== '0000-00-00 00:00:00' ? gmdate( 'Y-m-d', strtotime( $account->expires_at ) ) : '';
$current_security_question = $is_edit ? $account->security_question : '';
$current_notes = $is_edit ? $account->notes : '';
$current_custom_fields = $is_edit ? $account->custom_fields : array();
?>

<div class="vd-account-form-wrapper">

	<!-- Page Navigation -->
	<div class="vd-page-navigation">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=vd-accounts' ) ); ?>" class="page-title-action">
			&larr; <?php esc_html_e( 'Back to Accounts', 'vd-license-manager' ); ?>
		</a>
	</div>

	<h2><?php echo esc_html( $page_title ); ?></h2>

	<form method="post" action="" class="vd-account-form" autocomplete="off">
		<?php wp_nonce_field( 'vd_lm_account_action', 'vd_lm_nonce' ); ?>
		<input type="hidden" name="action" value="<?php echo esc_attr( $form_action ); ?>">
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="account_id" value="<?php echo esc_attr( $account->id ); ?>">
		<?php endif; ?>

		<div class="vd-form-container">
			<div class="vd-form-main">
				<!-- Basic Information -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Basic Information', 'vd-license-manager' ); ?></h2>
					</div>
					<div class="inside">
						<div class="vd-form-section">
							<div class="vd-form-row">
								<label for="provider"><?php esc_html_e( 'Provider', 'vd-license-manager' ); ?> <span class="required">*</span></label>
								<input type="text" name="provider" id="provider" value="<?php echo esc_attr( $current_provider ); ?>" class="regular-text" required placeholder="e.g., Netflix, Spotify, Disney+, YouTube Premium">
								<p class="description"><?php esc_html_e( 'Service provider name (free text input)', 'vd-license-manager' ); ?></p>
							</div>

							<div class="vd-form-row">
								<label for="account_login"><?php esc_html_e( 'Account Login', 'vd-license-manager' ); ?> <span class="required">*</span></label>
								<input type="text" name="account_login" id="account_login" value="<?php echo esc_attr( $current_account_login ); ?>" class="regular-text" required placeholder="Email or username">
								<p class="description"><?php esc_html_e( 'Email address or username for the account', 'vd-license-manager' ); ?></p>
							</div>

							<div class="vd-form-row">
								<label for="display_name"><?php esc_html_e( 'Display Name', 'vd-license-manager' ); ?></label>
								<input type="text" name="display_name" id="display_name" value="<?php echo esc_attr( $current_display_name ); ?>" class="regular-text" placeholder="Friendly name for admin display (optional)">
								<p class="description"><?php esc_html_e( 'Friendly name for admin display (optional)', 'vd-license-manager' ); ?></p>
							</div>

							<div class="vd-form-row">
								<label for="account_password"><?php esc_html_e( 'Password', 'vd-license-manager' ); ?> <?php if ( ! $is_edit ) : ?><span class="required">*</span><?php endif; ?></label>
								<div class="vd-password-field">
									<input type="password" name="account_password" id="account_password" value="" class="regular-text" placeholder="<?php echo $is_edit ? 'Leave blank to keep current password' : 'Enter account password'; ?>" <?php echo $is_edit ? '' : 'required'; ?> autocomplete="new-password">
									<button type="button" class="button vd-toggle-password" data-target="account_password">
										👁️ <?php esc_html_e( 'Show', 'vd-license-manager' ); ?>
									</button>
								</div>
								<?php if ( $is_edit ) : ?>
									<p class="description"><?php esc_html_e( 'Leave blank to keep current password', 'vd-license-manager' ); ?></p>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'Account password (will be encrypted)', 'vd-license-manager' ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Account Settings -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Account Settings', 'vd-license-manager' ); ?></h2>
					</div>
					<div class="inside">
						<div class="vd-form-section">
							<div class="vd-form-row vd-grid-2">
								<div>
									<label for="capacity"><?php esc_html_e( 'Capacity', 'vd-license-manager' ); ?></label>
									<input type="number" name="capacity" id="capacity" value="<?php echo esc_attr( $current_capacity ); ?>" class="small-text" min="1" max="100" required>
									<p class="description"><?php esc_html_e( 'Maximum licenses (1-100)', 'vd-license-manager' ); ?></p>
								</div>
								<div>
									<label for="status"><?php esc_html_e( 'Status', 'vd-license-manager' ); ?></label>
									<select name="status" id="status" class="regular-text">
										<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'vd-license-manager' ); ?></option>
										<option value="inactive" <?php selected( $current_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'vd-license-manager' ); ?></option>
										<option value="suspended" <?php selected( $current_status, 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'vd-license-manager' ); ?></option>
									</select>
								</div>
							</div>

							<div class="vd-form-row">
								<label for="expires_at"><?php esc_html_e( 'Expires On', 'vd-license-manager' ); ?></label>
								<input type="date" name="expires_at" id="expires_at" value="<?php echo esc_attr( $current_expires_at ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Account expiration date (optional)', 'vd-license-manager' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Session & Authentication -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Session & Authentication', 'vd-license-manager' ); ?></h2>
						<div class="handle-actions">
							<button type="button" class="handlediv" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Session & Authentication', 'vd-license-manager' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
						</div>
					</div>
					<div class="inside">
						<div class="vd-encryption-notice">
							<p><strong>🔒 <?php esc_html_e( 'Security Notice:', 'vd-license-manager' ); ?></strong> <?php esc_html_e( 'All sensitive fields below will be automatically encrypted before storage.', 'vd-license-manager' ); ?></p>
						</div>

						<div class="vd-form-section">
							<div class="vd-form-row">
								<label for="cookies"><?php esc_html_e( 'Session Cookies', 'vd-license-manager' ); ?></label>
								<textarea name="cookies" id="cookies" rows="4" class="large-text" placeholder="Paste cookie string here (for session-based authentication)"><?php echo $is_edit && ! empty( $account->cookies ) ? '••••••••••••••••' : ''; ?></textarea>
								<p class="description"><?php esc_html_e( 'Session cookies for maintaining logged-in state', 'vd-license-manager' ); ?></p>
							</div>

							<div class="vd-form-row">
								<label for="two_factor_secret"><?php esc_html_e( '2FA Secret', 'vd-license-manager' ); ?></label>
								<div class="vd-password-field">
									<input type="password" name="two_factor_secret" id="two_factor_secret" value="" class="regular-text" placeholder="JBSWY3DPEHPK3PXP">
									<button type="button" class="button vd-toggle-password" data-target="two_factor_secret">
										👁️ <?php esc_html_e( 'Show', 'vd-license-manager' ); ?>
									</button>
								</div>
								<p class="description"><?php esc_html_e( 'Two-factor authentication secret (if applicable)', 'vd-license-manager' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Recovery Information -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Recovery Information', 'vd-license-manager' ); ?></h2>
						<div class="handle-actions">
							<button type="button" class="handlediv" aria-expanded="false">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Recovery Information', 'vd-license-manager' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
						</div>
					</div>
					<div class="inside">
						<div class="vd-form-section">
							<div class="vd-form-row vd-grid-2">
								<div>
									<label for="phone_recovery"><?php esc_html_e( 'Recovery Phone', 'vd-license-manager' ); ?></label>
									<input type="tel" name="phone_recovery" id="phone_recovery" value="" class="regular-text" placeholder="+84 xxx xxx xxx">
									<p class="description"><?php esc_html_e( 'Phone for account recovery', 'vd-license-manager' ); ?></p>
								</div>
								<div>
									<label for="email_recovery"><?php esc_html_e( 'Recovery Email', 'vd-license-manager' ); ?></label>
									<input type="email" name="email_recovery" id="email_recovery" value="" class="regular-text" placeholder="recovery@example.com">
									<p class="description"><?php esc_html_e( 'Email for account recovery', 'vd-license-manager' ); ?></p>
								</div>
							</div>

							<div class="vd-form-row">
								<label for="security_question"><?php esc_html_e( 'Security Question', 'vd-license-manager' ); ?></label>
								<input type="text" name="security_question" id="security_question" value="<?php echo esc_attr( $current_security_question ); ?>" class="large-text" placeholder="e.g., What is your mother's maiden name?">
								<p class="description"><?php esc_html_e( 'Security question text (not encrypted)', 'vd-license-manager' ); ?></p>
							</div>

							<div class="vd-form-row">
								<label for="security_answer"><?php esc_html_e( 'Security Answer', 'vd-license-manager' ); ?></label>
								<div class="vd-password-field">
									<input type="password" name="security_answer" id="security_answer" value="" class="regular-text">
									<button type="button" class="button vd-toggle-password" data-target="security_answer">
										👁️ <?php esc_html_e( 'Show', 'vd-license-manager' ); ?>
									</button>
								</div>
								<p class="description"><?php esc_html_e( 'Answer to security question (will be encrypted)', 'vd-license-manager' ); ?></p>
							</div>

							<div class="vd-form-row">
								<label for="backup_codes"><?php esc_html_e( 'Backup Codes', 'vd-license-manager' ); ?></label>
								<textarea name="backup_codes" id="backup_codes" rows="3" class="large-text" placeholder="Enter backup codes, one per line"></textarea>
								<p class="description"><?php esc_html_e( 'Account backup codes for recovery (one per line)', 'vd-license-manager' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- API Credentials -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'API Credentials (Optional)', 'vd-license-manager' ); ?></h2>
						<div class="handle-actions">
							<button type="button" class="handlediv" aria-expanded="false">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: API Credentials', 'vd-license-manager' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
						</div>
					</div>
					<div class="inside">
						<p class="description" style="margin-bottom: 15px;"><?php esc_html_e( 'For providers that support API access', 'vd-license-manager' ); ?></p>

						<div class="vd-form-section">
							<div class="vd-form-row">
								<label for="api_key"><?php esc_html_e( 'API Key', 'vd-license-manager' ); ?></label>
								<input type="text" name="api_key" id="api_key" value="" class="regular-text" placeholder="Provider API key (if applicable)">
								<p class="description"><?php esc_html_e( 'Provider API key (will be encrypted)', 'vd-license-manager' ); ?></p>
							</div>

							<div class="vd-form-row">
								<label for="secret_key"><?php esc_html_e( 'Secret Key', 'vd-license-manager' ); ?></label>
								<div class="vd-password-field">
									<input type="password" name="secret_key" id="secret_key" value="" class="regular-text">
									<button type="button" class="button vd-toggle-password" data-target="secret_key">
										👁️ <?php esc_html_e( 'Show', 'vd-license-manager' ); ?>
									</button>
								</div>
								<p class="description"><?php esc_html_e( 'Provider secret key (will be encrypted)', 'vd-license-manager' ); ?></p>
							</div>

							<div class="vd-form-row">
								<label for="api_token"><?php esc_html_e( 'API Token', 'vd-license-manager' ); ?></label>
								<textarea name="api_token" id="api_token" rows="3" class="large-text" placeholder="Bearer token or OAuth token (if applicable)"></textarea>
								<p class="description"><?php esc_html_e( 'API authentication token (will be encrypted)', 'vd-license-manager' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Custom Fields -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Custom Fields', 'vd-license-manager' ); ?></h2>
						<div class="handle-actions">
							<button type="button" class="handlediv" aria-expanded="false">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Custom Fields', 'vd-license-manager' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
						</div>
					</div>
					<div class="inside">
						<p class="description" style="margin-bottom: 15px;"><?php esc_html_e( 'Add provider-specific fields such as subscription details or special configurations.', 'vd-license-manager' ); ?></p>

						<div id="custom-fields-container">
							<?php if ( ! empty( $current_custom_fields ) && is_array( $current_custom_fields ) ) : ?>
								<?php foreach ( $current_custom_fields as $key => $value ) : ?>
									<div class="vd-custom-field-row">
										<div class="vd-grid-4">
											<input type="text" name="custom_field_key[]" placeholder="<?php esc_attr_e( 'Field Key', 'vd-license-manager' ); ?>" value="<?php echo esc_attr( $key ); ?>" class="regular-text" required>
											<input type="text" name="custom_field_label[]" placeholder="<?php esc_attr_e( 'Field Label', 'vd-license-manager' ); ?>" value="<?php echo esc_attr( ucfirst( str_replace( '_', ' ', $key ) ) ); ?>" class="regular-text" required>
											<select name="custom_field_type[]" class="regular-text">
												<option value="text">Text</option>
												<option value="email">Email</option>
												<option value="url">URL</option>
												<option value="tel">Phone</option>
												<option value="password">Password (encrypted)</option>
												<option value="textarea">Long Text</option>
											</select>
											<button type="button" class="button button-small vd-remove-field"><?php esc_html_e( 'Remove', 'vd-license-manager' ); ?></button>
										</div>
										<input type="text" name="custom_field_value[]" placeholder="<?php esc_attr_e( 'Field Value', 'vd-license-manager' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="large-text" style="margin-top: 5px;">
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<button type="button" id="add-custom-field" class="button"><?php esc_html_e( '+ Add Custom Field', 'vd-license-manager' ); ?></button>
					</div>
				</div>

				<!-- Notes -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Internal Notes', 'vd-license-manager' ); ?></h2>
					</div>
					<div class="inside">
						<div class="vd-form-section">
							<div class="vd-form-row">
								<label for="notes"><?php esc_html_e( 'Admin Notes', 'vd-license-manager' ); ?></label>
								<textarea name="notes" id="notes" rows="4" class="large-text" placeholder="Internal notes for administrative purposes (not encrypted)"><?php echo esc_textarea( $current_notes ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Private notes visible only to administrators', 'vd-license-manager' ); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Sidebar -->
			<div class="vd-form-sidebar">
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Publish', 'vd-license-manager' ); ?></h2>
					</div>
					<div class="inside">
						<div class="submitbox">
							<div class="major-publishing-actions">
								<div class="publishing-action">
									<?php submit_button( $submit_text, 'primary large', 'submit', false ); ?>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>

				<?php if ( $is_edit ) : ?>
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Account Information', 'vd-license-manager' ); ?></h2>
					</div>
					<div class="inside">
						<ul class="vd-account-info">
							<li><strong><?php esc_html_e( 'ID:', 'vd-license-manager' ); ?></strong> <?php echo esc_html( $account->id ); ?></li>
							<li><strong><?php esc_html_e( 'Created:', 'vd-license-manager' ); ?></strong> <?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $account->created_at ) ) ); ?></li>
							<li><strong><?php esc_html_e( 'Updated:', 'vd-license-manager' ); ?></strong> <?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $account->updated_at ) ) ); ?></li>
							<?php if ( isset( $account->current_usage ) ) : ?>
							<li><strong><?php esc_html_e( 'Usage:', 'vd-license-manager' ); ?></strong> <?php echo esc_html( $account->current_usage ); ?>/<?php echo esc_html( $account->capacity ); ?></li>
							<?php endif; ?>
						</ul>
					</div>
				</div>
				<?php endif; ?>

				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Security Tips', 'vd-license-manager' ); ?></h2>
					</div>
					<div class="inside">
						<ul class="vd-security-tips">
							<li>🔒 All sensitive fields are automatically encrypted</li>
							<li>👁️ Use show/hide buttons to reveal passwords</li>
							<li>📱 2FA secrets should be Base32 format</li>
							<li>🔄 Recovery info helps with account maintenance</li>
							<li>⚠️ Never share these credentials outside admin</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
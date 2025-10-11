<?php
/**
 * Accounts Add/Edit Form Template
 *
 * Unified form template for both creating new accounts and editing existing ones.
 * Includes validation, security nonces, and comprehensive field support.
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
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="provider"><?php esc_html_e( 'Provider', 'vd-license-manager' ); ?> <span class="required">*</span></label>
									</th>
									<td>
										<select name="provider" id="provider" class="regular-text" required>
											<option value=""><?php esc_html_e( 'Select Provider...', 'vd-license-manager' ); ?></option>
											<?php foreach ( $providers as $value => $label ) : ?>
												<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_provider, $value ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php esc_html_e( 'The service provider (e.g., Netflix, Spotify)', 'vd-license-manager' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="account_login"><?php esc_html_e( 'Account Login', 'vd-license-manager' ); ?> <span class="required">*</span></label>
									</th>
									<td>
										<input type="text" name="account_login" id="account_login" value="<?php echo esc_attr( $current_account_login ); ?>" class="regular-text" required>
										<p class="description"><?php esc_html_e( 'Email address or username for the account', 'vd-license-manager' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="display_name"><?php esc_html_e( 'Display Name', 'vd-license-manager' ); ?></label>
									</th>
									<td>
										<input type="text" name="display_name" id="display_name" value="<?php echo esc_attr( $current_display_name ); ?>" class="regular-text">
										<p class="description"><?php esc_html_e( 'Friendly name for admin display (optional)', 'vd-license-manager' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="account_password"><?php esc_html_e( 'Password', 'vd-license-manager' ); ?> <?php if ( ! $is_edit ) : ?><span class="required">*</span><?php endif; ?></label>
									</th>
									<td>
										<input type="password" name="account_password" id="account_password" value="" class="regular-text" <?php echo $is_edit ? '' : 'required'; ?> autocomplete="new-password">
										<?php if ( $is_edit ) : ?>
											<p class="description"><?php esc_html_e( 'Leave blank to keep current password', 'vd-license-manager' ); ?></p>
										<?php else : ?>
											<p class="description"><?php esc_html_e( 'Account password (will be encrypted)', 'vd-license-manager' ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Account Settings -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Account Settings', 'vd-license-manager' ); ?></h2>
					</div>
					<div class="inside">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="capacity"><?php esc_html_e( 'Capacity', 'vd-license-manager' ); ?></label>
									</th>
									<td>
										<input type="number" name="capacity" id="capacity" value="<?php echo esc_attr( $current_capacity ); ?>" class="small-text" min="1" max="100">
										<p class="description"><?php esc_html_e( 'Maximum number of licenses this account can support (1-100)', 'vd-license-manager' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="status"><?php esc_html_e( 'Status', 'vd-license-manager' ); ?></label>
									</th>
									<td>
										<select name="status" id="status">
											<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'vd-license-manager' ); ?></option>
											<option value="inactive" <?php selected( $current_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'vd-license-manager' ); ?></option>
											<option value="suspended" <?php selected( $current_status, 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'vd-license-manager' ); ?></option>
										</select>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="expires_at"><?php esc_html_e( 'Expires On', 'vd-license-manager' ); ?></label>
									</th>
									<td>
										<input type="date" name="expires_at" id="expires_at" value="<?php echo esc_attr( $current_expires_at ); ?>" class="regular-text">
										<p class="description"><?php esc_html_e( 'Account expiration date (optional)', 'vd-license-manager' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Sensitive Information -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Sensitive Information', 'vd-license-manager' ); ?></h2>
						<div class="handle-actions">
							<button type="button" class="handlediv" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Sensitive Information', 'vd-license-manager' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
						</div>
					</div>
					<div class="inside">
						<p class="description" style="margin-bottom: 15px;">
							<strong><?php esc_html_e( 'Security Notice:', 'vd-license-manager' ); ?></strong>
							<?php esc_html_e( 'All sensitive fields below will be automatically encrypted before storage.', 'vd-license-manager' ); ?>
						</p>

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="api_key"><?php esc_html_e( 'API Key', 'vd-license-manager' ); ?></label>
									</th>
									<td>
										<input type="text" name="api_key" id="api_key" value="<?php echo $is_edit ? '••••••••••••••••' : ''; ?>" class="regular-text" autocomplete="off">
										<p class="description"><?php esc_html_e( 'Provider API key (if applicable)', 'vd-license-manager' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="secret_key"><?php esc_html_e( 'Secret Key', 'vd-license-manager' ); ?></label>
									</th>
									<td>
										<input type="text" name="secret_key" id="secret_key" value="<?php echo $is_edit ? '••••••••••••••••' : ''; ?>" class="regular-text" autocomplete="off">
										<p class="description"><?php esc_html_e( 'Provider secret key (if applicable)', 'vd-license-manager' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="two_factor_secret"><?php esc_html_e( '2FA Secret', 'vd-license-manager' ); ?></label>
									</th>
									<td>
										<input type="text" name="two_factor_secret" id="two_factor_secret" value="<?php echo $is_edit ? '••••••••••••••••' : ''; ?>" class="regular-text" autocomplete="off">
										<p class="description"><?php esc_html_e( 'Two-factor authentication secret (if applicable)', 'vd-license-manager' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
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
					<div class="inside" style="display: none;">
						<p class="description" style="margin-bottom: 15px;">
							<?php esc_html_e( 'Add provider-specific fields such as subscription details, backup codes, or special configurations.', 'vd-license-manager' ); ?>
						</p>

						<div id="custom-fields-container">
							<?php if ( ! empty( $current_custom_fields ) && is_array( $current_custom_fields ) ) : ?>
								<?php foreach ( $current_custom_fields as $key => $value ) : ?>
									<div class="custom-field-row">
										<input type="text" name="custom_fields[<?php echo esc_attr( $key ); ?>]" placeholder="<?php esc_attr_e( 'Field Name', 'vd-license-manager' ); ?>" value="<?php echo esc_attr( $key ); ?>" class="custom-field-key">
										<input type="text" name="custom_fields[<?php echo esc_attr( $key ); ?>]" placeholder="<?php esc_attr_e( 'Field Value', 'vd-license-manager' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="custom-field-value">
										<button type="button" class="button remove-custom-field"><?php esc_html_e( 'Remove', 'vd-license-manager' ); ?></button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<button type="button" id="add-custom-field" class="button"><?php esc_html_e( 'Add Custom Field', 'vd-license-manager' ); ?></button>
					</div>
				</div>

				<!-- Notes -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( 'Notes', 'vd-license-manager' ); ?></h2>
					</div>
					<div class="inside">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="notes"><?php esc_html_e( 'Internal Notes', 'vd-license-manager' ); ?></label>
									</th>
									<td>
										<textarea name="notes" id="notes" rows="4" class="large-text"><?php echo esc_textarea( $current_notes ); ?></textarea>
										<p class="description"><?php esc_html_e( 'Internal notes for administrative purposes (not encrypted)', 'vd-license-manager' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
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
						<ul class="account-info">
							<li><strong><?php esc_html_e( 'ID:', 'vd-license-manager' ); ?></strong> <?php echo esc_html( $account->id ); ?></li>
							<li><strong><?php esc_html_e( 'Created:', 'vd-license-manager' ); ?></strong> <?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $account->created_at ) ) ); ?></li>
							<li><strong><?php esc_html_e( 'Updated:', 'vd-license-manager' ); ?></strong> <?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $account->updated_at ) ) ); ?></li>
						</ul>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</form>
</div>

<style>
.vd-account-form-wrapper {
	max-width: 1200px;
}

.vd-form-container {
	display: flex;
	gap: 20px;
}

.vd-form-main {
	flex: 1;
}

.vd-form-sidebar {
	width: 280px;
	flex-shrink: 0;
}

.vd-page-navigation {
	margin-bottom: 10px;
}

.required {
	color: #d63638;
}

.custom-field-row {
	display: flex;
	gap: 10px;
	margin-bottom: 10px;
	align-items: center;
}

.custom-field-key,
.custom-field-value {
	flex: 1;
}

.account-info {
	margin: 0;
	padding: 0;
	list-style: none;
}

.account-info li {
	margin-bottom: 8px;
	padding-bottom: 8px;
	border-bottom: 1px solid #f0f0f1;
}

.account-info li:last-child {
	border-bottom: none;
	margin-bottom: 0;
	padding-bottom: 0;
}

@media (max-width: 850px) {
	.vd-form-container {
		flex-direction: column;
	}

	.vd-form-sidebar {
		width: 100%;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Handle collapsible postboxes
	$('.postbox .handlediv').on('click', function() {
		var $postbox = $(this).closest('.postbox');
		var $inside = $postbox.find('.inside');
		var $button = $(this);

		$inside.toggle();
		$button.attr('aria-expanded', $inside.is(':visible'));
	});

	// Auto-fill display name from account login
	$('#account_login').on('blur', function() {
		var accountLogin = $(this).val();
		var displayName = $('#display_name').val();

		if (accountLogin && !displayName) {
			$('#display_name').val(accountLogin);
		}
	});

	// Custom fields management
	var customFieldIndex = <?php echo count( $current_custom_fields ); ?>;

	$('#add-custom-field').on('click', function() {
		var fieldHtml = '<div class="custom-field-row">' +
			'<input type="text" name="custom_field_keys[]" placeholder="<?php esc_attr_e( 'Field Name', 'vd-license-manager' ); ?>" class="custom-field-key">' +
			'<input type="text" name="custom_field_values[]" placeholder="<?php esc_attr_e( 'Field Value', 'vd-license-manager' ); ?>" class="custom-field-value">' +
			'<button type="button" class="button remove-custom-field"><?php esc_html_e( 'Remove', 'vd-license-manager' ); ?></button>' +
			'</div>';

		$('#custom-fields-container').append(fieldHtml);
		customFieldIndex++;
	});

	// Remove custom field
	$(document).on('click', '.remove-custom-field', function() {
		$(this).closest('.custom-field-row').remove();
	});

	// Form validation
	$('.vd-account-form').on('submit', function(e) {
		var provider = $('#provider').val();
		var accountLogin = $('#account_login').val();
		var capacity = parseInt($('#capacity').val());

		if (!provider) {
			alert('<?php echo esc_js( __( 'Please select a provider.', 'vd-license-manager' ) ); ?>');
			e.preventDefault();
			$('#provider').focus();
			return false;
		}

		if (!accountLogin) {
			alert('<?php echo esc_js( __( 'Please enter an account login.', 'vd-license-manager' ) ); ?>');
			e.preventDefault();
			$('#account_login').focus();
			return false;
		}

		if (capacity < 1 || capacity > 100) {
			alert('<?php echo esc_js( __( 'Capacity must be between 1 and 100.', 'vd-license-manager' ) ); ?>');
			e.preventDefault();
			$('#capacity').focus();
			return false;
		}

		<?php if ( ! $is_edit ) : ?>
		var password = $('#account_password').val();
		if (!password) {
			alert('<?php echo esc_js( __( 'Please enter a password.', 'vd-license-manager' ) ); ?>');
			e.preventDefault();
			$('#account_password').focus();
			return false;
		}
		<?php endif; ?>
	});

	// Show placeholder for sensitive fields in edit mode
	<?php if ( $is_edit ) : ?>
	$('#api_key, #secret_key, #two_factor_secret').on('focus', function() {
		if ($(this).val() === '••••••••••••••••') {
			$(this).val('');
		}
	});

	$('#api_key, #secret_key, #two_factor_secret').on('blur', function() {
		if ($(this).val() === '') {
			$(this).val('••••••••••••••••');
		}
	});
	<?php endif; ?>
});
</script>
<?php
/**
 * Account View Template
 *
 * Displays detailed view of a single account with all data including decrypted passwords.
 *
 * @package    VD_License_Manager
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Verify we have account data
if ( ! isset( $account ) || ! $account ) {
	echo '<div class="error"><p>' . __( 'Account not found.', 'vd-license-manager' ) . '</p></div>';
	return;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( sprintf( __( 'View Account: %s', 'vd-license-manager' ), $account->display_name ?: $account->account_login ) ); ?></h1>

	<div class="card">
		<h2><?php _e( 'Account Details', 'vd-license-manager' ); ?></h2>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'ID', 'vd-license-manager' ); ?></th>
					<td><?php echo esc_html( $account->id ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Provider', 'vd-license-manager' ); ?></th>
					<td>
						<strong><?php echo esc_html( $account->provider ); ?></strong>
						<?php if ( $account->provider_url ): ?>
							<br><a href="<?php echo esc_url( $account->provider_url ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( $account->provider_url ); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Account Login', 'vd-license-manager' ); ?></th>
					<td>
						<code><?php echo esc_html( $account->account_login ); ?></code>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Display Name', 'vd-license-manager' ); ?></th>
					<td><?php echo esc_html( $account->display_name ?: __( '(Not set)', 'vd-license-manager' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Status', 'vd-license-manager' ); ?></th>
					<td>
						<span class="status-badge status-<?php echo esc_attr( $account->status ); ?>">
							<?php echo esc_html( ucfirst( $account->status ) ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Capacity', 'vd-license-manager' ); ?></th>
					<td><?php echo esc_html( $account->capacity ); ?> <?php _e( 'licenses', 'vd-license-manager' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<?php if ( ! empty( $account->account_password ) ): ?>
	<div class="card">
		<h2><?php _e( 'Credentials', 'vd-license-manager' ); ?></h2>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'Password', 'vd-license-manager' ); ?></th>
					<td>
						<div class="password-field">
							<input type="password" value="<?php echo esc_attr( $account->account_password ); ?>" readonly class="regular-text password-input" />
							<button type="button" class="button password-toggle" data-toggle-target=".password-input">
								<span class="dashicons dashicons-visibility"></span>
								<?php _e( 'Show', 'vd-license-manager' ); ?>
							</button>
						</div>
						<p class="description"><?php _e( 'Decrypted password for account access.', 'vd-license-manager' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $account->custom_fields ) ): ?>
	<div class="card">
		<h2><?php _e( 'Custom Fields', 'vd-license-manager' ); ?></h2>
		<table class="form-table">
			<tbody>
				<?php foreach ( $account->custom_fields as $field_name => $field_value ): ?>
				<tr>
					<th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $field_name ) ) ); ?></th>
					<td>
						<?php if ( strpos( strtolower( $field_name ), 'password' ) !== false || strpos( strtolower( $field_name ), 'secret' ) !== false || strpos( strtolower( $field_name ), 'key' ) !== false ): ?>
							<!-- Sensitive field with toggle -->
							<div class="password-field">
								<input type="password" value="<?php echo esc_attr( $field_value ); ?>" readonly class="regular-text password-input-<?php echo esc_attr( $field_name ); ?>" />
								<button type="button" class="button password-toggle" data-toggle-target=".password-input-<?php echo esc_attr( $field_name ); ?>">
									<span class="dashicons dashicons-visibility"></span>
									<?php _e( 'Show', 'vd-license-manager' ); ?>
								</button>
							</div>
						<?php elseif ( filter_var( $field_value, FILTER_VALIDATE_URL ) ): ?>
							<!-- URL field -->
							<a href="<?php echo esc_url( $field_value ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( $field_value ); ?>
							</a>
						<?php elseif ( filter_var( $field_value, FILTER_VALIDATE_EMAIL ) ): ?>
							<!-- Email field -->
							<a href="mailto:<?php echo esc_attr( $field_value ); ?>">
								<?php echo esc_html( $field_value ); ?>
							</a>
						<?php else: ?>
							<!-- Regular field -->
							<code><?php echo esc_html( $field_value ); ?></code>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<div class="card">
		<h2><?php _e( 'Metadata', 'vd-license-manager' ); ?></h2>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'Created', 'vd-license-manager' ); ?></th>
					<td>
						<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $account->created_at ) ) ); ?>
						<br><small class="description"><?php echo esc_html( human_time_diff( strtotime( $account->created_at ) ) ); ?> <?php _e( 'ago', 'vd-license-manager' ); ?></small>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Last Updated', 'vd-license-manager' ); ?></th>
					<td>
						<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $account->updated_at ) ) ); ?>
						<br><small class="description"><?php echo esc_html( human_time_diff( strtotime( $account->updated_at ) ) ); ?> <?php _e( 'ago', 'vd-license-manager' ); ?></small>
					</td>
				</tr>
				<?php if ( ! empty( $account->expires_at ) && $account->expires_at !== '0000-00-00 00:00:00' ): ?>
				<tr>
					<th scope="row"><?php _e( 'Expires', 'vd-license-manager' ); ?></th>
					<td>
						<?php
						$expires_timestamp = strtotime( $account->expires_at );
						$is_expired = $expires_timestamp < time();
						?>
						<span class="<?php echo $is_expired ? 'expired' : 'active'; ?>">
							<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $expires_timestamp ) ); ?>
						</span>
						<?php if ( $is_expired ): ?>
							<br><small class="description error"><?php _e( 'This account has expired.', 'vd-license-manager' ); ?></small>
						<?php else: ?>
							<br><small class="description"><?php echo esc_html( human_time_diff( $expires_timestamp ) ); ?> <?php _e( 'remaining', 'vd-license-manager' ); ?></small>
						<?php endif; ?>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="card">
		<h2><?php _e( 'Actions', 'vd-license-manager' ); ?></h2>
		<p class="submit">
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'vd-accounts', 'action' => 'edit', 'id' => $account->id ), admin_url( 'admin.php' ) ) ); ?>" class="button button-primary">
				<?php _e( 'Edit Account', 'vd-license-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'vd-accounts' ), admin_url( 'admin.php' ) ) ); ?>" class="button">
				<?php _e( 'Back to List', 'vd-license-manager' ); ?>
			</a>
			<a href="#" class="button button-secondary" onclick="if(confirm('<?php esc_attr_e( 'Are you sure you want to delete this account?', 'vd-license-manager' ); ?>')) { window.location.href='<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'vd-accounts', 'action' => 'delete', 'id' => $account->id ), admin_url( 'admin.php' ) ), 'vd_lm_account_action' ) ); ?>'; } return false;">
				<?php _e( 'Delete Account', 'vd-license-manager' ); ?>
			</a>
		</p>
	</div>
</div>

<style>
.status-badge {
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: bold;
	text-transform: uppercase;
}
.status-active { background: #46b450; color: white; }
.status-inactive { background: #dc3232; color: white; }
.status-expired { background: #ffb900; color: #000; }

.password-field {
	display: flex;
	align-items: center;
	gap: 10px;
}
.password-field .password-input {
	font-family: monospace;
}

.expired { color: #dc3232; font-weight: bold; }
.active { color: #46b450; font-weight: bold; }

.card {
	margin-bottom: 20px;
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
}
.card h2 {
	margin-top: 0;
	border-bottom: 1px solid #eee;
	padding-bottom: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Password toggle functionality
	$('.password-toggle').on('click', function() {
		var button = $(this);
		var target = $(button.data('toggle-target'));
		var icon = button.find('.dashicons');
		var text = button.contents().filter(function() {
			return this.nodeType === 3; // Text nodes only
		});

		if (target.attr('type') === 'password') {
			target.attr('type', 'text');
			icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
			text[0].textContent = '<?php _e( 'Hide', 'vd-license-manager' ); ?>';
		} else {
			target.attr('type', 'password');
			icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
			text[0].textContent = '<?php _e( 'Show', 'vd-license-manager' ); ?>';
		}
	});
});
</script>
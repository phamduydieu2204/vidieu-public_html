<?php
/**
 * Accounts List Template
 *
 * Displays provider accounts in a table format with filters, search,
 * pagination, and bulk actions.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/admin/partials
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if account has legacy encrypted data (date-based detection)
 *
 * Accounts created before the encryption fix (2025-10-12 04:00:00) that have
 * encrypted data are considered legacy and may need credential re-entry.
 */
function vd_has_legacy_encryption($account) {
	// Encryption fix date - accounts created before this may have legacy encryption
	$encryption_fix_date = '2025-10-12 04:00:00';

	// Check if account was created before encryption fix
	if (isset($account->created_at)) {
		$created = strtotime($account->created_at);
		$fix_time = strtotime($encryption_fix_date);

		// Only accounts created before the fix can have legacy encryption
		if ($created < $fix_time) {
			// Check if account has any encrypted data
			$encrypted_fields = array(
				'account_password', 'cookies', 'phone_recovery',
				'email_recovery', 'security_answer', 'backup_codes',
				'two_factor_secret', 'api_key', 'secret_key', 'api_token'
			);

			foreach ($encrypted_fields as $field) {
				if (!empty($account->$field)) {
					// Has encrypted data and was created before fix = legacy
					return true;
				}
			}
		}
	}

	return false;
}

// Variables available from controller:
// $accounts - array of account objects
// $total_accounts - total number of accounts
// $total_pages - total pagination pages
// $current_page - current page number
// $per_page - items per page
// $providers - array of unique providers

$current_provider = isset( $_GET['provider'] ) ? sanitize_text_field( $_GET['provider'] ) : '';
$current_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'created_at';
$order = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';
?>

<div class="vd-accounts-list-wrapper">

	<!-- Filters and Search -->
	<div class="tablenav top">
		<div class="alignleft actions">
			<form method="get" action="">
				<input type="hidden" name="page" value="vd-accounts">

				<label for="filter-by-provider" class="screen-reader-text"><?php esc_html_e( 'Filter by provider', 'vd-license-manager' ); ?></label>
				<select name="provider" id="filter-by-provider">
					<option value=""><?php esc_html_e( 'All Providers', 'vd-license-manager' ); ?></option>
					<?php foreach ( $providers as $provider ) : ?>
						<option value="<?php echo esc_attr( $provider ); ?>" <?php selected( $current_provider, $provider ); ?>>
							<?php echo esc_html( $provider ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="filter-by-status" class="screen-reader-text"><?php esc_html_e( 'Filter by status', 'vd-license-manager' ); ?></label>
				<select name="status" id="filter-by-status">
					<option value=""><?php esc_html_e( 'All Statuses', 'vd-license-manager' ); ?></option>
					<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'vd-license-manager' ); ?></option>
					<option value="inactive" <?php selected( $current_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'vd-license-manager' ); ?></option>
					<option value="suspended" <?php selected( $current_status, 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'vd-license-manager' ); ?></option>
				</select>

				<?php submit_button( __( 'Filter', 'vd-license-manager' ), 'secondary', 'filter_action', false ); ?>
			</form>
		</div>

		<div class="alignleft actions">
			<!-- Bulk actions moved inside main form below -->
		</div>

		<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				/* translators: %s: number of items */
				echo esc_html( sprintf( _n( '%s item', '%s items', $total_accounts, 'vd-license-manager' ), number_format_i18n( $total_accounts ) ) );
				?>
			</span>
			<?php
			echo paginate_links( array(
				'base' => add_query_arg( 'paged', '%#%' ),
				'format' => '',
				'prev_text' => __( '&laquo;' ),
				'next_text' => __( '&raquo;' ),
				'total' => $total_pages,
				'current' => $this->current_page,
				'type' => 'plain',
			) );
			?>
		</div>
		<?php endif; ?>
	</div>

	<!-- Accounts Table -->
	<form method="post" id="accounts-filter">
		<?php wp_nonce_field( 'vd_lm_account_action', 'vd_lm_nonce' ); ?>

		<!-- Bulk Actions moved here to be inside same form as checkboxes -->
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<select name="bulk_action" id="bulk-action-selector-top">
					<option value="-1"><?php esc_html_e( 'Bulk Actions', 'vd-license-manager' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'vd-license-manager' ); ?></option>
				</select>

				<?php submit_button( __( 'Apply', 'vd-license-manager' ), 'secondary action', 'bulk_action_submit', false, array( 'id' => 'doaction' ) ); ?>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped accounts">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'vd-license-manager' ); ?></label>
						<input id="cb-select-all-1" type="checkbox">
					</td>
					<th scope="col" class="manage-column column-provider <?php echo ( $orderby === 'provider' ) ? 'sorted ' . strtolower( $order ) : 'sortable desc'; ?>">
						<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'provider', 'order' => ( $orderby === 'provider' && $order === 'ASC' ) ? 'DESC' : 'ASC' ) ) ); ?>">
							<span><?php esc_html_e( 'Provider', 'vd-license-manager' ); ?></span>
							<span class="sorting-indicator"></span>
						</a>
					</th>
					<th scope="col" class="manage-column column-account-login <?php echo ( $orderby === 'account_login' ) ? 'sorted ' . strtolower( $order ) : 'sortable desc'; ?>">
						<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'account_login', 'order' => ( $orderby === 'account_login' && $order === 'ASC' ) ? 'DESC' : 'ASC' ) ) ); ?>">
							<span><?php esc_html_e( 'Account Login', 'vd-license-manager' ); ?></span>
							<span class="sorting-indicator"></span>
						</a>
					</th>
					<th scope="col" class="manage-column column-display-name"><?php esc_html_e( 'Display Name', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-pools"><?php esc_html_e( 'Pools', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-capacity"><?php esc_html_e( 'Capacity', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-status <?php echo ( $orderby === 'status' ) ? 'sorted ' . strtolower( $order ) : 'sortable desc'; ?>">
						<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'status', 'order' => ( $orderby === 'status' && $order === 'ASC' ) ? 'DESC' : 'ASC' ) ) ); ?>">
							<span><?php esc_html_e( 'Status', 'vd-license-manager' ); ?></span>
							<span class="sorting-indicator"></span>
						</a>
					</th>
					<th scope="col" class="manage-column column-expires"><?php esc_html_e( 'Expires', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-created <?php echo ( $orderby === 'created_at' ) ? 'sorted ' . strtolower( $order ) : 'sortable desc'; ?>">
						<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'created_at', 'order' => ( $orderby === 'created_at' && $order === 'ASC' ) ? 'DESC' : 'ASC' ) ) ); ?>">
							<span><?php esc_html_e( 'Created', 'vd-license-manager' ); ?></span>
							<span class="sorting-indicator"></span>
						</a>
					</th>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php if ( empty( $accounts ) ) : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="9">
							<?php esc_html_e( 'No accounts found.', 'vd-license-manager' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $accounts as $account ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $account->id ); ?>">
									<?php
									/* translators: %s: account display name */
									echo esc_html( sprintf( __( 'Select %s', 'vd-license-manager' ), $account->display_name ) );
									?>
								</label>
								<input id="cb-select-<?php echo esc_attr( $account->id ); ?>" type="checkbox" name="account_ids[]" value="<?php echo esc_attr( $account->id ); ?>">
							</th>

							<td class="provider column-provider">
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=vd-accounts&action=edit&id=' . $account->id ) ); ?>" class="row-title">
										<?php echo esc_html( $account->provider ); ?>
									</a>
									<?php if (vd_has_legacy_encryption($account)): ?>
										<span class="vd-legacy-badge"
											  title="This account has old encrypted data that cannot be decrypted. Please edit and re-enter credentials."
											  style="background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 5px;">
											⚠️ Legacy
										</span>
									<?php endif; ?>
								</strong>

								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=vd-accounts&action=edit&id=' . $account->id ) ); ?>" aria-label="<?php esc_attr_e( 'Edit this account', 'vd-license-manager' ); ?>">
											<?php esc_html_e( 'Edit', 'vd-license-manager' ); ?>
										</a> |
									</span>
									<span class="view">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=vd-accounts&action=view&id=' . $account->id ) ); ?>" aria-label="<?php esc_attr_e( 'View this account', 'vd-license-manager' ); ?>">
											<?php esc_html_e( 'View', 'vd-license-manager' ); ?>
										</a> |
									</span>
									<span class="trash">
										<?php
										// Generate delete URL with nonce
										$delete_url = wp_nonce_url(
											admin_url( 'admin.php?page=vd-accounts&action=delete&id=' . $account->id ),
											'delete-account-' . $account->id
										);
										?>
										<a href="<?php echo esc_url( $delete_url ); ?>"
										   class="delete"
										   onclick="return confirm('<?php echo esc_js( sprintf( __( 'Are you sure you want to delete the account: %s?', 'vd-license-manager' ), $account->display_name ) ); ?>');"
										   aria-label="<?php esc_attr_e( 'Delete this account', 'vd-license-manager' ); ?>">
											<?php esc_html_e( 'Delete', 'vd-license-manager' ); ?>
										</a>
									</span>
								</div>
							</td>

							<td class="account-login column-account-login">
								<?php echo esc_html( $account->account_login ); ?>
							</td>

							<td class="display-name column-display-name">
								<?php echo esc_html( $account->display_name ); ?>
							</td>

							<td class="pools column-pools">
								<?php
								// Get pools for this account
								global $wpdb;
								$pools_table = $wpdb->prefix . 'vd_pool_accounts';
								$pools_info_table = $wpdb->prefix . 'vd_pools';

								$pools = $wpdb->get_results($wpdb->prepare(
									"SELECT p.name, p.id, pa.weight, pa.is_primary
									 FROM {$pools_table} pa
									 LEFT JOIN {$pools_info_table} p ON pa.pool_id = p.id
									 WHERE pa.account_id = %d
									 ORDER BY pa.is_primary DESC, pa.weight DESC",
									$account->id
								));

								if (!empty($pools)) {
									$pool_display = array();
									foreach ($pools as $pool) {
										$pool_text = esc_html($pool->name);
										if ($pool->is_primary) {
											$pool_text = '<strong>' . $pool_text . '</strong> <span class="primary-badge">Primary</span>';
										}
										if ($pool->weight > 1) {
											$pool_text .= ' <span class="weight-badge">W:' . $pool->weight . '</span>';
										}
										$pool_display[] = $pool_text;
									}
									echo implode('<br>', $pool_display);
								} else {
									echo '<span class="no-pools" style="color: #d63638; font-style: italic;">' . esc_html__('Not assigned', 'vd-license-manager') . '</span>';
								}
								?>
							</td>

							<td class="capacity column-capacity">
								<?php echo esc_html( $account->capacity ); ?>
							</td>

							<td class="status column-status">
								<span class="status-badge status-<?php echo esc_attr( $account->status ); ?>">
									<?php echo esc_html( ucfirst( $account->status ) ); ?>
								</span>
							</td>

							<td class="expires column-expires">
								<?php
								if ( ! empty( $account->expires_at ) && $account->expires_at !== '0000-00-00 00:00:00' ) {
									$expires_timestamp = strtotime( $account->expires_at );
									$current_timestamp = current_time( 'timestamp' );

									if ( $expires_timestamp < $current_timestamp ) {
										echo '<span class="expired">' . esc_html__( 'Expired', 'vd-license-manager' ) . '</span>';
									} else {
										echo '<span class="expires-date">' . esc_html( wp_date( 'M j, Y', $expires_timestamp ) ) . '</span>';
									}
								} else {
									echo '<span class="no-expiry">' . esc_html__( 'Never', 'vd-license-manager' ) . '</span>';
								}
								?>
							</td>

							<td class="created column-created">
								<?php echo esc_html( wp_date( 'M j, Y', strtotime( $account->created_at ) ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>

			<tfoot>
				<tr>
					<td class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e( 'Select All', 'vd-license-manager' ); ?></label>
						<input id="cb-select-all-2" type="checkbox">
					</td>
					<th scope="col" class="manage-column column-provider"><?php esc_html_e( 'Provider', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-account-login"><?php esc_html_e( 'Account Login', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-display-name"><?php esc_html_e( 'Display Name', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-capacity"><?php esc_html_e( 'Capacity', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-expires"><?php esc_html_e( 'Expires', 'vd-license-manager' ); ?></th>
					<th scope="col" class="manage-column column-created"><?php esc_html_e( 'Created', 'vd-license-manager' ); ?></th>
				</tr>
			</tfoot>
		</table>
	</form>

	<!-- Bottom Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				/* translators: %s: number of items */
				echo esc_html( sprintf( _n( '%s item', '%s items', $total_accounts, 'vd-license-manager' ), number_format_i18n( $total_accounts ) ) );
				?>
			</span>
			<?php
			echo paginate_links( array(
				'base' => add_query_arg( 'paged', '%#%' ),
				'format' => '',
				'prev_text' => __( '&laquo;' ),
				'next_text' => __( '&raquo;' ),
				'total' => $total_pages,
				'current' => $this->current_page,
				'type' => 'plain',
			) );
			?>
		</div>
	</div>
	<?php endif; ?>
</div>

<!-- Delete Confirmation Modal (will be added via JavaScript) -->
<!-- Delete forms removed - using GET links with nonces now -->

<style>
.status-badge {
	padding: 2px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: bold;
	text-transform: uppercase;
}

.status-active {
	background-color: #dff0d8;
	color: #3c763d;
}

.status-inactive {
	background-color: #f2dede;
	color: #a94442;
}

.status-suspended {
	background-color: #fcf8e3;
	color: #8a6d3b;
}

.expired {
	color: #a94442;
	font-weight: bold;
}

.expires-date {
	color: #555;
}

.no-expiry {
	color: #999;
	font-style: italic;
}

.vd-accounts-list-wrapper .tablenav {
	margin-bottom: 10px;
}

.vd-accounts-list-wrapper .tablenav .actions {
	margin-right: 20px;
}

.vd-accounts-list-wrapper .tablenav .actions form {
	display: inline;
}

.vd-accounts-list-wrapper .tablenav .actions select {
	margin-right: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Handle select all checkboxes
	$('#cb-select-all-1, #cb-select-all-2').on('change', function() {
		var checked = $(this).prop('checked');
		$('input[name="account_ids[]"]').prop('checked', checked);
		$('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
	});

	// Handle individual checkboxes
	$('input[name="account_ids[]"]').on('change', function() {
		var total = $('input[name="account_ids[]"]').length;
		var checked = $('input[name="account_ids[]"]:checked').length;

		$('#cb-select-all-1, #cb-select-all-2').prop('checked', total === checked);
	});

	// Delete account links now use GET with nonce - no JavaScript needed

	// Handle bulk actions
	$('#doaction').on('click', function(e) {
		var action = $('#bulk-action-selector-top').val();
		var selected = $('input[name="account_ids[]"]:checked').length;

		if (action === '-1') {
			e.preventDefault();
			alert('<?php echo esc_js( __( 'Please select an action.', 'vd-license-manager' ) ); ?>');
			return;
		}

		if (selected === 0) {
			e.preventDefault();
			alert('<?php echo esc_js( __( 'Please select at least one account.', 'vd-license-manager' ) ); ?>');
			return;
		}

		if (action === 'delete') {
			if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete the selected accounts?', 'vd-license-manager' ) ); ?>')) {
				e.preventDefault();
				return;
			}
		}
	});
});
</script>
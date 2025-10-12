<?php
/**
 * Accounts Admin Page Controller
 *
 * Handles all Provider Account CRUD operations in WordPress admin.
 * Manages HTTP requests, form processing, and view rendering.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accounts Admin Page Controller Class
 *
 * Provides complete CRUD interface for provider accounts with
 * proper security, validation, and user experience.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/admin
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Accounts_Page {

	/**
	 * Account service instance
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    VD_LM_Account_Service $service
	 */
	private $service;

	/**
	 * Current page number for pagination
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int $current_page
	 */
	private $current_page = 1;

	/**
	 * Items per page
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int $per_page
	 */
	private $per_page = 20;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Load required services
		if ( ! class_exists( 'VD_LM_Account_Service' ) ) {
			require_once VD_PLUGIN_DIR . 'includes/services/class-vd-lm-account-service.php';
		}

		$this->service = new VD_LM_Account_Service();
		$this->current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		// Check and fix database schema on accounts page access
		add_action( 'admin_init', array( $this, 'check_database_schema' ) );
	}

	/**
	 * Main render method
	 *
	 * @since 1.0.0
	 */
	public function render() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'vd-license-manager' ) );
		}

		// Handle POST actions (create, update, delete)
		$this->handle_actions();

		// Display admin notices
		settings_errors( 'vd_lm_accounts' );

		// Determine which view to show based on GET action
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Provider Accounts', 'vd-license-manager' ) . '</h1>';

		switch ( $action ) {
			case 'add':
				$this->render_add_form();
				break;
			case 'edit':
				$this->render_edit_form();
				break;
			case 'view':
				$this->render_view();
				break;
			default:
				echo '<a href="' . esc_url( admin_url( 'admin.php?page=vd-accounts&action=add' ) ) . '" class="page-title-action">' . esc_html__( 'Add New', 'vd-license-manager' ) . '</a>';
				$this->render_list();
				break;
		}

		echo '</div>'; // .wrap
	}

	/**
	 * Handle POST actions
	 *
	 * @since 1.0.0
	 */
	private function handle_actions() {
		// Debug ALL request data
		error_log( 'VD Actions: POST data: ' . print_r( $_POST, true ) );
		error_log( 'VD Actions: GET data: ' . print_r( $_GET, true ) );

		// Handle GET requests (individual actions like edit, delete, view)
		if ( $_SERVER['REQUEST_METHOD'] === 'GET' && isset( $_GET['action'] ) ) {
			switch ( $_GET['action'] ) {
				case 'delete':
					error_log( 'VD Actions: Individual delete, ID: ' . ( $_GET['id'] ?? 'MISSING' ) );
					$this->handle_delete();
					break;
				case 'edit':
					$this->render_edit_form();
					break;
				case 'view':
					$this->render_view();
					break;
			}
			return;
		}

		// Handle POST requests
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}

		// Check for bulk actions - WordPress uses different field names
		$action = '';

		// Method 1: Standard WordPress bulk actions (action/action2)
		if ( ! empty( $_POST['action'] ) && $_POST['action'] !== '-1' ) {
			$action = sanitize_text_field( $_POST['action'] );
		} elseif ( ! empty( $_POST['action2'] ) && $_POST['action2'] !== '-1' ) {
			$action = sanitize_text_field( $_POST['action2'] );
		}
		// Method 2: Custom bulk action field (your form uses this)
		elseif ( ! empty( $_POST['bulk_action'] ) ) {
			$action = sanitize_text_field( $_POST['bulk_action'] );
		}

		error_log( 'VD Actions: Detected action: ' . $action );

		// Handle bulk delete
		if ( $action === 'delete' ) {
			error_log( 'VD Actions: Calling handle_bulk_delete()' );
			$this->handle_bulk_delete();
			return;
		}

		// Handle individual form actions (uses custom nonce)
		if ( ! isset( $_POST['vd_lm_nonce'] ) || ! wp_verify_nonce( $_POST['vd_lm_nonce'], 'vd_lm_account_action' ) ) {
			$this->add_notice( __( 'Security check failed. Please try again.', 'vd-license-manager' ), 'error' );
			return;
		}

		$form_action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';

		switch ( $form_action ) {
			case 'create':
				$this->handle_create();
				break;
			case 'update':
				$this->handle_update();
				break;
		}
	}

	/**
	 * Handle account creation with form data persistence
	 *
	 * @since 1.0.0
	 */
	private function handle_create() {
		// Start session if not started
		if ( ! session_id() ) {
			session_start();
		}

		try {
			// Sanitize input
			$data = $this->sanitize_account_data( $_POST );

			// Validate data
			$errors = $this->validate_account_data( $data );

			if ( ! empty( $errors ) ) {
				// Save form data to session
				$_SESSION['vd_form_data'] = $_POST;
				$_SESSION['vd_form_errors'] = $errors;

				// Redirect back to form
				wp_safe_redirect( add_query_arg( array(
					'page' => 'vd-accounts',
					'action' => 'add',
					'error' => 'validation_failed'
				), admin_url( 'admin.php' ) ) );
				exit;
			}

			// Create account
			$result = $this->service->create_account( $data );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			// Success - clear session and redirect
			unset( $_SESSION['vd_form_data'] );
			unset( $_SESSION['vd_form_errors'] );

			$this->add_notice( __( 'Account created successfully!', 'vd-license-manager' ), 'success' );
			wp_safe_redirect( admin_url( 'admin.php?page=vd-accounts' ) );
			exit;

		} catch ( Exception $e ) {
			// Save form data to session
			$_SESSION['vd_form_data'] = $_POST;
			$_SESSION['vd_form_errors'] = array(
				'_global' => $e->getMessage()
			);

			// Redirect back to form
			wp_safe_redirect( add_query_arg( array(
				'page' => 'vd-accounts',
				'action' => 'add',
				'error' => 'create_failed'
			), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Handle account update with form data persistence
	 *
	 * @since 1.0.0
	 */
	private function handle_update() {
		// Start session if not started
		if ( ! session_id() ) {
			session_start();
		}

		$id = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;

		if ( ! $id ) {
			$_SESSION['vd_form_errors'] = array(
				'_global' => __( 'Invalid account ID.', 'vd-license-manager' )
			);
			wp_safe_redirect( add_query_arg( array(
				'page' => 'vd-accounts',
				'action' => 'edit',
				'id' => $id,
				'error' => 'invalid_id'
			), admin_url( 'admin.php' ) ) );
			exit;
		}

		try {
			// Sanitize input
			$data = $this->sanitize_account_data( $_POST );

			// Remove empty password field (don't update if blank)
			if ( empty( $data['account_password'] ) ) {
				unset( $data['account_password'] );
			}

			// Validate data
			$errors = $this->validate_account_data( $data, true ); // true = edit mode

			if ( ! empty( $errors ) ) {
				// Save form data to session
				$_SESSION['vd_form_data'] = $_POST;
				$_SESSION['vd_form_errors'] = $errors;

				// Redirect back to form
				wp_safe_redirect( add_query_arg( array(
					'page' => 'vd-accounts',
					'action' => 'edit',
					'id' => $id,
					'error' => 'validation_failed'
				), admin_url( 'admin.php' ) ) );
				exit;
			}

			// Update account
			$result = $this->service->update_account( $id, $data );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			// Success - clear session and redirect
			unset( $_SESSION['vd_form_data'] );
			unset( $_SESSION['vd_form_errors'] );

			$this->add_notice( __( 'Account updated successfully!', 'vd-license-manager' ), 'success' );
			wp_safe_redirect( admin_url( 'admin.php?page=vd-accounts' ) );
			exit;

		} catch ( Exception $e ) {
			// Save form data to session
			$_SESSION['vd_form_data'] = $_POST;
			$_SESSION['vd_form_errors'] = array(
				'_global' => $e->getMessage()
			);

			// Redirect back to form
			wp_safe_redirect( add_query_arg( array(
				'page' => 'vd-accounts',
				'action' => 'edit',
				'id' => $id,
				'error' => 'update_failed'
			), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Handle account deletion
	 *
	 * @since 1.0.0
	 */
	private function handle_delete() {
		// Get account ID
		$account_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $account_id ) {
			$this->add_notice( __( 'Invalid account ID.', 'vd-license-manager' ), 'error' );
			return;
		}

		// Verify nonce - use WordPress nonce for GET requests
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete-account-' . $account_id ) ) {
			wp_die( __( 'Security check failed. Invalid nonce.', 'vd-license-manager' ) );
		}

		error_log( "VD Delete: Attempting to delete account ID $account_id" );

		// Delete account
		$result = $this->service->delete_account( $account_id );

		if ( is_wp_error( $result ) ) {
			$this->add_notice( $result->get_error_message(), 'error' );
			error_log( "VD Delete: Failed - " . $result->get_error_message() );
		} else {
			$this->add_notice( __( 'Account deleted successfully.', 'vd-license-manager' ), 'success' );
			error_log( "VD Delete: Account $account_id deleted successfully" );
			wp_safe_redirect( admin_url( 'admin.php?page=vd-accounts' ) );
			exit;
		}
	}

	/**
	 * Handle bulk delete
	 *
	 * @since 1.0.0
	 */
	private function handle_bulk_delete() {
		// Debug nonce
		error_log('VD Bulk Delete: Starting...');
		error_log('VD Bulk Delete: POST vd_lm_nonce = ' . ($_POST['vd_lm_nonce'] ?? 'NOT SET'));
		error_log('VD Bulk Delete: Verifying against action: vd_lm_action');

		// Try verification
		$nonce_check = wp_verify_nonce($_POST['vd_lm_nonce'] ?? '', 'vd_lm_action');
		error_log('VD Bulk Delete: Nonce verification result: ' . var_export($nonce_check, true));

		// If failed, try alternative
		if (!$nonce_check) {
			error_log('VD Bulk Delete: First verification failed, trying alternative nonce names...');

			// Try different action names that might have been used
			$alt_checks = array(
				'vd-accounts-nonce' => wp_verify_nonce($_POST['vd_lm_nonce'] ?? '', 'vd-accounts-nonce'),
				'bulk-accounts' => wp_verify_nonce($_POST['vd_lm_nonce'] ?? '', 'bulk-accounts'),
				'vd_accounts_action' => wp_verify_nonce($_POST['vd_lm_nonce'] ?? '', 'vd_accounts_action'),
				'vd_lm_account_action' => wp_verify_nonce($_POST['vd_lm_nonce'] ?? '', 'vd_lm_account_action'),
			);

			error_log('VD Bulk Delete: Alternative nonce checks: ' . print_r($alt_checks, true));

			// Find which one works
			foreach ($alt_checks as $action_name => $result) {
				if ($result) {
					error_log("VD Bulk Delete: SUCCESS with action name: $action_name");
					$nonce_check = true;
					break;
				}
			}
		}

		// Final check
		if (!$nonce_check) {
			error_log('VD Bulk Delete: All nonce verifications FAILED');
			wp_die('Security check failed. Invalid nonce for bulk action.');
		}

		error_log('VD Bulk Delete: Nonce verified successfully');

		// Get IDs from POST data
		$account_ids = isset( $_POST['account_ids'] ) ? array_map( 'absint', $_POST['account_ids'] ) : array();

		error_log( 'VD Bulk Delete: Received account_ids: ' . print_r( $account_ids, true ) );
		error_log( 'VD Bulk Delete: Count: ' . count( $account_ids ) );

		if ( empty( $account_ids ) ) {
			$this->add_notice( __( 'No accounts selected.', 'vd-license-manager' ), 'error' );
			return;
		}

		$deleted = 0;
		$errors = array();

		foreach ( $account_ids as $id ) {
			error_log( "VD Bulk Delete: Attempting to delete ID $id" );
			$result = $this->service->delete_account( $id );

			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf( __( 'Account ID %d: %s', 'vd-license-manager' ), $id, $result->get_error_message() );
				error_log( "VD Bulk Delete: Failed ID $id - " . $result->get_error_message() );
			} else {
				$deleted++;
				error_log( "VD Bulk Delete: Deleted ID $id" );
			}
		}

		// Show results
		if ( $deleted > 0 ) {
			$this->add_notice(
				sprintf(
					/* translators: %d: number of accounts deleted */
					__( '%d account(s) deleted successfully.', 'vd-license-manager' ),
					$deleted
				),
				'success'
			);
		}

		if ( ! empty( $errors ) ) {
			$this->add_notice(
				__( 'Some accounts could not be deleted: ', 'vd-license-manager' ) . implode( '; ', $errors ),
				'error'
			);
		}
	}

	/**
	 * Render accounts list
	 *
	 * @since 1.0.0
	 */
	private function render_list() {
		// Calculate pagination
		$offset = ( $this->current_page - 1 ) * $this->per_page;

		// Build query args
		$args = array(
			'limit' => $this->per_page,
			'offset' => $offset,
			'order_by' => isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'created_at',
			'order' => isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) === 'ASC' ? 'ASC' : 'DESC'
		);

		// Apply filters
		$where = array();

		if ( isset( $_GET['provider'] ) && ! empty( $_GET['provider'] ) ) {
			$where['provider'] = sanitize_text_field( $_GET['provider'] );
		}

		if ( isset( $_GET['status'] ) && ! empty( $_GET['status'] ) ) {
			$where['status'] = sanitize_text_field( $_GET['status'] );
		}

		// Get accounts
		if ( ! empty( $where ) ) {
			$accounts = $this->service->get_account_repository()->find_by( $where, $args );
			$total_accounts = $this->service->get_account_repository()->count_by( $where );
		} else {
			$accounts = $this->service->get_account_repository()->get_all( $args );
			$total_accounts = $this->service->get_account_repository()->get_total_count();
		}

		$total_pages = ceil( $total_accounts / $this->per_page );

		// Get providers for filter dropdown
		$providers = $this->get_unique_providers();

		// Load list template
		require_once VD_PLUGIN_DIR . 'admin/partials/accounts-list.php';
	}

	/**
	 * Render add account form
	 *
	 * @since 1.0.0
	 */
	private function render_add_form() {
		$account = null; // New account
		$providers = $this->get_provider_options();
		$form_action = 'create';

		require_once VD_PLUGIN_DIR . 'admin/partials/accounts-form.php';
	}

	/**
	 * Render edit account form
	 *
	 * @since 1.0.0
	 */
	private function render_edit_form() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		// DEBUG: Log edit form request
		error_log( 'VD DEBUG: render_edit_form called with ID: ' . $id );

		if ( ! $id ) {
			error_log( 'VD DEBUG: Invalid account ID in edit form' );
			$this->add_notice( __( 'Invalid account ID.', 'vd-license-manager' ), 'error' );
			$this->render_list();
			return;
		}

		// DEBUG: Fetch account with detailed logging
		error_log( 'VD DEBUG: Fetching account with ID: ' . $id );
		$account = $this->service->get_account( $id );

		if ( is_wp_error( $account ) ) {
			error_log( 'VD DEBUG: Error fetching account: ' . $account->get_error_message() );
			$this->add_notice( $account->get_error_message(), 'error' );
			$this->render_list();
			return;
		}

		// DEBUG: Log fetched account data structure
		if ( $account ) {
			error_log( 'VD DEBUG: Account fetched successfully' );
			error_log( 'VD DEBUG: Account provider: ' . ( isset( $account->provider ) ? $account->provider : 'NOT SET' ) );
			error_log( 'VD DEBUG: Account login: ' . ( isset( $account->account_login ) ? $account->account_login : 'NOT SET' ) );
			error_log( 'VD DEBUG: Account display_name: ' . ( isset( $account->display_name ) ? $account->display_name : 'NOT SET' ) );
			error_log( 'VD DEBUG: Account capacity: ' . ( isset( $account->capacity ) ? $account->capacity : 'NOT SET' ) );
			error_log( 'VD DEBUG: Account custom_fields: ' . ( isset( $account->custom_fields ) ? ( is_array( $account->custom_fields ) ? wp_json_encode( $account->custom_fields ) : $account->custom_fields ) : 'NOT SET' ) );
			error_log( 'VD DEBUG: Full account object type: ' . gettype( $account ) );

			// Log all available properties
			if ( is_object( $account ) ) {
				$props = get_object_vars( $account );
				error_log( 'VD DEBUG: Account properties: ' . implode( ', ', array_keys( $props ) ) );
			}
		} else {
			error_log( 'VD DEBUG: Account is null/empty' );
		}

		$providers = $this->get_provider_options();
		$form_action = 'update';

		// DEBUG: Log before including form
		error_log( 'VD DEBUG: Including accounts-form.php with form_action: ' . $form_action );

		require_once VD_PLUGIN_DIR . 'admin/partials/accounts-form.php';
	}

	/**
	 * Render view account details
	 *
	 * @since 1.0.0
	 */
	private function render_view() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $id ) {
			$this->add_notice( __( 'Invalid account ID.', 'vd-license-manager' ), 'error' );
			$this->render_list();
			return;
		}

		$account = $this->service->get_account( $id );

		if ( is_wp_error( $account ) ) {
			$this->add_notice( $account->get_error_message(), 'error' );
			$this->render_list();
			return;
		}

		require_once VD_PLUGIN_DIR . 'admin/partials/accounts-view.php';
	}

	/**
	 * Sanitize account form data
	 *
	 * @since 1.0.0
	 * @param array $post POST data
	 * @return array Sanitized data
	 */
	private function sanitize_account_data( $post ) {
		return array(
			'provider' => sanitize_text_field( $post['provider'] ?? '' ),
			'account_login' => sanitize_text_field( $post['account_login'] ?? '' ),
			'display_name' => sanitize_text_field( $post['display_name'] ?? '' ),
			'account_password' => $post['account_password'] ?? '', // Don't sanitize password
			'cookies' => $post['cookies'] ?? '', // Don't sanitize cookies
			'phone_recovery' => sanitize_text_field( $post['phone_recovery'] ?? '' ),
			'email_recovery' => sanitize_email( $post['email_recovery'] ?? '' ),
			'security_question' => sanitize_text_field( $post['security_question'] ?? '' ),
			'security_answer' => $post['security_answer'] ?? '', // Don't sanitize sensitive answer
			'backup_codes' => sanitize_textarea_field( $post['backup_codes'] ?? '' ),
			'two_factor_secret' => sanitize_text_field( $post['two_factor_secret'] ?? '' ),
			'api_key' => sanitize_text_field( $post['api_key'] ?? '' ),
			'secret_key' => $post['secret_key'] ?? '', // Don't sanitize secret
			'api_token' => $post['api_token'] ?? '', // Don't sanitize token
			'capacity' => absint( $post['capacity'] ?? 1 ),
			'status' => sanitize_text_field( $post['status'] ?? 'active' ),
			'expires_at' => sanitize_text_field( $post['expires_at'] ?? '' ),
			'custom_fields' => $this->process_custom_fields( $post ),
			'notes' => sanitize_textarea_field( $post['notes'] ?? '' ),
		);
	}

	/**
	 * Process custom fields from form data
	 *
	 * @since 1.0.0
	 * @param array $post POST data
	 * @return array Processed custom fields
	 */
	private function process_custom_fields( $post ) {
		$custom_fields = array();

		// Check if we have custom field arrays from the form
		if ( isset( $post['custom_field_key'] ) && is_array( $post['custom_field_key'] ) ) {
			$keys = $post['custom_field_key'];
			$values = $post['custom_field_value'] ?? array();
			$types = $post['custom_field_type'] ?? array();

			foreach ( $keys as $index => $key ) {
				$key = sanitize_key( $key );
				$value = isset( $values[$index] ) ? sanitize_text_field( $values[$index] ) : '';
				$type = isset( $types[$index] ) ? sanitize_text_field( $types[$index] ) : 'text';

				// Only add non-empty keys and values
				if ( ! empty( $key ) && ! empty( $value ) ) {
					$custom_fields[ $key ] = $value;
				}
			}
		}
		// Fallback: check for direct custom_fields array (for backward compatibility)
		elseif ( isset( $post['custom_fields'] ) && is_array( $post['custom_fields'] ) ) {
			$custom_fields = $this->sanitize_custom_fields( $post['custom_fields'] );
		}

		return $custom_fields;
	}

	/**
	 * Sanitize custom fields (legacy method)
	 *
	 * @since 1.0.0
	 * @param array $custom_fields Custom fields data
	 * @return array Sanitized custom fields
	 */
	private function sanitize_custom_fields( $custom_fields ) {
		if ( ! is_array( $custom_fields ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $custom_fields as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}

		return $sanitized;
	}

	/**
	 * Get unique providers from existing accounts
	 *
	 * @since 1.0.0
	 * @return array List of unique providers
	 */
	private function get_unique_providers() {
		$providers = $this->service->get_providers();

		// Add common providers if not already present
		$common_providers = array( 'Netflix', 'Spotify', 'Helium10', 'ChatGPT', 'YouTube Premium' );

		foreach ( $common_providers as $provider ) {
			if ( ! in_array( $provider, $providers, true ) ) {
				$providers[] = $provider;
			}
		}

		return $providers;
	}

	/**
	 * Get provider options for dropdown
	 *
	 * @since 1.0.0
	 * @return array Provider options
	 */
	private function get_provider_options() {
		return array(
			'Netflix' => 'Netflix',
			'Spotify' => 'Spotify',
			'Helium10' => 'Helium10',
			'ChatGPT' => 'ChatGPT',
			'YouTube Premium' => 'YouTube Premium',
			'Amazon Prime' => 'Amazon Prime',
			'Hulu' => 'Hulu',
			'Disney+' => 'Disney+',
			'HBO Max' => 'HBO Max',
			'Other' => 'Other'
		);
	}

	/**
	 * Add admin notice
	 *
	 * @since 1.0.0
	 * @param string $message Notice message
	 * @param string $type    Notice type (success, error, warning, info)
	 */
	private function add_notice( $message, $type = 'info' ) {
		add_settings_error(
			'vd_lm_accounts',
			'vd_lm_accounts_notice',
			$message,
			$type
		);
	}

	/**
	 * Get account repository (for direct access if needed)
	 *
	 * @since 1.0.0
	 * @return VD_LM_Account_Repository
	 */
	public function get_account_repository() {
		return $this->service->get_account_repository();
	}


	/**
	 * Validate account form data
	 *
	 * @since 1.0.0
	 * @param array $data Sanitized form data
	 * @param bool  $is_edit Whether this is an edit operation
	 * @return array Array of validation errors
	 */
	private function validate_account_data( $data, $is_edit = false ) {
		$errors = array();

		// Required fields
		if ( empty( $data['provider'] ) ) {
			$errors['provider'] = __( 'Provider is required', 'vd-license-manager' );
		}

		if ( empty( $data['account_login'] ) ) {
			$errors['account_login'] = __( 'Account Login is required', 'vd-license-manager' );
		}

		if ( empty( $data['account_password'] ) && ! $is_edit ) {
			$errors['account_password'] = __( 'Password is required', 'vd-license-manager' );
		}

		// Email validation
		if ( ! empty( $data['account_login'] ) && strpos( $data['account_login'], '@' ) !== false && ! is_email( $data['account_login'] ) ) {
			$errors['account_login'] = __( 'Invalid email address', 'vd-license-manager' );
		}

		if ( ! empty( $data['email_recovery'] ) && ! is_email( $data['email_recovery'] ) ) {
			$errors['email_recovery'] = __( 'Invalid recovery email address', 'vd-license-manager' );
		}

		// Capacity validation
		if ( isset( $data['capacity'] ) ) {
			$capacity = intval( $data['capacity'] );
			if ( $capacity < 1 || $capacity > 100 ) {
				$errors['capacity'] = __( 'Capacity must be between 1 and 100', 'vd-license-manager' );
			}
		}

		// Phone validation
		if ( ! empty( $data['phone_recovery'] ) && ! preg_match( '/^[\+]?[0-9\s\-\(\)\+]+$/', $data['phone_recovery'] ) ) {
			$errors['phone_recovery'] = __( 'Invalid phone number format', 'vd-license-manager' );
		}

		// URL validation for custom fields
		if ( ! empty( $data['custom_field_type'] ) && is_array( $data['custom_field_type'] ) ) {
			foreach ( $data['custom_field_type'] as $index => $type ) {
				if ( $type === 'url' && ! empty( $data['custom_field_value'][$index] ) ) {
					if ( ! filter_var( $data['custom_field_value'][$index], FILTER_VALIDATE_URL ) ) {
						$errors['custom_fields'] = __( 'Invalid URL in custom fields', 'vd-license-manager' );
						break;
					}
				}
				if ( $type === 'email' && ! empty( $data['custom_field_value'][$index] ) ) {
					if ( ! is_email( $data['custom_field_value'][$index] ) ) {
						$errors['custom_fields'] = __( 'Invalid email in custom fields', 'vd-license-manager' );
						break;
					}
				}
			}
		}

		return $errors;
	}

	/**
	 * Check and fix database schema if needed
	 *
	 * @since 1.0.0
	 */
	public function check_database_schema() {
		// Only check on accounts pages
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'vd-accounts' ) {
			return;
		}

		// Load database class if not already loaded
		if ( ! class_exists( 'VD_LM_Database' ) ) {
			require_once VD_PLUGIN_DIR . 'includes/class-vd-lm-database.php';
		}

		// Check and fix encrypted columns
		VD_LM_Database::check_and_fix_encrypted_columns();
	}
}
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

		// Enqueue assets for accounts page
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

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
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['vd_lm_nonce'] ) || ! wp_verify_nonce( $_POST['vd_lm_nonce'], 'vd_lm_account_action' ) ) {
			$this->add_notice( __( 'Security check failed. Please try again.', 'vd-license-manager' ), 'error' );
			return;
		}

		$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';

		switch ( $action ) {
			case 'create':
				$this->handle_create();
				break;
			case 'update':
				$this->handle_update();
				break;
			case 'delete':
				$this->handle_delete();
				break;
			case 'bulk_delete':
				$this->handle_bulk_delete();
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
		$id = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;

		if ( ! $id ) {
			$this->add_notice( __( 'Invalid account ID.', 'vd-license-manager' ), 'error' );
			return;
		}

		$result = $this->service->delete_account( $id );

		if ( is_wp_error( $result ) ) {
			$this->add_notice( $result->get_error_message(), 'error' );
		} else {
			$this->add_notice( __( 'Account deleted successfully!', 'vd-license-manager' ), 'success' );
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
		$ids = isset( $_POST['account_ids'] ) ? array_map( 'absint', $_POST['account_ids'] ) : array();

		if ( empty( $ids ) ) {
			$this->add_notice( __( 'No accounts selected.', 'vd-license-manager' ), 'error' );
			return;
		}

		$deleted = 0;
		$errors = array();

		foreach ( $ids as $id ) {
			$result = $this->service->delete_account( $id );

			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf( __( 'Account ID %d: %s', 'vd-license-manager' ), $id, $result->get_error_message() );
			} else {
				$deleted++;
			}
		}

		if ( $deleted > 0 ) {
			/* translators: %d: number of accounts deleted */
			$this->add_notice( sprintf( __( '%d account(s) deleted successfully!', 'vd-license-manager' ), $deleted ), 'success' );
		}

		if ( ! empty( $errors ) ) {
			$this->add_notice( implode( '<br>', $errors ), 'error' );
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

		$providers = $this->get_provider_options();
		$form_action = 'update';

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
			'custom_fields' => $this->sanitize_custom_fields( $post['custom_fields'] ?? array() ),
			'notes' => sanitize_textarea_field( $post['notes'] ?? '' ),
		);
	}

	/**
	 * Sanitize custom fields
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
	 * Enqueue CSS and JavaScript assets for accounts page
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_assets( $hook ) {
		// Debug logging to see what's happening
		error_log( '[VD DEBUG] enqueue_assets called with hook: ' . $hook );

		// Only enqueue on VD License Manager accounts pages
		// Hook format: vd-license-manager_page_vd-accounts
		if ( strpos( $hook, 'vd-accounts' ) === false ) {
			error_log( '[VD DEBUG] Hook does not contain vd-accounts, returning early' );
			return;
		}

		error_log( '[VD DEBUG] Hook contains vd-accounts, proceeding with asset enqueue' );

		// Enqueue accounts form CSS
		wp_enqueue_style(
			'vd-accounts-form',
			VD_PLUGIN_URL . 'admin/css/accounts-form.css',
			array(),
			VD_PLUGIN_VERSION
		);

		// Enqueue enhanced error styling
		wp_enqueue_style(
			'vd-accounts-form-errors',
			VD_PLUGIN_URL . 'admin/css/accounts-form-errors.css',
			array( 'vd-accounts-form' ),
			VD_PLUGIN_VERSION
		);

		// Enqueue accounts form JavaScript
		error_log( '[VD DEBUG] Enqueueing JavaScript: ' . VD_PLUGIN_URL . 'admin/js/accounts-form.js' );
		wp_enqueue_script(
			'vd-accounts-form',
			VD_PLUGIN_URL . 'admin/js/accounts-form.js',
			array( 'jquery' ),
			VD_PLUGIN_VERSION,
			true
		);
		error_log( '[VD DEBUG] JavaScript enqueued successfully' );

		// Localize script with translations and config
		$is_edit = isset( $_GET['action'] ) && $_GET['action'] === 'edit' ? '1' : '0';

		wp_localize_script(
			'vd-accounts-form',
			'vdAccountFormL10n',
			array(
				'isEdit' => $is_edit,
				'show' => __( 'Show', 'vd-license-manager' ),
				'hide' => __( 'Hide', 'vd-license-manager' ),
				'remove' => __( 'Remove', 'vd-license-manager' ),
				'fieldKey' => __( 'Field Key', 'vd-license-manager' ),
				'fieldLabel' => __( 'Field Label', 'vd-license-manager' ),
				'fieldValue' => __( 'Field Value', 'vd-license-manager' ),
				'fieldTypes' => array(
					'text' => __( 'Text', 'vd-license-manager' ),
					'email' => __( 'Email', 'vd-license-manager' ),
					'url' => __( 'URL', 'vd-license-manager' ),
					'tel' => __( 'Phone', 'vd-license-manager' ),
					'password' => __( 'Password (encrypted)', 'vd-license-manager' ),
					'textarea' => __( 'Long Text', 'vd-license-manager' ),
				),
				'errors' => array(
					'formErrors' => __( 'Please fix the following errors:', 'vd-license-manager' ),
					'providerRequired' => __( 'Provider name is required.', 'vd-license-manager' ),
					'loginRequired' => __( 'Account login is required.', 'vd-license-manager' ),
					'passwordRequired' => __( 'Password is required for new accounts.', 'vd-license-manager' ),
					'capacityInvalid' => __( 'Capacity must be between 1 and 100.', 'vd-license-manager' ),
				),
			)
		);
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
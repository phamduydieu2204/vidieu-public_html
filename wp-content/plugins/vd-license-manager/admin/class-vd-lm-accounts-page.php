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
	 * Handle account creation
	 *
	 * @since 1.0.0
	 */
	private function handle_create() {
		$data = $this->sanitize_account_data( $_POST );
		$result = $this->service->create_account( $data );

		if ( is_wp_error( $result ) ) {
			$this->add_notice( $result->get_error_message(), 'error' );
		} else {
			$this->add_notice( __( 'Account created successfully!', 'vd-license-manager' ), 'success' );
			wp_safe_redirect( admin_url( 'admin.php?page=vd-accounts' ) );
			exit;
		}
	}

	/**
	 * Handle account update
	 *
	 * @since 1.0.0
	 */
	private function handle_update() {
		$id = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;

		if ( ! $id ) {
			$this->add_notice( __( 'Invalid account ID.', 'vd-license-manager' ), 'error' );
			return;
		}

		$data = $this->sanitize_account_data( $_POST );

		// Only update password if provided
		if ( empty( $data['account_password'] ) ) {
			unset( $data['account_password'] );
		}

		$result = $this->service->update_account( $id, $data );

		if ( is_wp_error( $result ) ) {
			$this->add_notice( $result->get_error_message(), 'error' );
		} else {
			$this->add_notice( __( 'Account updated successfully!', 'vd-license-manager' ), 'success' );
			wp_safe_redirect( admin_url( 'admin.php?page=vd-accounts' ) );
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
}
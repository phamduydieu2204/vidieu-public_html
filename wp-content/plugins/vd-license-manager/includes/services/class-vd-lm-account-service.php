<?php
/**
 * Account Service Class
 *
 * Handles business logic for provider account management including
 * validation, encryption, and coordination with repositories.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Account Service Class
 *
 * Provides business logic layer for provider account management.
 * Handles creation, updates, validation, and coordination between
 * repositories and external services.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/services
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Account_Service {

	/**
	 * Account repository instance
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    VD_LM_Account_Repository $account_repository
	 */
	private $account_repository;

	/**
	 * Encryption service instance
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    VD_LM_Encryption_Service $encryption_service
	 */
	private $encryption_service;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param VD_LM_Account_Repository $account_repository Account repository instance (optional)
	 */
	public function __construct( $account_repository = null ) {
		// Load required classes
		if ( ! class_exists( 'VD_LM_Account_Repository' ) ) {
			require_once VD_PLUGIN_DIR . 'includes/repositories/class-vd-lm-base-repository.php';
			require_once VD_PLUGIN_DIR . 'includes/repositories/class-vd-lm-account-repository.php';
		}

		if ( ! class_exists( 'VD_LM_Encryption_Service' ) ) {
			require_once VD_PLUGIN_DIR . 'includes/services/class-vd-lm-encryption-service.php';
		}

		// Initialize dependencies
		$this->account_repository = $account_repository ?: new VD_LM_Account_Repository();
		$this->encryption_service = new VD_LM_Encryption_Service();
	}

	/**
	 * Create a new provider account
	 *
	 * @since 1.0.0
	 * @param array $data Account data
	 * @return int|WP_Error Account ID on success, WP_Error on failure
	 */
	public function create_account( $data ) {
		// Validate input data
		$validation_result = $this->validate_account_data( $data, 'create' );
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Check for duplicates
		if ( $this->account_repository->account_exists( $data['provider'], $data['account_login'] ) ) {
			return new WP_Error(
				'account_exists',
				__( 'An account with this login already exists for this provider.', 'vd-license-manager' )
			);
		}

		// Prepare data for insertion
		$account_data = $this->prepare_account_data( $data );

		// Insert account
		$account_id = $this->account_repository->insert( $account_data );

		if ( false === $account_id ) {
			VD_LM_Logger_Service::error( 'Failed to create account', array(
				'provider' => $data['provider'],
				'account_login' => $data['account_login'],
			) );

			return new WP_Error(
				'insert_failed',
				__( 'Failed to create account. Please try again.', 'vd-license-manager' )
			);
		}

		// Log successful creation
		VD_LM_Logger_Service::info( 'Account created successfully', array(
			'account_id' => $account_id,
			'provider' => $data['provider'],
			'account_login' => $data['account_login'],
		) );

		return $account_id;
	}

	/**
	 * Update an existing provider account
	 *
	 * @since 1.0.0
	 * @param int   $account_id Account ID
	 * @param array $data       Update data
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public function update_account( $account_id, $data ) {
		$account_id = absint( $account_id );

		// Check if account exists
		$existing_account = $this->get_account( $account_id );
		if ( is_wp_error( $existing_account ) ) {
			return $existing_account;
		}

		// Validate input data
		$validation_result = $this->validate_account_data( $data, 'update' );
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Check for duplicates (excluding current account)
		if ( isset( $data['provider'] ) && isset( $data['account_login'] ) ) {
			if ( $this->account_repository->account_exists( $data['provider'], $data['account_login'], $account_id ) ) {
				return new WP_Error(
					'account_exists',
					__( 'An account with this login already exists for this provider.', 'vd-license-manager' )
				);
			}
		}

		// Prepare data for update
		$update_data = $this->prepare_account_data( $data, 'update' );

		// Update account
		$result = $this->account_repository->update( $account_id, $update_data );

		if ( false === $result ) {
			VD_LM_Logger_Service::error( 'Failed to update account', array(
				'account_id' => $account_id,
				'data' => $data,
			) );

			return new WP_Error(
				'update_failed',
				__( 'Failed to update account. Please try again.', 'vd-license-manager' )
			);
		}

		// Log successful update
		VD_LM_Logger_Service::info( 'Account updated successfully', array(
			'account_id' => $account_id,
		) );

		return true;
	}

	/**
	 * Delete a provider account
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public function delete_account( $account_id ) {
		$account_id = absint( $account_id );

		// Check if account exists
		$account = $this->get_account( $account_id );
		if ( is_wp_error( $account ) ) {
			return $account;
		}

		// Check if account is assigned to any pools or licenses
		$dependencies = $this->check_account_dependencies( $account_id );
		if ( ! empty( $dependencies ) ) {
			return new WP_Error(
				'account_in_use',
				sprintf(
					__( 'Cannot delete account. It is currently assigned to %d pool(s) and %d license(s).', 'vd-license-manager' ),
					$dependencies['pools'],
					$dependencies['licenses']
				)
			);
		}

		// Delete account
		$result = $this->account_repository->delete( $account_id );

		if ( false === $result ) {
			VD_LM_Logger_Service::error( 'Failed to delete account', array(
				'account_id' => $account_id,
			) );

			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete account. Please try again.', 'vd-license-manager' )
			);
		}

		// Log successful deletion
		VD_LM_Logger_Service::info( 'Account deleted successfully', array(
			'account_id' => $account_id,
			'provider' => $account->provider,
			'account_login' => $account->account_login,
		) );

		return true;
	}

	/**
	 * Get account by ID
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return object|WP_Error Account object on success, WP_Error on failure
	 */
	public function get_account( $account_id ) {
		$account_id = absint( $account_id );

		if ( $account_id <= 0 ) {
			return new WP_Error(
				'invalid_account_id',
				__( 'Invalid account ID.', 'vd-license-manager' )
			);
		}

		$account = $this->account_repository->find( $account_id );

		if ( null === $account ) {
			return new WP_Error(
				'account_not_found',
				__( 'Account not found.', 'vd-license-manager' )
			);
		}

		return $account;
	}

	/**
	 * Get accounts by provider
	 *
	 * @since 1.0.0
	 * @param string $provider Provider name
	 * @param array  $args     Query arguments
	 * @return array Array of account objects
	 */
	public function get_accounts_by_provider( $provider, $args = array() ) {
		return $this->account_repository->find_by_provider( $provider, $args );
	}

	/**
	 * Get active accounts
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments
	 * @return array Array of active account objects
	 */
	public function get_active_accounts( $args = array() ) {
		return $this->account_repository->find_active( $args );
	}

	/**
	 * Get available accounts (not expired)
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments
	 * @return array Array of available account objects
	 */
	public function get_available_accounts( $args = array() ) {
		return $this->account_repository->find_available( $args );
	}

	/**
	 * Get accounts expiring soon
	 *
	 * @since 1.0.0
	 * @param int   $days_ahead Number of days to look ahead
	 * @param array $args       Query arguments
	 * @return array Array of expiring account objects
	 */
	public function get_expiring_accounts( $days_ahead = 7, $args = array() ) {
		return $this->account_repository->find_expiring_soon( $days_ahead, $args );
	}

	/**
	 * Get all providers
	 *
	 * @since 1.0.0
	 * @return array Array of unique provider names
	 */
	public function get_providers() {
		return $this->account_repository->get_providers();
	}

	/**
	 * Get account statistics
	 *
	 * @since 1.0.0
	 * @return array Account statistics
	 */
	public function get_statistics() {
		return $this->account_repository->get_statistics();
	}

	/**
	 * Test account credentials
	 *
	 * @since 1.0.0
	 * @param int $account_id Account ID
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public function test_account_credentials( $account_id ) {
		$account = $this->get_account( $account_id );
		if ( is_wp_error( $account ) ) {
			return $account;
		}

		// Basic availability test (check if account is not expired and active)
		if ( $account->status !== 'active' ) {
			return new WP_Error(
				'account_inactive',
				__( 'Account is not active.', 'vd-license-manager' )
			);
		}

		if ( ! empty( $account->expires_at ) ) {
			$expiry_time = strtotime( $account->expires_at );
			if ( $expiry_time && $expiry_time < current_time( 'timestamp' ) ) {
				return new WP_Error(
					'account_expired',
					__( 'Account has expired.', 'vd-license-manager' )
				);
			}
		}

		// For now, just return true for basic checks
		// TODO: Implement provider-specific credential testing
		return true;
	}

	/**
	 * Validate account data
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  array  $data      Account data
	 * @param  string $operation Operation type ('create' or 'update')
	 * @return true|WP_Error True if valid, WP_Error if invalid
	 */
	private function validate_account_data( $data, $operation = 'create' ) {
		$errors = array();

		// Required fields for creation
		if ( $operation === 'create' ) {
			if ( empty( $data['provider'] ) ) {
				$errors[] = __( 'Provider is required.', 'vd-license-manager' );
			}

			if ( empty( $data['account_login'] ) ) {
				$errors[] = __( 'Account login is required.', 'vd-license-manager' );
			}
		}

		// Validate provider
		if ( isset( $data['provider'] ) ) {
			if ( ! is_string( $data['provider'] ) || strlen( $data['provider'] ) > 100 ) {
				$errors[] = __( 'Provider name must be a string with maximum 100 characters.', 'vd-license-manager' );
			}
		}

		// Validate account login
		if ( isset( $data['account_login'] ) ) {
			if ( ! is_string( $data['account_login'] ) || strlen( $data['account_login'] ) > 255 ) {
				$errors[] = __( 'Account login must be a string with maximum 255 characters.', 'vd-license-manager' );
			}
		}

		// Validate email if provided in account_login
		if ( isset( $data['account_login'] ) && filter_var( $data['account_login'], FILTER_VALIDATE_EMAIL ) === false && strpos( $data['account_login'], '@' ) !== false ) {
			$errors[] = __( 'Account login appears to be an email but is not valid.', 'vd-license-manager' );
		}

		// Validate capacity
		if ( isset( $data['capacity'] ) ) {
			$capacity = absint( $data['capacity'] );
			if ( $capacity < 1 || $capacity > 100 ) {
				$errors[] = __( 'Capacity must be between 1 and 100.', 'vd-license-manager' );
			}
		}

		// Validate status
		if ( isset( $data['status'] ) ) {
			$valid_statuses = array( 'active', 'inactive', 'suspended' );
			if ( ! in_array( $data['status'], $valid_statuses, true ) ) {
				$errors[] = __( 'Status must be one of: active, inactive, suspended.', 'vd-license-manager' );
			}
		}

		// Validate expiry date
		if ( isset( $data['expires_at'] ) && ! empty( $data['expires_at'] ) ) {
			$timestamp = strtotime( $data['expires_at'] );
			if ( false === $timestamp ) {
				$errors[] = __( 'Expiry date must be a valid date.', 'vd-license-manager' );
			}
		}

		// Validate custom fields
		if ( isset( $data['custom_fields'] ) && ! is_array( $data['custom_fields'] ) ) {
			$errors[] = __( 'Custom fields must be an array.', 'vd-license-manager' );
		}

		// Return validation result
		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', implode( ' ', $errors ) );
		}

		return true;
	}

	/**
	 * Prepare account data for database operations
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  array  $data      Raw account data
	 * @param  string $operation Operation type ('create' or 'update')
	 * @return array Prepared account data
	 */
	private function prepare_account_data( $data, $operation = 'create' ) {
		$prepared_data = array();

		// Copy allowed fields
		$allowed_fields = array(
			'provider',
			'account_login',
			'display_name',
			'account_password',
			'cookies',
			'phone_recovery',
			'email_recovery',
			'security_question',
			'security_answer',
			'backup_codes',
			'two_factor_secret',
			'api_key',
			'secret_key',
			'api_token',
			'status',
			'capacity',
			'expires_at',
			'custom_fields',
			'notes',
		);

		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$prepared_data[ $field ] = $data[ $field ];
			}
		}

		// Set defaults for creation
		if ( $operation === 'create' ) {
			if ( ! isset( $prepared_data['status'] ) ) {
				$prepared_data['status'] = 'active';
			}

			if ( ! isset( $prepared_data['capacity'] ) ) {
				$prepared_data['capacity'] = 1;
			}

			if ( ! isset( $prepared_data['display_name'] ) && isset( $prepared_data['account_login'] ) ) {
				$prepared_data['display_name'] = $prepared_data['account_login'];
			}
		}

		// Sanitize data
		if ( isset( $prepared_data['provider'] ) ) {
			$prepared_data['provider'] = sanitize_text_field( $prepared_data['provider'] );
		}

		if ( isset( $prepared_data['account_login'] ) ) {
			$prepared_data['account_login'] = sanitize_text_field( $prepared_data['account_login'] );
		}

		if ( isset( $prepared_data['display_name'] ) ) {
			$prepared_data['display_name'] = sanitize_text_field( $prepared_data['display_name'] );
		}

		if ( isset( $prepared_data['status'] ) ) {
			$prepared_data['status'] = sanitize_text_field( $prepared_data['status'] );
		}

		if ( isset( $prepared_data['capacity'] ) ) {
			$prepared_data['capacity'] = absint( $prepared_data['capacity'] );
		}

		if ( isset( $prepared_data['notes'] ) ) {
			$prepared_data['notes'] = sanitize_textarea_field( $prepared_data['notes'] );
		}

		return $prepared_data;
	}

	/**
	 * Check account dependencies (pools, licenses)
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  int $account_id Account ID
	 * @return array Dependencies count
	 */
	private function check_account_dependencies( $account_id ) {
		global $wpdb;

		$dependencies = array(
			'pools' => 0,
			'licenses' => 0,
		);

		// Check pool assignments (if table exists)
		$pool_table = $wpdb->prefix . 'vd_pool_accounts';
		$pool_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$pool_table} WHERE account_id = %d",
				$account_id
			)
		);
		$dependencies['pools'] = absint( $pool_count );

		// Check license assignments (if table exists)
		$license_table = $wpdb->prefix . 'vd_license_keys';
		$license_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$license_table} WHERE assigned_account_id = %d",
				$account_id
			)
		);
		$dependencies['licenses'] = absint( $license_count );

		return $dependencies;
	}

	/**
	 * Get account repository instance
	 *
	 * @since 1.0.0
	 * @return VD_LM_Account_Repository Account repository instance
	 */
	public function get_account_repository() {
		return $this->account_repository;
	}
}
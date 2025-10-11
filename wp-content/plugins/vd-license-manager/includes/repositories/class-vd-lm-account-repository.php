<?php
/**
 * Account Repository Class
 *
 * Handles database operations for provider accounts with encryption support.
 * Extends the base repository to provide specialized account management.
 *
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/repositories
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Account Repository Class
 *
 * Manages provider account data with automatic encryption/decryption
 * of sensitive fields like passwords and API keys.
 *
 * @since      1.0.0
 * @package    VD_License_Manager
 * @subpackage VD_License_Manager/includes/repositories
 * @author     Vidieu Team <admin@vidieu.vn>
 */
class VD_LM_Account_Repository extends VD_LM_Base_Repository {

	/**
	 * Encryption service instance
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    VD_LM_Encryption_Service $encryption_service
	 */
	private $encryption_service;

	/**
	 * Fields that should be encrypted
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array $encrypted_fields
	 */
	private $encrypted_fields = array(
		'account_password',
		'api_key',
		'secret_key',
		'two_factor_secret',
		'custom_fields',
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( 'vd_provider_accounts' );

		// Load encryption service
		if ( ! class_exists( 'VD_LM_Encryption_Service' ) ) {
			require_once VD_PLUGIN_DIR . 'includes/services/class-vd-lm-encryption-service.php';
		}

		$this->encryption_service = new VD_LM_Encryption_Service();
	}

	/**
	 * Find account by provider and login
	 *
	 * @since 1.0.0
	 * @param string $provider      Provider name
	 * @param string $account_login Account login/email
	 * @return object|null Account object or null if not found
	 */
	public function find_by_provider_and_login( $provider, $account_login ) {
		return $this->find_one_by( array(
			'provider' => $provider,
			'account_login' => $account_login,
		) );
	}

	/**
	 * Find accounts by provider
	 *
	 * @since 1.0.0
	 * @param string $provider Provider name
	 * @param array  $args     Additional query arguments
	 * @return array Array of account objects
	 */
	public function find_by_provider( $provider, $args = array() ) {
		return $this->find_by( array( 'provider' => $provider ), $args );
	}

	/**
	 * Find accounts by status
	 *
	 * @since 1.0.0
	 * @param string $status Account status
	 * @param array  $args   Additional query arguments
	 * @return array Array of account objects
	 */
	public function find_by_status( $status, $args = array() ) {
		return $this->find_by( array( 'status' => $status ), $args );
	}

	/**
	 * Find active accounts
	 *
	 * @since 1.0.0
	 * @param array $args Additional query arguments
	 * @return array Array of active account objects
	 */
	public function find_active( $args = array() ) {
		return $this->find_by_status( 'active', $args );
	}

	/**
	 * Find available accounts (not expired)
	 *
	 * @since 1.0.0
	 * @param array $args Additional query arguments
	 * @return array Array of available account objects
	 */
	public function find_available( $args = array() ) {
		$current_time = current_time( 'mysql' );

		$sql = "SELECT * FROM {$this->full_table_name}
				WHERE status = 'active'
				AND (expires_at IS NULL OR expires_at > %s)";

		// Add order and limit
		$order_by = isset( $args['order_by'] ) ? sanitize_key( $args['order_by'] ) : 'created_at';
		$order = isset( $args['order'] ) && strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		$sql .= " ORDER BY `{$order_by}` {$order}";

		if ( isset( $args['limit'] ) ) {
			$limit = absint( $args['limit'] );
			$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
			$sql .= " LIMIT {$offset}, {$limit}";
		}

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( $sql, $current_time )
		);

		return array_map( array( $this, 'format_result' ), $results );
	}

	/**
	 * Find accounts expiring soon
	 *
	 * @since 1.0.0
	 * @param int   $days_ahead Number of days to look ahead
	 * @param array $args       Additional query arguments
	 * @return array Array of expiring account objects
	 */
	public function find_expiring_soon( $days_ahead = 7, $args = array() ) {
		$current_time = current_time( 'mysql' );
		$expiry_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "+{$days_ahead} days", current_time( 'timestamp' ) ) );

		$sql = "SELECT * FROM {$this->full_table_name}
				WHERE status = 'active'
				AND expires_at IS NOT NULL
				AND expires_at BETWEEN %s AND %s";

		// Add order and limit
		$order_by = isset( $args['order_by'] ) ? sanitize_key( $args['order_by'] ) : 'expires_at';
		$order = isset( $args['order'] ) && strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		$sql .= " ORDER BY `{$order_by}` {$order}";

		if ( isset( $args['limit'] ) ) {
			$limit = absint( $args['limit'] );
			$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
			$sql .= " LIMIT {$offset}, {$limit}";
		}

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( $sql, $current_time, $expiry_threshold )
		);

		return array_map( array( $this, 'format_result' ), $results );
	}

	/**
	 * Count accounts by provider
	 *
	 * @since 1.0.0
	 * @param string $provider Provider name
	 * @return int Number of accounts for provider
	 */
	public function count_by_provider( $provider ) {
		return $this->count_by( array( 'provider' => $provider ) );
	}

	/**
	 * Count active accounts
	 *
	 * @since 1.0.0
	 * @return int Number of active accounts
	 */
	public function count_active() {
		return $this->count_by( array( 'status' => 'active' ) );
	}

	/**
	 * Insert new account with encryption
	 *
	 * @since 1.0.0
	 * @param array $data Account data
	 * @return int|false Insert ID on success, false on failure
	 */
	public function insert( $data ) {
		// Encrypt sensitive fields before insertion
		$data = $this->encrypt_sensitive_fields( $data );

		return parent::insert( $data );
	}

	/**
	 * Update account with encryption
	 *
	 * @since 1.0.0
	 * @param int   $id   Account ID
	 * @param array $data Update data
	 * @return bool True on success, false on failure
	 */
	public function update( $id, $data ) {
		// Encrypt sensitive fields before update
		$data = $this->encrypt_sensitive_fields( $data );

		return parent::update( $id, $data );
	}

	/**
	 * Get providers list
	 *
	 * @since 1.0.0
	 * @return array Array of unique provider names
	 */
	public function get_providers() {
		$sql = "SELECT DISTINCT provider FROM {$this->full_table_name}
				WHERE provider IS NOT NULL AND provider != ''
				ORDER BY provider ASC";

		$results = $this->wpdb->get_col( $sql );

		return $results ? $results : array();
	}

	/**
	 * Get account statistics
	 *
	 * @since 1.0.0
	 * @return array Account statistics
	 */
	public function get_statistics() {
		$stats = array(
			'total' => 0,
			'active' => 0,
			'inactive' => 0,
			'expired' => 0,
			'by_provider' => array(),
		);

		// Total counts
		$stats['total'] = $this->get_total_count();
		$stats['active'] = $this->count_by( array( 'status' => 'active' ) );
		$stats['inactive'] = $this->count_by( array( 'status' => 'inactive' ) );

		// Expired accounts
		$current_time = current_time( 'mysql' );
		$expired_count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->full_table_name}
				WHERE expires_at IS NOT NULL AND expires_at < %s",
				$current_time
			)
		);
		$stats['expired'] = absint( $expired_count );

		// By provider
		$provider_stats = $this->wpdb->get_results(
			"SELECT provider, COUNT(*) as count
			FROM {$this->full_table_name}
			GROUP BY provider
			ORDER BY count DESC"
		);

		foreach ( $provider_stats as $provider_stat ) {
			$stats['by_provider'][ $provider_stat->provider ] = absint( $provider_stat->count );
		}

		return $stats;
	}

	/**
	 * Format database result with decryption
	 *
	 * @since 1.0.0
	 * @param object $result Raw database result
	 * @return object Formatted result with decrypted fields
	 */
	protected function format_result( $result ) {
		if ( ! is_object( $result ) ) {
			return $result;
		}

		// Apply parent formatting first
		$result = parent::format_result( $result );

		// Decrypt sensitive fields
		$result = $this->decrypt_sensitive_fields( $result );

		// Parse custom fields if they exist
		if ( isset( $result->custom_fields ) && ! empty( $result->custom_fields ) ) {
			$custom_fields = json_decode( $result->custom_fields, true );
			$result->custom_fields = is_array( $custom_fields ) ? $custom_fields : array();
		} else {
			$result->custom_fields = array();
		}

		return $result;
	}

	/**
	 * Encrypt sensitive fields in data array
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  array $data Data array
	 * @return array Data array with encrypted fields
	 */
	private function encrypt_sensitive_fields( $data ) {
		foreach ( $this->encrypted_fields as $field ) {
			if ( isset( $data[ $field ] ) && ! empty( $data[ $field ] ) ) {
				// Special handling for custom_fields (JSON encode before encryption)
				if ( $field === 'custom_fields' && is_array( $data[ $field ] ) ) {
					$data[ $field ] = wp_json_encode( $data[ $field ] );
				}

				// Encrypt the field
				try {
					$data[ $field ] = $this->encryption_service->encrypt( $data[ $field ] );
				} catch ( Exception $e ) {
					VD_LM_Logger_Service::error( 'Failed to encrypt field', array(
						'field' => $field,
						'error' => $e->getMessage(),
					) );

					// Don't save if encryption fails
					unset( $data[ $field ] );
				}
			}
		}

		return $data;
	}

	/**
	 * Decrypt sensitive fields in result object
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  object $result Result object
	 * @return object Result object with decrypted fields
	 */
	private function decrypt_sensitive_fields( $result ) {
		foreach ( $this->encrypted_fields as $field ) {
			if ( isset( $result->$field ) && ! empty( $result->$field ) ) {
				try {
					$result->$field = $this->encryption_service->decrypt( $result->$field );
				} catch ( Exception $e ) {
					VD_LM_Logger_Service::error( 'Failed to decrypt field', array(
						'field' => $field,
						'account_id' => isset( $result->id ) ? $result->id : 'unknown',
						'error' => $e->getMessage(),
					) );

					// Set to empty string if decryption fails
					$result->$field = '';
				}
			}
		}

		return $result;
	}

	/**
	 * Check if account exists by provider and login
	 *
	 * @since 1.0.0
	 * @param string $provider      Provider name
	 * @param string $account_login Account login
	 * @param int    $exclude_id    Account ID to exclude from check
	 * @return bool True if account exists, false otherwise
	 */
	public function account_exists( $provider, $account_login, $exclude_id = 0 ) {
		$sql = "SELECT COUNT(*) FROM {$this->full_table_name}
				WHERE provider = %s AND account_login = %s";

		$params = array( $provider, $account_login );

		if ( $exclude_id > 0 ) {
			$sql .= " AND id != %d";
			$params[] = $exclude_id;
		}

		$count = $this->wpdb->get_var(
			$this->wpdb->prepare( $sql, $params )
		);

		return absint( $count ) > 0;
	}

	/**
	 * Get encrypted field names
	 *
	 * @since 1.0.0
	 * @return array Array of encrypted field names
	 */
	public function get_encrypted_fields() {
		return $this->encrypted_fields;
	}
}
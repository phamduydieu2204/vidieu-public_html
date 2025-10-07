<?php
/**
 * Provider Accounts list table
 *
 * Extends WP_List_Table for accounts display
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check
if (!defined('ABSPATH')) {
	exit;
}

// Load WP_List_Table if not already loaded
if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * VD Accounts List Table class
 *
 * Displays provider accounts in WordPress admin table format
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Accounts_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(array(
			'singular' => 'account',
			'plural'   => 'accounts',
			'ajax'     => false
		));
	}

	/**
	 * Get table columns
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'id'            => __('ID', 'vd-license-manager'),
			'display_name'  => __('Account Name', 'vd-license-manager'),
			'provider'      => __('Provider', 'vd-license-manager'),
			'account_login' => __('Login', 'vd-license-manager'),
			'capacity'      => __('Capacity', 'vd-license-manager'),
			'status'        => __('Status', 'vd-license-manager'),
			'created_at'    => __('Created', 'vd-license-manager'),
			'actions'       => __('Actions', 'vd-license-manager')
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'id'         => array('id', false),
			'provider'   => array('provider', false),
			'status'     => array('status', false),
			'created_at' => array('created_at', true)  // true = default sort
		);
	}

	/**
	 * Prepare table items
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		// Set columns
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// Pagination
		$per_page = 20;
		$current_page = $this->get_pagenum();
		$offset = ($current_page - 1) * $per_page;

		// Get filters from URL
		$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
		$provider_filter = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
		$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

		// Get sorting
		$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'id';
		$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

		// Build args for repository
		$args = array(
			'offset'  => $offset,
			'limit'   => $per_page,
			'orderby' => $orderby,
			'order'   => $order
		);

		if (!empty($search)) {
			$args['search'] = $search;
		}

		if (!empty($provider_filter)) {
			$args['provider'] = $provider_filter;
		}

		if (!empty($status_filter)) {
			$args['status'] = $status_filter;
		}

		// Get data from repository
		$accounts = VD_Accounts_Repository::get_accounts($args);
		$total_items = VD_Accounts_Repository::get_total_count($args);

		// Set items
		$this->items = $accounts;

		// Set pagination
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));
	}

	/**
	 * Default column handler
	 *
	 * @since 1.0.0
	 * @param object $item        The current item
	 * @param string $column_name The column name
	 * @return string
	 */
	public function column_default($item, $column_name) {
		switch ($column_name) {
			case 'id':
			case 'account_login':
			case 'created_at':
				return esc_html($item->$column_name);
			default:
				return '';
		}
	}

	/**
	 * Checkbox column
	 *
	 * @since 1.0.0
	 * @param object $item The current item
	 * @return string
	 */
	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="account_ids[]" value="%d" />',
			$item->id
		);
	}

	/**
	 * Display name column with edit link
	 *
	 * @since 1.0.0
	 * @param object $item The current item
	 * @return string
	 */
	public function column_display_name($item) {
		$edit_url = admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $item->id);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>',
			esc_url($edit_url),
			esc_html($item->display_name)
		);
	}

	/**
	 * Provider column with icon (supports free-text providers)
	 *
	 * @since 1.0.0
	 * @param object $item The current item
	 * @return string
	 */
	public function column_provider($item) {
		// Icon mapping for known providers (case-insensitive)
		$icons = array(
			'netflix'        => '🎬',
			'spotify'        => '🎵',
			'youtube'        => '📺',
			'disney'         => '🏰',
			'disney+'        => '🏰',
			'hbo'            => '📽️',
			'hbo max'        => '📽️',
			'amazon'         => '📦',
			'amazon prime'   => '📦',
			'hulu'           => '🟢',
			'apple'          => '🍎',
			'apple tv'       => '🍎',
			'paramount'      => '⭐',
			'peacock'        => '🦚',
			'tiktok'         => '🎤',
			'twitch'         => '🎮',
			'canva'          => '🎨',
			'other'          => '📌'
		);

		// Get provider name and normalize for icon lookup
		$provider_name = strtolower(trim($item->provider));
		$icon = isset($icons[$provider_name]) ? $icons[$provider_name] : '📌';

		return sprintf(
			'<span title="%s">%s %s</span>',
			esc_attr($item->provider), // Tooltip shows full provider name
			$icon,
			esc_html($item->provider) // Display provider name as-is (preserve case)
		);
	}

	/**
	 * Capacity column with usage
	 *
	 * @since 1.0.0
	 * @param object $item The current item
	 * @return string
	 */
	public function column_capacity($item) {
		$usage = VD_Accounts_Repository::get_account_usage($item->id);
		$used = $usage['used'];
		$total = $item->capacity;
		$percentage = $total > 0 ? ($used / $total) * 100 : 0;

		// Color based on usage
		$color = $percentage >= 90 ? '#dc3232' : ($percentage >= 70 ? '#f0b849' : '#46b450');

		return sprintf(
			'%d / %d <span style="color: %s;">(%.0f%%)</span>',
			$used,
			$total,
			$color,
			$percentage
		);
	}

	/**
	 * Status column with colored indicator
	 *
	 * @since 1.0.0
	 * @param object $item The current item
	 * @return string
	 */
	public function column_status($item) {
		$statuses = array(
			'active'    => array('color' => '#46b450', 'label' => __('Active', 'vd-license-manager')),
			'suspended' => array('color' => '#f0b849', 'label' => __('Suspended', 'vd-license-manager')),
			'expired'   => array('color' => '#dc3232', 'label' => __('Expired', 'vd-license-manager'))
		);

		$status_info = isset($statuses[$item->status]) ? $statuses[$item->status] : $statuses['active'];

		return sprintf(
			'<span style="color: %s;">● %s</span>',
			$status_info['color'],
			$status_info['label']
		);
	}

	/**
	 * Actions column
	 *
	 * @since 1.0.0
	 * @param object $item The current item
	 * @return string
	 */
	public function column_actions($item) {
		$edit_url = admin_url('admin.php?page=vd-provider-accounts&action=edit&id=' . $item->id);

		return sprintf(
			'<a href="%s" class="button button-small">%s</a>',
			esc_url($edit_url),
			__('Edit', 'vd-license-manager')
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete'    => __('Delete', 'vd-license-manager'),
			'activate'  => __('Activate', 'vd-license-manager'),
			'suspend'   => __('Suspend', 'vd-license-manager')
		);
	}

	/**
	 * Extra table navigation (filters)
	 *
	 * @since 1.0.0
	 * @param string $which Top or bottom of table
	 */
	public function extra_tablenav($which) {
		if ($which !== 'top') {
			return;
		}

		$providers = VD_Accounts_Repository::get_providers();
		$selected_provider = isset($_GET['provider']) ? $_GET['provider'] : '';
		$selected_status = isset($_GET['status']) ? $_GET['status'] : '';

		?>
		<div class="alignleft actions">
			<select name="provider">
				<option value=""><?php _e('All Providers', 'vd-license-manager'); ?></option>
				<?php foreach ($providers as $provider): ?>
					<option value="<?php echo esc_attr($provider); ?>" <?php selected($selected_provider, $provider); ?>>
						<?php echo esc_html($provider); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="status">
				<option value=""><?php _e('All Statuses', 'vd-license-manager'); ?></option>
				<option value="active" <?php selected($selected_status, 'active'); ?>><?php _e('Active', 'vd-license-manager'); ?></option>
				<option value="suspended" <?php selected($selected_status, 'suspended'); ?>><?php _e('Suspended', 'vd-license-manager'); ?></option>
				<option value="expired" <?php selected($selected_status, 'expired'); ?>><?php _e('Expired', 'vd-license-manager'); ?></option>
			</select>

			<?php submit_button(__('Filter', 'vd-license-manager'), '', 'filter_action', false); ?>
		</div>
		<?php
	}
}
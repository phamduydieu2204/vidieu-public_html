<?php
/**
 * Product Pools Admin Controller
 *
 * Handles the Product Pools admin page routing and actions
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Product Pools Admin Controller Class
 *
 * Manages admin interface for product pools using static methods
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Product_Pools {

	/**
	 * Initialize hooks
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action('admin_menu', array(__CLASS__, 'add_menu_page'));
	}

	/**
	 * Add menu page to WordPress admin
	 *
	 * @since 1.0.0
	 */
	public static function add_menu_page() {
		add_submenu_page(
			'vd-license-manager',           // Parent slug
			__('Product Pools', 'vd-license-manager'),
			__('Product Pools', 'vd-license-manager'),
			'manage_options',
			'vd-product-pools',
			array(__CLASS__, 'render')
		);
	}

	/**
	 * Handle pool actions (delete, bulk delete)
	 *
	 * @since 1.0.0
	 */
	public static function handle_actions() {
		// Only process on our admin page
		if (!isset($_GET['page']) || $_GET['page'] !== 'vd-product-pools') {
			return;
		}

		// Handle delete action
		if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['pool'])) {
			self::handle_delete();
			return; // handle_delete() will redirect, this is just safety
		}

		// Handle bulk delete action
		if (isset($_POST['action']) && $_POST['action'] === 'bulk-delete') {
			self::handle_bulk_delete();
			return; // handle_bulk_delete() will redirect
		}
	}

	/**
	 * Handle single pool deletion
	 *
	 * @since 1.0.0
	 */
	private static function handle_delete() {
		// Verify nonce
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete-pool_' . $_GET['pool'])) {
			wp_die(__('Kiểm tra bảo mật thất bại.', 'vd-license-manager'));
		}

		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('Bạn không có quyền thực hiện hành động này.', 'vd-license-manager'));
		}

		$pool_id = intval($_GET['pool']);

		// Load repository
		require_once VD_PLUGIN_PATH . 'includes/repositories/class-vd-pools-repository.php';

		// Delete pool
		$result = VD_Pools_Repository::delete_pool($pool_id);

		if (is_wp_error($result)) {
			$message = urlencode(__('Lỗi khi xóa pool: ', 'vd-license-manager') . $result->get_error_message());
			$redirect = add_query_arg(array('message' => $message, 'type' => 'error'), admin_url('admin.php?page=vd-product-pools'));
		} else {
			$message = urlencode(__('Pool đã được xóa thành công.', 'vd-license-manager'));
			$redirect = add_query_arg(array('message' => $message, 'type' => 'success'), admin_url('admin.php?page=vd-product-pools'));
		}

		wp_redirect($redirect);
		exit;
	}

	/**
	 * Handle bulk pool deletion
	 *
	 * @since 1.0.0
	 */
	private static function handle_bulk_delete() {
		// Verify nonce
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bulk-pools')) {
			wp_die(__('Kiểm tra bảo mật thất bại.', 'vd-license-manager'));
		}

		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('Bạn không có quyền thực hiện hành động này.', 'vd-license-manager'));
		}

		// Get selected pools
		$pool_ids = isset($_POST['pool']) ? array_map('intval', $_POST['pool']) : array();

		if (empty($pool_ids)) {
			return;
		}

		// Load repository
		require_once VD_PLUGIN_PATH . 'includes/repositories/class-vd-pools-repository.php';

		// Delete pools
		$deleted = 0;
		$errors = array();

		foreach ($pool_ids as $pool_id) {
			$result = VD_Pools_Repository::delete_pool($pool_id);
			if (is_wp_error($result)) {
				$errors[] = sprintf(__('Không thể xóa pool ID %d: %s', 'vd-license-manager'), $pool_id, $result->get_error_message());
			} else {
				$deleted++;
			}
		}

		if (!empty($errors)) {
			$message = sprintf(
				__('%d pool(s) đã được xóa. Lỗi: %s', 'vd-license-manager'),
				$deleted,
				implode('; ', $errors)
			);
			$type = 'warning';
		} else {
			$message = sprintf(
				__('%d pool(s) đã được xóa thành công.', 'vd-license-manager'),
				$deleted
			);
			$type = 'success';
		}

		$redirect = add_query_arg(
			array('message' => urlencode($message), 'type' => $type),
			admin_url('admin.php?page=vd-product-pools')
		);

		wp_redirect($redirect);
		exit;
	}

	/**
	 * Render the main page
	 *
	 * @since 1.0.0
	 */
	public static function render() {
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'vd-license-manager'));
		}

		// Process form submission FIRST
		require_once VD_PLUGIN_PATH . 'admin/pages/pools/class-vd-pools-form-handler.php';
		$form_result = VD_Pools_Form_Handler::process();

		// Handle other actions
		self::handle_actions();

		// Determine which view to show
		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

		switch ($action) {
			case 'add':
				self::render_add_form($form_result);
				break;

			case 'edit':
				self::render_edit_form($form_result);
				break;

			case 'list':
			default:
				self::render_list_page();
				break;
		}
	}

	/**
	 * Render add form
	 *
	 * @since 1.0.0
	 * @param array|null $form_result Form processing result
	 */
	private static function render_add_form($form_result) {
		require_once VD_PLUGIN_PATH . 'admin/pages/pools/class-vd-pools-form-view.php';

		$errors = $form_result && !$form_result['success'] ? $form_result['errors'] : null;

		$view = new VD_Pools_Form_View(null, $errors);
		$view->render();
	}

	/**
	 * Render edit form
	 *
	 * @since 1.0.0
	 * @param array|null $form_result Form processing result
	 */
	private static function render_edit_form($form_result) {
		if (!isset($_GET['pool'])) {
			wp_die(__('Pool ID là bắt buộc.', 'vd-license-manager'));
		}

		$pool_id = intval($_GET['pool']);

		require_once VD_PLUGIN_PATH . 'admin/pages/pools/class-vd-pools-form-handler.php';

		// Validate access
		$access_check = VD_Pools_Form_Handler::validate_edit_access($pool_id);
		if ($access_check) {
			wp_die($access_check['message']);
		}

		$pool = VD_Pools_Form_Handler::get_pool_for_edit($pool_id);

		if (!$pool) {
			wp_die(__('Pool không tồn tại.', 'vd-license-manager'));
		}

		require_once VD_PLUGIN_PATH . 'admin/pages/pools/class-vd-pools-form-view.php';

		$errors = $form_result && !$form_result['success'] ? $form_result['errors'] : null;

		$view = new VD_Pools_Form_View($pool, $errors);
		$view->render();
	}

	/**
	 * Render the list view
	 *
	 * @since 1.0.0
	 */
	private static function render_list_page() {
		// Load list view
		require_once VD_PLUGIN_PATH . 'admin/pages/pools/class-vd-pools-list-view.php';

		$view = new VD_Pools_List_View();
		$view->render();
	}
}

// Initialize the controller
VD_Admin_Product_Pools::init();
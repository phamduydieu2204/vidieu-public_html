<?php
/**
 * Share Configs Admin Controller
 *
 * Handles the Share Configs admin page routing and actions
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Share Configs Admin Controller Class
 *
 * Manages admin interface for share configs using static methods
 *
 * @package VD_License_Manager
 * @since 1.0.1
 */
class VD_Admin_Share_Configs {

	/**
	 * Initialize hooks
	 *
	 * @since 1.0.1
	 */
	public static function init() {
		add_action('admin_menu', array(__CLASS__, 'add_menu_page'));
	}

	/**
	 * Add menu page to WordPress admin
	 *
	 * @since 1.0.1
	 */
	public static function add_menu_page() {
		add_submenu_page(
			'vd-license-manager',           // Parent slug
			__('Cấu Hình Chia Sẻ', 'vd-license-manager'),
			__('Cấu Hình Chia Sẻ', 'vd-license-manager'),
			'manage_options',
			'vd-share-configs',
			array(__CLASS__, 'render')
		);
	}

	/**
	 * Handle config actions (delete, bulk delete)
	 *
	 * @since 1.0.1
	 */
	public static function handle_actions() {
		// Only process on our admin page
		if (!isset($_GET['page']) || $_GET['page'] !== 'vd-share-configs') {
			return;
		}

		// Handle delete action
		if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['config'])) {
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
	 * Handle single config deletion
	 *
	 * @since 1.0.1
	 */
	private static function handle_delete() {
		// Verify nonce
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete-config_' . $_GET['config'])) {
			wp_die(__('Kiểm tra bảo mật thất bại.', 'vd-license-manager'));
		}

		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('Bạn không có quyền thực hiện hành động này.', 'vd-license-manager'));
		}

		$config_id = intval($_GET['config']);

		// Delete config
		$result = VD_Share_Config_Repository::delete_config($config_id);

		if (is_wp_error($result)) {
			$message = urlencode(__('Lỗi khi xóa cấu hình: ', 'vd-license-manager') . $result->get_error_message());
			$redirect = add_query_arg(array('message' => $message, 'type' => 'error'), admin_url('admin.php?page=vd-share-configs'));
		} else {
			$message = urlencode(__('Cấu hình đã được xóa thành công.', 'vd-license-manager'));
			$redirect = add_query_arg(array('message' => $message, 'type' => 'success'), admin_url('admin.php?page=vd-share-configs'));
		}

		wp_redirect($redirect);
		exit;
	}

	/**
	 * Handle bulk config deletion
	 *
	 * @since 1.0.1
	 */
	private static function handle_bulk_delete() {
		// Verify nonce
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bulk-configs')) {
			wp_die(__('Kiểm tra bảo mật thất bại.', 'vd-license-manager'));
		}

		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('Bạn không có quyền thực hiện hành động này.', 'vd-license-manager'));
		}

		// Get selected configs
		$config_ids = isset($_POST['config']) ? array_map('intval', $_POST['config']) : array();

		if (empty($config_ids)) {
			return;
		}

		// Delete configs
		$deleted = 0;
		$errors = array();

		foreach ($config_ids as $config_id) {
			$result = VD_Share_Config_Repository::delete_config($config_id);
			if (is_wp_error($result)) {
				$errors[] = sprintf(__('Không thể xóa cấu hình ID %d: %s', 'vd-license-manager'), $config_id, $result->get_error_message());
			} else {
				$deleted++;
			}
		}

		if (!empty($errors)) {
			$message = sprintf(
				__('%d cấu hình đã được xóa. Lỗi: %s', 'vd-license-manager'),
				$deleted,
				implode('; ', $errors)
			);
			$type = 'warning';
		} else {
			$message = sprintf(
				__('%d cấu hình đã được xóa thành công.', 'vd-license-manager'),
				$deleted
			);
			$type = 'success';
		}

		$redirect = add_query_arg(
			array('message' => urlencode($message), 'type' => $type),
			admin_url('admin.php?page=vd-share-configs')
		);

		wp_redirect($redirect);
		exit;
	}

	/**
	 * Render the main page
	 *
	 * @since 1.0.1
	 */
	public static function render() {
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'vd-license-manager'));
		}

		// Process form submission FIRST
		$form_result = VD_Share_Configs_Form_Handler::process();

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
	 * @since 1.0.1
	 * @param array|null $form_result Form processing result
	 */
	private static function render_add_form($form_result) {
		$errors = $form_result && !$form_result['success'] ? $form_result['errors'] : null;

		$view = new VD_Share_Configs_Form_View(null, $errors);
		$view->render();
	}

	/**
	 * Render edit form
	 *
	 * @since 1.0.1
	 * @param array|null $form_result Form processing result
	 */
	private static function render_edit_form($form_result) {
		$config_id = isset($_GET['config']) ? intval($_GET['config']) : 0;

		if ($config_id <= 0) {
			wp_die(__('ID cấu hình không hợp lệ.', 'vd-license-manager'));
		}

		// Get config
		$config = VD_Share_Config_Repository::get_config($config_id);

		if (!$config) {
			wp_die(__('Không tìm thấy cấu hình.', 'vd-license-manager'));
		}

		$errors = $form_result && !$form_result['success'] ? $form_result['errors'] : null;

		$view = new VD_Share_Configs_Form_View($config, $errors);
		$view->render();
	}

	/**
	 * Render list page
	 *
	 * @since 1.0.1
	 */
	private static function render_list_page() {
		$view = new VD_Share_Configs_List_View();
		$view->render();
	}
}
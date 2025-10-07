<?php
/**
 * Provider Accounts main controller
 *
 * Handles routing between list, add, edit views
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check: Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * VD Admin Provider Accounts class
 *
 * Main controller for provider accounts management in WordPress admin
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Admin_Provider_Accounts {

	/**
	 * Main render method - routes to appropriate view
	 *
	 * @since 1.0.0
	 */
	public static function render() {
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'vd-license-manager'));
		}

		// Get action from URL
		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

		// Route to appropriate view
		switch ($action) {
			case 'add':
				self::render_add();
				break;

			case 'edit':
				self::render_edit();
				break;

			case 'save':
				self::handle_save();
				break;

			default:
				self::render_list();
				break;
		}
	}

	/**
	 * Render accounts list view
	 *
	 * @since 1.0.0
	 */
	private static function render_list() {
		// Load list view class
		require_once VD_PLUGIN_PATH . 'admin/pages/accounts/class-vd-accounts-list-view.php';

		// Render the list page
		VD_Accounts_List_View::render();
	}

	/**
	 * Render add new account form
	 *
	 * @since 1.0.0
	 */
	private static function render_add() {
		// Load form view class
		require_once VD_PLUGIN_PATH . 'admin/pages/accounts/class-vd-accounts-form-view.php';

		// Render add form
		VD_Accounts_Form_View::render_add();
	}

	/**
	 * Render edit account form
	 *
	 * @since 1.0.0
	 */
	private static function render_edit() {
		// Get account ID from URL
		$account_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

		if ($account_id <= 0) {
			wp_die(__('Invalid account ID.', 'vd-license-manager'));
		}

		// Verify account exists
		$account = VD_Accounts_Repository::get_account($account_id);
		if (!$account) {
			wp_die(__('Account not found.', 'vd-license-manager'));
		}

		// Load form view class
		require_once VD_PLUGIN_PATH . 'admin/pages/accounts/class-vd-accounts-form-view.php';

		// Render edit form
		VD_Accounts_Form_View::render_edit($account_id);
	}

	/**
	 * Handle form save operations
	 *
	 * @since 1.0.0
	 */
	private static function handle_save() {
		// This will be implemented in Sprint 3
		// For now, just add a TODO comment

		// TODO: Implement form handler in Sprint 3
		wp_die(__('Form handler not yet implemented.', 'vd-license-manager'));
	}
}
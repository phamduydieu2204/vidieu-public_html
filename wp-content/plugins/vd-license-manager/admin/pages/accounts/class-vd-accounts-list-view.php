<?php
/**
 * Provider Accounts list view wrapper
 *
 * Renders the accounts list page layout
 *
 * @package VD_License_Manager
 * @subpackage Admin
 * @since 1.0.0
 */

// Security check
if (!defined('ABSPATH')) {
	exit;
}

/**
 * VD Accounts List View class
 *
 * Handles the layout and rendering of the accounts list page
 *
 * @package VD_License_Manager
 * @since 1.0.0
 */
class VD_Accounts_List_View {

	/**
	 * Main render method
	 *
	 * @since 1.0.0
	 */
	public static function render() {
		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'vd-license-manager'));
		}

		// Render messages
		self::render_messages();

		// Render page
		?>
		<div class="wrap">
			<?php self::render_header(); ?>
			<?php self::render_table(); ?>
		</div>
		<?php
	}

	/**
	 * Render page header with title and "Add New" button
	 *
	 * @since 1.0.0
	 */
	private static function render_header() {
		?>
		<h1 class="wp-heading-inline">
			<?php echo esc_html__('Provider Accounts', 'vd-license-manager'); ?>
		</h1>

		<a href="<?php echo esc_url(admin_url('admin.php?page=vd-provider-accounts&action=add')); ?>" class="page-title-action">
			<?php echo esc_html__('Add New', 'vd-license-manager'); ?>
		</a>

		<hr class="wp-header-end">
		<?php
	}

	/**
	 * Render the accounts list table
	 *
	 * @since 1.0.0
	 */
	private static function render_table() {
		// Load list table class
		require_once VD_PLUGIN_PATH . 'admin/pages/accounts/class-vd-accounts-list-table.php';

		// Create table instance
		$list_table = new VD_Accounts_List_Table();

		// Prepare items
		$list_table->prepare_items();

		?>
		<form method="get">
			<input type="hidden" name="page" value="vd-provider-accounts">
			<?php
			$list_table->search_box(__('Search Accounts', 'vd-license-manager'), 'vd-accounts');
			$list_table->display();
			?>
		</form>
		<?php
	}

	/**
	 * Render admin messages from URL parameters
	 *
	 * @since 1.0.0
	 */
	private static function render_messages() {
		// Check for message in URL
		if (!isset($_GET['message'])) {
			return;
		}

		$message = sanitize_text_field($_GET['message']);
		$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';

		// Determine notice class
		$notice_class = ($type === 'error') ? 'notice-error' : 'notice-success';

		?>
		<div class="notice <?php echo esc_attr($notice_class); ?> is-dismissible">
			<p><?php echo esc_html($message); ?></p>
		</div>
		<?php
	}
}
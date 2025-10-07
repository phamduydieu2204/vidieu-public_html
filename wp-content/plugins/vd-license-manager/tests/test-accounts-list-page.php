<?php
/**
 * TEMPORARY Test File - Delete after verification
 * Access: /wp-content/plugins/vd-license-manager/tests/test-accounts-list-page.php?run_test=yes
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Security: Only allow admin users
if (!current_user_can('manage_options')) {
	wp_die('Access denied. Admin access required.');
}

// Security: Require special URL parameter
if (!isset($_GET['run_test']) || $_GET['run_test'] !== 'yes') {
	wp_die('Add ?run_test=yes to URL to run tests');
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>VD Accounts List Page Tests</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 1200px;
			margin: 20px auto;
			padding: 20px;
			background: #f5f5f5;
		}
		h1 {
			color: #333;
			border-bottom: 3px solid #0073aa;
			padding-bottom: 10px;
		}
		h2 {
			color: #0073aa;
			margin-top: 30px;
			padding: 10px;
			background: #fff;
			border-left: 4px solid #0073aa;
		}
		p {
			padding: 10px;
			background: #fff;
			margin: 10px 0;
		}
		.summary {
			background: #0073aa;
			color: white;
			padding: 20px;
			margin: 20px 0;
			border-radius: 5px;
		}
	</style>
</head>
<body>
	<h1>🧪 VD Accounts List Page Tests</h1>
	<p><strong>Warning:</strong> This is a temporary test file. Delete after verification.</p>

	<?php
	// TEST 1: Check Admin Classes Load
	echo '<h2>TEST 1: Check Admin Classes Load</h2>';

	$classes_to_test = array(
		'VD_Admin_Provider_Accounts',
		'VD_Accounts_List_Table',
		'VD_Accounts_List_View'
	);

	$all_classes_loaded = true;

	foreach ($classes_to_test as $class_name) {
		if (class_exists($class_name)) {
			echo '<p style="color: green;">✅ ' . $class_name . ' class loaded successfully</p>';
		} else {
			echo '<p style="color: red;">❌ ' . $class_name . ' class NOT found</p>';
			$all_classes_loaded = false;
		}
	}

	if (!$all_classes_loaded) {
		echo '<p style="color: red;"><strong>STOPPING TESTS:</strong> Required classes not loaded. Check autoloader.</p>';
		exit;
	}

	// TEST 2: Test Repository Classes
	echo '<h2>TEST 2: Check Repository Classes</h2>';

	if (class_exists('VD_Accounts_Repository')) {
		echo '<p style="color: green;">✅ VD_Accounts_Repository available</p>';
	} else {
		echo '<p style="color: red;">❌ VD_Accounts_Repository NOT available</p>';
	}

	if (class_exists('VD_Account_Validator')) {
		echo '<p style="color: green;">✅ VD_Account_Validator available</p>';
	} else {
		echo '<p style="color: red;">❌ VD_Account_Validator NOT available</p>';
	}

	// TEST 3: Test Controller Instantiation
	echo '<h2>TEST 3: Test Controller Methods</h2>';

	try {
		// Check if render method exists
		if (method_exists('VD_Admin_Provider_Accounts', 'render')) {
			echo '<p style="color: green;">✅ VD_Admin_Provider_Accounts::render() method exists</p>';
		} else {
			echo '<p style="color: red;">❌ VD_Admin_Provider_Accounts::render() method missing</p>';
		}
	} catch (Exception $e) {
		echo '<p style="color: red;">❌ Controller test failed: ' . $e->getMessage() . '</p>';
	}

	// TEST 4: Test List Table Instantiation
	echo '<h2>TEST 4: Test List Table Creation</h2>';

	try {
		$list_table = new VD_Accounts_List_Table();
		echo '<p style="color: green;">✅ VD_Accounts_List_Table instantiated successfully</p>';

		// Test methods exist
		$required_methods = array('get_columns', 'prepare_items', 'column_default');
		foreach ($required_methods as $method) {
			if (method_exists($list_table, $method)) {
				echo '<p style="color: green;">✅ Method ' . $method . '() exists</p>';
			} else {
				echo '<p style="color: red;">❌ Method ' . $method . '() missing</p>';
			}
		}
	} catch (Exception $e) {
		echo '<p style="color: red;">❌ List table creation failed: ' . $e->getMessage() . '</p>';
	}

	// TEST 5: Test List View Methods
	echo '<h2>TEST 5: Test List View Methods</h2>';

	$view_methods = array('render');
	foreach ($view_methods as $method) {
		if (method_exists('VD_Accounts_List_View', $method)) {
			echo '<p style="color: green;">✅ VD_Accounts_List_View::' . $method . '() exists</p>';
		} else {
			echo '<p style="color: red;">❌ VD_Accounts_List_View::' . $method . '() missing</p>';
		}
	}

	// TEST 6: Test Database Connection
	echo '<h2>TEST 6: Test Database Access</h2>';

	try {
		$total_accounts = VD_Accounts_Repository::get_total_count();
		echo '<p style="color: green;">✅ Database access working - Total accounts: ' . $total_accounts . '</p>';
	} catch (Exception $e) {
		echo '<p style="color: red;">❌ Database access failed: ' . $e->getMessage() . '</p>';
	}

	// TEST 7: Test Provider List
	echo '<h2>TEST 7: Test Provider List</h2>';

	try {
		$providers = VD_Accounts_Repository::get_providers();
		if (is_array($providers)) {
			echo '<p style="color: green;">✅ Provider list retrieved - Count: ' . count($providers) . '</p>';
		} else {
			echo '<p style="color: red;">❌ Provider list not an array</p>';
		}
	} catch (Exception $e) {
		echo '<p style="color: red;">❌ Provider list failed: ' . $e->getMessage() . '</p>';
	}
	?>

	<div class="summary">
		<h2 style="color: white; background: transparent; border: none;">✅ Test Summary</h2>
		<p style="background: transparent;">If all tests above show ✅ PASSED, the accounts list page is ready!</p>
		<p style="background: transparent;"><strong>Next steps:</strong></p>
		<ol style="background: transparent;">
			<li>Delete this test file</li>
			<li>Access the accounts page in WordPress admin</li>
			<li>Verify the page displays correctly</li>
			<li>SPRINT 2 COMPLETED! 🎉</li>
		</ol>
		<p style="background: transparent;"><strong>File to delete:</strong></p>
		<pre style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3);">/wp-content/plugins/vd-license-manager/tests/test-accounts-list-page.php</pre>
	</div>
</body>
</html>
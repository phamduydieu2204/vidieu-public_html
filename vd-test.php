<?php
/**
 * TEMPORARY Test File - Delete after verification
 * Access: /vd-test.php?run_test=yes
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Security: Only allow admin users
if (!current_user_can('manage_options')) {
	wp_die('Access denied. Admin access required.');
}

// Security: Require special URL parameter
if (!isset($_GET['run_test']) || $_GET['run_test'] !== 'yes') {
	wp_die('Add ?run_test=yes to URL to run tests');
}

// Global variable to store test account ID
global $test_account_id;
$test_account_id = null;

?>
<!DOCTYPE html>
<html>
<head>
	<title>VD Accounts Repository Tests</title>
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
		pre {
			background: #f9f9f9;
			padding: 15px;
			border: 1px solid #ddd;
			overflow-x: auto;
		}
		table {
			width: 100%;
			background: #fff;
			border-collapse: collapse;
		}
		table th, table td {
			padding: 8px;
			border: 1px solid #ddd;
			text-align: left;
		}
		table th {
			background: #f0f0f0;
		}
		.summary {
			background: #0073aa;
			color: white;
			padding: 20px;
			margin: 20px 0;
			border-radius: 5px;
		}
		ul, ol {
			background: #fff;
			padding: 15px;
			margin: 10px 0;
		}
	</style>
</head>
<body>
	<h1>🧪 VD Accounts Repository & Validator Tests</h1>
	<p><strong>Warning:</strong> This is a temporary test file. Delete after verification.</p>
	<p><strong>Location:</strong> Root directory (easier access)</p>

	<?php
	// TEST 1: Test Classes Load
	echo '<h2>TEST 0: Check Classes Load</h2>';

	if (class_exists('VD_Accounts_Repository')) {
		echo '<p style="color: green;">✅ VD_Accounts_Repository class loaded</p>';
	} else {
		echo '<p style="color: red;">❌ VD_Accounts_Repository class NOT found</p>';
	}

	if (class_exists('VD_Account_Validator')) {
		echo '<p style="color: green;">✅ VD_Account_Validator class loaded</p>';
	} else {
		echo '<p style="color: red;">❌ VD_Account_Validator class NOT found</p>';
	}

	// Check if we can proceed
	if (!class_exists('VD_Accounts_Repository') || !class_exists('VD_Account_Validator')) {
		echo '<p style="color: red;"><strong>STOPPING TESTS:</strong> Required classes not loaded. Check autoloader.</p>';
		exit;
	}

	// TEST 1: Test Validator - Valid Data
	echo '<h2>TEST 1: Validate Add - Valid Data</h2>';

	$valid_data = array(
		'provider' => 'netflix',
		'account_login' => 'test_' . time() . '@example.com',
		'display_name' => 'Test Netflix Account',
		'capacity' => 5,
		'status' => 'active',
		'login_email' => 'login@example.com',
		'recovery_email' => 'recovery@example.com',
		'recovery_phone' => '+1234567890'
	);

	$result = VD_Account_Validator::validate_add($valid_data);

	if (is_wp_error($result)) {
		echo '<p style="color: red;">❌ FAILED: ' . $result->get_error_message() . '</p>';
	} else {
		echo '<p style="color: green;">✅ PASSED: Validation successful</p>';
	}

	// TEST 2: Test Validator - Invalid Data
	echo '<h2>TEST 2: Validate Add - Invalid Data (Should Fail)</h2>';

	$invalid_data = array(
		'provider' => '',  // Empty - should fail
		'account_login' => '',  // Empty - should fail
		'display_name' => '',  // Empty - should fail
		'login_email' => 'not-an-email',  // Invalid format
	);

	$result = VD_Account_Validator::validate_add($invalid_data);

	if (is_wp_error($result)) {
		echo '<p style="color: green;">✅ PASSED: Validation correctly failed</p>';
		echo '<ul>';
		foreach ($result->get_error_messages() as $error) {
			echo '<li>' . esc_html($error) . '</li>';
		}
		echo '</ul>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Should have returned validation errors</p>';
	}

	// TEST 3: Test Insert Account
	echo '<h2>TEST 3: Insert New Account</h2>';

	// First, check if table exists
	global $wpdb;
	$table_exists = $wpdb->get_var("SHOW TABLES LIKE 'bz_vd_provider_accounts'");
	if ($table_exists) {
		echo '<p style="color: green;">✅ Table bz_vd_provider_accounts exists</p>';
	} else {
		echo '<p style="color: red;">❌ Table bz_vd_provider_accounts does NOT exist</p>';
		echo '<p><strong>Available tables with bz_vd prefix:</strong></p>';
		$tables = $wpdb->get_results("SHOW TABLES LIKE 'bz_vd_%'");
		if ($tables) {
			echo '<ul>';
			foreach ($tables as $table) {
				$name = array_values((array)$table)[0];
				echo '<li>' . $name . '</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>No tables found with bz_vd prefix</p>';
		}
	}

	$test_account_data = array(
		'provider' => 'netflix',
		'account_login' => 'test_' . time() . '@example.com',
		'display_name' => 'Test Account ' . time(),
		'capacity' => 5,
		'status' => 'active',
		'login_email' => 'test@example.com',
		'login_password' => 'test_password_123',
		'notes' => 'Created by automated test'
	);

	echo '<p><strong>Data to insert:</strong></p>';
	echo '<pre>' . print_r($test_account_data, true) . '</pre>';

	$account_id = VD_Accounts_Repository::insert_account($test_account_data);

	if (is_wp_error($account_id)) {
		echo '<p style="color: red;">❌ FAILED: ' . $account_id->get_error_message() . '</p>';
		echo '<p><strong>WordPress Database Error:</strong> ' . $wpdb->last_error . '</p>';
		echo '<p><strong>Last Query:</strong> ' . $wpdb->last_query . '</p>';
	} else {
		echo '<p style="color: green;">✅ PASSED: Account created with ID: ' . $account_id . '</p>';
		$test_account_id = $account_id;
	}

	// TEST 4: Test Get Account
	echo '<h2>TEST 4: Get Single Account</h2>';

	if (isset($test_account_id) && $test_account_id) {
		$account = VD_Accounts_Repository::get_account($test_account_id);

		if ($account) {
			echo '<p style="color: green;">✅ PASSED: Account retrieved successfully</p>';
			echo '<pre>' . print_r($account, true) . '</pre>';
		} else {
			echo '<p style="color: red;">❌ FAILED: Could not retrieve account</p>';
		}
	} else {
		echo '<p style="color: orange;">⚠️ SKIPPED: No test account ID available</p>';
	}

	// TEST 5: Cleanup
	echo '<h2>TEST 5: Delete Test Account (Cleanup)</h2>';

	if (isset($test_account_id) && $test_account_id) {
		$result = VD_Accounts_Repository::delete_account($test_account_id);

		if (is_wp_error($result)) {
			echo '<p style="color: red;">❌ FAILED: ' . $result->get_error_message() . '</p>';
		} else {
			echo '<p style="color: green;">✅ PASSED: Test account deleted successfully</p>';
		}
	}
	?>

	<div class="summary">
		<h2 style="color: white; background: transparent; border: none;">✅ Test Summary</h2>
		<p style="background: transparent;">Basic tests completed. If successful, classes are working.</p>
		<p style="background: transparent;"><strong>File to delete after testing:</strong></p>
		<pre style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3);">/vd-test.php</pre>
	</div>
</body>
</html>
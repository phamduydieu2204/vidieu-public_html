<?php
/**
 * TEMPORARY Test File - Delete after verification
 * Access: /wp-content/plugins/vd-license-manager/tests/test-accounts-repository.php?run_test=yes
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

	<?php
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

	$account_id = VD_Accounts_Repository::insert_account($test_account_data);

	if (is_wp_error($account_id)) {
		echo '<p style="color: red;">❌ FAILED: ' . $account_id->get_error_message() . '</p>';
	} else {
		echo '<p style="color: green;">✅ PASSED: Account created with ID: ' . $account_id . '</p>';

		// Store for next tests
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

	// TEST 5: Test Get Accounts List
	echo '<h2>TEST 5: Get Accounts List</h2>';

	$accounts = VD_Accounts_Repository::get_accounts(array(
		'limit' => 10,
		'offset' => 0,
		'orderby' => 'id',
		'order' => 'DESC'
	));

	if (is_array($accounts)) {
		echo '<p style="color: green;">✅ PASSED: Retrieved ' . count($accounts) . ' accounts</p>';
		echo '<table border="1" cellpadding="5">';
		echo '<tr><th>ID</th><th>Provider</th><th>Display Name</th><th>Status</th><th>Capacity</th></tr>';
		foreach ($accounts as $acc) {
			echo '<tr>';
			echo '<td>' . $acc->id . '</td>';
			echo '<td>' . esc_html($acc->provider) . '</td>';
			echo '<td>' . esc_html($acc->display_name) . '</td>';
			echo '<td>' . esc_html($acc->status) . '</td>';
			echo '<td>' . $acc->capacity . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Could not retrieve accounts list</p>';
	}

	// TEST 6: Test Update Account
	echo '<h2>TEST 6: Update Account</h2>';

	if (isset($test_account_id) && $test_account_id) {
		$update_data = array(
			'display_name' => 'Updated Test Account',
			'capacity' => 10,
			'notes' => 'Updated by test at ' . date('Y-m-d H:i:s')
		);

		$result = VD_Accounts_Repository::update_account($test_account_id, $update_data);

		if (is_wp_error($result)) {
			echo '<p style="color: red;">❌ FAILED: ' . $result->get_error_message() . '</p>';
		} else {
			echo '<p style="color: green;">✅ PASSED: Account updated successfully</p>';

			// Verify update
			$updated_account = VD_Accounts_Repository::get_account($test_account_id);
			if ($updated_account && $updated_account->display_name === 'Updated Test Account') {
				echo '<p style="color: green;">✅ VERIFIED: Display name updated correctly</p>';
			} else {
				echo '<p style="color: red;">❌ VERIFICATION FAILED: Display name not updated</p>';
			}
		}
	} else {
		echo '<p style="color: orange;">⚠️ SKIPPED: No test account ID available</p>';
	}

	// TEST 7: Test Total Count
	echo '<h2>TEST 7: Get Total Count</h2>';

	$total = VD_Accounts_Repository::get_total_count(array());

	if (is_numeric($total)) {
		echo '<p style="color: green;">✅ PASSED: Total accounts count: ' . $total . '</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Could not get count</p>';
	}

	// TEST 8: Test Get Providers List
	echo '<h2>TEST 8: Get Providers List</h2>';

	$providers = VD_Accounts_Repository::get_providers();

	if (is_array($providers)) {
		echo '<p style="color: green;">✅ PASSED: Retrieved ' . count($providers) . ' distinct providers</p>';
		if (count($providers) > 0) {
			echo '<ul>';
			foreach ($providers as $provider) {
				echo '<li>' . esc_html($provider) . '</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>No providers found in database yet.</p>';
		}
	} else {
		echo '<p style="color: red;">❌ FAILED: Could not retrieve providers</p>';
	}

	// TEST 9: Test Duplicate Detection
	echo '<h2>TEST 9: Test Duplicate Prevention</h2>';

	if (isset($test_account_id) && $test_account_id) {
		$account = VD_Accounts_Repository::get_account($test_account_id);

		if ($account) {
			// Try to insert duplicate
			$duplicate_data = array(
				'provider' => $account->provider,
				'account_login' => $account->account_login,
				'display_name' => 'Duplicate Test',
				'capacity' => 5
			);

			$result = VD_Accounts_Repository::insert_account($duplicate_data);

			if (is_wp_error($result)) {
				echo '<p style="color: green;">✅ PASSED: Duplicate correctly prevented</p>';
				echo '<p>Error message: ' . esc_html($result->get_error_message()) . '</p>';
			} else {
				echo '<p style="color: red;">❌ FAILED: Duplicate was not prevented</p>';
			}
		} else {
			echo '<p style="color: orange;">⚠️ SKIPPED: Could not retrieve test account for duplicate test</p>';
		}
	} else {
		echo '<p style="color: orange;">⚠️ SKIPPED: No test account ID available</p>';
	}

	// TEST 10: Test Account Usage
	echo '<h2>TEST 10: Test Account Usage Statistics</h2>';

	if (isset($test_account_id) && $test_account_id) {
		$usage = VD_Accounts_Repository::get_account_usage($test_account_id);

		if (is_array($usage) && isset($usage['used']) && isset($usage['capacity'])) {
			echo '<p style="color: green;">✅ PASSED: Usage statistics retrieved</p>';
			echo '<p>Used: ' . $usage['used'] . ' / Capacity: ' . $usage['capacity'] . '</p>';
		} else {
			echo '<p style="color: red;">❌ FAILED: Could not get usage statistics</p>';
		}
	} else {
		echo '<p style="color: orange;">⚠️ SKIPPED: No test account ID available</p>';
	}

	// TEST 11: Test Delete Account (Cleanup)
	echo '<h2>TEST 11: Delete Test Account (Cleanup)</h2>';

	if (isset($test_account_id) && $test_account_id) {
		$result = VD_Accounts_Repository::delete_account($test_account_id);

		if (is_wp_error($result)) {
			echo '<p style="color: red;">❌ FAILED: ' . $result->get_error_message() . '</p>';
		} else {
			echo '<p style="color: green;">✅ PASSED: Test account deleted successfully</p>';

			// Verify deletion
			$deleted_account = VD_Accounts_Repository::get_account($test_account_id);
			if (!$deleted_account) {
				echo '<p style="color: green;">✅ VERIFIED: Account no longer exists</p>';
			} else {
				echo '<p style="color: red;">❌ VERIFICATION FAILED: Account still exists</p>';
			}
		}
	} else {
		echo '<p style="color: orange;">⚠️ SKIPPED: No test account to delete</p>';
	}

	// TEST 12: Test Provider Validation
	echo '<h2>TEST 12: Test Provider Validation</h2>';

	$valid_provider = VD_Account_Validator::validate_provider('netflix');
	if ($valid_provider === true) {
		echo '<p style="color: green;">✅ PASSED: Valid provider accepted</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Valid provider rejected</p>';
	}

	$invalid_provider = VD_Account_Validator::validate_provider('invalid_provider');
	if (is_wp_error($invalid_provider)) {
		echo '<p style="color: green;">✅ PASSED: Invalid provider correctly rejected</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Invalid provider was accepted</p>';
	}

	// TEST 13: Test Email Validation
	echo '<h2>TEST 13: Test Email Validation</h2>';

	$valid_email = VD_Account_Validator::validate_email('test@example.com');
	if ($valid_email === true) {
		echo '<p style="color: green;">✅ PASSED: Valid email accepted</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Valid email rejected</p>';
	}

	$invalid_email = VD_Account_Validator::validate_email('not-an-email');
	if (is_wp_error($invalid_email)) {
		echo '<p style="color: green;">✅ PASSED: Invalid email correctly rejected</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Invalid email was accepted</p>';
	}

	// TEST 14: Test Phone Validation
	echo '<h2>TEST 14: Test Phone Validation</h2>';

	$valid_phone = VD_Account_Validator::validate_phone('+1234567890');
	if ($valid_phone === true) {
		echo '<p style="color: green;">✅ PASSED: Valid phone accepted</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Valid phone rejected</p>';
	}

	$invalid_phone = VD_Account_Validator::validate_phone('123abc');
	if (is_wp_error($invalid_phone)) {
		echo '<p style="color: green;">✅ PASSED: Invalid phone correctly rejected</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Invalid phone was accepted</p>';
	}

	// TEST 15: Test Cookie Validation
	echo '<h2>TEST 15: Test Cookie Validation</h2>';

	$valid_json_cookie = '{"session_id": "abc123", "user_token": "def456"}';
	$json_result = VD_Account_Validator::validate_cookie($valid_json_cookie, 'json');
	if ($json_result === true) {
		echo '<p style="color: green;">✅ PASSED: Valid JSON cookie accepted</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Valid JSON cookie rejected</p>';
	}

	$invalid_json_cookie = '{"invalid": json}';
	$invalid_json_result = VD_Account_Validator::validate_cookie($invalid_json_cookie, 'json');
	if (is_wp_error($invalid_json_result)) {
		echo '<p style="color: green;">✅ PASSED: Invalid JSON cookie correctly rejected</p>';
	} else {
		echo '<p style="color: red;">❌ FAILED: Invalid JSON cookie was accepted</p>';
	}
	?>

	<div class="summary">
		<h2 style="color: white; background: transparent; border: none;">✅ Test Summary</h2>
		<p style="background: transparent;">All tests completed. Review results above.</p>
		<p style="background: transparent;"><strong>Next steps:</strong></p>
		<ol style="background: transparent;">
			<li>If all tests passed, delete this test file</li>
			<li>Proceed to Sprint 2 (Accounts List UI)</li>
			<li>Check WordPress debug.log for any errors</li>
		</ol>
		<p style="background: transparent;"><strong>File to delete:</strong></p>
		<pre style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3);">/wp-content/plugins/vd-license-manager/tests/test-accounts-repository.php</pre>
	</div>
</body>
</html>
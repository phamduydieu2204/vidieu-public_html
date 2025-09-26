# VD License Manager - Testing Strategy

## 1. Testing Overview

### 1.1 Testing Objectives
- Ensure 99%+ license resolution success rate
- Validate security measures against common attack vectors
- Verify performance under high concurrent load (1000+ requests/minute)
- Confirm compatibility across WordPress 5.8+ versions
- Validate data integrity and encryption security
- Ensure user interface functionality across browsers

### 1.2 Quality Gates
```
✅ Unit Testing: 90%+ code coverage
✅ Integration Testing: All API endpoints functional
✅ Security Testing: Zero critical vulnerabilities
✅ Performance Testing: <200ms average response time
✅ Compatibility Testing: WordPress 5.8-6.4, PHP 7.4-8.2
✅ User Acceptance: 95%+ satisfaction score
```

### 1.3 Testing Environment Strategy
```
Development Environment:
- Local Docker containers
- Test database with sample data
- Debug mode enabled
- All logging active

Staging Environment:
- Production-like configuration
- Anonymized production data
- Performance monitoring enabled
- Integration with external services

Production Environment:
- Live monitoring only
- Canary deployments
- Real user monitoring
- Automated rollback capabilities
```

## 2. Unit Testing Framework

### 2.1 PHPUnit Configuration
```php
// phpunit.xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         backupGlobals="false"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true">

    <testsuites>
        <testsuite name="VD License Manager">
            <directory>./tests/</directory>
        </testsuite>
    </testsuites>

    <filter>
        <whitelist>
            <directory>./includes/</directory>
            <exclude>
                <directory>./includes/vendor/</directory>
            </exclude>
        </whitelist>
    </filter>

    <logging>
        <log type="coverage-html" target="./tests/coverage"/>
        <log type="coverage-clover" target="./tests/coverage.xml"/>
    </logging>
</phpunit>
```

### 2.2 Test Bootstrap Setup
```php
// tests/bootstrap.php
<?php
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find $_tests_dir/includes/functions.php\n";
    exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname( dirname( __FILE__ ) ) . '/vd-license-manager.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
```

### 2.3 Core Unit Tests

#### License Resolution Tests
```php
<?php
class Test_VD_License_Resolver extends WP_UnitTestCase {

    private $resolver;
    private $test_license_key = 'VD-TEST-1234-ABCD';

    public function setUp(): void {
        parent::setUp();
        $this->resolver = new VD_License_Resolver();
        $this->create_test_license();
    }

    public function test_valid_license_resolution() {
        $device_fp = hash('sha256', 'test_device_fingerprint');
        $device_info = [
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'country' => 'VN'
        ];

        $result = $this->resolver->resolve_license_info(
            $this->test_license_key,
            $device_fp,
            $device_info
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('account_info', $result['data']);
    }

    public function test_expired_license_rejection() {
        // Update license to expired status
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vd_licenses',
            ['expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
            ['license_key' => $this->test_license_key]
        );

        $result = $this->resolver->resolve_license_info(
            $this->test_license_key,
            'test_device_fp',
            []
        );

        $this->assertFalse($result['success']);
        $this->assertContains('expired', strtolower($result['message']));
    }

    public function test_rate_limit_enforcement() {
        $device_fp = 'rate_limit_test_device';

        // Simulate exceeding rate limit
        for ($i = 0; $i < 101; $i++) {
            $this->resolver->resolve_license_info(
                $this->test_license_key,
                $device_fp,
                []
            );
        }

        $result = $this->resolver->resolve_license_info(
            $this->test_license_key,
            $device_fp,
            []
        );

        $this->assertFalse($result['success']);
        $this->assertContains('rate limit', strtolower($result['message']));
    }

    public function test_risk_score_calculation() {
        $high_risk_device_info = [
            'ip' => '1.1.1.1',
            'user_agent' => 'Bot/1.0',
            'country' => 'XX'  // Unknown country
        ];

        $risk_score = $this->resolver->calculate_risk_score(
            ['max_devices' => 1],
            'high_risk_device',
            $high_risk_device_info
        );

        $this->assertGreaterThan(70, $risk_score);
        $this->assertLessThanOrEqual(100, $risk_score);
    }

    private function create_test_license() {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vd_licenses',
            [
                'license_key' => $this->test_license_key,
                'product_id' => 1,
                'customer_email' => 'test@example.com',
                'status' => 'active',
                'max_devices' => 5,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
            ]
        );
    }
}
```

#### Security Tests
```php
<?php
class Test_VD_Security extends WP_UnitTestCase {

    private $security;

    public function setUp(): void {
        parent::setUp();
        $this->security = new VD_Security();
    }

    public function test_encryption_decryption() {
        $test_data = [
            'username' => 'test@example.com',
            'password' => 'secure_password_123'
        ];

        $encrypted = $this->security->encrypt_account_info($test_data);
        $decrypted = $this->security->decrypt_account_info($encrypted);

        $this->assertEquals($test_data, $decrypted);
        $this->assertNotEquals($test_data, $encrypted);
    }

    public function test_device_fingerprint_generation() {
        $device_info = [
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 Test',
            'screen_resolution' => '1920x1080',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'language' => 'vi-VN'
        ];

        $fingerprint = $this->security->generate_device_fingerprint($device_info);

        $this->assertEquals(64, strlen($fingerprint)); // SHA-256 hash length
        $this->assertRegExp('/^[a-f0-9]{64}$/', $fingerprint);

        // Same input should produce same fingerprint
        $fingerprint2 = $this->security->generate_device_fingerprint($device_info);
        $this->assertEquals($fingerprint, $fingerprint2);
    }

    public function test_sql_injection_prevention() {
        $malicious_input = "'; DROP TABLE wp_vd_licenses; --";

        $result = $this->security->sanitize_license_key($malicious_input);

        $this->assertNotContains('DROP', $result);
        $this->assertNotContains(';', $result);
        $this->assertNotContains('--', $result);
    }
}
```

## 3. Integration Testing

### 3.1 API Integration Tests
```php
<?php
class Test_VD_API_Integration extends WP_UnitTestCase {

    private $server;

    public function setUp(): void {
        parent::setUp();
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server;
        do_action('rest_api_init');
    }

    public function test_license_resolve_endpoint() {
        $request = new WP_REST_Request('POST', '/vd/v1/license/resolve-info');
        $request->set_header('content-type', 'application/json');
        $request->set_body(json_encode([
            'license_key' => 'VD-TEST-1234-ABCD',
            'device_fp' => hash('sha256', 'test_device'),
            'device_info' => [
                'ip' => '192.168.1.1',
                'user_agent' => 'Test Browser'
            ]
        ]));

        $response = $this->server->dispatch($request);
        $data = $response->get_data();

        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('success', $data);
    }

    public function test_admin_api_authentication() {
        $request = new WP_REST_Request('GET', '/vd/v1/admin/licenses');

        // Test without authentication
        $response = $this->server->dispatch($request);
        $this->assertEquals(401, $response->get_status());

        // Test with valid authentication
        $user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($user);

        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
    }

    public function test_rate_limiting_headers() {
        $request = new WP_REST_Request('POST', '/vd/v1/license/resolve-info');
        $request->set_body(json_encode([
            'license_key' => 'VD-TEST-1234-ABCD',
            'device_fp' => 'test_device'
        ]));

        $response = $this->server->dispatch($request);
        $headers = $response->get_headers();

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }
}
```

### 3.2 Database Integration Tests
```php
<?php
class Test_VD_Database_Integration extends WP_UnitTestCase {

    private $database;

    public function setUp(): void {
        parent::setUp();
        $this->database = new VD_Database();
    }

    public function test_table_creation() {
        $this->database->create_tables();

        global $wpdb;
        $tables = [
            'vd_licenses',
            'vd_provider_accounts',
            'vd_license_assignments',
            'vd_device_records',
            'vd_usage_logs'
        ];

        foreach ($tables as $table) {
            $result = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->prefix . $table
            ));
            $this->assertEquals($wpdb->prefix . $table, $result);
        }
    }

    public function test_foreign_key_constraints() {
        global $wpdb;

        // Create test data
        $license_id = $wpdb->insert(
            $wpdb->prefix . 'vd_licenses',
            [
                'license_key' => 'VD-FK-TEST-1234',
                'product_id' => 1,
                'customer_email' => 'test@example.com'
            ]
        );

        $provider_id = $wpdb->insert(
            $wpdb->prefix . 'vd_provider_accounts',
            [
                'product_id' => 1,
                'account_data' => 'encrypted_data',
                'is_active' => 1
            ]
        );

        // Test valid foreign key relationship
        $assignment_result = $wpdb->insert(
            $wpdb->prefix . 'vd_license_assignments',
            [
                'license_key' => 'VD-FK-TEST-1234',
                'provider_id' => $provider_id,
                'assigned_at' => current_time('mysql')
            ]
        );

        $this->assertNotFalse($assignment_result);

        // Test invalid foreign key (should fail)
        $invalid_result = $wpdb->insert(
            $wpdb->prefix . 'vd_license_assignments',
            [
                'license_key' => 'VD-INVALID-KEY',
                'provider_id' => 99999,
                'assigned_at' => current_time('mysql')
            ]
        );

        $this->assertFalse($invalid_result);
    }
}
```

### 3.3 WooCommerce Integration Tests
```php
<?php
class Test_VD_WooCommerce_Integration extends WC_Unit_Test_Case {

    public function test_license_generation_on_order_completion() {
        // Create a product that requires license
        $product = WC_Helper_Product::create_simple_product();
        update_post_meta($product->get_id(), '_vd_requires_license', 'yes');
        update_post_meta($product->get_id(), '_vd_max_devices', 5);

        // Create an order
        $order = WC_Helper_Order::create_order();
        $order->add_product($product);
        $order->save();

        // Complete the order
        $order->update_status('completed');

        // Check if license was created
        global $wpdb;
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vd_licenses WHERE product_id = %d",
            $product->get_id()
        ));

        $this->assertNotNull($license);
        $this->assertEquals('active', $license->status);
        $this->assertEquals(5, $license->max_devices);
    }
}
```

## 4. Load and Performance Testing

### 4.1 Apache JMeter Test Plan
```xml
<!-- VD License Manager Load Test Plan -->
<jmeterTestPlan version="1.2">
  <hashTree>
    <TestPlan testname="VD License Manager Load Test">
      <elementProp name="TestPlan.arguments" elementType="Arguments"/>
      <stringProp name="TestPlan.user_define_classpath"></stringProp>
      <boolProp name="TestPlan.serialize_threadgroups">false</boolProp>
    </TestPlan>

    <hashTree>
      <!-- Thread Group for License Resolution API -->
      <ThreadGroup testname="License Resolution Load">
        <stringProp name="ThreadGroup.num_threads">100</stringProp>
        <stringProp name="ThreadGroup.ramp_time">60</stringProp>
        <stringProp name="ThreadGroup.duration">300</stringProp>
        <boolProp name="ThreadGroup.scheduler">true</boolProp>
      </ThreadGroup>

      <hashTree>
        <!-- HTTP Request for License Resolution -->
        <HTTPSamplerProxy testname="Resolve License">
          <elementProp name="HTTPsampler.Arguments" elementType="Arguments">
            <collectionProp name="Arguments.arguments">
              <elementProp name="" elementType="HTTPArgument">
                <boolProp name="HTTPArgument.always_encode">false</boolProp>
                <stringProp name="Argument.value">{
                  "license_key": "VD-LOAD-TEST-${__Random(1000,9999)}",
                  "device_fp": "${__UUID()}",
                  "device_info": {
                    "ip": "192.168.1.${__Random(1,254)}",
                    "user_agent": "LoadTest/1.0"
                  }
                }</stringProp>
              </elementProp>
            </collectionProp>
          </elementProp>
          <stringProp name="HTTPSampler.domain">localhost</stringProp>
          <stringProp name="HTTPSampler.port">8080</stringProp>
          <stringProp name="HTTPSampler.path">/wp-json/vd/v1/license/resolve-info</stringProp>
          <stringProp name="HTTPSampler.method">POST</stringProp>
        </HTTPSamplerProxy>

        <!-- Response Time Assertion -->
        <DurationAssertion testname="Response Time < 200ms">
          <stringProp name="DurationAssertion.duration">200</stringProp>
        </DurationAssertion>
      </hashTree>
    </hashTree>
  </hashTree>
</jmeterTestPlan>
```

### 4.2 Performance Test Scripts
```bash
#!/bin/bash
# performance-test.sh

echo "=== VD License Manager Performance Tests ==="

# Database performance test
echo "Testing database query performance..."
php -r "
require_once 'wp-config.php';
\$start = microtime(true);
for (\$i = 0; \$i < 1000; \$i++) {
    \$wpdb->get_row('SELECT * FROM {$wpdb->prefix}vd_licenses LIMIT 1');
}
\$end = microtime(true);
echo 'Average query time: ' . ((\$end - \$start) / 1000 * 1000) . 'ms' . PHP_EOL;
"

# API endpoint performance test
echo "Testing API endpoint performance..."
curl -w "@curl-format.txt" -s -o /dev/null \
    -X POST \
    -H "Content-Type: application/json" \
    -d '{"license_key":"VD-TEST-1234","device_fp":"test"}' \
    http://localhost:8080/wp-json/vd/v1/license/resolve-info

# Memory usage test
echo "Testing memory usage..."
php -d memory_limit=128M -r "
require_once 'wp-config.php';
echo 'Initial memory: ' . memory_get_usage(true) / 1024 / 1024 . 'MB' . PHP_EOL;
\$resolver = new VD_License_Resolver();
for (\$i = 0; \$i < 100; \$i++) {
    \$resolver->resolve_license_info('VD-TEST-' . \$i, 'device_' . \$i);
}
echo 'Peak memory: ' . memory_get_peak_usage(true) / 1024 / 1024 . 'MB' . PHP_EOL;
"
```

### 4.3 Performance Benchmarks
```php
<?php
// Performance benchmark test
class Test_VD_Performance extends WP_UnitTestCase {

    public function test_license_resolution_performance() {
        $resolver = new VD_License_Resolver();
        $iterations = 100;
        $total_time = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $resolver->resolve_license_info(
                'VD-PERF-TEST-' . $i,
                'device_' . $i
            );
            $total_time += microtime(true) - $start;
        }

        $average_time = ($total_time / $iterations) * 1000; // Convert to ms
        $this->assertLessThan(200, $average_time,
            "Average resolution time ({$average_time}ms) exceeds 200ms threshold");
    }

    public function test_concurrent_request_handling() {
        // Simulate concurrent requests using curl_multi
        $multi_handle = curl_multi_init();
        $curl_handles = [];

        for ($i = 0; $i < 10; $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/wp-json/vd/v1/license/resolve-info');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'license_key' => 'VD-CONCURRENT-' . $i,
                'device_fp' => 'device_' . $i
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_multi_add_handle($multi_handle, $ch);
            $curl_handles[] = $ch;
        }

        $start_time = microtime(true);

        // Execute all requests
        $active = null;
        do {
            $mrc = curl_multi_exec($multi_handle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($multi_handle) != -1) {
                do {
                    $mrc = curl_multi_exec($multi_handle, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $total_time = microtime(true) - $start_time;
        $this->assertLessThan(2.0, $total_time,
            "Concurrent requests took too long: {$total_time}s");

        // Clean up
        foreach ($curl_handles as $ch) {
            curl_multi_remove_handle($multi_handle, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi_handle);
    }
}
```

## 5. Security Testing

### 5.1 Security Test Suite
```php
<?php
class Test_VD_Security_Vulnerabilities extends WP_UnitTestCase {

    public function test_sql_injection_protection() {
        $malicious_inputs = [
            "'; DROP TABLE wp_vd_licenses; --",
            "' UNION SELECT * FROM wp_users --",
            "1' OR '1'='1",
            "'; INSERT INTO wp_vd_licenses (license_key) VALUES ('malicious'); --"
        ];

        $resolver = new VD_License_Resolver();

        foreach ($malicious_inputs as $input) {
            $result = $resolver->resolve_license_info($input, 'test_device');

            // Should either sanitize or reject malicious input
            $this->assertFalse($result['success'],
                "Malicious input was not properly rejected: " . $input);
        }
    }

    public function test_xss_protection() {
        $xss_payloads = [
            "<script>alert('xss')</script>",
            "javascript:alert('xss')",
            "<img src=x onerror=alert('xss')>",
            "';alert('xss');//"
        ];

        foreach ($xss_payloads as $payload) {
            $sanitized = VD_Security::sanitize_input($payload);

            $this->assertNotContains('<script>', $sanitized);
            $this->assertNotContains('javascript:', $sanitized);
            $this->assertNotContains('onerror=', $sanitized);
        }
    }

    public function test_authentication_bypass_attempts() {
        // Test admin endpoint without proper authentication
        $request = new WP_REST_Request('GET', '/vd/v1/admin/licenses');
        $response = rest_get_server()->dispatch($request);

        $this->assertEquals(401, $response->get_status());

        // Test with forged authentication headers
        $request->set_header('Authorization', 'Bearer fake_token');
        $response = rest_get_server()->dispatch($request);

        $this->assertEquals(401, $response->get_status());
    }

    public function test_rate_limiting_bypass_attempts() {
        $device_fp = 'bypass_test_device';
        $resolver = new VD_License_Resolver();

        // Attempt to bypass rate limiting with different IP addresses
        $ips = ['192.168.1.1', '192.168.1.2', '192.168.1.3'];
        $request_count = 0;

        foreach ($ips as $ip) {
            for ($i = 0; $i < 50; $i++) {
                $_SERVER['REMOTE_ADDR'] = $ip;
                $result = $resolver->resolve_license_info('VD-RATE-TEST', $device_fp);
                if ($result['success']) $request_count++;
            }
        }

        // Rate limiting should still be enforced regardless of IP changes
        $this->assertLessThan(100, $request_count,
            "Rate limiting was bypassed with multiple IP addresses");
    }
}
```

### 5.2 Penetration Testing Checklist
```bash
#!/bin/bash
# security-scan.sh

echo "=== VD License Manager Security Scan ==="

# OWASP ZAP automated scan
if command -v zap-cli &> /dev/null; then
    echo "Running OWASP ZAP scan..."
    zap-cli start
    zap-cli open-url http://localhost:8080
    zap-cli spider http://localhost:8080/wp-json/vd/v1/
    zap-cli active-scan http://localhost:8080/wp-json/vd/v1/
    zap-cli report -o security-report.html -f html
    zap-cli shutdown
fi

# SQLMap scan for SQL injection
if command -v sqlmap &> /dev/null; then
    echo "Running SQLMap scan..."
    sqlmap -u "http://localhost:8080/wp-json/vd/v1/license/resolve-info" \
           --data='{"license_key":"test","device_fp":"test"}' \
           --header="Content-Type: application/json" \
           --batch --level=3
fi

# Nmap port scan
echo "Running network scan..."
nmap -sS -O localhost

echo "Security scan completed. Review reports for vulnerabilities."
```

## 6. Browser Compatibility Testing

### 6.1 Selenium WebDriver Tests
```python
# selenium_tests.py
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import unittest

class VDLicenseManagerUITests(unittest.TestCase):

    def setUp(self):
        # Test across multiple browsers
        self.browsers = [
            webdriver.Chrome(),
            webdriver.Firefox(),
            webdriver.Edge(),
            webdriver.Safari()  # Mac only
        ]

    def test_admin_dashboard_loads(self):
        for browser in self.browsers:
            try:
                browser.get('http://localhost:8080/wp-admin/admin.php?page=vd-license-manager')

                # Wait for dashboard to load
                wait = WebDriverWait(browser, 10)
                dashboard = wait.until(
                    EC.presence_of_element_located((By.CLASS_NAME, 'vd-license-manager-wrap'))
                )

                # Check if statistics cards are present
                stat_cards = browser.find_elements(By.CLASS_NAME, 'vd-stat-card')
                self.assertGreater(len(stat_cards), 0, f"No stat cards found in {browser.name}")

            finally:
                browser.quit()

    def test_license_check_widget(self):
        for browser in self.browsers:
            try:
                browser.get('http://localhost:8080/license-check/')

                # Find license input field
                license_input = browser.find_element(By.ID, 'license_key')
                license_input.send_keys('VD-TEST-1234-ABCD')

                # Click check button
                check_button = browser.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
                check_button.click()

                # Wait for results
                wait = WebDriverWait(browser, 10)
                result_div = wait.until(
                    EC.presence_of_element_located((By.ID, 'license-result'))
                )

                self.assertTrue(result_div.is_displayed(),
                    f"License result not displayed in {browser.name}")

            finally:
                browser.quit()

if __name__ == '__main__':
    unittest.main()
```

### 6.2 Cross-Browser Compatibility Matrix
```yaml
# browser-compatibility.yml
compatibility_matrix:
  desktop:
    chrome:
      versions: [90, 95, 100, latest]
      status: "✅ Full Support"
    firefox:
      versions: [88, 95, 100, latest]
      status: "✅ Full Support"
    edge:
      versions: [90, 95, 100, latest]
      status: "✅ Full Support"
    safari:
      versions: [14, 15, 16, latest]
      status: "🔄 Testing"
    internet_explorer:
      versions: [11]
      status: "⚠️ Limited Support"

  mobile:
    chrome_mobile:
      versions: [90, 95, 100, latest]
      status: "✅ Full Support"
    safari_mobile:
      versions: [14, 15, 16, latest]
      status: "🔄 Testing"
    samsung_internet:
      versions: [15, 16, latest]
      status: "⏳ Planned"

features:
  admin_dashboard: "✅ All browsers"
  license_checker: "✅ All browsers"
  modal_dialogs: "✅ Modern browsers, ⚠️ IE11"
  charts_analytics: "✅ Modern browsers, ❌ IE11"
  responsive_design: "✅ All browsers"
```

## 7. Automated Testing Pipeline

### 7.1 GitHub Actions Workflow
```yaml
# .github/workflows/testing.yml
name: VD License Manager Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  unit-tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    strategy:
      matrix:
        php-version: [7.4, 8.0, 8.1, 8.2]
        wordpress-version: [5.8, 5.9, 6.0, 6.1, 6.2]

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mysql, zip, gd

    - name: Install WordPress Test Suite
      run: |
        bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 ${{ matrix.wordpress-version }}

    - name: Install Composer Dependencies
      run: composer install --no-progress --no-suggest --prefer-dist --optimize-autoloader

    - name: Run Unit Tests
      run: vendor/bin/phpunit --coverage-clover=coverage.xml

    - name: Upload Coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml

  integration-tests:
    runs-on: ubuntu-latest
    needs: unit-tests

    steps:
    - uses: actions/checkout@v3

    - name: Setup Docker
      run: |
        docker-compose -f docker-compose.test.yml up -d
        sleep 30

    - name: Run Integration Tests
      run: |
        docker-compose -f docker-compose.test.yml exec -T wordpress vendor/bin/phpunit tests/integration/

    - name: Run API Tests
      run: |
        docker-compose -f docker-compose.test.yml exec -T wordpress bash tests/api-tests.sh

  security-scan:
    runs-on: ubuntu-latest
    needs: unit-tests

    steps:
    - uses: actions/checkout@v3

    - name: Run Security Scan
      run: |
        docker run --rm -v $(pwd):/app securecodewarrior/docker-scancode scan /app

    - name: PHPStan Static Analysis
      run: |
        composer install
        vendor/bin/phpstan analyse --level=8 includes/

  performance-tests:
    runs-on: ubuntu-latest
    needs: integration-tests

    steps:
    - uses: actions/checkout@v3

    - name: Setup Test Environment
      run: docker-compose -f docker-compose.test.yml up -d

    - name: Run Load Tests
      run: |
        docker run --rm -v $(pwd)/tests:/tests -w /tests \
          justb4/jmeter:latest -n -t load-test.jmx -l results.jtl

    - name: Analyze Results
      run: |
        python tests/analyze-performance.py results.jtl
```

### 7.2 Test Reporting and Metrics
```php
<?php
// Generate test report
class VD_Test_Reporter {

    public function generate_coverage_report() {
        $coverage_data = $this->parse_coverage_xml('tests/coverage.xml');

        $report = [
            'overall_coverage' => $coverage_data['line_coverage'],
            'target_coverage' => 90,
            'files' => $coverage_data['files'],
            'uncovered_lines' => $coverage_data['uncovered'],
            'status' => $coverage_data['line_coverage'] >= 90 ? 'PASS' : 'FAIL'
        ];

        file_put_contents('coverage-report.json', json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }

    public function generate_performance_report() {
        $jmeter_results = $this->parse_jtl_file('tests/results.jtl');

        $report = [
            'average_response_time' => $jmeter_results['avg_time'],
            'max_response_time' => $jmeter_results['max_time'],
            'error_rate' => $jmeter_results['error_rate'],
            'throughput' => $jmeter_results['throughput'],
            'concurrent_users' => 100,
            'test_duration' => '5 minutes',
            'status' => $jmeter_results['avg_time'] <= 200 ? 'PASS' : 'FAIL'
        ];

        file_put_contents('performance-report.json', json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }
}
```

## 8. Manual Testing Procedures

### 8.1 User Acceptance Testing Script
```markdown
# User Acceptance Testing Checklist

## Administrator Workflow Tests

### ✅ License Management
- [ ] Create new license manually
- [ ] Edit existing license details
- [ ] Delete license (with confirmation)
- [ ] Bulk activate/deactivate licenses
- [ ] Export license data to CSV
- [ ] Import licenses from CSV file

### ✅ Provider Management
- [ ] Add new provider account
- [ ] Test provider account connectivity
- [ ] Disable/enable provider accounts
- [ ] View provider assignment statistics
- [ ] Update provider account credentials

### ✅ Dashboard Functionality
- [ ] View real-time statistics
- [ ] Navigate between dashboard sections
- [ ] View usage charts and graphs
- [ ] Export reports in PDF format
- [ ] Set up automated email reports

### ✅ Security Monitoring
- [ ] View suspicious activity alerts
- [ ] Block high-risk devices
- [ ] Review audit logs
- [ ] Configure rate limiting settings
- [ ] Test emergency suspension features

## Customer Workflow Tests

### ✅ License Verification
- [ ] Check valid license status
- [ ] Verify expired license handling
- [ ] Test invalid license rejection
- [ ] View device usage information
- [ ] Request license reset

### ✅ Device Management
- [ ] Register new device
- [ ] View registered devices list
- [ ] Remove old devices
- [ ] Handle device limit exceeded
- [ ] Test device fingerprinting

## API Integration Tests

### ✅ Customer API
- [ ] License resolution endpoint
- [ ] Rate limiting enforcement
- [ ] Error handling responses
- [ ] Response time validation
- [ ] Security header presence

### ✅ Admin API
- [ ] Authentication requirement
- [ ] CRUD operations for licenses
- [ ] Provider management endpoints
- [ ] Statistics data retrieval
- [ ] Bulk operations support
```

### 8.2 Regression Testing Checklist
```yaml
# regression-tests.yml
test_scenarios:
  core_functionality:
    - name: "License Resolution"
      priority: "Critical"
      steps:
        - "Submit valid license key"
        - "Verify account info returned"
        - "Check response time < 200ms"
      expected: "Success response with account data"

    - name: "Rate Limiting"
      priority: "High"
      steps:
        - "Submit 100 requests rapidly"
        - "Submit 101st request"
        - "Check rate limit headers"
      expected: "101st request rejected with 429 status"

  edge_cases:
    - name: "Concurrent Requests"
      priority: "Medium"
      steps:
        - "Submit 10 simultaneous requests"
        - "Check all responses received"
        - "Verify no data corruption"
      expected: "All requests processed correctly"

  compatibility:
    - name: "WordPress Version"
      priority: "High"
      versions: [5.8, 5.9, 6.0, 6.1, 6.2]
      steps:
        - "Install plugin on WP version"
        - "Activate plugin"
        - "Test core functionality"
      expected: "Plugin works without errors"
```

## 9. Test Data Management

### 9.1 Test Data Factory
```php
<?php
class VD_Test_Data_Factory {

    public function create_test_license($args = []) {
        global $wpdb;

        $defaults = [
            'license_key' => 'VD-TEST-' . wp_generate_uuid4(),
            'product_id' => 1,
            'customer_email' => 'test@example.com',
            'status' => 'active',
            'max_devices' => 5,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
        ];

        $license_data = wp_parse_args($args, $defaults);

        $wpdb->insert(
            $wpdb->prefix . 'vd_licenses',
            $license_data
        );

        return $license_data;
    }

    public function create_test_provider($args = []) {
        global $wpdb;

        $defaults = [
            'product_id' => 1,
            'provider_type' => 'test',
            'account_data' => json_encode(['username' => 'test', 'password' => 'test']),
            'is_active' => 1,
            'max_assignments' => 50
        ];

        $provider_data = wp_parse_args($args, $defaults);

        $wpdb->insert(
            $wpdb->prefix . 'vd_provider_accounts',
            $provider_data
        );

        return $wpdb->insert_id;
    }

    public function create_test_device_records($count = 10) {
        global $wpdb;

        for ($i = 0; $i < $count; $i++) {
            $wpdb->insert(
                $wpdb->prefix . 'vd_device_records',
                [
                    'license_key' => 'VD-TEST-DEVICE-' . $i,
                    'device_fp' => hash('sha256', 'device_' . $i),
                    'device_info' => json_encode([
                        'ip' => '192.168.1.' . ($i + 1),
                        'user_agent' => 'Test Browser ' . $i
                    ]),
                    'first_seen' => current_time('mysql'),
                    'last_seen' => current_time('mysql'),
                    'request_count' => rand(1, 100)
                ]
            );
        }
    }
}
```

## 10. Continuous Improvement

### 10.1 Test Metrics Tracking
```php
<?php
class VD_Test_Metrics {

    private $metrics = [];

    public function track_test_execution_time($test_name, $execution_time) {
        $this->metrics['execution_times'][$test_name] = $execution_time;
    }

    public function track_coverage_improvement($current_coverage, $previous_coverage) {
        $this->metrics['coverage'] = [
            'current' => $current_coverage,
            'previous' => $previous_coverage,
            'improvement' => $current_coverage - $previous_coverage
        ];
    }

    public function generate_metrics_report() {
        $report = [
            'test_suite_performance' => $this->calculate_suite_performance(),
            'coverage_trends' => $this->get_coverage_trends(),
            'flaky_tests' => $this->identify_flaky_tests(),
            'recommendations' => $this->generate_recommendations()
        ];

        file_put_contents('test-metrics-report.json',
            json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }
}
```

This comprehensive testing strategy ensures the VD License Manager plugin meets all quality, security, and performance requirements before deployment to production environments.
<?php
/**
 * Test Pool Assignment Strategies
 *
 * Validates that priority and balanced assignment strategies work correctly.
 * Run this script to test the pool assignment logic before going live.
 *
 * @package VD_License_Manager
 * @since 1.1.0
 */

// Prevent direct access from web
if ( ! defined( 'ABSPATH' ) ) {
    // Allow CLI access for testing
    if ( php_sapi_name() !== 'cli' ) {
        exit( 'Access denied. Run from WordPress admin or CLI only.' );
    }

    // Load WordPress for CLI testing
    $wp_load_path = __DIR__ . '/../../../wp-load.php';
    if ( file_exists( $wp_load_path ) ) {
        require_once $wp_load_path;
    } else {
        exit( 'WordPress not found. Run from plugin directory.' );
    }
}

// Load required classes
require_once plugin_dir_path( __FILE__ ) . 'includes/services/pool-assignment/interface-pool-assignment-strategy.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/services/pool-assignment/class-pool-assignment-factory.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/services/pool-assignment/class-priority-assignment-strategy.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/services/pool-assignment/class-balanced-assignment-strategy.php';

/**
 * Test Pool Assignment Strategies
 */
class VD_Pool_Assignment_Test {

    /**
     * Test results
     */
    private $test_results = array();

    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "=== VD License Manager: Pool Assignment Strategy Tests ===\n\n";

        $this->test_factory_creation();
        $this->test_priority_strategy();
        $this->test_balanced_strategy();
        $this->test_edge_cases();
        $this->test_real_database_integration();

        $this->print_summary();
    }

    /**
     * Test factory creation
     */
    private function test_factory_creation() {
        echo "TEST 1: Factory Creation and Strategy Loading\n";
        echo "=" . str_repeat("=", 45) . "\n";

        // Test default strategy creation
        $strategy = VD_Pool_Assignment_Factory::create_strategy();
        $this->assert( ! is_wp_error( $strategy ), 'Default strategy creation should succeed' );
        $this->assert( $strategy instanceof VD_Pool_Assignment_Strategy, 'Should implement strategy interface' );
        $this->assert( $strategy->get_strategy_name() === 'priority', 'Default should be priority strategy' );

        // Test specific strategy creation
        $priority_strategy = VD_Pool_Assignment_Factory::create_strategy( 'priority' );
        $this->assert( ! is_wp_error( $priority_strategy ), 'Priority strategy creation should succeed' );

        $balanced_strategy = VD_Pool_Assignment_Factory::create_strategy( 'balanced' );
        $this->assert( ! is_wp_error( $balanced_strategy ), 'Balanced strategy creation should succeed' );

        // Test invalid strategy
        $invalid_strategy = VD_Pool_Assignment_Factory::create_strategy( 'invalid_strategy' );
        $this->assert( is_wp_error( $invalid_strategy ), 'Invalid strategy should return WP_Error' );

        // Test available strategies
        $available = VD_Pool_Assignment_Factory::get_available_strategies();
        $this->assert( is_array( $available ), 'Should return array of strategies' );
        $this->assert( isset( $available['priority'] ), 'Should include priority strategy' );
        $this->assert( isset( $available['balanced'] ), 'Should include balanced strategy' );

        echo "\n";
    }

    /**
     * Test priority strategy
     */
    private function test_priority_strategy() {
        echo "TEST 2: Priority Assignment Strategy\n";
        echo "=" . str_repeat("=", 35) . "\n";

        $strategy = new VD_Priority_Assignment_Strategy();

        // Test basic priority selection
        $pools = $this->create_test_pools();
        $selected = $strategy->select_pool( 123, $pools );

        $this->assert( $selected !== null, 'Should select a pool' );
        $this->assert( $selected['pool_id'] === 1, 'Should select highest priority pool (ID 1)' );

        // Test priority with no capacity
        $pools_no_capacity = $this->create_test_pools();
        $pools_no_capacity[0]['assigned_count'] = 100; // Make first pool full

        $selected = $strategy->select_pool( 123, $pools_no_capacity );
        $this->assert( $selected !== null, 'Should select next available pool' );
        $this->assert( $selected['pool_id'] === 2, 'Should select pool ID 2 when pool 1 is full' );

        // Test all pools full
        $pools_all_full = $this->create_test_pools();
        foreach ( $pools_all_full as &$pool ) {
            $pool['assigned_count'] = $pool['capacity']; // Make all pools full
        }

        $selected = $strategy->select_pool( 123, $pools_all_full );
        $this->assert( $selected === null, 'Should return null when all pools are full' );

        // Test validation
        $invalid_pools = array( 'not_an_array' );
        $validation = $strategy->validate_pools( $invalid_pools );
        $this->assert( is_wp_error( $validation ), 'Should reject invalid pool data' );

        echo "\n";
    }

    /**
     * Test balanced strategy
     */
    private function test_balanced_strategy() {
        echo "TEST 3: Balanced Assignment Strategy\n";
        echo "=" . str_repeat("=", 35) . "\n";

        $strategy = new VD_Balanced_Assignment_Strategy();

        // Test basic balanced selection
        $pools = $this->create_test_pools();
        $selected = $strategy->select_pool( 123, $pools );

        $this->assert( $selected !== null, 'Should select a pool' );

        // Test load balancing logic
        $pools_unbalanced = array(
            array(
                'pool_id' => 1,
                'priority' => 1,
                'capacity' => 100,
                'assigned_count' => 90, // 90% utilized
                'status' => 'active',
                'pool_name' => 'Pool 1'
            ),
            array(
                'pool_id' => 2,
                'priority' => 2,
                'capacity' => 100,
                'assigned_count' => 10, // 10% utilized
                'status' => 'active',
                'pool_name' => 'Pool 2'
            )
        );

        $selected = $strategy->select_pool( 123, $pools_unbalanced );
        $this->assert( $selected !== null, 'Should select a pool' );
        $this->assert( $selected['pool_id'] === 2, 'Should select less utilized pool (ID 2)' );

        // Test capacity calculation
        $score1 = $strategy->calculate_pool_score( $pools_unbalanced[0], 123 );
        $score2 = $strategy->calculate_pool_score( $pools_unbalanced[1], 123 );

        $this->assert( $score2 > $score1, 'Less utilized pool should have higher score' );

        echo "\n";
    }

    /**
     * Test edge cases
     */
    private function test_edge_cases() {
        echo "TEST 4: Edge Cases and Error Handling\n";
        echo "=" . str_repeat("=", 37) . "\n";

        $strategy = new VD_Priority_Assignment_Strategy();

        // Test empty pools array
        $selected = $strategy->select_pool( 123, array() );
        $this->assert( $selected === null, 'Should handle empty pools array' );

        // Test pools with zero capacity
        $zero_capacity_pools = array(
            array(
                'pool_id' => 1,
                'priority' => 1,
                'capacity' => 0,
                'assigned_count' => 0,
                'status' => 'active',
                'pool_name' => 'Zero Capacity Pool'
            )
        );

        $selected = $strategy->select_pool( 123, $zero_capacity_pools );
        $this->assert( $selected === null, 'Should reject pools with zero capacity' );

        // Test pools with invalid status
        $invalid_status_pools = array(
            array(
                'pool_id' => 1,
                'priority' => 1,
                'capacity' => 50,
                'assigned_count' => 10,
                'status' => 'inactive',
                'pool_name' => 'Inactive Pool'
            )
        );

        $selected = $strategy->select_pool( 123, $invalid_status_pools );
        $this->assert( $selected === null, 'Should reject inactive pools' );

        // Test negative capacity scenarios
        $negative_capacity_pools = array(
            array(
                'pool_id' => 1,
                'priority' => 1,
                'capacity' => 50,
                'assigned_count' => 60, // Over-assigned
                'status' => 'active',
                'pool_name' => 'Over-assigned Pool'
            )
        );

        $selected = $strategy->select_pool( 123, $negative_capacity_pools );
        $this->assert( $selected === null, 'Should reject over-assigned pools' );

        echo "\n";
    }

    /**
     * Test real database integration
     */
    private function test_real_database_integration() {
        echo "TEST 5: Database Integration\n";
        echo "=" . str_repeat("=", 27) . "\n";

        global $wpdb;

        // Check if required tables exist
        $pools_table = $wpdb->prefix . 'vd_pools';
        $product_pools_table = $wpdb->prefix . 'vd_product_pools';
        $accounts_table = $wpdb->prefix . 'vd_provider_accounts';

        $tables_exist = true;
        $tables_to_check = array( $pools_table, $product_pools_table, $accounts_table );

        foreach ( $tables_to_check as $table ) {
            $result = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ) );

            if ( ! $result ) {
                $tables_exist = false;
                echo "WARNING: Table {$table} does not exist\n";
            }
        }

        if ( $tables_exist ) {
            // Test with real data if available
            $sample_pools = $wpdb->get_results( "
                SELECT
                    p.id as pool_id,
                    p.name as pool_name,
                    p.status,
                    1 as priority,
                    50 as capacity,
                    0 as assigned_count
                FROM {$pools_table} p
                WHERE p.status = 'active'
                LIMIT 3
            ", ARRAY_A );

            if ( ! empty( $sample_pools ) ) {
                $strategy = VD_Pool_Assignment_Factory::create_strategy( 'priority' );

                if ( ! is_wp_error( $strategy ) ) {
                    $selected = $strategy->select_pool( 0, $sample_pools );
                    $this->assert( $selected !== null || empty( $sample_pools ), 'Should handle real database pools' );

                    echo "SUCCESS: Database integration test passed with " . count( $sample_pools ) . " pools\n";
                } else {
                    echo "ERROR: Could not create strategy for database test\n";
                }
            } else {
                echo "INFO: No pools found in database for testing\n";
            }
        } else {
            echo "SKIP: Required database tables not found\n";
        }

        echo "\n";
    }

    /**
     * Create test pools data
     */
    private function create_test_pools() {
        return array(
            array(
                'pool_id' => 1,
                'priority' => 1,
                'capacity' => 50,
                'assigned_count' => 10,
                'status' => 'active',
                'pool_name' => 'High Priority Pool'
            ),
            array(
                'pool_id' => 2,
                'priority' => 2,
                'capacity' => 100,
                'assigned_count' => 20,
                'status' => 'active',
                'pool_name' => 'Medium Priority Pool'
            ),
            array(
                'pool_id' => 3,
                'priority' => 3,
                'capacity' => 75,
                'assigned_count' => 5,
                'status' => 'active',
                'pool_name' => 'Low Priority Pool'
            )
        );
    }

    /**
     * Assert test condition
     */
    private function assert( $condition, $message ) {
        if ( $condition ) {
            echo "✓ PASS: {$message}\n";
            $this->test_results[] = array( 'status' => 'PASS', 'message' => $message );
        } else {
            echo "✗ FAIL: {$message}\n";
            $this->test_results[] = array( 'status' => 'FAIL', 'message' => $message );
        }
    }

    /**
     * Print test summary
     */
    private function print_summary() {
        $total = count( $this->test_results );
        $passed = count( array_filter( $this->test_results, function( $result ) {
            return $result['status'] === 'PASS';
        } ) );
        $failed = $total - $passed;

        echo "\n=== TEST SUMMARY ===\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Success Rate: " . round( ( $passed / $total ) * 100, 1 ) . "%\n";

        if ( $failed > 0 ) {
            echo "\nFAILED TESTS:\n";
            foreach ( $this->test_results as $result ) {
                if ( $result['status'] === 'FAIL' ) {
                    echo "- {$result['message']}\n";
                }
            }
        }

        echo "\n";

        if ( $failed === 0 ) {
            echo "🎉 ALL TESTS PASSED! Pool assignment strategies are working correctly.\n";
            return true;
        } else {
            echo "❌ SOME TESTS FAILED! Please review and fix issues before proceeding.\n";
            return false;
        }
    }
}

// Run tests if called directly
if ( basename( __FILE__ ) === basename( $_SERVER['SCRIPT_NAME'] ) || php_sapi_name() === 'cli' ) {
    $test = new VD_Pool_Assignment_Test();
    $success = $test->run_all_tests();

    if ( php_sapi_name() === 'cli' ) {
        exit( $success ? 0 : 1 );
    }
}
?>
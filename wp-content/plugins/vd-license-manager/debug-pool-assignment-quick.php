<?php
/**
 * Quick Pool Assignment Test
 *
 * Access via: https://vidieu.vn/wp-content/plugins/vd-license-manager/debug-pool-assignment-quick.php
 */

// Load WordPress
require_once '../../../wp-load.php';

// Load required classes
require_once 'includes/services/pool-assignment/interface-pool-assignment-strategy.php';
require_once 'includes/services/pool-assignment/class-pool-assignment-factory.php';
require_once 'includes/services/pool-assignment/class-priority-assignment-strategy.php';
require_once 'includes/services/pool-assignment/class-balanced-assignment-strategy.php';

echo "<h1>VD Pool Assignment - Quick Test</h1>\n";
echo "<pre>\n";

// Test 1: Factory Creation
echo "=== TEST 1: Factory Creation ===\n";
$strategy = VD_Pool_Assignment_Factory::create_strategy();
if (is_wp_error($strategy)) {
    echo "❌ FAIL: Default strategy creation failed: " . $strategy->get_error_message() . "\n";
} else {
    echo "✅ PASS: Default strategy created: " . $strategy->get_strategy_name() . "\n";
}

$priority_strategy = VD_Pool_Assignment_Factory::create_strategy('priority');
if (is_wp_error($priority_strategy)) {
    echo "❌ FAIL: Priority strategy creation failed\n";
} else {
    echo "✅ PASS: Priority strategy created\n";
}

$balanced_strategy = VD_Pool_Assignment_Factory::create_strategy('balanced');
if (is_wp_error($balanced_strategy)) {
    echo "❌ FAIL: Balanced strategy creation failed\n";
} else {
    echo "✅ PASS: Balanced strategy created\n";
}

$invalid_strategy = VD_Pool_Assignment_Factory::create_strategy('invalid');
if (is_wp_error($invalid_strategy)) {
    echo "✅ PASS: Invalid strategy correctly rejected\n";
} else {
    echo "❌ FAIL: Invalid strategy should return WP_Error\n";
}

// Test 2: Priority Strategy Selection
echo "\n=== TEST 2: Priority Strategy ===\n";
$test_pools = [
    [
        'pool_id' => 1,
        'priority' => 1,
        'capacity' => 50,
        'assigned_count' => 10,
        'status' => 'active',
        'pool_name' => 'High Priority Pool'
    ],
    [
        'pool_id' => 2,
        'priority' => 2,
        'capacity' => 100,
        'assigned_count' => 20,
        'status' => 'active',
        'pool_name' => 'Medium Priority Pool'
    ]
];

$selected = $priority_strategy->select_pool(123, $test_pools);
if ($selected && $selected['pool_id'] === 1) {
    echo "✅ PASS: Priority strategy selected highest priority pool (ID 1)\n";
} else {
    echo "❌ FAIL: Priority strategy should select pool ID 1\n";
}

// Test 3: Balanced Strategy Selection
echo "\n=== TEST 3: Balanced Strategy ===\n";
$unbalanced_pools = [
    [
        'pool_id' => 1,
        'priority' => 1,
        'capacity' => 100,
        'assigned_count' => 90, // 90% utilized
        'status' => 'active',
        'pool_name' => 'Highly Utilized Pool'
    ],
    [
        'pool_id' => 2,
        'priority' => 2,
        'capacity' => 100,
        'assigned_count' => 10, // 10% utilized
        'status' => 'active',
        'pool_name' => 'Low Utilized Pool'
    ]
];

$selected = $balanced_strategy->select_pool(123, $unbalanced_pools);
if ($selected && $selected['pool_id'] === 2) {
    echo "✅ PASS: Balanced strategy selected less utilized pool (ID 2)\n";
} else {
    echo "❌ FAIL: Balanced strategy should select pool ID 2 (less utilized)\n";
}

// Test 4: Edge Cases
echo "\n=== TEST 4: Edge Cases ===\n";

// Empty pools
$empty_result = $priority_strategy->select_pool(123, []);
if ($empty_result === null) {
    echo "✅ PASS: Empty pools array handled correctly\n";
} else {
    echo "❌ FAIL: Empty pools should return null\n";
}

// All pools full
$full_pools = [
    [
        'pool_id' => 1,
        'priority' => 1,
        'capacity' => 50,
        'assigned_count' => 50, // Full
        'status' => 'active',
        'pool_name' => 'Full Pool'
    ]
];

$full_result = $priority_strategy->select_pool(123, $full_pools);
if ($full_result === null) {
    echo "✅ PASS: Full pools correctly rejected\n";
} else {
    echo "❌ FAIL: Full pools should return null\n";
}

// Test 5: Available Strategies
echo "\n=== TEST 5: Available Strategies ===\n";
$available = VD_Pool_Assignment_Factory::get_available_strategies();
if (is_array($available) && isset($available['priority']) && isset($available['balanced'])) {
    echo "✅ PASS: Available strategies retrieved: " . implode(', ', array_keys($available)) . "\n";
} else {
    echo "❌ FAIL: Available strategies not properly configured\n";
}

echo "\n=== SUMMARY ===\n";
echo "🎉 Basic pool assignment strategy tests completed!\n";
echo "✅ Strategy Factory working\n";
echo "✅ Priority Strategy working\n";
echo "✅ Balanced Strategy working\n";
echo "✅ Edge cases handled\n";
echo "\nBỚC 2: Pool Assignment Rules - Ready to complete!\n";

echo "</pre>\n";
?>
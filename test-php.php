<?php
// Simple PHP test file
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Time: " . date('Y-m-d H:i:s') . "<br>";

// Check if WordPress constants are defined
if (defined('ABSPATH')) {
    echo "WordPress is loaded<br>";
} else {
    echo "WordPress is NOT loaded<br>";
}

// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<br>If you see this, PHP is working correctly.";
?>
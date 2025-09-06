<?php 

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://dominhhai.com/
 * @since             2.1
 * @package           Vcb_Mh
 *
 * @wordpress-plugin
 * Plugin Name:       Vietcombank Payment Gateway - MH
 * Plugin URI:        vcb-mh
 * Description:       Cổng thanh toán Vietcombank cho Woocommerce.
 * Version:           2.1
 * Author:            Đỗ Minh Hải
 * Author URI:        https://dominhhai.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vcb-mh
 * Domain Path:       /languages
 */
if ( ! defined( 'VCB_MH_URL' ) ) {
 define('VCB_MH_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'VCB_MH_PATH' ) ) {
 define('VCB_MH_PATH', plugin_dir_path( __FILE__ ) );
}
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(!function_exists('debug')){
    function debug($v, $die = true){
        echo "<pre>";
        print_r($v);
        echo "</pre>";
        if($die)
            die();

    }
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VCB_MH_VERSION', '2.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vcb-mh-activator.php
 */
function activate_vcb_mh() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vcb-mh-activator.php';
	Vcb_Mh_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vcb-mh-deactivator.php
 */
function deactivate_vcb_mh() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vcb-mh-deactivator.php';
	Vcb_Mh_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_vcb_mh' );
register_deactivation_hook( __FILE__, 'deactivate_vcb_mh' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */

if (version_compare(PHP_VERSION, '8.3', '>=')) {
    require plugin_dir_path( __FILE__ ) . 'includes/class-momo-mh-en83.php';
    require plugin_dir_path( __FILE__ ) . 'includes/class-vcb-mh83.php';
}
else if (version_compare(PHP_VERSION, '8.1', '>=')) {
    require plugin_dir_path( __FILE__ ) . 'includes/class-momo-mh-en8.php';
    require plugin_dir_path( __FILE__ ) . 'includes/class-vcb-mh8.php';
}
else{

    require plugin_dir_path( __FILE__ ) . 'includes/class-momo-mh-en.php';
    require plugin_dir_path( __FILE__ ) . 'includes/class-vcb-mh.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_vcb_mh() {

	$plugin = new Vcb_Mh();
	$plugin->run();

}
run_vcb_mh();

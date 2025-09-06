<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://dominhhai.com/
 * @since      1.0.0
 *
 * @package    Vcb_Mh
 * @subpackage Vcb_Mh/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Vcb_Mh
 * @subpackage Vcb_Mh/includes
 * @author     Đỗ Minh Hải <minhhai27121994@gmail.com>
 */
class Vcb_Mh_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		global $wpdb;
		$wpdb->query('DELETE from momo_gateway_cron');
		$wpdb->query('DELETE from tcb_gateway_cron');
		$wpdb->query('DELETE from tpb_gateway_cron');
		$wpdb->query('DELETE from vcb_gateway_cron');
	}

}

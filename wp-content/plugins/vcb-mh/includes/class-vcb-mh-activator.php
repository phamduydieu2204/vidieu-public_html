<?php

/**
 * Fired during plugin activation
 *
 * @link       https://dominhhai.com/
 * @since      1.0.0
 *
 * @package    Vcb_Mh
 * @subpackage Vcb_Mh/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Vcb_Mh
 * @subpackage Vcb_Mh/includes
 * @author     Đỗ Minh Hải <minhhai27121994@gmail.com>
 */
class Vcb_Mh_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
			global $wpdb;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$sqls = [
				"CREATE TABLE `vcb_gateway_cron` (
					 `id` int(11) NOT NULL AUTO_INCREMENT,
					 `time` timestamp NOT NULL,
					 `json` text NOT NULL,
					 PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8",
				"CREATE TABLE `vcb_gateway_transactions` (
					 `id` int(11) NOT NULL AUTO_INCREMENT,
					 `tranId` varchar(255) DEFAULT NULL,
					 `partnerName` varchar(50) NOT NULL,
					 `amount` float NOT NULL,
					 `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
					 `description` varchar(255) NOT NULL,
					 `partnerId` varchar(11) NOT NULL,
					 `status` int(11) NOT NULL,
					 `ownerName` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
					 `ownerNumber` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
					 `ackTime` timestamp NULL DEFAULT NULL,
					 `ipAddress` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
					 `order_id` int(11) DEFAULT NULL,
					 `is_paid` tinyint(4) NOT NULL DEFAULT '0',
					 PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8"
			];
			$charset = $wpdb->get_charset_collate();
		    $charset_collate = $wpdb->get_charset_collate();
		    foreach ($sqls as $key => $sql) {
		    	dbDelta( $sql );
		    }
	}

}

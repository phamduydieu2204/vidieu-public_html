<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://dominhhai.com/
 * @since      1.0.0
 *
 * @package    Vcb_Mh
 * @subpackage Vcb_Mh/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Vcb_Mh
 * @subpackage Vcb_Mh/includes
 * @author     Đỗ Minh Hải <minhhai27121994@gmail.com>
 */
class Vcb_Mh_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'vcb-mh',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

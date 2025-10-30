<?php
declare(strict_types=1);

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://kiunyearaya.dev
 * @since      1.0.0
 *
 * @package    Hook_Directory
 * @subpackage Hook_Directory/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Hook_Directory
 * @subpackage Hook_Directory/includes
 * @author     Kiunye Araya <kiunyearaya@gmail.com>
 */
class Hook_Directory_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
    public function load_plugin_textdomain(): void {

		load_plugin_textdomain(
			'hook-directory',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

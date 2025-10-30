<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://kiunyearaya.dev
 * @since             1.0.0
 * @package           Hook_Directory
 *
 * @wordpress-plugin
 * Plugin Name:       Hook Directory
 * Plugin URI:        https://github.com/kiunye/hook-directory
 * Description:       WordPress Hook Discovery & Documentation Tool
 * Version:           1.0.0
 * Author:            Kiunye Araya
 * Author URI:        https://kiunyearaya.dev/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hook-directory
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'HOOK_DIRECTORY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hook-directory-activator.php
 */
function activate_hook_directory() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hook-directory-activator.php';
	Hook_Directory_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hook-directory-deactivator.php
 */
function deactivate_hook_directory() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hook-directory-deactivator.php';
	Hook_Directory_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hook_directory' );
register_deactivation_hook( __FILE__, 'deactivate_hook_directory' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-hook-directory.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_hook_directory() {

	$plugin = new Hook_Directory();
	$plugin->run();

}
run_hook_directory();

<?php
declare(strict_types=1);

/**
 * Fired during plugin activation
 *
 * @link       https://kiunyearaya.dev
 * @since      1.0.0
 *
 * @package    Hook_Directory
 * @subpackage Hook_Directory/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Hook_Directory
 * @subpackage Hook_Directory/includes
 * @author     Kiunye Araya <kiunyearaya@gmail.com>
 */
class Hook_Directory_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
    public static function activate(): void {

		global $wpdb;
		$table_name = $wpdb->prefix . 'hook_explorer_cache';
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Check if table already exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;
		if ( $table_exists ) {
			return; // Table already exists
		}

		// Use direct CREATE TABLE query for better compatibility
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			hook_name varchar(191) NOT NULL,
			hook_type varchar(20) NOT NULL,
			file_path varchar(255) DEFAULT NULL,
			line int(11) DEFAULT NULL,
			source_type varchar(20) DEFAULT NULL,
			source_name varchar(191) DEFAULT NULL,
			detection_method varchar(20) DEFAULT NULL,
			first_seen datetime DEFAULT NULL,
			last_seen datetime DEFAULT NULL,
			meta longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY hook_name (hook_name),
			KEY source_type (source_type),
			KEY hook_source (hook_name, source_type)
		) {$charset_collate};";

		$result = $wpdb->query( $sql );
		if ( $result === false ) {
			error_log( 'Hook Explorer: Failed to create table: ' . $wpdb->last_error );
		}

		$default_settings = array(
			'scan_core'         => true,
			'scan_plugins'      => true,
			'scan_themes'       => true,
			'runtime_capture'   => false,
			'capture_sample'    => 0,
			'cache_expiry_days' => 7,
		);

		if ( get_option( 'hook_explorer_settings', null ) === null ) {
			add_option( 'hook_explorer_settings', $default_settings, '', false );
		}

		if ( get_option( 'hook_explorer_last_scan', null ) === null ) {
			add_option( 'hook_explorer_last_scan', 0, '', false );
		}

		if ( get_option( 'hook_explorer_db_version', null ) === null ) {
			add_option( 'hook_explorer_db_version', '1', '', false );
		}

    }

}

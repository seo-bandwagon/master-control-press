<?php
/**
 * Fired during plugin activation.
 *
 * @package MasterControlPress
 */

/**
 * Fired during plugin activation.
 */
class Master_Control_Press_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		// Set default options.
		if ( ! get_option( 'master_control_press_version' ) ) {
			update_option( 'master_control_press_version', MASTER_CONTROL_PRESS_VERSION );
		}

		// Create custom database tables if needed.
		// self::create_tables();

		// Set activation time.
		if ( ! get_option( 'master_control_press_activated_time' ) ) {
			update_option( 'master_control_press_activated_time', time() );
		}

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Create custom database tables.
	 */
	private static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Example table creation.
		/*
		$table_name = $wpdb->prefix . 'master_control_press_table';
		$sql        = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			name tinytext NOT NULL,
			text text NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		*/
	}
}

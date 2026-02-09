<?php
/**
 * Fired during plugin deactivation.
 *
 * @package MasterControlPress
 */

/**
 * Fired during plugin deactivation.
 */
class Master_Control_Press_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		// Clear scheduled hooks.
		// wp_clear_scheduled_hook( 'master_control_press_cron_hook' );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

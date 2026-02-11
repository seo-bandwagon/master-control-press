<?php
/**
 * Define the internationalization functionality.
 *
 * @package MasterControlPress
 */

/**
 * Define the internationalization functionality.
 */
class Master_Control_Press_i18n {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'master-control-press',
			false,
			dirname( MASTER_CONTROL_PRESS_PLUGIN_BASE ) . '/languages/'
		);
	}
}

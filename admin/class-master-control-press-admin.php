<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package MasterControlPress
 */

/**
 * The admin-specific functionality of the plugin.
 */
class Master_Control_Press_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			MASTER_CONTROL_PRESS_PLUGIN_URL . 'assets/css/master-control-press-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			MASTER_CONTROL_PRESS_PLUGIN_URL . 'assets/js/master-control-press-admin.js',
			array( 'jquery' ),
			$this->version,
			false
		);
	}
}

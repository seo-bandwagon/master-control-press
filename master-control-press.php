<?php
/**
 * Plugin Name: Master Control Press
 * Plugin URI: https://github.com/seo-bandwagon/master-control-press
 * Description: MCP Plugin for WordPress
 * Version: 1.0.0
 * Author: SEO Bandwagon
 * Author URI: https://github.com/seo-bandwagon
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: master-control-press
 * Domain Path: /languages
 *
 * @package MasterControlPress
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'MASTER_CONTROL_PRESS_VERSION', '1.0.0' );

/**
 * Plugin base name.
 */
define( 'MASTER_CONTROL_PRESS_PLUGIN_FILE', __FILE__ );
define( 'MASTER_CONTROL_PRESS_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'MASTER_CONTROL_PRESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MASTER_CONTROL_PRESS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_master_control_press() {
	require_once MASTER_CONTROL_PRESS_PLUGIN_DIR . 'includes/class-master-control-press-activator.php';
	Master_Control_Press_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_master_control_press() {
	require_once MASTER_CONTROL_PRESS_PLUGIN_DIR . 'includes/class-master-control-press-deactivator.php';
	Master_Control_Press_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_master_control_press' );
register_deactivation_hook( __FILE__, 'deactivate_master_control_press' );

/**
 * The core plugin class.
 */
require MASTER_CONTROL_PRESS_PLUGIN_DIR . 'includes/class-master-control-press.php';

/**
 * Begins execution of the plugin.
 */
function run_master_control_press() {
	$plugin = new Master_Control_Press();
	$plugin->run();
}
run_master_control_press();

<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package MasterControlPress
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 */
function master_control_press_uninstall() {
	// Delete plugin options.
	delete_option( 'master_control_press_options' );
	delete_option( 'master_control_press_version' );
	delete_option( 'master_control_press_activated_time' );

	// For site options in Multisite.
	delete_site_option( 'master_control_press_options' );

	// Drop custom database tables if any.
	global $wpdb;
	// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}master_control_press_table" );

	// Clear any cached data.
	wp_cache_flush();
}

master_control_press_uninstall();

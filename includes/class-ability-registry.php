<?php
/**
 * Ability Registry
 *
 * Central registry for all WP MCP Core Abilities.
 *
 * @package WP_MCP_Core_Abilities
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_MCP_Ability_Registry
 *
 * Manages registration of all abilities.
 */

class WP_MCP_Ability_Registry {

	/**
	 * Register all abilities.
	 *
	 * Note: Categories must be registered separately on wp_abilities_api_categories_init hook.
	 *
	 * @return void
	 */
	public static function register_all() {
		// Register Core site abilities.
		WP_MCP_Core_Site_Abilities::register();

		// Register Post abilities.
		WP_MCP_Post_Abilities::register();

		// Register Page abilities.
		WP_MCP_Page_Abilities::register();

		// Register Taxonomy abilities.
		WP_MCP_Taxonomy_Abilities::register();

		// Register User abilities.
		WP_MCP_User_Abilities::register();

		// Register Comment abilities.
		WP_MCP_Comment_Abilities::register();

		// Register Media abilities.
		WP_MCP_Media_Abilities::register();

		// Register Block abilities.
		WP_MCP_Block_Abilities::register();

		// Register Theme abilities.
		WP_MCP_Theme_Abilities::register();

		// Register Plugin abilities.
		WP_MCP_Plugin_Abilities::register();

        // Register CPT abilities.
        if ( class_exists( 'WP_MCP_CPT_Abilities' ) ) {
            WP_MCP_CPT_Abilities::register();
        }

		   // WooCommerce abilities are now registered by the wp-woo-abilities plugin.

		// ACF CPT ability registration removed. Only WP_MCP_CPT_Abilities is registered for CPT listing.
	}
}

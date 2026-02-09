<?php
/**
 * Ability Categories Registration
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all ability categories for the plugin.
 */
class WP_MCP_Ability_Categories {

	/**
	 * Register all categories.
	 *
	 * @return void
	 */
	public static function register_all() {
		// Core category (site-level operations).
		wp_register_ability_category(
			'core',
			array(
				'label'       => __( 'Core', 'wp-mcp-core-abilities' ),
				'description' => __( 'Site-level operations including plugins, themes, menus, options, and system maintenance.', 'wp-mcp-core-abilities' ),
			)
		);

		// Content category (posts and pages).
		wp_register_ability_category(
			'content',
			array(
				'label'       => __( 'Content', 'wp-mcp-core-abilities' ),
				'description' => __( 'Create, read, update, and delete posts and pages.', 'wp-mcp-core-abilities' ),
			)
		);

		// Taxonomies category (categories and tags).
		wp_register_ability_category(
			'taxonomies',
			array(
				'label'       => __( 'Taxonomies', 'wp-mcp-core-abilities' ),
				'description' => __( 'Manage categories, tags, and custom taxonomies.', 'wp-mcp-core-abilities' ),
			)
		);

		// Users category.
		wp_register_ability_category(
			'users',
			array(
				'label'       => __( 'Users', 'wp-mcp-core-abilities' ),
				'description' => __( 'User account and profile management.', 'wp-mcp-core-abilities' ),
			)
		);

		// Comments category.
		wp_register_ability_category(
			'comments',
			array(
				'label'       => __( 'Comments', 'wp-mcp-core-abilities' ),
				'description' => __( 'Comment moderation and management.', 'wp-mcp-core-abilities' ),
			)
		);

		// Media category.
		wp_register_ability_category(
			'media',
			array(
				'label'       => __( 'Media', 'wp-mcp-core-abilities' ),
				'description' => __( 'Media library and file management.', 'wp-mcp-core-abilities' ),
			)
		);

		// WooCommerce category.
		wp_register_ability_category(
			'woocommerce',
			array(
				'label'       => __( 'WooCommerce', 'wp-mcp-core-abilities' ),
				'description' => __( 'Manage products, orders, and store settings.', 'wp-mcp-core-abilities' ),
			)
		);
	}
}

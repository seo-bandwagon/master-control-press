#!/usr/bin/env php
<?php
/**
 * Plugin activation script
 *
 * Run this to activate the wp-mcp-core-abilities plugin
 */

// Load WordPress
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 3 ) . '/wp-load.php';

$plugin = 'wp-mcp-core-abilities/wp-mcp-core-abilities.php';

echo "=== WP MCP Core Abilities Activation Script ===\n\n";

// Check if plugin exists
$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
if ( ! file_exists( $plugin_file ) ) {
	echo "ERROR: Plugin file not found: $plugin_file\n";
	exit( 1 );
}

echo "✓ Plugin file found: $plugin_file\n";

// Check if already active
if ( is_plugin_active( $plugin ) ) {
	echo "✓ Plugin is already active\n";
	echo "\nDeactivating and reactivating to reload...\n";
	deactivate_plugins( $plugin );
}

// Activate the plugin
$result = activate_plugin( $plugin );

if ( is_wp_error( $result ) ) {
	echo "✗ ERROR activating plugin:\n";
	echo "  " . $result->get_error_message() . "\n";
	exit( 1 );
}

echo "✓ Plugin activated successfully!\n\n";

// Check if wp_register_ability exists
if ( ! function_exists( 'wp_register_ability' ) ) {
	echo "WARNING: wp_register_ability() function not found!\n";
	echo "Make sure WordPress 6.9+ with Abilities API is installed.\n";
	exit( 1 );
}

echo "✓ wp_register_ability() function exists\n\n";

// Trigger init hooks to register abilities
do_action( 'plugins_loaded' );
do_action( 'init' );

echo "Checking registered abilities...\n\n";

// Try to get abilities
if ( function_exists( 'wp_get_abilities' ) ) {
	$all_abilities = wp_get_abilities();
	$core_abilities = array();

	foreach ( $all_abilities as $ability ) {
		$name = $ability->get_name();
		if ( str_starts_with( $name, 'wp-mcp-core/' ) ) {
			$meta = $ability->get_meta();
			$core_abilities[] = array(
				'name'   => $name,
				'label'  => $ability->get_label(),
				'public' => $meta['mcp']['public'] ?? false,
			);
		}
	}

	if ( empty( $core_abilities ) ) {
		echo "✗ WARNING: No wp-mcp-core/* abilities found!\n";
		echo "\nDebugging information:\n";
		echo "- Total abilities registered: " . count( $all_abilities ) . "\n";
		echo "- Plugin classes loaded:\n";
		echo "  - WP_MCP_Core_Abilities: " . ( class_exists( 'WP_MCP_Core_Abilities' ) ? 'YES' : 'NO' ) . "\n";
		echo "  - WP_MCP_Ability_Registry: " . ( class_exists( 'WP_MCP_Ability_Registry' ) ? 'YES' : 'NO' ) . "\n";
		echo "  - WP_MCP_Core_Abilities_Set: " . ( class_exists( 'WP_MCP_Core_Abilities_Set' ) ? 'YES' : 'NO' ) . "\n";
	} else {
		echo "✓ Found " . count( $core_abilities ) . " wp-mcp-core abilities:\n\n";
		foreach ( $core_abilities as $ability ) {
			echo "  - {$ability['name']}\n";
			echo "    Label: {$ability['label']}\n";
			echo "    Public: " . ( $ability['public'] ? 'YES' : 'NO' ) . "\n\n";
		}
	}
} else {
	echo "✗ wp_get_abilities() function not found\n";
}

echo "\nActivation complete!\n";

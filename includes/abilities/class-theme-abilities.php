<?php
/**
 * Theme Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme management abilities.
 */
class WP_MCP_Theme_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all theme abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_list_themes();
		$instance->register_install_theme();
		$instance->register_activate_theme();
		$instance->register_delete_theme();
	}

	/**
	 * Register list-themes ability.
	 *
	 * @return void
	 */
	private function register_list_themes() {
		wp_register_ability(
			'wp-mcp-core/list-themes',
			array(
				'category'            => 'site',
				'label'               => __( 'List Themes', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Retrieve a list of installed themes.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type'        => 'string',
							'description' => 'Filter by status (active, inactive)',
							'enum'        => array( 'all', 'active', 'inactive' ),
							'default'     => 'all',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'themes' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'        => array( 'type' => 'string' ),
									'slug'        => array( 'type' => 'string' ),
									'version'     => array( 'type' => 'string' ),
									'status'      => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'author'      => array( 'type' => 'string' ),
									'screenshot'  => array( 'type' => 'string' ),
								),
							),
						),
						'total'  => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_themes' ),
				   // Allow any authenticated user to list themes.
				   'permission_callback' => function() {
					   return is_user_logged_in();
				   },
				'meta'                => array(
					'mcp'         => array( 'public' => true ),
					'annotations' => array(
						'readonly'    => true,
						'idempotent'  => true,
						'destructive' => false,
					),
				),
			)
		);
	}

	/**
	 * Execute list-themes.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	       public function execute_list_themes( $input ) {
			   $result = array(
				   'success' => false,
				   'themes'  => [],
				   'total'   => 0,
				   'error'   => ''
			   );
			   // No capability check: allow any user to list themes.
			   if ( ! function_exists( 'wp_get_themes' ) ) {
				   if ( ! @include_once ABSPATH . 'wp-admin/includes/theme.php' ) {
					   $result['error'] = 'WordPress function wp_get_themes() not available.';
					   return $result;
				   }
			   }
			   $status = $input['status'] ?? 'all';
			   try {
				   $themes = wp_get_themes();
				   $current_theme = wp_get_theme();

				   $formatted_themes = array();
				   foreach ( $themes as $slug => $theme ) {
					   // Compare using the theme's stylesheet, matching WordPress REST API pattern
					   $is_active = ( $theme->get_stylesheet() === $current_theme->get_stylesheet() );
					   if ( 'active' === $status && ! $is_active ) {
						   continue;
					   }
					   if ( 'inactive' === $status && $is_active ) {
						   continue;
					   }
					   $formatted_themes[] = array(
						   'name'        => $theme->get( 'Name' ),
						   'slug'        => $slug,
						   'version'     => $theme->get( 'Version' ),
						   'status'      => $is_active ? 'active' : 'inactive',
						   'description' => $theme->get( 'Description' ),
						   'author'      => $theme->get( 'Author' ),
						   'screenshot'  => $theme->get_screenshot(),
					   );
				   }
				   $result['themes'] = $formatted_themes;
				   $result['total'] = count($formatted_themes);
				   $result['success'] = true;
				   if (empty($formatted_themes)) {
					   $result['error'] = 'No themes found for the given status.';
				   }
				   return $result;
			   } catch (\Throwable $e) {
				   $result['error'] = 'Failed to list themes: ' . $e->getMessage();
				   return $result;
			   }
	       }

	/**
	 * Register install-theme ability.
	 *
	 * @return void
	 */
	private function register_install_theme() {
		wp_register_ability(
			'wp-mcp-core/install-theme',
			array(
				'category'            => 'site',
				'label'               => __( 'Install Theme', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Install a theme from the WordPress.org repository.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'slug' ),
					'properties' => array(
						'slug' => array(
							'type'        => 'string',
							'description' => 'Theme slug to install',
						),
						'activate' => array(
							'type'        => 'boolean',
							'description' => 'Activate after installation',
							'default'     => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'slug'    => array( 'type' => 'string' ),
						'log'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'execute_callback'    => array( $this, 'execute_install_theme' ),
				'permission_callback' => function() {
					return current_user_can( 'install_themes' );
				},
				'meta'                => array(
					'mcp'         => array( 'public' => true ),
					'annotations' => array(
						'readonly'    => false,
						'idempotent'  => false,
						'destructive' => false,
					),
				),
			)
		);
	}

	/**
	 * Execute install-theme.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_install_theme( $input ) {
		$slug = $input['slug'];

		// Check if theme is already installed.
		$theme = wp_get_theme( $slug );
		if ( $theme->exists() ) {
			return new WP_Error(
				'theme_already_installed',
				sprintf( "Theme '%s' is already installed.", $slug ),
				array( 'status' => 400 )
			);
		}

		// Include required WordPress files.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/theme.php';

		// Initialize WordPress filesystem.
		WP_Filesystem();

		$api = themes_api(
			'theme_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return new WP_Error(
				'theme_not_found',
				sprintf( "Theme '%s' not found in WordPress.org repository.", $slug ),
				array( 'status' => 404 )
			);
		}

		$skin = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Theme_Upgrader( $skin );

		// Perform the installation.
		$result = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new WP_Error( 'install_failed', 'Theme installation failed', array( 'errors' => $skin->get_errors() ) );
		}

		if ( ! empty( $input['activate'] ) ) {
			// Get the installed theme's stylesheet.
			$theme_info = $upgrader->theme_info();
			if ( $theme_info ) {
				switch_theme( $theme_info->get_stylesheet() );
			}
		}

		return array(
			'success' => true,
			'slug'    => $slug,
			'log'     => $skin->get_upgrade_messages(),
		);
	}

	/**
	 * Register activate-theme ability.
	 *
	 * @return void
	 */
	private function register_activate_theme() {
		wp_register_ability(
			'wp-mcp-core/activate-theme',
			array(
				'category'            => 'site',
				'label'               => __( 'Activate Theme', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Activate an installed theme.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'slug' ),
					'properties' => array(
						'slug' => array(
							'type'        => 'string',
							'description' => 'Theme slug to activate',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'slug'    => array( 'type' => 'string' ),
						'name'    => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_activate_theme' ),
				'permission_callback' => function() {
					return current_user_can( 'switch_themes' );
				},
				'meta'                => array(
					'mcp'         => array( 'public' => true ),
					'annotations' => array(
						'readonly'    => false,
						'idempotent'  => true,
						'destructive' => false,
					),
				),
			)
		);
	}

	/**
	 * Execute activate-theme.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_activate_theme( $input ) {
		$slug = $input['slug'];
		$theme = wp_get_theme( $slug );

		if ( ! $theme->exists() ) {
			return new WP_Error( 'theme_not_found', 'Theme not found', array( 'status' => 404 ) );
		}

		switch_theme( $slug );

		return array(
			'success' => true,
			'slug'    => $slug,
			'name'    => $theme->get( 'Name' ),
		);
	}

	/**
	 * Register delete-theme ability.
	 *
	 * @return void
	 */
	private function register_delete_theme() {
		wp_register_ability(
			'wp-mcp-core/delete-theme',
			array(
				'category'            => 'site',
				'label'               => __( 'Delete Theme', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete an installed theme.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'slug' ),
					'properties' => array(
						'slug' => array(
							'type'        => 'string',
							'description' => 'Theme slug to delete',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'slug'    => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_theme' ),
				'permission_callback' => function() {
					return current_user_can( 'delete_themes' );
				},
				'meta'                => array(
					'mcp'         => array( 'public' => true ),
					'annotations' => array(
						'readonly'    => false,
						'idempotent'  => true,
						'destructive' => true,
					),
				),
			)
		);
	}

	/**
	 * Execute delete-theme.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_theme( $input ) {
		$slug = $input['slug'];
		$theme = wp_get_theme( $slug );

		if ( ! $theme->exists() ) {
			return new WP_Error(
				'theme_not_found',
				sprintf( "Theme '%s' not found.", $slug ),
				array( 'status' => 404 )
			);
		}

		if ( $theme->get_stylesheet() === get_stylesheet() ) {
			return new WP_Error(
				'cannot_delete_active_theme',
				'Cannot delete the currently active theme. Please activate a different theme first.',
				array( 'status' => 400 )
			);
		}

		include_once ABSPATH . 'wp-admin/includes/theme.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';

		$result = delete_theme( $slug );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', 'Theme deletion failed' );
		}

		return array(
			'success' => true,
			'slug'    => $slug,
		);
	}
}

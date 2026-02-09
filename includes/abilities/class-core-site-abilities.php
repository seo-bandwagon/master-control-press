<?php
/**
 * Core Site Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core site-level abilities.
 */
class WP_MCP_Core_Site_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all core site abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_get_site_info();
		$instance->register_update_site_info();
		$instance->register_get_environment_info();
		$instance->register_search_content();
		// Plugin abilities moved to WP_MCP_Plugin_Abilities class.
		$instance->register_get_plugin();
		// Theme abilities moved to WP_MCP_Theme_Abilities class.
		$instance->register_get_active_theme();
		$instance->register_list_menus();
		$instance->register_get_menu();
		$instance->register_create_menu();
		$instance->register_delete_menu();
		$instance->register_get_option();
		$instance->register_update_option();
		$instance->register_flush_cache();
	}

	/**
	 * Register get-site-info ability.
	 *
	 * @return void
	 */
	private function register_get_site_info() {
		wp_register_ability(
			'wp-mcp-core/get-site-info',
			array(
				'category'            => 'core',
				'label'               => __( 'Get Site Info', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get WordPress site information including name, URL, description, language, and timezone.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'url'         => array( 'type' => 'string' ),
						'admin_email' => array( 'type' => 'string' ),
						'language'    => array( 'type' => 'string' ),
						'timezone'    => array( 'type' => 'string' ),
						'date_format' => array( 'type' => 'string' ),
						'time_format' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_site_info' ),
				'permission_callback' => function() {
					return current_user_can( 'read' );
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
	 * Execute get-site-info.
	 *
	 * @return array Site information.
	 */
	public function execute_get_site_info() {
		return array(
			'name'        => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'url'         => get_bloginfo( 'url' ),
			'admin_email' => get_bloginfo( 'admin_email' ),
			'language'    => get_bloginfo( 'language' ),
			'timezone'    => get_option( 'timezone_string' ) ?: 'UTC',
			'date_format' => get_option( 'date_format' ),
			'time_format' => get_option( 'time_format' ),
		);
	}

	/**
	 * Register update-site-info ability.
	 *
	 * @return void
	 */
	private function register_update_site_info() {
		wp_register_ability(
			'wp-mcp-core/update-site-info',
			array(
				'category'            => 'core',
				'label'               => __( 'Update Site Info', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Update WordPress site information including name, description, admin email, and timezone.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array(
							'type'        => 'string',
							'description' => 'Site Title',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'Tagline',
						),
						'admin_email' => array(
							'type'        => 'string',
							'format'      => 'email',
							'description' => 'Administration Email Address',
						),
						'timezone'    => array(
							'type'        => 'string',
							'description' => 'Timezone string (e.g., "America/New_York")',
						),
						'date_format' => array(
							'type'        => 'string',
							'description' => 'Date Format',
						),
						'time_format' => array(
							'type'        => 'string',
							'description' => 'Time Format',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'updated' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_site_info' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
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
	 * Execute update-site-info.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_update_site_info( $input ) {
		$updated = array();

		if ( isset( $input['name'] ) ) {
			update_option( 'blogname', sanitize_text_field( $input['name'] ) );
			$updated[] = 'name';
		}

		if ( isset( $input['description'] ) ) {
			update_option( 'blogdescription', sanitize_text_field( $input['description'] ) );
			$updated[] = 'description';
		}

		if ( isset( $input['admin_email'] ) && is_email( $input['admin_email'] ) ) {
			update_option( 'admin_email', sanitize_email( $input['admin_email'] ) );
			$updated[] = 'admin_email';
		}

		if ( isset( $input['timezone'] ) ) {
			update_option( 'timezone_string', sanitize_text_field( $input['timezone'] ) );
			$updated[] = 'timezone';
		}

		if ( isset( $input['date_format'] ) ) {
			update_option( 'date_format', sanitize_text_field( $input['date_format'] ) );
			$updated[] = 'date_format';
		}

		if ( isset( $input['time_format'] ) ) {
			update_option( 'time_format', sanitize_text_field( $input['time_format'] ) );
			$updated[] = 'time_format';
		}

		return array(
			'success' => true,
			'updated' => $updated,
		);
	}

	/**
	 * Register get-environment-info ability.
	 *
	 * @return void
	 */
	private function register_get_environment_info() {
		wp_register_ability(
			'wp-mcp-core/get-environment-info',
			array(
				'category'            => 'core',
				'label'               => __( 'Get Environment Info', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get WordPress environment information including versions, server details, and active theme.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'wp_version'      => array( 'type' => 'string' ),
						'php_version'     => array( 'type' => 'string' ),
						'mysql_version'   => array( 'type' => 'string' ),
						'server_software' => array( 'type' => 'string' ),
						'active_theme'    => array( 'type' => 'string' ),
						'multisite'       => array( 'type' => 'boolean' ),
						'debug_mode'      => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_environment_info' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
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
	 * Execute get-environment-info.
	 *
	 * @return array Environment information.
	 */
	public function execute_get_environment_info() {
		global $wpdb;
		$theme = wp_get_theme();

		return array(
			'wp_version'      => get_bloginfo( 'version' ),
			'php_version'     => PHP_VERSION,
			'mysql_version'   => $wpdb->db_version(),
			'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
			'active_theme'    => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
			'multisite'       => is_multisite(),
			'debug_mode'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
		);
	}

	/**
	 * Register search-content ability.
	 *
	 * @return void
	 */
	private function register_search_content() {
		wp_register_ability(
			'wp-mcp-core/search-content',
			array(
				'category'            => 'core',
				'label'               => __( 'Search Content', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Global search across posts, pages, and custom post types.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'query' ),
					'properties' => array(
						'query'     => array(
							'type'        => 'string',
							'description' => 'Search query',
							'minLength'   => 1,
						),
						'post_type' => array(
							'type'        => 'string',
							'description' => 'Post type to search (post, page, or any)',
							'default'     => 'any',
						),
						'per_page'  => array(
							'type'    => 'integer',
							'default' => 10,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'results' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'      => array( 'type' => 'integer' ),
									'title'   => array( 'type' => 'string' ),
									'excerpt' => array( 'type' => 'string' ),
									'type'    => array( 'type' => 'string' ),
									'url'     => array( 'type' => 'string' ),
								),
							),
						),
						'total'   => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_search_content' ),
				'permission_callback' => function() {
					return current_user_can( 'read' );
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
	 * Execute search-content.
	 *
	 * @param array $input Input parameters.
	 * @return array Search results.
	 */
	public function execute_search_content( $input ) {
		$args = array(
			's'              => sanitize_text_field( $input['query'] ),
			'post_type'      => $input['post_type'] ?? 'any',
			'posts_per_page' => $input['per_page'] ?? 10,
			'post_status'    => 'publish',
		);

		$query   = new WP_Query( $args );
		$results = array();

		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'      => $post->ID,
				'title'   => get_the_title( $post->ID ),
				'excerpt' => get_the_excerpt( $post->ID ),
				'type'    => $post->post_type,
				'url'     => get_permalink( $post->ID ),
			);
		}

		return array(
			'results' => $results,
			'total'   => $query->found_posts,
		);
	}

	/**
	 * Register list-plugins ability.
	 *
	 * @return void
	 */
	private function register_list_plugins() {
		wp_register_ability(
			'wp-mcp-core/list-plugins',
			array(
				'category'            => 'core',
				'label'               => __( 'List Plugins', 'wp-mcp-core-abilities' ),
				'description'         => __( 'List all installed plugins with their status and metadata.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'plugins' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'        => array( 'type' => 'string' ),
									'version'     => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'author'      => array( 'type' => 'string' ),
									'active'      => array( 'type' => 'boolean' ),
									'plugin_file' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_plugins' ),
				'permission_callback' => function() {
					return current_user_can( 'activate_plugins' );
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
	 * Execute list-plugins.
	 *
	 * @return array Plugins list.
	 */
	public function execute_list_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		$plugins        = array();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$plugins[] = array(
				'name'        => $plugin_data['Name'],
				'version'     => $plugin_data['Version'],
				'description' => $plugin_data['Description'],
				'author'      => strip_tags( $plugin_data['Author'] ),
				'active'      => in_array( $plugin_file, $active_plugins, true ),
				'plugin_file' => $plugin_file,
			);
		}

		return array( 'plugins' => $plugins );
	}

	/**
	 * Register get-plugin ability.
	 *
	 * @return void
	 */
	private function register_get_plugin() {
		wp_register_ability(
			'wp-mcp-core/get-plugin',
			array(
				'category'            => 'core',
				'label'               => __( 'Get Plugin', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get detailed information about a specific plugin.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'plugin_file' ),
					'properties' => array(
						'plugin_file' => array(
							'type'        => 'string',
							'description' => 'Plugin file path (e.g., "akismet/akismet.php")',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'version'     => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'author'      => array( 'type' => 'string' ),
						'author_uri'  => array( 'type' => 'string' ),
						'plugin_uri'  => array( 'type' => 'string' ),
						'active'      => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_plugin' ),
				'permission_callback' => function() {
					return current_user_can( 'activate_plugins' );
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
	 * Execute get-plugin.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Plugin data.
	 */
	public function execute_get_plugin( $input ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = sanitize_text_field( $input['plugin_file'] );
		$all_plugins = get_plugins();

		if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
			return new WP_Error( 'plugin_not_found', 'Plugin not found', array( 'status' => 404 ) );
		}

		$plugin_data    = $all_plugins[ $plugin_file ];
		$active_plugins = get_option( 'active_plugins', array() );

		return array(
			'name'        => $plugin_data['Name'],
			'version'     => $plugin_data['Version'],
			'description' => $plugin_data['Description'],
			'author'      => strip_tags( $plugin_data['Author'] ),
			'author_uri'  => $plugin_data['AuthorURI'],
			'plugin_uri'  => $plugin_data['PluginURI'],
			'active'      => in_array( $plugin_file, $active_plugins, true ),
		);
	}

	/**
	 * Register activate-plugin ability.
	 *
	 * @return void
	 */
	private function register_activate_plugin() {
		wp_register_ability(
			'wp-mcp-core/activate-plugin',
			array(
				'category'            => 'core',
				'label'               => __( 'Activate Plugin', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Activate a WordPress plugin.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'plugin_file' ),
					'properties' => array(
						'plugin_file' => array(
							'type'        => 'string',
							'description' => 'Plugin file path (e.g., "akismet/akismet.php")',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_activate_plugin' ),
				'permission_callback' => function() {
					return current_user_can( 'activate_plugins' );
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
	 * Execute activate-plugin.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_activate_plugin( $input ) {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = sanitize_text_field( $input['plugin_file'] );
		$result      = activate_plugin( $plugin_file );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'success' => true );
	}

	/**
	 * Register deactivate-plugin ability.
	 *
	 * @return void
	 */
	private function register_deactivate_plugin() {
		wp_register_ability(
			'wp-mcp-core/deactivate-plugin',
			array(
				'category'            => 'core',
				'label'               => __( 'Deactivate Plugin', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Deactivate a WordPress plugin.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'plugin_file' ),
					'properties' => array(
						'plugin_file' => array(
							'type'        => 'string',
							'description' => 'Plugin file path (e.g., "akismet/akismet.php")',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_deactivate_plugin' ),
				'permission_callback' => function() {
					return current_user_can( 'activate_plugins' );
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
	 * Execute deactivate-plugin.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_deactivate_plugin( $input ) {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = sanitize_text_field( $input['plugin_file'] );
		deactivate_plugins( $plugin_file );

		return array( 'success' => true );
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
				'category'            => 'core',
				'label'               => __( 'List Themes', 'wp-mcp-core-abilities' ),
				'description'         => __( 'List all installed themes.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'themes' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'        => array( 'type' => 'string' ),
									'version'     => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'author'      => array( 'type' => 'string' ),
									'active'      => array( 'type' => 'boolean' ),
									'stylesheet'  => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_themes' ),
				'permission_callback' => function() {
					return current_user_can( 'switch_themes' );
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
	 * @return array Themes list.
	 */
	public function execute_list_themes() {
		$all_themes     = wp_get_themes();
		$current_theme  = wp_get_theme();
		$themes         = array();

		foreach ( $all_themes as $theme ) {
			$themes[] = array(
				'name'        => $theme->get( 'Name' ),
				'version'     => $theme->get( 'Version' ),
				'description' => $theme->get( 'Description' ),
				'author'      => $theme->get( 'Author' ),
				'active'      => ( $theme->get_stylesheet() === $current_theme->get_stylesheet() ),
				'stylesheet'  => $theme->get_stylesheet(),
			);
		}

		return array( 'themes' => $themes );
	}

	/**
	 * Register get-active-theme ability.
	 *
	 * @return void
	 */
	private function register_get_active_theme() {
		wp_register_ability(
			'wp-mcp-core/get-active-theme',
			array(
				'category'            => 'core',
				'label'               => __( 'Get Active Theme', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get current active theme information.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'version'     => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'author'      => array( 'type' => 'string' ),
						'stylesheet'  => array( 'type' => 'string' ),
						'template'    => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_active_theme' ),
				'permission_callback' => function() {
					return current_user_can( 'read' );
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
	 * Execute get-active-theme.
	 *
	 * @return array Active theme data.
	 */
	public function execute_get_active_theme() {
		$theme = wp_get_theme();

		return array(
			'name'        => $theme->get( 'Name' ),
			'version'     => $theme->get( 'Version' ),
			'description' => $theme->get( 'Description' ),
			'author'      => $theme->get( 'Author' ),
			'stylesheet'  => $theme->get_stylesheet(),
			'template'    => $theme->get_template(),
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
				'category'            => 'core',
				'label'               => __( 'Activate Theme', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Activate a WordPress theme.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'stylesheet' ),
					'properties' => array(
						'stylesheet' => array(
							'type'        => 'string',
							'description' => 'Theme stylesheet (e.g., "twentytwentyfour")',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
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
		$stylesheet = sanitize_text_field( $input['stylesheet'] );
		$theme      = wp_get_theme( $stylesheet );

		if ( ! $theme->exists() ) {
			return new WP_Error( 'theme_not_found', 'Theme not found', array( 'status' => 404 ) );
		}

		switch_theme( $stylesheet );

		return array( 'success' => true );
	}

	/**
	 * Register list-menus ability.
	 *
	 * @return void
	 */
	private function register_list_menus() {
		wp_register_ability(
			'wp-mcp-core/list-menus',
			array(
				'category'            => 'core',
				'label'               => __( 'List Menus', 'wp-mcp-core-abilities' ),
				'description'         => __( 'List all registered navigation menus.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'menus' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'term_id' => array( 'type' => 'integer' ),
									'name'    => array( 'type' => 'string' ),
									'slug'    => array( 'type' => 'string' ),
									'count'   => array( 'type' => 'integer' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_menus' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_theme_options' );
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
	 * Execute list-menus.
	 *
	 * @return array Menus list.
	 */
	public function execute_list_menus() {
		$nav_menus = wp_get_nav_menus();
		$menus     = array();

		foreach ( $nav_menus as $menu ) {
			$menus[] = array(
				'term_id' => $menu->term_id,
				'name'    => $menu->name,
				'slug'    => $menu->slug,
				'count'   => $menu->count,
			);
		}

		return array( 'menus' => $menus );
	}

	/**
	 * Register get-menu ability.
	 *
	 * @return void
	 */
	private function register_get_menu() {
		wp_register_ability(
			'wp-mcp-core/get-menu',
			array(
				'category'            => 'core',
				'label'               => __( 'Get Menu', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get menu items and structure for a specific menu.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'menu_id' ),
					'properties' => array(
						'menu_id' => array(
							'type'        => 'integer',
							'description' => 'Menu term ID',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'menu'  => array(
							'type'       => 'object',
							'properties' => array(
								'term_id' => array( 'type' => 'integer' ),
								'name'    => array( 'type' => 'string' ),
								'slug'    => array( 'type' => 'string' ),
							),
						),
						'items' => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_menu' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_theme_options' );
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
	 * Execute get-menu.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Menu data.
	 */
	public function execute_get_menu( $input ) {
		$menu_id = absint( $input['menu_id'] );
		$menu    = wp_get_nav_menu_object( $menu_id );

		if ( ! $menu ) {
			return new WP_Error( 'menu_not_found', 'Menu not found', array( 'status' => 404 ) );
		}

		$menu_items = wp_get_nav_menu_items( $menu_id );
		$items      = array();

		foreach ( $menu_items as $item ) {
			$items[] = array(
				'id'     => $item->ID,
				'title'  => $item->title,
				'url'    => $item->url,
				'parent' => $item->menu_item_parent,
				'order'  => $item->menu_order,
			);
		}

		return array(
			'menu'  => array(
				'term_id' => $menu->term_id,
				'name'    => $menu->name,
				'slug'    => $menu->slug,
			),
			'items' => $items,
		);
	}

	/**
	 * Register create-menu ability.
	 *
	 * @return void
	 */
	private function register_create_menu() {
		wp_register_ability(
			'wp-mcp-core/create-menu',
			array(
				'category'            => 'core',
				'label'               => __( 'Create Menu', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Create a new navigation menu.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'name' ),
					'properties' => array(
						'name' => array(
							'type'        => 'string',
							'description' => 'Menu name',
						),
						'slug' => array(
							'type'        => 'string',
							'description' => 'Menu slug (optional)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'term_id' => array( 'type' => 'integer' ),
						'name'    => array( 'type' => 'string' ),
						'slug'    => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_menu' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_theme_options' );
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
	 * Execute create-menu.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_create_menu( $input ) {
		$menu_name = sanitize_text_field( $input['name'] );
		$args      = array();

		if ( isset( $input['slug'] ) ) {
			$args['slug'] = sanitize_title( $input['slug'] );
		}

		$menu_id = wp_create_nav_menu( $menu_name, $args );

		if ( is_wp_error( $menu_id ) ) {
			return $menu_id;
		}

		$menu = wp_get_nav_menu_object( $menu_id );

		return array(
			'term_id' => $menu->term_id,
			'name'    => $menu->name,
			'slug'    => $menu->slug,
		);
	}

	/**
	 * Register delete-menu ability.
	 *
	 * @return void
	 */
	private function register_delete_menu() {
		wp_register_ability(
			'wp-mcp-core/delete-menu',
			array(
				'category'            => 'core',
				'label'               => __( 'Delete Menu', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete a navigation menu.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'menu_id' ),
					'properties' => array(
						'menu_id' => array(
							'type'        => 'integer',
							'description' => 'Menu term ID',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_menu' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_theme_options' );
				},
				'meta'                => array(
					'mcp'         => array( 'public' => true ),
					'annotations' => array(
						'readonly'    => false,
						'idempotent'  => false,
						'destructive' => true,
					),
				),
			)
		);
	}

	/**
	 * Execute delete-menu.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_menu( $input ) {
		$menu_id = absint( $input['menu_id'] );
		$result  = wp_delete_nav_menu( $menu_id );

		if ( is_wp_error( $result ) || ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete menu', array( 'status' => 500 ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Register get-option ability.
	 *
	 * @return void
	 */
	private function register_get_option() {
		wp_register_ability(
			'wp-mcp-core/get-option',
			array(
				'category'            => 'core',
				'label'               => __( 'Get Option', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get a WordPress option value by key.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'option_name' ),
					'properties' => array(
						'option_name' => array(
							'type'        => 'string',
							'description' => 'Option name/key',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'option_name'  => array( 'type' => 'string' ),
						'option_value' => array(),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_option' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
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
	 * Execute get-option.
	 *
	 * @param array $input Input parameters.
	 * @return array Option data.
	 */
	public function execute_get_option( $input ) {
		$option_name  = sanitize_text_field( $input['option_name'] );
		$option_value = get_option( $option_name );

		return array(
			'option_name'  => $option_name,
			'option_value' => $option_value,
		);
	}

	/**
	 * Register update-option ability.
	 *
	 * @return void
	 */
	private function register_update_option() {
		wp_register_ability(
			'wp-mcp-core/update-option',
			array(
				'category'            => 'core',
				'label'               => __( 'Update Option', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Set or update a WordPress option value.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'option_name', 'option_value' ),
					'properties' => array(
						'option_name'  => array(
							'type'        => 'string',
							'description' => 'Option name/key',
						),
						'option_value' => array(
							'description' => 'Option value (can be any type)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'      => array( 'type' => 'boolean' ),
						'option_name'  => array( 'type' => 'string' ),
						'option_value' => array(),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_option' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
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
	 * Execute update-option.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_update_option( $input ) {
		$option_name  = sanitize_text_field( $input['option_name'] );
		$option_value = $input['option_value'];

		$success = update_option( $option_name, $option_value );

		return array(
			'success'      => $success,
			'option_name'  => $option_name,
			'option_value' => $option_value,
		);
	}

	/**
	 * Register flush-cache ability.
	 *
	 * @return void
	 */
	private function register_flush_cache() {
		wp_register_ability(
			'wp-mcp-core/flush-cache',
			array(
				'category'            => 'core',
				'label'               => __( 'Flush Object Cache', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Flushes the WordPress object cache.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_flush_cache' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'mcp'         => array( 'public' => true ),
					'annotations' => array(
						'readonly'    => false,
						'idempotent'  => false,
						'destructive' => true,
					),
				),
			)
		);
	}

	/**
	 * Execute flush-cache.
	 *
	 * @return array Result.
	 */
	public function execute_flush_cache() {
		if ( function_exists( 'wp_cache_flush' ) ) {
			$result = wp_cache_flush();
			$success = ( false !== $result );
		} else {
			$success = false;
		}

		return array( 'success' => $success );
	}
}

<?php
/**
 * Plugin Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin management abilities.
 */
class WP_MCP_Plugin_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all plugin abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_list_plugins();
		$instance->register_install_plugin();
		$instance->register_activate_plugin();
		$instance->register_deactivate_plugin();
		$instance->register_delete_plugin();
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
				'category'            => 'site',
				'label'               => __( 'List Plugins', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Retrieve a list of installed plugins.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type'        => 'string',
							'description' => 'Filter by status (all, active, inactive, must-use)',
							'enum'        => array( 'all', 'active', 'inactive', 'must-use' ),
							'default'     => 'all',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'plugins' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'        => array( 'type' => 'string' ),
									'plugin_file' => array( 'type' => 'string' ),
									'version'     => array( 'type' => 'string' ),
									'status'      => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'author'      => array( 'type' => 'string' ),
									'plugin_uri'  => array( 'type' => 'string' ),
								),
							),
						),
						'total'   => array( 'type' => 'integer' ),
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
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
		       public function execute_list_plugins( $input ) {
			       $result = array(
				       'success' => false,
				       'plugins' => [],
				       'total'   => 0,
				       'error'   => ''
			       );
				   // No capability check: allow any user to list plugins.
			       if ( ! function_exists( 'get_plugins' ) ) {
				       if ( ! @include_once ABSPATH . 'wp-admin/includes/plugin.php' ) {
					       $result['error'] = 'WordPress function get_plugins() not available.';
					       return $result;
				       }
			       }
			       $status = $input['status'] ?? 'all';
			       try {
				       if ( 'must-use' === $status ) {
					       $plugins = function_exists('get_mu_plugins') ? get_mu_plugins() : [];
				       } else {
					       $plugins = get_plugins();
				       }
				       $formatted_plugins = array();
				       foreach ( $plugins as $plugin_file => $data ) {
					       $is_active = function_exists('is_plugin_active') ? is_plugin_active( $plugin_file ) : false;
					       if ( 'active' === $status && ! $is_active ) {
						       continue;
					       }
					       if ( 'inactive' === $status && $is_active ) {
						       continue;
					       }
					       $formatted_plugins[] = array(
						       'name'        => isset($data['Name']) ? $data['Name'] : '',
						       'plugin_file' => $plugin_file,
						       'version'     => isset($data['Version']) ? $data['Version'] : '',
						       'status'      => 'must-use' === $status ? 'must-use' : ( $is_active ? 'active' : 'inactive' ),
						       'description' => isset($data['Description']) ? $data['Description'] : '',
						       'author'      => isset($data['Author']) ? $data['Author'] : '',
						       'plugin_uri'  => isset($data['PluginURI']) ? $data['PluginURI'] : '',
					       );
				       }
				       $result['plugins'] = $formatted_plugins;
				       $result['total'] = count($formatted_plugins);
				       $result['success'] = true;
				       if (empty($formatted_plugins)) {
					       $result['error'] = 'No plugins found for the given status.';
				       }
				       return $result;
			       } catch (\Throwable $e) {
				       $result['error'] = 'Failed to list plugins: ' . $e->getMessage();
				       return $result;
			       }
		       }

	/**
	 * Register install-plugin ability.
	 *
	 * @return void
	 */
	private function register_install_plugin() {
		wp_register_ability(
			'wp-mcp-core/install-plugin',
			array(
				'category'            => 'site',
				'label'               => __( 'Install Plugin', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Install a plugin from the WordPress.org repository.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'slug' ),
					'properties' => array(
						'slug' => array(
							'type'        => 'string',
							'description' => 'Plugin slug to install',
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
				'execute_callback'    => array( $this, 'execute_install_plugin' ),
				'permission_callback' => function() {
					return current_user_can( 'install_plugins' );
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
	 * Execute install-plugin.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_install_plugin( $input ) {
		$slug = $input['slug'];

		// Include required WordPress files.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		// Initialize WordPress filesystem.
		WP_Filesystem();

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		$skin = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );

		// Perform the installation.
		$result = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new WP_Error( 'install_failed', 'Plugin installation failed', array( 'errors' => $skin->get_errors() ) );
		}

		if ( ! empty( $input['activate'] ) ) {
			$plugin_file = $upgrader->plugin_info();
			if ( $plugin_file ) {
				activate_plugin( $plugin_file );
			}
		}

		return array(
			'success' => true,
			'slug'    => $slug,
			'log'     => $skin->get_upgrade_messages(),
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
				'category'            => 'site',
				'label'               => __( 'Activate Plugin', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Activate an installed plugin.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'plugin_file' ),
					'properties' => array(
						'plugin_file' => array(
							'type'        => 'string',
							'description' => 'Plugin file path (e.g., plugin-slug/plugin-file.php)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'plugin_file' => array( 'type' => 'string' ),
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
		$plugin_file = $input['plugin_file'];
		
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Validate plugin exists
		$plugins = get_plugins();
		if ( ! isset( $plugins[ $plugin_file ] ) ) {
			// Try to find by slug if full path not provided
			$found = false;
			foreach ( array_keys( $plugins ) as $file ) {
				if ( strpos( $file, $plugin_file . '/' ) === 0 ) {
					$plugin_file = $file;
					$found = true;
					break;
				}
			}
			
			if ( ! $found ) {
				return new WP_Error( 'plugin_not_found', 'Plugin not found', array( 'status' => 404 ) );
			}
		}

		$result = activate_plugin( $plugin_file );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'plugin_file' => $plugin_file,
		);
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
				'category'            => 'site',
				'label'               => __( 'Deactivate Plugin', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Deactivate an active plugin.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'plugin_file' ),
					'properties' => array(
						'plugin_file' => array(
							'type'        => 'string',
							'description' => 'Plugin file path (e.g., plugin-slug/plugin-file.php)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'plugin_file' => array( 'type' => 'string' ),
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
	 * @return array|WP_Error Result.
	 */
	public function execute_deactivate_plugin( $input ) {
		$plugin_file = $input['plugin_file'];
		
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Validate plugin exists
		$plugins = get_plugins();
		if ( ! isset( $plugins[ $plugin_file ] ) ) {
			// Try to find by slug if full path not provided
			$found = false;
			foreach ( array_keys( $plugins ) as $file ) {
				if ( strpos( $file, $plugin_file . '/' ) === 0 ) {
					$plugin_file = $file;
					$found = true;
					break;
				}
			}
			
			if ( ! $found ) {
				return new WP_Error( 'plugin_not_found', 'Plugin not found', array( 'status' => 404 ) );
			}
		}

		deactivate_plugins( $plugin_file );

		return array(
			'success' => true,
			'plugin_file' => $plugin_file,
		);
	}

	/**
	 * Register delete-plugin ability.
	 *
	 * @return void
	 */
	private function register_delete_plugin() {
		wp_register_ability(
			'wp-mcp-core/delete-plugin',
			array(
				'category'            => 'site',
				'label'               => __( 'Delete Plugin', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete an installed plugin.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'plugin_file' ),
					'properties' => array(
						'plugin_file' => array(
							'type'        => 'string',
							'description' => 'Plugin file path (e.g., plugin-slug/plugin-file.php)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'plugin_file' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_plugin' ),
				'permission_callback' => function() {
					return current_user_can( 'delete_plugins' );
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
	 * Execute delete-plugin.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_plugin( $input ) {
		$plugin_file = $input['plugin_file'];
		
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Validate plugin exists
		$plugins = get_plugins();
		if ( ! isset( $plugins[ $plugin_file ] ) ) {
			// Try to find by slug if full path not provided
			$found = false;
			foreach ( array_keys( $plugins ) as $file ) {
				if ( strpos( $file, $plugin_file . '/' ) === 0 ) {
					$plugin_file = $file;
					$found = true;
					break;
				}
			}
			
			if ( ! $found ) {
				return new WP_Error( 'plugin_not_found', 'Plugin not found', array( 'status' => 404 ) );
			}
		}

		if ( is_plugin_active( $plugin_file ) ) {
			return new WP_Error( 'plugin_active', 'Cannot delete active plugin', array( 'status' => 400 ) );
		}

		$result = delete_plugins( array( $plugin_file ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', 'Plugin deletion failed' );
		}

		return array(
			'success' => true,
			'plugin_file' => $plugin_file,
		);
	}
}

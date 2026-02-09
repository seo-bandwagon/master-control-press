<?php
/**
 * Block Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block and Pattern abilities.
 */
class WP_MCP_Block_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all block abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_list_block_patterns();
		$instance->register_get_block_pattern();
		$instance->register_list_block_types();
	}

	/**
	 * Register list-block-patterns ability.
	 *
	 * @return void
	 */
	private function register_list_block_patterns() {
		wp_register_ability(
			'wp-mcp-core/list-block-patterns',
			array(
				'category'            => 'content',
				'label'               => __( 'List Block Patterns', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Retrieve a list of registered block patterns.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'category' => array(
							'type'        => 'string',
							'description' => 'Filter patterns by category name',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'patterns' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'          => array( 'type' => 'string' ),
									'title'         => array( 'type' => 'string' ),
									'description'   => array( 'type' => 'string' ),
									'categories'    => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
									'keywords'      => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
									'viewportWidth' => array( 'type' => 'integer' ),
									'blockTypes'    => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
								),
							),
						),
						'total'    => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_block_patterns' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
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
	 * Execute list-block-patterns.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_list_block_patterns( $input ) {
		$result = array(
			'success' => false,
			'patterns' => [],
			'total' => 0,
			'error' => ''
		);

		try {
			// Ensure input is an array.
			if ( ! is_array( $input ) ) {
				$input = array();
			}

			// Apply defaults using null coalescing operator.
			$category = $input['category'] ?? '';

			$registry = WP_Block_Patterns_Registry::get_instance();
			$patterns = $registry->get_all_registered();

			if ( ! empty( $category ) ) {
				$patterns = array_filter(
					$patterns,
					function( $pattern ) use ( $category ) {
						return isset( $pattern['categories'] ) && in_array( $category, $pattern['categories'], true );
					}
				);
			}

			$formatted_patterns = array();
			foreach ( $patterns as $pattern ) {
				$formatted_patterns[] = array(
					'name'          => $pattern['name'],
					'title'         => $pattern['title'],
					'description'   => $pattern['description'] ?? '',
					'categories'    => $pattern['categories'] ?? array(),
					'keywords'      => $pattern['keywords'] ?? array(),
					'viewportWidth' => $pattern['viewportWidth'] ?? null,
					'blockTypes'    => $pattern['blockTypes'] ?? array(),
				);
			}

			$result['patterns'] = $formatted_patterns;
			$result['total'] = count($formatted_patterns);
			$result['success'] = true;
			if (empty($formatted_patterns)) {
				$result['error'] = 'No block patterns found for the given category.';
			}
			return $result;
		} catch (\Throwable $e) {
			$result['error'] = 'Failed to list block patterns: ' . $e->getMessage();
			return $result;
		}
	}

	/**
	 * Register get-block-pattern ability.
	 *
	 * @return void
	 */
	private function register_get_block_pattern() {
		wp_register_ability(
			'wp-mcp-core/get-block-pattern',
			array(
				'category'            => 'content',
				'label'               => __( 'Get Block Pattern', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Retrieve details of a specific block pattern, including its content.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'name' ),
					'properties' => array(
						'name' => array(
							'type'        => 'string',
							'description' => 'Pattern name (e.g., core/query-standard-posts)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'          => array( 'type' => 'string' ),
						'title'         => array( 'type' => 'string' ),
						'content'       => array( 'type' => 'string' ),
						'description'   => array( 'type' => 'string' ),
						'categories'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'keywords'      => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'viewportWidth' => array( 'type' => 'integer' ),
						'blockTypes'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_block_pattern' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
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
	 * Execute get-block-pattern.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_get_block_pattern( $input ) {
		$name = $input['name'];
		$registry = WP_Block_Patterns_Registry::get_instance();
		
		if ( ! $registry->is_registered( $name ) ) {
			return new WP_Error( 'pattern_not_found', 'Block pattern not found', array( 'status' => 404 ) );
		}

		$pattern = $registry->get_registered( $name );

		return array(
			'name'          => $pattern['name'],
			'title'         => $pattern['title'],
			'content'       => $pattern['content'],
			'description'   => $pattern['description'] ?? '',
			'categories'    => $pattern['categories'] ?? array(),
			'keywords'      => $pattern['keywords'] ?? array(),
			'viewportWidth' => $pattern['viewportWidth'] ?? null,
			'blockTypes'    => $pattern['blockTypes'] ?? array(),
		);
	}

	/**
	 * Register list-block-types ability.
	 *
	 * @return void
	 */
	private function register_list_block_types() {
		wp_register_ability(
			'wp-mcp-core/list-block-types',
			array(
				'category'            => 'content',
				'label'               => __( 'List Block Types', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Retrieve a list of registered block types.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'namespace' => array(
							'type'        => 'string',
							'description' => 'Filter blocks by namespace (e.g., core)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'block_types' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'        => array( 'type' => 'string' ),
									'title'       => array( 'type' => 'string' ),
									'category'    => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'keywords'    => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
									'attributes'  => array( 'type' => 'object' ),
									'supports'    => array( 'type' => 'object' ),
								),
							),
						),
						'total'       => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_block_types' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
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
	 * Execute list-block-types.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_list_block_types( $input ) {
		$result = array(
			'success' => false,
			'block_types' => [],
			'total' => 0,
			'error' => ''
		);

		try {
			// Ensure input is an array.
			if ( ! is_array( $input ) ) {
				$input = array();
			}

			// Apply defaults using null coalescing operator.
			$namespace = $input['namespace'] ?? '';

			$registry = WP_Block_Type_Registry::get_instance();
			$block_types = $registry->get_all_registered();

			if ( ! empty( $namespace ) ) {
				$block_types = array_filter(
					$block_types,
					function( $block_type ) use ( $namespace ) {
						return strpos( $block_type->name, $namespace . '/' ) === 0;
					}
				);
			}

			$formatted_blocks = array();
			foreach ( $block_types as $block_type ) {
				$formatted_blocks[] = array(
					'name'        => $block_type->name,
					'title'       => $block_type->title,
					'category'    => $block_type->category,
					'description' => $block_type->description ?? '',
					'keywords'    => $block_type->keywords ?? array(),
					'attributes'  => $block_type->attributes ?? array(),
					'supports'    => $block_type->supports ?? array(),
				);
			}

			$result['block_types'] = $formatted_blocks;
			$result['total'] = count($formatted_blocks);
			$result['success'] = true;
			if (empty($formatted_blocks)) {
				$result['error'] = 'No block types found for the given namespace.';
			}
			return $result;
		} catch (\Throwable $e) {
			$result['error'] = 'Failed to list block types: ' . $e->getMessage();
			return $result;
		}
	}
}

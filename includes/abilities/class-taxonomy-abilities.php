<?php
/**
 * Taxonomy Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy (categories and tags) abilities.
 */
class WP_MCP_Taxonomy_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all taxonomy abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();

		// Category abilities.
		$instance->register_list_categories();
		$instance->register_get_category();
		$instance->register_create_category();
		$instance->register_update_category();
		$instance->register_delete_category();

		// Tag abilities.
		$instance->register_list_tags();
		$instance->register_create_tag();
		$instance->register_update_tag();
		$instance->register_delete_tag();

		// Post taxonomy relationships.
		$instance->register_get_post_categories();
		$instance->register_set_post_categories();
		$instance->register_get_post_tags();
		$instance->register_set_post_tags();
	}

	/**
	 * Register list-categories ability.
	 *
	 * @return void
	 */
	private function register_list_categories() {
		wp_register_ability(
			'wp-mcp-core/list-categories',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'List Categories', 'wp-mcp-core-abilities' ),
				'description'         => __( 'List all post categories with hierarchy.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'hide_empty' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'categories' => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_categories' ),
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
	 * Execute list-categories.
	 *
	 * @param array $input Input parameters.
	 * @return array Categories list.
	 */
	public function execute_list_categories( $input ) {
		$categories = get_categories(
			array(
				'hide_empty' => $input['hide_empty'] ?? false,
			)
		);

		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		   $formatted = array();
		   foreach ( $categories as $cat ) {
			   $formatted[] = array(
				   'id'   => $cat->term_id,
				   'name' => $cat->name,
				   'slug' => $cat->slug,
			   );
		   }
		   return array( 'categories' => $formatted );
	}

	/**
	 * Register get-category ability.
	 *
	 * @return void
	 */
	private function register_get_category() {
		wp_register_ability(
			'wp-mcp-core/get-category',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Get Category', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get a single category by ID.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'category_id' ),
					'properties' => array(
						'category_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'count'       => array( 'type' => 'integer' ),
						'parent'      => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_category' ),
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
	 * Execute get-category.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Category data.
	 */
	public function execute_get_category( $input ) {
		$category = get_category( absint( $input['category_id'] ) );

		if ( ! $category || is_wp_error( $category ) ) {
			return new WP_Error( 'category_not_found', 'Category not found', array( 'status' => 404 ) );
		}

		return array(
			'id'          => $category->term_id,
			'name'        => $category->name,
			'slug'        => $category->slug,
			'description' => $category->description,
			'count'       => $category->count,
			'parent'      => $category->parent,
		);
	}

	/**
	 * Register create-category ability.
	 *
	 * @return void
	 */
	private function register_create_category() {
		wp_register_ability(
			'wp-mcp-core/create-category',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Create Category', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Create a new category.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'name' ),
					'properties' => array(
						'name'        => array(
							'type' => 'string',
						),
						'slug'        => array(
							'type' => 'string',
						),
						'description' => array(
							'type' => 'string',
						),
						'parent'      => array(
							'type'    => 'integer',
							'default' => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'category_id' => array( 'type' => 'integer' ),
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_category' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_categories' );
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
	 * Execute create-category.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Category data.
	 */
	public function execute_create_category( $input ) {
		$args = array(
			'description' => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
			'parent'      => isset( $input['parent'] ) ? absint( $input['parent'] ) : 0,
		);

		if ( isset( $input['slug'] ) ) {
			$args['slug'] = sanitize_title( $input['slug'] );
		}

		$result = wp_insert_term( sanitize_text_field( $input['name'] ), 'category', $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$category = get_category( $result['term_id'] );

		return array(
			'category_id' => $category->term_id,
			'name'        => $category->name,
			'slug'        => $category->slug,
		);
	}

	/**
	 * Register update-category ability.
	 *
	 * @return void
	 */
	private function register_update_category() {
		wp_register_ability(
			'wp-mcp-core/update-category',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Update Category', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Update an existing category.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'category_id' ),
					'properties' => array(
						'category_id' => array(
							'type' => 'integer',
						),
						'name'        => array(
							'type' => 'string',
						),
						'slug'        => array(
							'type' => 'string',
						),
						'description' => array(
							'type' => 'string',
						),
						'parent'      => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'category_id' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_category' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_categories' );
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
	 * Execute update-category.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_update_category( $input ) {
		$category_id = absint( $input['category_id'] );
		$args        = array();

		if ( isset( $input['name'] ) ) {
			$args['name'] = sanitize_text_field( $input['name'] );
		}

		if ( isset( $input['slug'] ) ) {
			$args['slug'] = sanitize_title( $input['slug'] );
		}

		if ( isset( $input['description'] ) ) {
			$args['description'] = sanitize_textarea_field( $input['description'] );
		}

		if ( isset( $input['parent'] ) ) {
			$args['parent'] = absint( $input['parent'] );
		}

		$result = wp_update_term( $category_id, 'category', $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'category_id' => $result['term_id'] );
	}

	/**
	 * Register delete-category ability.
	 *
	 * @return void
	 */
	private function register_delete_category() {
		wp_register_ability(
			'wp-mcp-core/delete-category',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Delete Category', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete a category.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'category_id' ),
					'properties' => array(
						'category_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_category' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_categories' );
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
	 * Execute delete-category.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_category( $input ) {
		$result = wp_delete_term( absint( $input['category_id'] ), 'category' );

		if ( is_wp_error( $result ) || ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete category', array( 'status' => 500 ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Register list-tags ability.
	 *
	 * @return void
	 */
	private function register_list_tags() {
		wp_register_ability(
			'wp-mcp-core/list-tags',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'List Tags', 'wp-mcp-core-abilities' ),
				'description'         => __( 'List all post tags.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'hide_empty' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'tags' => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_tags' ),
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
	 * Execute list-tags.
	 *
	 * @param array $input Input parameters.
	 * @return array Tags list.
	 */
	public function execute_list_tags( $input ) {
		$tags = get_tags(
			array(
				'hide_empty' => $input['hide_empty'] ?? false,
			)
		);

		if ( is_wp_error( $tags ) ) {
			return $tags;
		}

		   $formatted = array();
		   foreach ( $tags as $tag ) {
			   $formatted[] = array(
				   'id'   => $tag->term_id,
				   'name' => $tag->name,
				   'slug' => $tag->slug,
			   );
		   }
		   return array( 'tags' => $formatted );
	}

	/**
	 * Register create-tag ability.
	 *
	 * @return void
	 */
	private function register_create_tag() {
		wp_register_ability(
			'wp-mcp-core/create-tag',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Create Tag', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Create a new tag.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'name' ),
					'properties' => array(
						'name'        => array(
							'type' => 'string',
						),
						'slug'        => array(
							'type' => 'string',
						),
						'description' => array(
							'type' => 'string',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'tag_id' => array( 'type' => 'integer' ),
						'name'   => array( 'type' => 'string' ),
						'slug'   => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_tag' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_categories' );
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
	 * Execute create-tag.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Tag data.
	 */
	public function execute_create_tag( $input ) {
		$args = array(
			'description' => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
		);

		if ( isset( $input['slug'] ) ) {
			$args['slug'] = sanitize_title( $input['slug'] );
		}

		$result = wp_insert_term( sanitize_text_field( $input['name'] ), 'post_tag', $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$tag = get_tag( $result['term_id'] );

		return array(
			'tag_id' => $tag->term_id,
			'name'   => $tag->name,
			'slug'   => $tag->slug,
		);
	}

	/**
	 * Register update-tag ability.
	 *
	 * @return void
	 */
	private function register_update_tag() {
		wp_register_ability(
			'wp-mcp-core/update-tag',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Update Tag', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Update an existing tag.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'tag_id' ),
					'properties' => array(
						'tag_id'      => array(
							'type' => 'integer',
						),
						'name'        => array(
							'type' => 'string',
						),
						'slug'        => array(
							'type' => 'string',
						),
						'description' => array(
							'type' => 'string',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'tag_id' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_tag' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_categories' );
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
	 * Execute update-tag.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_update_tag( $input ) {
		$tag_id = absint( $input['tag_id'] );
		$args   = array();

		if ( isset( $input['name'] ) ) {
			$args['name'] = sanitize_text_field( $input['name'] );
		}

		if ( isset( $input['slug'] ) ) {
			$args['slug'] = sanitize_title( $input['slug'] );
		}

		if ( isset( $input['description'] ) ) {
			$args['description'] = sanitize_textarea_field( $input['description'] );
		}

		$result = wp_update_term( $tag_id, 'post_tag', $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'tag_id' => $result['term_id'] );
	}

	/**
	 * Register delete-tag ability.
	 *
	 * @return void
	 */
	private function register_delete_tag() {
		wp_register_ability(
			'wp-mcp-core/delete-tag',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Delete Tag', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete a tag.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'tag_id' ),
					'properties' => array(
						'tag_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_tag' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_categories' );
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
	 * Execute delete-tag.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_tag( $input ) {
		$result = wp_delete_term( absint( $input['tag_id'] ), 'post_tag' );

		if ( is_wp_error( $result ) || ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete tag', array( 'status' => 500 ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Register get-post-categories ability.
	 *
	 * @return void
	 */
	private function register_get_post_categories() {
		wp_register_ability(
			'wp-mcp-core/get-post-categories',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Get Post Categories', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get categories assigned to a post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'categories' => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_post_categories' ),
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
	 * Execute get-post-categories.
	 *
	 * @param array $input Input parameters.
	 * @return array Categories.
	 */
	public function execute_get_post_categories( $input ) {
		$categories = get_the_category( absint( $input['post_id'] ) );
		$formatted  = array();

		foreach ( $categories as $cat ) {
			$formatted[] = array(
				'id'   => $cat->term_id,
				'name' => $cat->name,
				'slug' => $cat->slug,
			);
		}

		return array( 'categories' => $formatted );
	}

	/**
	 * Register set-post-categories ability.
	 *
	 * @return void
	 */
	private function register_set_post_categories() {
		wp_register_ability(
			'wp-mcp-core/set-post-categories',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Set Post Categories', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Assign categories to a post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'category_ids' ),
					'properties' => array(
						'post_id'      => array(
							'type' => 'integer',
						),
						'category_ids' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_set_post_categories' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
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
	 * Execute set-post-categories.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_set_post_categories( $input ) {
		$result = wp_set_post_categories( absint( $input['post_id'] ), array_map( 'absint', $input['category_ids'] ) );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'update_failed', $result->get_error_message(), array( 'status' => 500 ) );
		}

		return array( 'success' => (bool) $result );
	}

	/**
	 * Register get-post-tags ability.
	 *
	 * @return void
	 */
	private function register_get_post_tags() {
		wp_register_ability(
			'wp-mcp-core/get-post-tags',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Get Post Tags', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get tags assigned to a post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'tags' => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_post_tags' ),
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
	 * Execute get-post-tags.
	 *
	 * @param array $input Input parameters.
	 * @return array Tags.
	 */
	public function execute_get_post_tags( $input ) {
		$tags      = get_the_tags( absint( $input['post_id'] ) );
		$formatted = array();

		if ( $tags && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				$formatted[] = array(
					'id'   => $tag->term_id,
					'name' => $tag->name,
					'slug' => $tag->slug,
				);
			}
		}

		return array( 'tags' => $formatted );
	}

	/**
	 * Register set-post-tags ability.
	 *
	 * @return void
	 */
	private function register_set_post_tags() {
		wp_register_ability(
			'wp-mcp-core/set-post-tags',
			array(
				'category'            => 'taxonomies',
				'label'               => __( 'Set Post Tags', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Assign tags to a post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'tags' ),
					'properties' => array(
						'post_id' => array(
							'type' => 'integer',
						),
						'tags'    => array(
							'type'        => 'array',
							'description' => 'Array of tag names or IDs',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_set_post_tags' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
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
	 * Execute set-post-tags.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_set_post_tags( $input ) {
		$result = wp_set_post_tags( absint( $input['post_id'] ), $input['tags'] );

		return array( 'success' => ! is_wp_error( $result ) && false !== $result );
	}
}

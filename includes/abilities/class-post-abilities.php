<?php
/**
 * Post Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post content abilities.
 */
class WP_MCP_Post_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all post abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_list_posts();
		$instance->register_get_post();
		$instance->register_create_post();
		$instance->register_update_post();
		$instance->register_delete_post();
		$instance->register_get_post_meta();
		$instance->register_update_post_meta();
	}

	/**
	 * Register list-posts ability.
	 *
	 * @return void
	 */
	private function register_list_posts() {
		wp_register_ability(
			'wp-mcp-core/list-posts',
			array(
				'category'            => 'content',
				'label'               => __( 'List Posts', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Retrieve a list of WordPress posts with filtering and pagination.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'per_page'    => array(
							'type'    => 'integer',
							'default' => 10,
							'minimum' => 1,
							'maximum' => 100,
						),
						'page'        => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'post_status' => array(
							'type'    => 'string',
							'default' => 'publish',
							'enum'    => array( 'publish', 'draft', 'pending', 'private', 'any' ),
						),
						'order'       => array(
							'type'    => 'string',
							'default' => 'DESC',
							'enum'    => array( 'ASC', 'DESC' ),
						),
						'orderby'     => array(
							'type'    => 'string',
							'default' => 'date',
							'enum'    => array( 'date', 'title', 'modified', 'ID' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'posts' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'      => array( 'type' => 'integer' ),
									'title'   => array( 'type' => 'string' ),
									'excerpt' => array( 'type' => 'string' ),
									'status'  => array( 'type' => 'string' ),
									'url'     => array( 'type' => 'string' ),
									'date'    => array( 'type' => 'string' ),
								),
							),
						),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_posts' ),
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
	 * Execute list-posts.
	 *
	 * @param array $input Input parameters.
	 * @return array Posts list.
	 */
	public function execute_list_posts( $input ) {
		try {
			$args = array(
				'post_type'      => 'post',
				'posts_per_page' => $input['per_page'] ?? 10,
				'paged'          => $input['page'] ?? 1,
				'post_status'    => $input['post_status'] ?? 'publish',
				'order'          => $input['order'] ?? 'DESC',
				'orderby'        => $input['orderby'] ?? 'date',
			);
			$query = new WP_Query( $args );
			$posts = array();
			foreach ( $query->posts as $post ) {
				$posts[] = array(
					'id'      => $post->ID,
					'title'   => get_the_title( $post->ID ),
					'excerpt' => get_the_excerpt( $post->ID ),
					'status'  => $post->post_status,
					'url'     => get_permalink( $post->ID ),
					'date'    => $post->post_date,
				);
			}
			if (empty($posts)) {
				return array(
					'success' => false,
					'error'   => 'No posts found for the given parameters.',
					'posts'   => [],
					'total'   => 0,
				);
			}
			return array(
				'posts' => $posts,
				'total' => $query->found_posts,
			);
		} catch (\Throwable $e) {
			return array(
				'success' => false,
				'error'   => 'Failed to list posts: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Register get-post ability.
	 *
	 * @return void
	 */
	private function register_get_post() {
		wp_register_ability(
			'wp-mcp-core/get-post',
			array(
				'category'            => 'content',
				'label'               => __( 'Get Post', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get a single post by ID with all content and metadata.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => 'Post ID',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
						'excerpt' => array( 'type' => 'string' ),
						'status'  => array( 'type' => 'string' ),
						'author'  => array( 'type' => 'integer' ),
						'date'    => array( 'type' => 'string' ),
						'url'     => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_post' ),
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
	 * Execute get-post.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Post data.
	 */
	public function execute_get_post( $input ) {
		$post_id = absint( $input['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post || 'post' !== $post->post_type ) {
			return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
		}

		return array(
			'id'      => $post->ID,
			'title'   => $post->post_title,
			'content' => $post->post_content,
			'excerpt' => $post->post_excerpt,
			'status'  => $post->post_status,
			'author'  => $post->post_author,
			'date'    => $post->post_date,
			'url'     => get_permalink( $post->ID ),
		);
	}

	/**
	 * Register create-post ability.
	 *
	 * @return void
	 */
	private function register_create_post() {
		wp_register_ability(
			'wp-mcp-core/create-post',
			array(
				'category'            => 'content',
				'label'               => __( 'Create Post', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Create a new WordPress post with content, title, and metadata.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_title', 'post_content' ),
					'properties' => array(
						'post_title'   => array(
							'type'        => 'string',
							'description' => 'Post title',
							'minLength'   => 1,
							'maxLength'   => 200,
						),
						'post_content' => array(
							'type'        => 'string',
							'description' => 'Post content (HTML allowed)',
						),
						'post_status'  => array(
							'type'        => 'string',
							'description' => 'Post status',
							'enum'        => array( 'draft', 'publish', 'pending' ),
							'default'     => 'draft',
						),
						'post_excerpt' => array(
							'type'        => 'string',
							'description' => 'Post excerpt',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'post_title'  => array( 'type' => 'string' ),
						'post_url'    => array( 'type' => 'string' ),
						'edit_url'    => array( 'type' => 'string' ),
						'post_status' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_post' ),
				'permission_callback' => function() {
					return current_user_can( 'publish_posts' );
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
	 * Execute create-post.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Post data.
	 */
	public function execute_create_post( $input ) {
		$post_data = array(
			'post_title'   => sanitize_text_field( $input['post_title'] ),
			'post_content' => wp_kses_post( $input['post_content'] ),
			'post_status'  => $input['post_status'] ?? 'draft',
			'post_type'    => 'post',
		);

		if ( isset( $input['post_excerpt'] ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $input['post_excerpt'] );
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		return array(
			'post_id'     => $post_id,
			'post_title'  => get_the_title( $post_id ),
			'post_url'    => get_permalink( $post_id ),
			'edit_url'    => get_edit_post_link( $post_id, 'raw' ),
			'post_status' => get_post_status( $post_id ),
		);
	}

	/**
	 * Register update-post ability.
	 *
	 * @return void
	 */
	private function register_update_post() {
		wp_register_ability(
			'wp-mcp-core/update-post',
			array(
				'category'            => 'content',
				'label'               => __( 'Update Post', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Update an existing WordPress post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id'      => array(
							'type'        => 'integer',
							'description' => 'Post ID to update',
						),
						'post_title'   => array(
							'type'        => 'string',
							'description' => 'Post title',
						),
						'post_name'    => array(
							'type'        => 'string',
							'description' => 'Post slug (permalink)',
						),
						'post_content' => array(
							'type'        => 'string',
							'description' => 'Post content (HTML allowed)',
						),
						'post_status'  => array(
							'type'        => 'string',
							'description' => 'Post status',
							'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
						),
						'post_excerpt' => array(
							'type'        => 'string',
							'description' => 'Post excerpt',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'post_title'  => array( 'type' => 'string' ),
						'post_url'    => array( 'type' => 'string' ),
						'post_status' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_post' ),
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
	 * Execute update-post.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Post data.
	 */
	public function execute_update_post( $input ) {
		$post_id = absint( $input['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post || 'post' !== $post->post_type ) {
			return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
		}

		$post_data = array( 'ID' => $post_id );

		if ( isset( $input['post_title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $input['post_title'] );
		}

		if ( isset( $input['post_name'] ) ) {
			$post_data['post_name'] = sanitize_title( $input['post_name'] );
		}

		if ( isset( $input['post_content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $input['post_content'] );
		}

		if ( isset( $input['post_status'] ) ) {
			$post_data['post_status'] = $input['post_status'];
		}

		if ( isset( $input['post_excerpt'] ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $input['post_excerpt'] );
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'post_id'     => $post_id,
			'post_title'  => get_the_title( $post_id ),
			'post_url'    => get_permalink( $post_id ),
			'post_status' => get_post_status( $post_id ),
		);
	}

	/**
	 * Register delete-post ability.
	 *
	 * @return void
	 */
	private function register_delete_post() {
		wp_register_ability(
			'wp-mcp-core/delete-post',
			array(
				'category'            => 'content',
				'label'               => __( 'Delete Post', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete a WordPress post (move to trash or permanently delete).', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id'      => array(
							'type'        => 'integer',
							'description' => 'Post ID to delete',
						),
						'force_delete' => array(
							'type'        => 'boolean',
							'description' => 'Permanently delete (true) or move to trash (false)',
							'default'     => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'post_id' => array( 'type' => 'integer' ),
						'message' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_post' ),
				'permission_callback' => function() {
					return current_user_can( 'delete_posts' );
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
	 * Execute delete-post.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_post( $input ) {
		$post_id      = absint( $input['post_id'] );
		$force_delete = $input['force_delete'] ?? false;
		$post         = get_post( $post_id );

		if ( ! $post || 'post' !== $post->post_type ) {
			return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
		}

		$result = wp_delete_post( $post_id, $force_delete );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete post', array( 'status' => 500 ) );
		}

		return array(
			'success' => true,
			'post_id' => $post_id,
			'message' => $force_delete ? 'Post permanently deleted' : 'Post moved to trash',
		);
	}

	/**
	 * Register get-post-meta ability.
	 *
	 * @return void
	 */
	private function register_get_post_meta() {
		wp_register_ability(
			'wp-mcp-core/get-post-meta',
			array(
				'category'            => 'content',
				'label'               => __( 'Get Post Meta', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get custom fields/metadata for a post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id'  => array(
							'type'        => 'integer',
							'description' => 'Post ID',
						),
						'meta_key' => array(
							'type'        => 'string',
							'description' => 'Specific meta key (optional)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'meta'    => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_post_meta' ),
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
	 * Execute get-post-meta.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Meta data.
	 */
	public function execute_get_post_meta( $input ) {
		$post_id = absint( $input['post_id'] );

		if ( ! get_post( $post_id ) ) {
			return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
		}

		if ( isset( $input['meta_key'] ) ) {
			$meta_value = get_post_meta( $post_id, sanitize_key( $input['meta_key'] ), true );
			return array(
				'post_id' => $post_id,
				'meta'    => array( $input['meta_key'] => $meta_value ),
			);
		}

		$all_meta = get_post_meta( $post_id );

		return array(
			'post_id' => $post_id,
			'meta'    => $all_meta,
		);
	}

	/**
	 * Register update-post-meta ability.
	 *
	 * @return void
	 */
	private function register_update_post_meta() {
		wp_register_ability(
			'wp-mcp-core/update-post-meta',
			array(
				'category'            => 'content',
				'label'               => __( 'Update Post Meta', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Set or update custom fields/metadata for a post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'meta_key', 'meta_value' ),
					'properties' => array(
						'post_id'    => array(
							'type'        => 'integer',
							'description' => 'Post ID',
						),
						'meta_key'   => array(
							'type'        => 'string',
							'description' => 'Meta key',
						),
						'meta_value' => array(
							'description' => 'Meta value (can be any type)',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'post_id'    => array( 'type' => 'integer' ),
						'meta_key'   => array( 'type' => 'string' ),
						'meta_value' => array(),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_post_meta' ),
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
	 * Execute update-post-meta.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_update_post_meta( $input ) {
		$post_id    = absint( $input['post_id'] );
		$meta_key   = sanitize_key( $input['meta_key'] );
		$meta_value = $input['meta_value'];

		if ( ! get_post( $post_id ) ) {
			return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
		}

		$success = update_post_meta( $post_id, $meta_key, $meta_value );

		return array(
			'success'    => (bool) $success,
			'post_id'    => $post_id,
			'meta_key'   => $meta_key,
			'meta_value' => $meta_value,
		);
	}
}

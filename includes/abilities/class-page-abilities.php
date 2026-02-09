<?php
/**
 * Page Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page content abilities.
 */
class WP_MCP_Page_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all page abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_list_pages();
		$instance->register_get_page();
		$instance->register_create_page();
		$instance->register_update_page();
		$instance->register_delete_page();
		$instance->register_get_page_hierarchy();
	}

	/**
	 * Register list-pages ability.
	 *
	 * @return void
	 */
	private function register_list_pages() {
		wp_register_ability(
			'wp-mcp-core/list-pages',
			array(
				'category'            => 'content',
				'label'               => __( 'List Pages', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Retrieve a list of WordPress pages with hierarchy support.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'per_page'    => array(
							'type'    => 'integer',
							'default' => 10,
							'minimum' => 1,
							'maximum' => 100,
						),
						'post_status' => array(
							'type'    => 'string',
							'default' => 'publish',
							'enum'    => array( 'publish', 'draft', 'pending', 'private', 'any' ),
						),
						'orderby'     => array(
							'type'    => 'string',
							'default' => 'menu_order',
							'enum'    => array( 'menu_order', 'title', 'date', 'modified' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'pages' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'        => array( 'type' => 'integer' ),
									'title'     => array( 'type' => 'string' ),
									'parent_id' => array( 'type' => 'integer' ),
									'status'    => array( 'type' => 'string' ),
									'url'       => array( 'type' => 'string' ),
								),
							),
						),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_pages' ),
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
	 * Execute list-pages.
	 *
	 * @param array $input Input parameters.
	 * @return array Pages list.
	 */
	public function execute_list_pages( $input ) {
		// Apply defaults using null coalescing operator.
		$per_page = $input['per_page'] ?? 10;
		$post_status = $input['post_status'] ?? 'publish';
		$orderby = $input['orderby'] ?? 'menu_order';

		// Add validation.
		$per_page = max( 1, min( 100, $per_page ) );

		$args = array(
			'post_type'      => 'page',
			'posts_per_page' => $per_page,
			'post_status'    => $post_status,
			'orderby'        => $orderby,
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );
		$pages = array();

		foreach ( $query->posts as $page ) {
			$pages[] = array(
				'id'        => $page->ID,
				'title'     => get_the_title( $page->ID ),
				'parent_id' => $page->post_parent,
				'status'    => $page->post_status,
				'url'       => get_permalink( $page->ID ),
			);
		}

		return array(
			'pages' => $pages,
			'total' => $query->found_posts,
		);
	}

	/**
	 * Register get-page ability.
	 *
	 * @return void
	 */
	private function register_get_page() {
		wp_register_ability(
			'wp-mcp-core/get-page',
			array(
				'category'            => 'content',
				'label'               => __( 'Get Page', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get a single page by ID with all content and metadata.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'page_id' ),
					'properties' => array(
						'page_id' => array(
							'type'        => 'integer',
							'description' => 'Page ID',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'        => array( 'type' => 'integer' ),
						'title'     => array( 'type' => 'string' ),
						'content'   => array( 'type' => 'string' ),
						'excerpt'   => array( 'type' => 'string' ),
						'status'    => array( 'type' => 'string' ),
						'parent_id' => array( 'type' => 'integer' ),
						'template'  => array( 'type' => 'string' ),
						'url'       => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_page' ),
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
	 * Execute get-page.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Page data.
	 */
	public function execute_get_page( $input ) {
		$page_id = absint( $input['page_id'] );
		$page    = get_post( $page_id );

		if ( ! $page || 'page' !== $page->post_type ) {
			return new WP_Error( 'page_not_found', 'Page not found', array( 'status' => 404 ) );
		}

		return array(
			'id'        => $page->ID,
			'title'     => $page->post_title,
			'content'   => $page->post_content,
			'excerpt'   => $page->post_excerpt,
			'status'    => $page->post_status,
			'parent_id' => $page->post_parent,
			'template'  => get_page_template_slug( $page->ID ),
			'url'       => get_permalink( $page->ID ),
		);
	}

	/**
	 * Register create-page ability.
	 *
	 * @return void
	 */
	private function register_create_page() {
		wp_register_ability(
			'wp-mcp-core/create-page',
			array(
				'category'            => 'content',
				'label'               => __( 'Create Page', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Create a new WordPress page.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_title', 'post_content' ),
					'properties' => array(
						'post_title'   => array(
							'type'        => 'string',
							'description' => 'Page title',
							'minLength'   => 1,
							'maxLength'   => 200,
						),
						'post_content' => array(
							'type'        => 'string',
							'description' => 'Page content (HTML allowed)',
						),
						'post_status'  => array(
							'type'        => 'string',
							'description' => 'Page status',
							'enum'        => array( 'draft', 'publish', 'pending' ),
							'default'     => 'draft',
						),
						'parent_id'    => array(
							'type'        => 'integer',
							'description' => 'Parent page ID',
							'default'     => 0,
						),
						'template'     => array(
							'type'        => 'string',
							'description' => 'Page template filename',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'page_id'     => array( 'type' => 'integer' ),
						'page_title'  => array( 'type' => 'string' ),
						'page_url'    => array( 'type' => 'string' ),
						'edit_url'    => array( 'type' => 'string' ),
						'page_status' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_page' ),
				'permission_callback' => function() {
					return current_user_can( 'publish_pages' );
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
	 * Execute create-page.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Page data.
	 */
	public function execute_create_page( $input ) {
		$page_data = array(
			'post_title'   => sanitize_text_field( $input['post_title'] ),
			'post_content' => wp_kses_post( $input['post_content'] ),
			'post_status'  => $input['post_status'] ?? 'draft',
			'post_type'    => 'page',
			'post_parent'  => isset( $input['parent_id'] ) ? absint( $input['parent_id'] ) : 0,
		);

		$page_id = wp_insert_post( $page_data, true );

		if ( is_wp_error( $page_id ) ) {
			return $page_id;
		}

		// Set page template if provided.
		if ( isset( $input['template'] ) ) {
			update_post_meta( $page_id, '_wp_page_template', sanitize_text_field( $input['template'] ) );
		}

		return array(
			'page_id'     => $page_id,
			'page_title'  => get_the_title( $page_id ),
			'page_url'    => get_permalink( $page_id ),
			'edit_url'    => get_edit_post_link( $page_id, 'raw' ),
			'page_status' => get_post_status( $page_id ),
		);
	}

	/**
	 * Register update-page ability.
	 *
	 * @return void
	 */
	private function register_update_page() {
		wp_register_ability(
			'wp-mcp-core/update-page',
			array(
				'category'            => 'content',
				'label'               => __( 'Update Page', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Update an existing WordPress page.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'page_id' ),
					'properties' => array(
						'page_id'      => array(
							'type'        => 'integer',
							'description' => 'Page ID to update',
						),
						'post_title'   => array(
							'type'        => 'string',
							'description' => 'Page title',
						),
						'post_name'    => array(
							'type'        => 'string',
							'description' => 'Page slug (permalink)',
						),
						'post_content' => array(
							'type'        => 'string',
							'description' => 'Page content (HTML allowed)',
						),
						'post_status'  => array(
							'type'        => 'string',
							'description' => 'Page status',
							'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
						),
						'parent_id'    => array(
							'type'        => 'integer',
							'description' => 'Parent page ID',
						),
						'template'     => array(
							'type'        => 'string',
							'description' => 'Page template filename',
						),
						'menu_order'   => array(
							'type'        => 'integer',
							'description' => 'Order in menu',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'page_id'     => array( 'type' => 'integer' ),
						'page_title'  => array( 'type' => 'string' ),
						'page_url'    => array( 'type' => 'string' ),
						'page_status' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_page' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_pages' );
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
	 * Execute update-page.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Page data.
	 */
	public function execute_update_page( $input ) {
		$page_id = absint( $input['page_id'] );
		$page    = get_post( $page_id );

		if ( ! $page || 'page' !== $page->post_type ) {
			return new WP_Error( 'page_not_found', 'Page not found', array( 'status' => 404 ) );
		}

		$page_data = array( 'ID' => $page_id );

		if ( isset( $input['post_title'] ) ) {
			$page_data['post_title'] = sanitize_text_field( $input['post_title'] );
		}

		if ( isset( $input['post_name'] ) ) {
			$page_data['post_name'] = sanitize_title( $input['post_name'] );
		}

		if ( isset( $input['post_content'] ) ) {
			$page_data['post_content'] = wp_kses_post( $input['post_content'] );
		}

		if ( isset( $input['post_status'] ) ) {
			$page_data['post_status'] = $input['post_status'];
		}

		if ( isset( $input['parent_id'] ) ) {
			$page_data['post_parent'] = absint( $input['parent_id'] );
		}

		if ( isset( $input['menu_order'] ) ) {
			$page_data['menu_order'] = absint( $input['menu_order'] );
		}

		$result = wp_update_post( $page_data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( isset( $input['template'] ) ) {
			update_post_meta( $page_id, '_wp_page_template', sanitize_text_field( $input['template'] ) );
		}

		return array(
			'page_id'     => $page_id,
			'page_title'  => get_the_title( $page_id ),
			'page_url'    => get_permalink( $page_id ),
			'page_status' => get_post_status( $page_id ),
		);
	}

	/**
	 * Register delete-page ability.
	 *
	 * @return void
	 */
	private function register_delete_page() {
		wp_register_ability(
			'wp-mcp-core/delete-page',
			array(
				'category'            => 'content',
				'label'               => __( 'Delete Page', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete a WordPress page (move to trash or permanently delete).', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'page_id' ),
					'properties' => array(
						'page_id'      => array(
							'type'        => 'integer',
							'description' => 'Page ID to delete',
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
						'page_id' => array( 'type' => 'integer' ),
						'message' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_page' ),
				'permission_callback' => function() {
					return current_user_can( 'delete_pages' );
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
	 * Execute delete-page.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_page( $input ) {
		$page_id      = absint( $input['page_id'] );
		$force_delete = $input['force_delete'] ?? false;
		$page         = get_post( $page_id );

		if ( ! $page || 'page' !== $page->post_type ) {
			return new WP_Error( 'page_not_found', 'Page not found', array( 'status' => 404 ) );
		}

		$result = wp_delete_post( $page_id, $force_delete );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete page', array( 'status' => 500 ) );
		}

		return array(
			'success' => true,
			'page_id' => $page_id,
			'message' => $force_delete ? 'Page permanently deleted' : 'Page moved to trash',
		);
	}

	/**
	 * Register get-page-hierarchy ability.
	 *
	 * @return void
	 */
	private function register_get_page_hierarchy() {
		wp_register_ability(
			'wp-mcp-core/get-page-hierarchy',
			array(
				'category'            => 'content',
				'label'               => __( 'Get Page Hierarchy', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get the hierarchical structure of all pages.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'pages' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array( 'type' => 'integer' ),
									'title'    => array( 'type' => 'string' ),
									'parent'   => array( 'type' => 'integer' ),
									'children' => array( 'type' => 'array' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_page_hierarchy' ),
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
	 * Execute get-page-hierarchy.
	 *
	 * @return array Page hierarchy.
	 */
	public function execute_get_page_hierarchy() {
		$pages = get_pages(
			array(
				'sort_column'  => 'menu_order',
				'hierarchical' => false,
			)
		);

		$hierarchy = array();

		foreach ( $pages as $page ) {
			$hierarchy[] = array(
				'id'       => $page->ID,
				'title'    => $page->post_title,
				'parent'   => $page->post_parent,
				'children' => $this->get_child_pages( $page->ID ),
			);
		}

		return array( 'pages' => $hierarchy );
	}

	/**
	 * Get child page IDs.
	 *
	 * @param int $parent_id Parent page ID.
	 * @return array Child page IDs.
	 */
	private function get_child_pages( $parent_id ) {
		$children = get_pages(
			array(
				'child_of' => $parent_id,
				'parent'   => $parent_id,
			)
		);

		$child_ids = array();
		foreach ( $children as $child ) {
			$child_ids[] = $child->ID;
		}

		return $child_ids;
	}
}

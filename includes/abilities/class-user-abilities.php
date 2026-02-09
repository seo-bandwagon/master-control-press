<?php
/**
 * User Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User management abilities.
 */
class WP_MCP_User_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all user abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_list_users();
		$instance->register_get_user();
		$instance->register_get_current_user();
		$instance->register_create_user();
		$instance->register_update_user();
		$instance->register_delete_user();
		$instance->register_get_user_meta();
		$instance->register_update_user_meta();
		$instance->register_list_user_posts();
		$instance->register_update_user_role();
	}

	/**
	 * Register list-users ability.
	 *
	 * @return void
	 */
	private function register_list_users() {
		wp_register_ability(
			'wp-mcp-core/list-users',
			array(
				'category'            => 'users',
				'label'               => __( 'List Users', 'wp-mcp-core-abilities' ),
				'description'         => __( 'List all users with roles and metadata.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'role'     => array(
							'type'        => 'string',
							'description' => 'Filter by role',
						),
						'per_page' => array(
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
						'users' => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_users' ),
				'permission_callback' => function() {
					return current_user_can( 'list_users' );
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
	 * Execute list-users.
	 *
	 * @param array $input Input parameters.
	 * @return array Users list.
	 */
	public function execute_list_users( $input ) {
		// Apply defaults using null coalescing operator.
		$role = $input['role'] ?? '';
		$per_page = $input['per_page'] ?? 10;

		// Add validation.
		$per_page = max( 1, min( 100, $per_page ) );

		$args = array(
			'number' => $per_page,
		);

		if ( ! empty( $role ) ) {
			$args['role'] = sanitize_text_field( $role );
		}

		$user_query = new WP_User_Query( $args );
		$users      = array();

		foreach ( $user_query->get_results() as $user ) {
			$users[] = array(
				'id'           => $user->ID,
				'username'     => $user->user_login,
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'roles'        => $user->roles,
			);
		}

		return array(
			'users' => $users,
			'total' => $user_query->get_total(),
		);
	}

	/**
	 * Register get-user ability.
	 *
	 * @return void
	 */
	private function register_get_user() {
		wp_register_ability(
			'wp-mcp-core/get-user',
			array(
				'category'            => 'users',
				'label'               => __( 'Get User', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get a single user by ID.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'user_id' ),
					'properties' => array(
						'user_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'username'     => array( 'type' => 'string' ),
						'email'        => array( 'type' => 'string' ),
						'display_name' => array( 'type' => 'string' ),
						'roles'        => array( 'type' => 'array' ),
						'registered'   => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_user' ),
				'permission_callback' => function() {
					return current_user_can( 'list_users' );
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
	 * Execute get-user.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error User data.
	 */
	public function execute_get_user( $input ) {
		$user = get_user_by( 'id', absint( $input['user_id'] ) );

		if ( ! $user ) {
			return new WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		return array(
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'roles'        => $user->roles,
			'registered'   => $user->user_registered,
		);
	}

	/**
	 * Register get-current-user ability.
	 *
	 * @return void
	 */
	private function register_get_current_user() {
		wp_register_ability(
			'wp-mcp-core/get-current-user',
			array(
				'category'            => 'users',
				'label'               => __( 'Get Current User', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get currently authenticated user info.', 'wp-mcp-core-abilities' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'username'     => array( 'type' => 'string' ),
						'email'        => array( 'type' => 'string' ),
						'display_name' => array( 'type' => 'string' ),
						'roles'        => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_current_user' ),
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
	 * Execute get-current-user.
	 *
	 * @return array Current user data.
	 */
	public function execute_get_current_user() {
		$user = wp_get_current_user();

		return array(
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'roles'        => $user->roles,
		);
	}

	/**
	 * Register create-user ability.
	 *
	 * @return void
	 */
	private function register_create_user() {
		wp_register_ability(
			'wp-mcp-core/create-user',
			array(
				'category'            => 'users',
				'label'               => __( 'Create User', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Create a new user account.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'username', 'email', 'password' ),
					'properties' => array(
						'username' => array(
							'type' => 'string',
						),
						'email'    => array(
							'type'   => 'string',
							'format' => 'email',
						),
						'password' => array(
							'type' => 'string',
						),
						'role'     => array(
							'type'    => 'string',
							'default' => 'subscriber',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'user_id' => array( 'type' => 'integer' ),
						'username' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_user' ),
				'permission_callback' => function() {
					return current_user_can( 'create_users' );
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
	 * Execute create-user.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error User data.
	 */
	public function execute_create_user( $input ) {
		$user_id = wp_create_user(
			sanitize_user( $input['username'] ),
			$input['password'],
			sanitize_email( $input['email'] )
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		if ( isset( $input['role'] ) ) {
			$user = new WP_User( $user_id );
			$user->set_role( sanitize_text_field( $input['role'] ) );
		}

		return array(
			'user_id'  => $user_id,
			'username' => $input['username'],
		);
	}

	/**
	 * Register update-user ability.
	 *
	 * @return void
	 */
	private function register_update_user() {
		wp_register_ability(
			'wp-mcp-core/update-user',
			array(
				'category'            => 'users',
				'label'               => __( 'Update User', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Update user profile.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'user_id' ),
					'properties' => array(
						'user_id'      => array(
							'type' => 'integer',
						),
						'email'        => array(
							'type'   => 'string',
							'format' => 'email',
						),
						'display_name' => array(
							'type' => 'string',
						),
						'first_name'   => array(
							'type' => 'string',
						),
						'last_name'    => array(
							'type' => 'string',
						),
						'password'     => array(
							'type' => 'string',
						),
						'url'          => array(
							'type' => 'string',
						),
						'description'  => array(
							'type' => 'string',
						),
						'role'         => array(
							'type' => 'string',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'user_id' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_user' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_users' );
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
	 * Execute update-user.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_update_user( $input ) {
		$user_id = absint( $input['user_id'] );
		$args    = array( 'ID' => $user_id );

		if ( isset( $input['email'] ) ) {
			$args['user_email'] = sanitize_email( $input['email'] );
		}

		if ( isset( $input['display_name'] ) ) {
			$args['display_name'] = sanitize_text_field( $input['display_name'] );
		}

		if ( isset( $input['first_name'] ) ) {
			$args['first_name'] = sanitize_text_field( $input['first_name'] );
		}

		if ( isset( $input['last_name'] ) ) {
			$args['last_name'] = sanitize_text_field( $input['last_name'] );
		}

		if ( isset( $input['password'] ) ) {
			$args['user_pass'] = $input['password'];
		}

		if ( isset( $input['url'] ) ) {
			$args['user_url'] = esc_url_raw( $input['url'] );
		}

		if ( isset( $input['description'] ) ) {
			$args['description'] = sanitize_textarea_field( $input['description'] );
		}

		if ( isset( $input['role'] ) ) {
			$args['role'] = sanitize_text_field( $input['role'] );
		}

		$result = wp_update_user( $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'user_id' => $result );
	}

	/**
	 * Register delete-user ability.
	 *
	 * @return void
	 */
	private function register_delete_user() {
		wp_register_ability(
			'wp-mcp-core/delete-user',
			array(
				'category'            => 'users',
				'label'               => __( 'Delete User', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete a user account.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'user_id' ),
					'properties' => array(
						'user_id'  => array(
							'type' => 'integer',
						),
						'reassign' => array(
							'type'        => 'integer',
							'description' => 'Reassign posts to this user ID',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_user' ),
				'permission_callback' => function() {
					return current_user_can( 'delete_users' );
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
	 * Execute delete-user.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_user( $input ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$user_id  = absint( $input['user_id'] );
		$reassign = isset( $input['reassign'] ) ? absint( $input['reassign'] ) : null;

		$result = wp_delete_user( $user_id, $reassign );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete user', array( 'status' => 500 ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Register get-user-meta ability.
	 *
	 * @return void
	 */
	private function register_get_user_meta() {
		wp_register_ability(
			'wp-mcp-core/get-user-meta',
			array(
				'category'            => 'users',
				'label'               => __( 'Get User Meta', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get user custom metadata.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'user_id' ),
					'properties' => array(
						'user_id'  => array(
							'type' => 'integer',
						),
						'meta_key' => array(
							'type' => 'string',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'user_id' => array( 'type' => 'integer' ),
						'meta'    => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_user_meta' ),
				'permission_callback' => function() {
					return current_user_can( 'list_users' );
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
	 * Execute get-user-meta.
	 *
	 * @param array $input Input parameters.
	 * @return array Meta data.
	 */
	public function execute_get_user_meta( $input ) {
		$user_id = absint( $input['user_id'] );

		if ( isset( $input['meta_key'] ) ) {
			$meta_value = get_user_meta( $user_id, sanitize_key( $input['meta_key'] ), true );
			return array(
				'user_id' => $user_id,
				'meta'    => array( $input['meta_key'] => $meta_value ),
			);
		}

		$all_meta = get_user_meta( $user_id );

		return array(
			'user_id' => $user_id,
			'meta'    => $all_meta,
		);
	}

	/**
	 * Register update-user-meta ability.
	 *
	 * @return void
	 */
	private function register_update_user_meta() {
		wp_register_ability(
			'wp-mcp-core/update-user-meta',
			array(
				'category'            => 'users',
				'label'               => __( 'Update User Meta', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Set user custom metadata.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'user_id', 'meta_key', 'meta_value' ),
					'properties' => array(
						'user_id'    => array(
							'type' => 'integer',
						),
						'meta_key'   => array(
							'type' => 'string',
						),
						'meta_value' => array(),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_user_meta' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_users' );
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
	 * Execute update-user-meta.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_update_user_meta( $input ) {
		$success = update_user_meta(
			absint( $input['user_id'] ),
			sanitize_key( $input['meta_key'] ),
			$input['meta_value']
		);

		return array( 'success' => (bool) $success );
	}

	/**
	 * Register list-user-posts ability.
	 *
	 * @return void
	 */
	private function register_list_user_posts() {
		wp_register_ability(
			'wp-mcp-core/list-user-posts',
			array(
				'category'            => 'users',
				'label'               => __( 'List User Posts', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get all posts by a specific user.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'user_id' ),
					'properties' => array(
						'user_id'  => array(
							'type' => 'integer',
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 10,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'posts' => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_user_posts' ),
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
	 * Execute list-user-posts.
	 *
	 * @param array $input Input parameters.
	 * @return array Posts.
	 */
	public function execute_list_user_posts( $input ) {
		$args = array(
			'author'         => absint( $input['user_id'] ),
			'posts_per_page' => $input['per_page'] ?? 10,
			'post_status'    => 'publish',
		);

		$query = new WP_Query( $args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$posts[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'url'   => get_permalink( $post->ID ),
				'date'  => $post->post_date,
			);
		}

		return array(
			'posts' => $posts,
			'total' => $query->found_posts,
		);
	}

	/**
	 * Register update-user-role ability.
	 *
	 * @return void
	 */
	private function register_update_user_role() {
		wp_register_ability(
			'wp-mcp-core/update-user-role',
			array(
				'category'            => 'users',
				'label'               => __( 'Update User Role', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Change user role/capabilities.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'user_id', 'role' ),
					'properties' => array(
						'user_id' => array(
							'type' => 'integer',
						),
						'role'    => array(
							'type' => 'string',
							'enum' => array( 'subscriber', 'contributor', 'author', 'editor', 'administrator' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_user_role' ),
				'permission_callback' => function() {
					return current_user_can( 'promote_users' );
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
	 * Execute update-user-role.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_update_user_role( $input ) {
		$user = new WP_User( absint( $input['user_id'] ) );

		if ( ! $user->exists() ) {
			return new WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		$user->set_role( sanitize_text_field( $input['role'] ) );

		return array( 'success' => true );
	}
}

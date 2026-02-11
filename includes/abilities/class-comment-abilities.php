<?php
/**
 * Comment Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Comment moderation and management abilities.
 */
class WP_MCP_Comment_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all comment abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_list_comments();
		$instance->register_get_comment();
		$instance->register_create_comment();
		$instance->register_update_comment();
		$instance->register_delete_comment();
		$instance->register_approve_comment();
		$instance->register_spam_comment();
		$instance->register_get_post_comments();
	}

	/**
	 * Register list-comments ability.
	 *
	 * @return void
	 */
	private function register_list_comments() {
		wp_register_ability(
			'wp-mcp-core/list-comments',
			array(
				'category'            => 'comments',
				'label'               => __( 'List Comments', 'wp-mcp-core-abilities' ),
				'description'         => __( 'List comments with filtering.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'status'   => array(
							'type'    => 'string',
							'default' => 'approve',
							'enum'    => array( 'hold', 'approve', 'spam', 'trash' ),
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
						'comments' => array( 'type' => 'array' ),
						'total'    => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_comments' ),
				'permission_callback' => function() {
					return current_user_can( 'moderate_comments' );
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
	 * Execute list-comments.
	 *
	 * @param array $input Input parameters.
	 * @return array Comments.
	 */
	public function execute_list_comments( $input ) {
		// Apply defaults using null coalescing operator.
		$status = $input['status'] ?? 'approve';
		$per_page = $input['per_page'] ?? 10;
		$page = $input['page'] ?? 1;

		// Add validation.
		$per_page = max( 1, min( 100, $per_page ) );
		$page = max( 1, $page );

		$args = array(
			'status' => $status,
			'number' => $per_page,
			'paged'  => $page,
		);

		$comments  = get_comments( $args );
		$formatted = array();

		foreach ( $comments as $comment ) {
			$formatted[] = array(
				'id'           => $comment->comment_ID,
				'post_id'      => $comment->comment_post_ID,
				'author'       => $comment->comment_author,
				'author_email' => $comment->comment_author_email,
				'content'      => $comment->comment_content,
				'status'       => wp_get_comment_status( $comment->comment_ID ),
				'date'         => $comment->comment_date,
			);
		}

		return array(
			'comments' => $formatted,
			'total'    => count( $formatted ),
		);
	}

	/**
	 * Register get-comment ability.
	 *
	 * @return void
	 */
	private function register_get_comment() {
		wp_register_ability(
			'wp-mcp-core/get-comment',
			array(
				'category'            => 'comments',
				'label'               => __( 'Get Comment', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get a single comment by ID.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'comment_id' ),
					'properties' => array(
						'comment_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'post_id' => array( 'type' => 'integer' ),
						'author'  => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
						'status'  => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_comment' ),
				'permission_callback' => function() {
					return current_user_can( 'moderate_comments' );
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
	 * Execute get-comment.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Comment data.
	 */
	public function execute_get_comment( $input ) {
		$comment = get_comment( absint( $input['comment_id'] ) );

		if ( ! $comment ) {
			return new WP_Error( 'comment_not_found', 'Comment not found', array( 'status' => 404 ) );
		}

		return array(
			'id'      => $comment->comment_ID,
			'post_id' => $comment->comment_post_ID,
			'author'  => $comment->comment_author,
			'content' => $comment->comment_content,
			'status'  => wp_get_comment_status( $comment->comment_ID ),
		);
	}

	/**
	 * Register create-comment ability.
	 *
	 * @return void
	 */
	private function register_create_comment() {
		wp_register_ability(
			'wp-mcp-core/create-comment',
			array(
				'category'            => 'comments',
				'label'               => __( 'Create Comment', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Add a new comment to a post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'content' ),
					'properties' => array(
						'post_id' => array(
							'type' => 'integer',
						),
						'content' => array(
							'type' => 'string',
						),
						'author'  => array(
							'type' => 'string',
						),
						'email'   => array(
							'type'   => 'string',
							'format' => 'email',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'comment_id' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_create_comment' ),
				'permission_callback' => function() {
					return is_user_logged_in();
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
	 * Execute create-comment.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Comment data.
	 */
	public function execute_create_comment( $input ) {
		$current_user = wp_get_current_user();

		$comment_data = array(
			'comment_post_ID' => absint( $input['post_id'] ),
			'comment_content' => wp_kses_post( $input['content'] ),
			'comment_author'  => isset( $input['author'] ) ? sanitize_text_field( $input['author'] ) : $current_user->display_name,
			'comment_author_email' => isset( $input['email'] ) ? sanitize_email( $input['email'] ) : $current_user->user_email,
			'user_id'         => $current_user->ID,
		);

		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			return new WP_Error( 'comment_failed', 'Failed to create comment', array( 'status' => 500 ) );
		}

		return array( 'comment_id' => $comment_id );
	}

	/**
	 * Register update-comment ability.
	 *
	 * @return void
	 */
	private function register_update_comment() {
		wp_register_ability(
			'wp-mcp-core/update-comment',
			array(
				'category'            => 'comments',
				'label'               => __( 'Update Comment', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Update comment content/status.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'comment_id' ),
					'properties' => array(
						'comment_id' => array(
							'type' => 'integer',
						),
						'content'    => array(
							'type' => 'string',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_comment' ),
				'permission_callback' => function() {
					return current_user_can( 'moderate_comments' );
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
	 * Execute update-comment.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_update_comment( $input ) {
		$comment_id = absint( $input['comment_id'] );
		$args       = array( 'comment_ID' => $comment_id );

		if ( isset( $input['content'] ) ) {
			$args['comment_content'] = wp_kses_post( $input['content'] );
		}

		$result = wp_update_comment( $args );

		if ( is_wp_error( $result ) || ! $result ) {
			return new WP_Error( 'update_failed', 'Failed to update comment', array( 'status' => 500 ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Register delete-comment ability.
	 *
	 * @return void
	 */
	private function register_delete_comment() {
		wp_register_ability(
			'wp-mcp-core/delete-comment',
			array(
				'category'            => 'comments',
				'label'               => __( 'Delete Comment', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Trash or permanently delete a comment.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'comment_id' ),
					'properties' => array(
						'comment_id'   => array(
							'type' => 'integer',
						),
						'force_delete' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_delete_comment' ),
				'permission_callback' => function() {
					return current_user_can( 'moderate_comments' );
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
	 * Execute delete-comment.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_comment( $input ) {
		$comment_id   = absint( $input['comment_id'] );
		$force_delete = $input['force_delete'] ?? false;

		$result = wp_delete_comment( $comment_id, $force_delete );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete comment', array( 'status' => 500 ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Register approve-comment ability.
	 *
	 * @return void
	 */
	private function register_approve_comment() {
		wp_register_ability(
			'wp-mcp-core/approve-comment',
			array(
				'category'            => 'comments',
				'label'               => __( 'Approve Comment', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Approve a pending comment.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'comment_id' ),
					'properties' => array(
						'comment_id' => array(
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
				'execute_callback'    => array( $this, 'execute_approve_comment' ),
				'permission_callback' => function() {
					return current_user_can( 'moderate_comments' );
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
	 * Execute approve-comment.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_approve_comment( $input ) {
		$result = wp_set_comment_status( absint( $input['comment_id'] ), 'approve' );

		return array( 'success' => (bool) $result );
	}

	/**
	 * Register spam-comment ability.
	 *
	 * @return void
	 */
	private function register_spam_comment() {
		wp_register_ability(
			'wp-mcp-core/spam-comment',
			array(
				'category'            => 'comments',
				'label'               => __( 'Mark Comment as Spam', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Mark a comment as spam.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'comment_id' ),
					'properties' => array(
						'comment_id' => array(
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
				'execute_callback'    => array( $this, 'execute_spam_comment' ),
				'permission_callback' => function() {
					return current_user_can( 'moderate_comments' );
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
	 * Execute spam-comment.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_spam_comment( $input ) {
		$result = wp_spam_comment( absint( $input['comment_id'] ) );

		return array( 'success' => (bool) $result );
	}

	/**
	 * Register get-post-comments ability.
	 *
	 * @return void
	 */
	private function register_get_post_comments() {
		wp_register_ability(
			'wp-mcp-core/get-post-comments',
			array(
				'category'            => 'comments',
				'label'               => __( 'Get Post Comments', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get all comments for a specific post.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type' => 'integer',
						),
						'status'  => array(
							'type'    => 'string',
							'default' => 'approve',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'comments' => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_post_comments' ),
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
	 * Execute get-post-comments.
	 *
	 * @param array $input Input parameters.
	 * @return array Comments.
	 */
	public function execute_get_post_comments( $input ) {
		$comments = get_comments(
			array(
				'post_id' => absint( $input['post_id'] ),
				'status'  => $input['status'] ?? 'approve',
			)
		);

		$formatted = array();
		foreach ( $comments as $comment ) {
			$formatted[] = array(
				'id'      => $comment->comment_ID,
				'author'  => $comment->comment_author,
				'content' => $comment->comment_content,
				'date'    => $comment->comment_date,
			);
		}

		return array( 'comments' => $formatted );
	}
}

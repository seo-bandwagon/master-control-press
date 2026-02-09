<?php
/**
 * Ability Helpers Trait
 *
 * Provides common helper methods for ability classes.
 *
 * @package WP_MCP_Core_Abilities
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only declare the trait if it doesn't already exist (may be defined by another plugin).
if ( trait_exists( 'WP_MCP_Ability_Helpers' ) ) {
	return;
}

/**
 * Trait WP_MCP_Ability_Helpers
 *
 * Common helper methods for ability implementations.
 */
trait WP_MCP_Ability_Helpers {

	/**
	 * Validate required fields in input.
	 *
	 * @param array $input    Input data.
	 * @param array $required Required field names.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	protected function validate_required_fields( $input, $required ) {
		foreach ( $required as $field ) {
			if ( ! isset( $input[ $field ] ) || '' === $input[ $field ] ) {
				return new WP_Error(
					'missing_required_field',
					sprintf(
						/* translators: %s: Field name */
						__( 'Required field missing: %s', 'wp-mcp-core-abilities' ),
						$field
					)
				);
			}
		}
		return true;
	}

	/**
	 * Sanitize post data for creation/update.
	 *
	 * @param array $input Input data.
	 * @return array Sanitized data.
	 */
	protected function sanitize_post_data( $input ) {
		$sanitized = array();

		if ( isset( $input['post_title'] ) ) {
			$sanitized['post_title'] = sanitize_text_field( $input['post_title'] );
		}

		if ( isset( $input['post_content'] ) ) {
			$sanitized['post_content'] = wp_kses_post( $input['post_content'] );
		}

		if ( isset( $input['post_excerpt'] ) ) {
			$sanitized['post_excerpt'] = sanitize_textarea_field( $input['post_excerpt'] );
		}

		if ( isset( $input['post_status'] ) ) {
			$sanitized['post_status'] = sanitize_key( $input['post_status'] );
		}

		if ( isset( $input['post_name'] ) ) {
			$sanitized['post_name'] = sanitize_title( $input['post_name'] );
		}

		if ( isset( $input['post_parent'] ) ) {
			$sanitized['post_parent'] = absint( $input['post_parent'] );
		}

		if ( isset( $input['menu_order'] ) ) {
			$sanitized['menu_order'] = absint( $input['menu_order'] );
		}

		return $sanitized;
	}

	/**
	 * Format post object for output.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Formatted post data.
	 */
	protected function format_post( $post ) {
		return array(
			'id'            => $post->ID,
			'title'         => $post->post_title,
			'content'       => $post->post_content,
			'excerpt'       => $post->post_excerpt,
			'status'        => $post->post_status,
			'slug'          => $post->post_name,
			'url'           => get_permalink( $post->ID ),
			'edit_url'      => get_edit_post_link( $post->ID, 'raw' ),
			'author_id'     => $post->post_author,
			'author_name'   => get_the_author_meta( 'display_name', $post->post_author ),
			'date'          => $post->post_date,
			'modified'      => $post->post_modified,
			'post_type'     => $post->post_type,
			'parent_id'     => $post->post_parent,
			'menu_order'    => $post->menu_order,
			'comment_count' => $post->comment_count,
		);
	}

	/**
	 * Format user object for output.
	 *
	 * @param WP_User $user User object.
	 * @param bool    $include_email Whether to include email (for admins).
	 * @return array Formatted user data.
	 */
	protected function format_user( $user, $include_email = false ) {
		$formatted = array(
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'display_name' => $user->display_name,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'roles'        => $user->roles,
			'registered'   => $user->user_registered,
		);

		if ( $include_email ) {
			$formatted['email'] = $user->user_email;
		}

		return $formatted;
	}

	/**
	 * Format term object for output.
	 *
	 * @param WP_Term $term Term object.
	 * @return array Formatted term data.
	 */
	protected function format_term( $term ) {
		return array(
			'id'          => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent_id'   => $term->parent,
			'count'       => $term->count,
			'taxonomy'    => $term->taxonomy,
		);
	}

	/**
	 * Format comment object for output.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @return array Formatted comment data.
	 */
	protected function format_comment( $comment ) {
		return array(
			'id'            => $comment->comment_ID,
			'post_id'       => $comment->comment_post_ID,
			'author_name'   => $comment->comment_author,
			'author_email'  => $comment->comment_author_email,
			'author_url'    => $comment->comment_author_url,
			'content'       => $comment->comment_content,
			'date'          => $comment->comment_date,
			'approved'      => $comment->comment_approved,
			'type'          => $comment->comment_type,
			'parent_id'     => $comment->comment_parent,
			'user_id'       => $comment->user_id,
		);
	}

	/**
	 * Get pagination args for queries.
	 *
	 * @param array $input Input data.
	 * @param int   $default_per_page Default items per page.
	 * @return array Pagination args.
	 */
	protected function get_pagination_args( $input, $default_per_page = 10 ) {
		return array(
			'posts_per_page' => isset( $input['per_page'] ) ? absint( $input['per_page'] ) : $default_per_page,
			'paged'          => isset( $input['page'] ) ? absint( $input['page'] ) : 1,
		);
	}

	/**
	 * Get ordering args for queries.
	 *
	 * @param array  $input Input data.
	 * @param string $default_orderby Default orderby field.
	 * @param string $default_order Default order direction.
	 * @return array Ordering args.
	 */
	protected function get_ordering_args( $input, $default_orderby = 'date', $default_order = 'DESC' ) {
		return array(
			'orderby' => isset( $input['orderby'] ) ? sanitize_key( $input['orderby'] ) : $default_orderby,
			'order'   => isset( $input['order'] ) ? strtoupper( sanitize_key( $input['order'] ) ) : $default_order,
		);
	}

	/**
	 * Handle taxonomy assignment for posts.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $input Input data with categories and tags.
	 * @return void
	 */
	protected function handle_post_taxonomies( $post_id, $input ) {
		// Handle categories.
		if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
			$category_ids = array_map( 'absint', $input['categories'] );
			wp_set_post_categories( $post_id, $category_ids );
		}

		// Handle tags.
		if ( isset( $input['tags'] ) && is_array( $input['tags'] ) ) {
			wp_set_post_tags( $post_id, $input['tags'] );
		}
	}

	/**
	 * Get post categories and tags.
	 *
	 * @param int $post_id Post ID.
	 * @return array Categories and tags data.
	 */
	protected function get_post_taxonomies( $post_id ) {
		$categories = wp_get_post_categories( $post_id );
		$tags       = wp_get_post_tags( $post_id );

		return array(
			'categories' => array_map(
				function ( $cat_id ) {
					$cat = get_category( $cat_id );
					return array(
						'id'   => $cat->term_id,
						'name' => $cat->name,
						'slug' => $cat->slug,
					);
				},
				$categories
			),
			'tags'       => array_map(
				function ( $tag ) {
					return array(
						'id'   => $tag->term_id,
						'name' => $tag->name,
						'slug' => $tag->slug,
					);
				},
				$tags
			),
		);
	}
}

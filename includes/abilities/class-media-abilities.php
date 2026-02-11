<?php
/**
 * Media Abilities
 *
 * @package WP_MCP_Core_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media library and file management abilities.
 */
class WP_MCP_Media_Abilities {

	use WP_MCP_Ability_Helpers;

	/**
	 * Register all media abilities.
	 *
	 * @return void
	 */
	public static function register() {
		$instance = new self();
		$instance->register_list_media();
		$instance->register_get_media();
		$instance->register_upload_media();
		$instance->register_update_media();
		$instance->register_delete_media();
		$instance->register_search_media();
		$instance->register_get_media_meta();
		$instance->register_update_media_meta();
		$instance->register_get_attachment_url();
		$instance->register_get_image_sizes();
	}

	/**
	 * Register list-media ability.
	 *
	 * @return void
	 */
	private function register_list_media() {
		wp_register_ability(
			'wp-mcp-core/list-media',
			array(
				'category'            => 'media',
				'label'               => __( 'List Media', 'wp-mcp-core-abilities' ),
				'description'         => __( 'List media library files with filtering.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'mime_type' => array(
							'type'        => 'string',
							'description' => 'Filter by MIME type (e.g., image/jpeg)',
						),
						'per_page'  => array(
							'type'    => 'integer',
							'default' => 10,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'media' => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_media' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute list-media.
	 *
	 * @param array $input Input parameters.
	 * @return array Media list.
	 */
	public function execute_list_media( $input ) {
		// Apply defaults using null coalescing operator.
		$mime_type = $input['mime_type'] ?? '';
		$per_page = $input['per_page'] ?? 10;
		$page = $input['page'] ?? 1;

		// Add validation.
		$per_page = max( 1, min( 100, $per_page ) );
		$page = max( 1, $page );

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		);

		if ( ! empty( $mime_type ) ) {
			$args['post_mime_type'] = sanitize_mime_type( $mime_type );
		}

		$query = new WP_Query( $args );
		$media = array();

		foreach ( $query->posts as $attachment ) {
			$media[] = array(
				'id'        => $attachment->ID,
				'title'     => $attachment->post_title,
				'url'       => wp_get_attachment_url( $attachment->ID ),
				'mime_type' => $attachment->post_mime_type,
				'date'      => $attachment->post_date,
			);
		}

		return array(
			'media' => $media,
			'total' => $query->found_posts,
		);
	}

	/**
	 * Register get-media ability.
	 *
	 * @return void
	 */
	private function register_get_media() {
		wp_register_ability(
			'wp-mcp-core/get-media',
			array(
				'category'            => 'media',
				'label'               => __( 'Get Media', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get single media item with metadata.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'media_id' ),
					'properties' => array(
						'media_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
						'url'         => array( 'type' => 'string' ),
						'mime_type'   => array( 'type' => 'string' ),
						'alt_text'    => array( 'type' => 'string' ),
						'caption'     => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_media' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute get-media.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Media data.
	 */
	public function execute_get_media( $input ) {
		$media_id = absint( $input['media_id'] );
		$post     = get_post( $media_id );

		if ( ! $post || 'attachment' !== $post->post_type ) {
			return new WP_Error( 'media_not_found', 'Media not found', array( 'status' => 404 ) );
		}

		return array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'url'         => wp_get_attachment_url( $post->ID ),
			'mime_type'   => $post->post_mime_type,
			'alt_text'    => get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
			'caption'     => $post->post_excerpt,
			'description' => $post->post_content,
		);
	}

	/**
	 * Register upload-media ability.
	 *
	 * @return void
	 */
	private function register_upload_media() {
		wp_register_ability(
			'wp-mcp-core/upload-media',
			array(
				'category'            => 'media',
				'label'               => __( 'Upload Media', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Upload a new media file (base64 encoded).', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'filename', 'file_data' ),
					'properties' => array(
						'filename'  => array(
							'type'        => 'string',
							'description' => 'File name with extension',
						),
						'file_data' => array(
							'type'        => 'string',
							'description' => 'Base64 encoded file data',
						),
						'title'     => array(
							'type' => 'string',
						),
						'alt_text'  => array(
							'type' => 'string',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'media_id' => array( 'type' => 'integer' ),
						'url'      => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_upload_media' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute upload-media.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Media data.
	 */
	public function execute_upload_media( $input ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Decode base64 file data.
		$file_data = base64_decode( $input['file_data'] );
		if ( ! $file_data ) {
			return new WP_Error( 'invalid_file_data', 'Invalid base64 file data', array( 'status' => 400 ) );
		}

		// Create temp file.
		$upload_dir = wp_upload_dir();
		$filename   = sanitize_file_name( $input['filename'] );
		$temp_file  = $upload_dir['path'] . '/' . $filename;

		// Write file.
		if ( false === file_put_contents( $temp_file, $file_data ) ) {
			return new WP_Error( 'upload_failed', 'Failed to write file', array( 'status' => 500 ) );
		}

		// Insert attachment.
		$attachment = array(
			'post_mime_type' => mime_content_type( $temp_file ),
			'post_title'     => isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $filename,
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $temp_file, 0, true );

		if ( is_wp_error( $attach_id ) ) {
			unlink( $temp_file );
			return $attach_id;
		}

		// Generate metadata.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $temp_file );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Set alt text if provided.
		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $attach_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}

		return array(
			'media_id' => $attach_id,
			'url'      => wp_get_attachment_url( $attach_id ),
		);
	}

	/**
	 * Register update-media ability.
	 *
	 * @return void
	 */
	private function register_update_media() {
		wp_register_ability(
			'wp-mcp-core/update-media',
			array(
				'category'            => 'media',
				'label'               => __( 'Update Media', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Update media metadata.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'media_id' ),
					'properties' => array(
						'media_id'    => array(
							'type' => 'integer',
						),
						'title'       => array(
							'type' => 'string',
						),
						'alt_text'    => array(
							'type' => 'string',
						),
						'caption'     => array(
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
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_media' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute update-media.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_update_media( $input ) {
		$media_id = absint( $input['media_id'] );
		$post     = get_post( $media_id );

		if ( ! $post || 'attachment' !== $post->post_type ) {
			return new WP_Error( 'media_not_found', 'Media not found', array( 'status' => 404 ) );
		}

		$update = array( 'ID' => $media_id );

		if ( isset( $input['title'] ) ) {
			$update['post_title'] = sanitize_text_field( $input['title'] );
		}

		if ( isset( $input['caption'] ) ) {
			$update['post_excerpt'] = sanitize_textarea_field( $input['caption'] );
		}

		if ( isset( $input['description'] ) ) {
			$update['post_content'] = wp_kses_post( $input['description'] );
		}

		$result = wp_update_post( $update, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $media_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Register delete-media ability.
	 *
	 * @return void
	 */
	private function register_delete_media() {
		wp_register_ability(
			'wp-mcp-core/delete-media',
			array(
				'category'            => 'media',
				'label'               => __( 'Delete Media', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Delete media file permanently.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'media_id' ),
					'properties' => array(
						'media_id' => array(
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
				'execute_callback'    => array( $this, 'execute_delete_media' ),
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
	 * Execute delete-media.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Result.
	 */
	public function execute_delete_media( $input ) {
		$result = wp_delete_attachment( absint( $input['media_id'] ), true );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete media', array( 'status' => 500 ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Register search-media ability.
	 *
	 * @return void
	 */
	private function register_search_media() {
		wp_register_ability(
			'wp-mcp-core/search-media',
			array(
				'category'            => 'media',
				'label'               => __( 'Search Media', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Search media library by filename, alt text, caption.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'query' ),
					'properties' => array(
						'query'    => array(
							'type' => 'string',
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
						'media' => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_search_media' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute search-media.
	 *
	 * @param array $input Input parameters.
	 * @return array Search results.
	 */
	public function execute_search_media( $input ) {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			's'              => sanitize_text_field( $input['query'] ),
			'posts_per_page' => $input['per_page'] ?? 10,
		);

		$query = new WP_Query( $args );
		$media = array();

		foreach ( $query->posts as $attachment ) {
			$media[] = array(
				'id'        => $attachment->ID,
				'title'     => $attachment->post_title,
				'url'       => wp_get_attachment_url( $attachment->ID ),
				'mime_type' => $attachment->post_mime_type,
			);
		}

		return array(
			'media' => $media,
			'total' => $query->found_posts,
		);
	}

	/**
	 * Register get-media-meta ability.
	 *
	 * @return void
	 */
	private function register_get_media_meta() {
		wp_register_ability(
			'wp-mcp-core/get-media-meta',
			array(
				'category'            => 'media',
				'label'               => __( 'Get Media Meta', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get custom metadata for media attachment.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'media_id' ),
					'properties' => array(
						'media_id' => array(
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
						'media_id' => array( 'type' => 'integer' ),
						'meta'     => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_media_meta' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute get-media-meta.
	 *
	 * @param array $input Input parameters.
	 * @return array Meta data.
	 */
	public function execute_get_media_meta( $input ) {
		$media_id = absint( $input['media_id'] );

		if ( isset( $input['meta_key'] ) ) {
			$meta_value = get_post_meta( $media_id, sanitize_key( $input['meta_key'] ), true );
			return array(
				'media_id' => $media_id,
				'meta'     => array( $input['meta_key'] => $meta_value ),
			);
		}

		$all_meta = get_post_meta( $media_id );

		return array(
			'media_id' => $media_id,
			'meta'     => $all_meta,
		);
	}

	/**
	 * Register update-media-meta ability.
	 *
	 * @return void
	 */
	private function register_update_media_meta() {
		wp_register_ability(
			'wp-mcp-core/update-media-meta',
			array(
				'category'            => 'media',
				'label'               => __( 'Update Media Meta', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Set custom metadata for media attachment.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'media_id', 'meta_key', 'meta_value' ),
					'properties' => array(
						'media_id'   => array(
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
				'execute_callback'    => array( $this, 'execute_update_media_meta' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute update-media-meta.
	 *
	 * @param array $input Input parameters.
	 * @return array Result.
	 */
	public function execute_update_media_meta( $input ) {
		$success = update_post_meta(
			absint( $input['media_id'] ),
			sanitize_key( $input['meta_key'] ),
			$input['meta_value']
		);

		return array( 'success' => (bool) $success );
	}

	/**
	 * Register get-attachment-url ability.
	 *
	 * @return void
	 */
	private function register_get_attachment_url() {
		wp_register_ability(
			'wp-mcp-core/get-attachment-url',
			array(
				'category'            => 'media',
				'label'               => __( 'Get Attachment URL', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get direct URL for media file.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'media_id' ),
					'properties' => array(
						'media_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'url' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_attachment_url' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute get-attachment-url.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error URL.
	 */
	public function execute_get_attachment_url( $input ) {
		$url = wp_get_attachment_url( absint( $input['media_id'] ) );

		if ( ! $url ) {
			return new WP_Error( 'media_not_found', 'Media not found', array( 'status' => 404 ) );
		}

		return array( 'url' => $url );
	}

	/**
	 * Register get-image-sizes ability.
	 *
	 * @return void
	 */
	private function register_get_image_sizes() {
		wp_register_ability(
			'wp-mcp-core/get-image-sizes',
			array(
				'category'            => 'media',
				'label'               => __( 'Get Image Sizes', 'wp-mcp-core-abilities' ),
				'description'         => __( 'Get all available image size URLs for image attachment.', 'wp-mcp-core-abilities' ),
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'media_id' ),
					'properties' => array(
						'media_id' => array(
							'type' => 'integer',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'sizes' => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_image_sizes' ),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
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
	 * Execute get-image-sizes.
	 *
	 * @param array $input Input parameters.
	 * @return array|WP_Error Image sizes.
	 */
	public function execute_get_image_sizes( $input ) {
		$media_id = absint( $input['media_id'] );
		$sizes    = array();

		foreach ( array( 'thumbnail', 'medium', 'large', 'full' ) as $size ) {
			$src = wp_get_attachment_image_src( $media_id, $size );
			if ( $src ) {
				$sizes[ $size ] = array(
					'url'    => $src[0],
					'width'  => $src[1],
					'height' => $src[2],
				);
			}
		}

		if ( empty( $sizes ) ) {
			return new WP_Error( 'not_image', 'Not an image attachment', array( 'status' => 400 ) );
		}

		return array( 'sizes' => $sizes );
	}
}

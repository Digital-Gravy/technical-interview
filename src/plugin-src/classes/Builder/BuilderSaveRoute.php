<?php
/**
 * Builder Save Route — REST endpoint to save builder HTML back as Gutenberg blocks.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Builder;

use DgInterview\Blocks\BlockRegistry;
/**
 * Registers and handles the builder save REST endpoint.
 */
class BuilderSaveRoute {

	/**
	 * Serializer for builder HTML -> Gutenberg conversion.
	 *
	 * @var BuilderContentSerializer
	 */
	private BuilderContentSerializer $content_serializer;

	/**
	 * Hook into WordPress.
	 *
	 * @param BlockRegistry                 $registry           The block registry.
	 * @param BuilderContentSerializer|null $content_serializer Serializer override (tests/composition).
	 */
	public function __construct( BlockRegistry $registry, ?BuilderContentSerializer $content_serializer = null ) {
		$this->content_serializer = $content_serializer ?? new BuilderContentSerializer( $registry );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'dg-interview/v1',
			'/posts/(?P<id>\\d+)/content',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_content' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'id'   => array(
						'type'     => 'integer',
						'required' => true,
					),
					'html' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Save the builder HTML back to the post as Gutenberg blocks.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_content( \WP_REST_Request $request ) {
		$raw_id = $request->get_param( 'id' );
		$post_id = is_numeric( $raw_id ) ? (int) $raw_id : 0;

		$raw_html = $request->get_param( 'html' );
		$html     = is_string( $raw_html ) ? $raw_html : '';

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error( 'not_found', 'Post not found', array( 'status' => 404 ) );
		}

		/**
		 * Parsed block array from the post content.
		 *
		 * @var array<int, array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>}> $original_blocks
		 */
		$original_blocks = parse_blocks( $post->post_content );
		$new_content     = $this->content_serializer->rebuild_content( $html, $original_blocks );

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( array( 'success' => true ) );
	}
}

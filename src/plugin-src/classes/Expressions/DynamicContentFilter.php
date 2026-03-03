<?php
/**
 * DynamicContentFilter — resolves dynamic expressions on the frontend.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Expressions;

/**
 * Hooks into render_block to resolve {expression} templates on frontend
 * requests while leaving them intact for the builder/REST API.
 */
class DynamicContentFilter {

	/**
	 * Hook into WordPress.
	 */
	public function __construct() {
		add_filter( 'render_block', array( $this, 'resolve_expressions' ) );
	}

	/**
	 * Replace expression templates in rendered block content.
	 *
	 * Skips REST API requests so the builder sees raw expressions.
	 *
	 * @param string $block_content The rendered block content.
	 * @return string
	 */
	public function resolve_expressions( string $block_content ): string {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $block_content;
		}

		if ( ! str_contains( $block_content, '{' ) ) {
			return $block_content;
		}

		return DynamicContentProcessor::replace_templates(
			$block_content,
			DynamicContentProcessor::get_post_sources()
		);
	}
}

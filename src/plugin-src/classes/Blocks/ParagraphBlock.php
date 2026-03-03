<?php
/**
 * ParagraphBlock — server-side rendering for the paragraph block.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks;

/**
 * Registers and renders the dg-interview/paragraph block.
 */
class ParagraphBlock implements Block {

	use RendersHtmlAttributes;

	/**
	 * Hook into WordPress.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register the block type.
	 *
	 * @return void
	 */
	public function register_block(): void {
		register_block_type(
			$this->block_name(),
			array(
				'api_version'     => '3',
				'attributes'      => array(
					'content'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'htmlAttributes' => array(
						'type'    => 'object',
						'default' => array(),
					),
				),
				'supports'        => array( 'html' => false ),
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Render the block on the front end.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner block content (unused).
	 * @return string The rendered HTML.
	 */
	public function render( array $attributes, string $content ): string {
		$text = '';
		if ( isset( $attributes['content'] ) && is_string( $attributes['content'] ) ) {
			$text = $attributes['content'];
		}

		$attr_string = $this->render_html_attributes( $attributes );

		return sprintf( '<p%s>%s</p>', $attr_string, $text );
	}

	/**
	 * The Gutenberg block name.
	 *
	 * @return string
	 */
	public function block_name(): string {
		return self::PREFIX . '/paragraph';
	}

	/**
	 * HTML tags this block handles.
	 *
	 * @return string[]
	 */
	public function html_tags(): array {
		return array( 'p' );
	}

	/**
	 * Serialize an HTML paragraph back into a Gutenberg block comment.
	 *
	 * @param HtmlElementDto $element  The parsed HTML element data.
	 * @param BlockRegistry  $registry The registry (unused).
	 * @return string The Gutenberg block comment.
	 */
	public function serialize_from_html( HtmlElementDto $element, BlockRegistry $registry ): string {
		$block_attrs = array( 'content' => $element->text_content );

		if ( ! empty( $element->attributes ) ) {
			$block_attrs['htmlAttributes'] = $element->attributes;
		}

		$attrs_json = ' ' . wp_json_encode( $block_attrs );
		return sprintf( "<!-- wp:%s%s /-->\n\n", $this->block_name(), $attrs_json );
	}
}

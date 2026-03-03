<?php
/**
 * Heading Block — renders a heading element (h1–h6) with configurable level.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks;

/**
 * Registers and renders the dg-interview/heading block.
 */
class HeadingBlock implements Block {

	use RendersHtmlAttributes;

	/**
	 * Hook into WordPress to register the block.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register the block type with WordPress.
	 *
	 * @return void
	 */
	public function register_block(): void {
		register_block_type(
			$this->block_name(),
			array(
				'api_version'     => '3',
				'attributes'      => array(
					'level'      => array(
						'type'    => 'integer',
						'default' => 2,
					),
					'content'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'htmlAttributes' => array(
						'type'    => 'object',
						'default' => array(),
					),
				),
				'supports'        => array(
					'html' => false,
				),
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Render the block on the frontend.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner blocks HTML (unused).
	 * @return string
	 */
	public function render( array $attributes, string $content ): string {
		$level = 2;
		if ( isset( $attributes['level'] ) && is_int( $attributes['level'] ) ) {
			$level = $attributes['level'];
		}

		if ( $level < 1 || $level > 6 ) {
			$level = 2;
		}

		$text = '';
		if ( isset( $attributes['content'] ) && is_string( $attributes['content'] ) ) {
			$text = $attributes['content'];
		}

		$tag         = 'h' . $level;
		$attr_string = $this->render_html_attributes( $attributes );

		return sprintf( '<%1$s%2$s>%3$s</%1$s>', $tag, $attr_string, $text );
	}

	/**
	 * The Gutenberg block name.
	 *
	 * @return string
	 */
	public function block_name(): string {
		return self::PREFIX . '/heading';
	}

	/**
	 * HTML tags this block handles.
	 *
	 * @return string[]
	 */
	public function html_tags(): array {
		return array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
	}

	/**
	 * Serialize an HTML heading back into a Gutenberg block comment.
	 *
	 * @param HtmlElementDto $element  The parsed HTML element data.
	 * @param BlockRegistry  $registry The registry (unused).
	 * @return string The Gutenberg block comment.
	 */
	public function serialize_from_html( HtmlElementDto $element, BlockRegistry $registry ): string {
		$level       = (int) substr( $element->tag, 1 );
		$level       = ( $level >= 1 && $level <= 6 ) ? $level : 2;
		$block_attrs = array( 'content' => $element->text_content );
		if ( 2 !== $level ) {
			$block_attrs['level'] = $level;
		}
		if ( ! empty( $element->attributes ) ) {
			$block_attrs['htmlAttributes'] = $element->attributes;
		}
		$attrs_json = ' ' . wp_json_encode( $block_attrs );
		return sprintf( "<!-- wp:%s%s /-->\n\n", $this->block_name(), $attrs_json );
	}
}

<?php
/**
 * Div Block — renders a simple <div> wrapper with optional CSS class.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks;

/**
 * Registers and renders the dg-interview/div block.
 */
class DivBlock implements Block {

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
					'className'  => array(
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
	 * @param string               $content    Inner blocks HTML.
	 * @return string
	 */
	public function render( array $attributes, string $content ): string {
		$class_name = '';
		if ( isset( $attributes['className'] ) && is_string( $attributes['className'] ) ) {
			$class_name = $attributes['className'];
		}
		$class_attr  = '' !== $class_name ? sprintf( ' class="%s"', esc_attr( $class_name ) ) : '';
		$attr_string = $this->render_html_attributes( $attributes );

		return sprintf( '<div%s%s>%s</div>', $class_attr, $attr_string, $content );
	}

	/**
	 * The Gutenberg block name.
	 *
	 * @return string
	 */
	public function block_name(): string {
		return self::PREFIX . '/div';
	}

	/**
	 * HTML tags this block handles.
	 *
	 * @return string[]
	 */
	public function html_tags(): array {
		return array( 'div' );
	}

	/**
	 * Serialize an HTML div back into a Gutenberg block comment.
	 *
	 * @param HtmlElementDto $element  The parsed HTML element data.
	 * @param BlockRegistry  $registry The registry, used to process inner blocks.
	 * @return string The Gutenberg block comment.
	 */
	public function serialize_from_html( HtmlElementDto $element, BlockRegistry $registry ): string {
		$inner_blocks = $registry->serialize_inner_html( $element->inner_html );

		$block_attrs = array();
		if ( ! empty( $element->attributes ) ) {
			$block_attrs['htmlAttributes'] = $element->attributes;
		}

		$attrs_json = ! empty( $block_attrs ) ? ' ' . wp_json_encode( $block_attrs ) : '';

		return sprintf(
			"<!-- wp:%1\$s%2\$s -->\n%3\$s\n<!-- /wp:%1\$s -->\n\n",
			$this->block_name(),
			$attrs_json,
			$inner_blocks
		);
	}
}

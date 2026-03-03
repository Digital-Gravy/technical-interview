<?php
/**
 * Trait for rendering the generic HTML attributes bag.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks;

/**
 * Shared logic for rendering the 'htmlAttributes' bag into an HTML attribute string.
 */
trait RendersHtmlAttributes {

	/**
	 * Build an HTML attribute string from the block's 'htmlAttributes' property.
	 *
	 * @param array<string, mixed> $block_attributes The full block attributes array.
	 * @return string The HTML attribute string (e.g. ' id="foo" class="bar"'), or empty.
	 */
	private function render_html_attributes( array $block_attributes ): string {
		if ( ! isset( $block_attributes['htmlAttributes'] ) || ! is_array( $block_attributes['htmlAttributes'] ) ) {
			return '';
		}

		$result = '';
		foreach ( $block_attributes['htmlAttributes'] as $name => $value ) {
			if ( ! is_string( $name ) || ! is_string( $value ) ) {
				continue;
			}
			$result .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}

		return $result;
	}
}

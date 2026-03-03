<?php
/**
 * HtmlElementDto — data transfer object for a parsed HTML element.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks;

/**
 * Immutable DTO carrying the data extracted from a parsed HTML element.
 */
class HtmlElementDto {

	/**
	 * Constructor.
	 *
	 * @param string               $tag          The HTML tag name (e.g. 'p', 'h3', 'div').
	 * @param string               $inner_html   The element's inner HTML (or outer HTML for container blocks).
	 * @param string               $text_content The element's text content.
	 * @param array<string,string> $attributes   HTML attributes as name → value pairs.
	 */
	public function __construct(
		public readonly string $tag,
		public readonly string $inner_html,
		public readonly string $text_content,
		public readonly array $attributes = array(),
	) {}
}

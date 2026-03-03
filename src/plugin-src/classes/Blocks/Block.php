<?php
/**
 * Block interface — contract for custom Gutenberg blocks.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks;

/**
 * Interface for custom blocks that can render and serialize back from HTML.
 */
interface Block {

	/**
	 * The block namespace prefix shared by all blocks in this plugin.
	 */
	public const PREFIX = 'dg-interview';

	/**
	 * The Gutenberg block name (e.g. 'dg-interview/heading').
	 *
	 * @return string
	 */
	public function block_name(): string;

	/**
	 * HTML tags this block handles (e.g. ['h1', 'h2', ..., 'h6']).
	 *
	 * @return string[]
	 */
	public function html_tags(): array;

	/**
	 * Serialize an HTML element back into a Gutenberg block comment.
	 *
	 * @param HtmlElementDto $element  The parsed HTML element data.
	 * @param BlockRegistry  $registry The registry, for container blocks that need to process children.
	 * @return string The Gutenberg block comment string.
	 */
	public function serialize_from_html( HtmlElementDto $element, BlockRegistry $registry ): string;
}

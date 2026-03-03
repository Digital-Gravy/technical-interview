<?php
/**
 * FakeBlock — test double for BlockRegistry tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks\Tests;

use DgInterview\Blocks\Block;
use DgInterview\Blocks\BlockRegistry;
use DgInterview\Blocks\HtmlElementDto;

/**
 * A fake block for testing the registry in isolation.
 */
class FakeBlock implements Block {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The HTML tags.
	 *
	 * @var string[]
	 */
	private array $tags;

	/**
	 * The serialized output.
	 *
	 * @var string
	 */
	private string $output;

	/**
	 * Constructor.
	 *
	 * @param string   $name   Block name.
	 * @param string[] $tags   HTML tags.
	 * @param string   $output Fixed serialized output.
	 */
	public function __construct( string $name, array $tags, string $output = "<!-- fake /-->\n\n" ) {
		$this->name   = $name;
		$this->tags   = $tags;
		$this->output = $output;
	}

	/**
	 * Block name.
	 *
	 * @return string
	 */
	public function block_name(): string {
		return $this->name;
	}

	/**
	 * HTML tags.
	 *
	 * @return string[]
	 */
	public function html_tags(): array {
		return $this->tags;
	}

	/**
	 * Serialize from HTML.
	 *
	 * @param HtmlElementDto $element  The parsed HTML element data.
	 * @param BlockRegistry  $registry Registry.
	 * @return string
	 */
	public function serialize_from_html( HtmlElementDto $element, BlockRegistry $registry ): string {
		return $this->output;
	}
}

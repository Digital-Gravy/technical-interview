<?php
/**
 * Block integration tests.
 *
 * Tests interactions between blocks: nested serialization and cross-block
 * round-trips (serialize → do_blocks → render).
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks\Tests;

use DgInterview\Blocks\BlockRegistry;
use DgInterview\Blocks\DivBlock;
use DgInterview\Blocks\HeadingBlock;
use DgInterview\Blocks\HtmlElementDto;
use WP_UnitTestCase;

/**
 * Integration tests for cross-block interactions.
 */
class BlockIntegrationTest extends WP_UnitTestCase {

	/**
	 * Registry with all blocks registered.
	 *
	 * @var BlockRegistry
	 */
	private BlockRegistry $registry;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->registry = new BlockRegistry();
		$this->registry->register( new HeadingBlock() );
		$this->registry->register( new DivBlock() );
	}

	/**
	 * Heading inside div renders correctly after serialize round-trip.
	 *
	 * @test
	 */
	public function heading_inside_div_renders_correctly_after_serialize_round_trip(): void {
		$div           = new DivBlock();
		$element       = new HtmlElementDto( 'div', '<h3>Section</h3>', 'Section' );
		$block_comment = $div->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '<h3>Section</h3>', do_blocks( $block_comment ) );
	}

	/**
	 * Heading level change persists after serialize round-trip through div.
	 *
	 * @test
	 */
	public function heading_level_change_persists_after_serialize_round_trip_through_div(): void {
		$div           = new DivBlock();
		$element       = new HtmlElementDto( 'div', '<h1>Title</h1>', 'Title' );
		$block_comment = $div->serialize_from_html( $element, $this->registry );
		$rendered      = do_blocks( $block_comment );

		$this->assertStringContainsString( '<h1>Title</h1>', $rendered );
		$this->assertStringNotContainsString( '<h3>', $rendered );
	}
}

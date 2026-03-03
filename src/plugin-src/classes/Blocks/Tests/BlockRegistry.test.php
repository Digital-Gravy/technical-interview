<?php
/**
 * BlockRegistry tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks\Tests;

use DgInterview\Blocks\BlockRegistry;
use WP_UnitTestCase;

/**
 * Tests for the BlockRegistry class.
 */
class BlockRegistryTest extends WP_UnitTestCase {

	// ── get_by_block_name ──────────────────────────────────────────────

	/**
	 * Returns null when block name is not registered.
	 *
	 * @test
	 */
	public function returns_null_when_block_name_is_not_registered(): void {
		$registry = new BlockRegistry();

		$this->assertNull( $registry->get_by_block_name( 'test/nonexistent' ) );
	}

	/**
	 * Returns block when looking up by block name.
	 *
	 * @test
	 */
	public function returns_block_when_looking_up_by_block_name(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/alpha', array( 'div' ) ) );

		$this->assertSame( 'test/alpha', $registry->get_by_block_name( 'test/alpha' )->block_name() );
	}

	/**
	 * Returns correct block when multiple blocks registered.
	 *
	 * @test
	 */
	public function returns_correct_block_when_multiple_blocks_registered(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/alpha', array( 'div' ) ) );
		$registry->register( new FakeBlock( 'test/beta', array( 'span' ) ) );

		$this->assertSame( 'test/alpha', $registry->get_by_block_name( 'test/alpha' )->block_name() );
		$this->assertSame( 'test/beta', $registry->get_by_block_name( 'test/beta' )->block_name() );
	}

	/**
	 * Last registration wins when same block name registered twice.
	 *
	 * @test
	 */
	public function last_registration_wins_when_same_block_name_registered_twice(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/alpha', array( 'div' ) ) );
		$registry->register( new FakeBlock( 'test/alpha', array( 'span' ) ) );

		$this->assertSame( array( 'span' ), $registry->get_by_block_name( 'test/alpha' )->html_tags() );
	}

	// ── get_by_tag ─────────────────────────────────────────────────────

	/**
	 * Returns null when HTML tag is not registered.
	 *
	 * @test
	 */
	public function returns_null_when_html_tag_is_not_registered(): void {
		$registry = new BlockRegistry();

		$this->assertNull( $registry->get_by_tag( 'span' ) );
	}

	/**
	 * Returns block when looking up by HTML tag.
	 *
	 * @test
	 */
	public function returns_block_when_looking_up_by_html_tag(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/alpha', array( 'span', 'em' ) ) );

		$this->assertSame( 'test/alpha', $registry->get_by_tag( 'em' )->block_name() );
	}

	/**
	 * Last registration wins when same tag registered twice.
	 *
	 * @test
	 */
	public function last_registration_wins_when_same_tag_registered_twice(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/alpha', array( 'p' ) ) );
		$registry->register( new FakeBlock( 'test/beta', array( 'p' ) ) );

		$this->assertSame( 'test/beta', $registry->get_by_tag( 'p' )->block_name() );
	}

	// ── serialize_inner_html ───────────────────────────────────────────

	/**
	 * Returns empty string when serializing empty inner HTML.
	 *
	 * @test
	 */
	public function returns_empty_string_when_serializing_empty_inner_html(): void {
		$registry = new BlockRegistry();

		$result = $registry->serialize_inner_html( '' );

		$this->assertSame( '', $result );
	}

	/**
	 * Delegates to registered block when serializing inner HTML.
	 *
	 * @test
	 */
	public function delegates_to_registered_block_when_serializing_inner_html(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/alpha', array( 'p' ), "<!-- test-output /-->\n\n" ) );

		$result = $registry->serialize_inner_html( '<p>Hello</p>' );

		$this->assertStringContainsString( '<!-- test-output /-->', $result );
	}

	/**
	 * Serializes multiple elements when inner HTML has multiple children.
	 *
	 * @test
	 */
	public function serializes_multiple_elements_when_inner_html_has_multiple_children(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/para', array( 'p' ), "<!-- para /-->\n\n" ) );
		$registry->register( new FakeBlock( 'test/heading', array( 'h1' ), "<!-- heading /-->\n\n" ) );

		$result = $registry->serialize_inner_html( '<h1>Title</h1><p>Body</p>' );

		$this->assertStringContainsString( '<!-- heading /-->', $result );
		$this->assertStringContainsString( '<!-- para /-->', $result );
	}

	/**
	 * Passes through unknown elements when serializing inner HTML.
	 *
	 * @test
	 */
	public function passes_through_unknown_elements_when_serializing_inner_html(): void {
		$registry = new BlockRegistry();

		$result = $registry->serialize_inner_html( '<span>Unknown</span>' );

		$this->assertStringContainsString( '<span>Unknown</span>', $result );
	}

	/**
	 * Preserves original core block when data-dg-block-path maps to original block.
	 *
	 * @test
	 */
	public function preserves_original_core_block_when_data_dg_block_path_is_present(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/paragraph', array( 'p' ), "<!-- custom-para /-->\n\n" ) );

		$original = parse_blocks( "<!-- wp:paragraph -->\n<p>Native paragraph</p>\n<!-- /wp:paragraph -->" );
		$registry->set_original_blocks_by_path(
			array(
				'0.0' => $original[0],
			)
		);

		$result = $registry->serialize_inner_html(
			'<p data-dg-block-name="core/paragraph" data-dg-block-path="0.0">Native paragraph</p>'
		);

		$this->assertStringContainsString( '<!-- wp:paragraph -->', $result );
		$this->assertStringNotContainsString( '<!-- custom-para /-->', $result );
	}

	/**
	 * Uses block-name serializer when data-dg-block-name maps to registered block.
	 *
	 * @test
	 */
	public function uses_block_name_serializer_when_data_dg_block_name_matches_registered_block(): void {
		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'test/mapped', array( 'p' ), "<!-- mapped /-->\n\n" ) );
		$registry->register( new FakeBlock( 'test/tag-only', array( 'p' ), "<!-- tag-only /-->\n\n" ) );

		$result = $registry->serialize_inner_html(
			'<p data-dg-block-name="test/mapped" data-dg-block-path="0.0">Hello</p>'
		);

		$this->assertStringContainsString( '<!-- mapped /-->', $result );
		$this->assertStringNotContainsString( '<!-- tag-only /-->', $result );
	}

	/**
	 * Falls back to unknown block serialization and strips data attributes.
	 *
	 * @test
	 */
	public function falls_back_to_unknown_block_serialization_and_strips_data_attributes(): void {
		$registry = new BlockRegistry();

		$result = $registry->serialize_inner_html(
			'<blockquote data-dg-block-name="core/quote" data-dg-block-path="9.0" class="wp-block-quote"><p data-dg-block-name="core/paragraph" data-dg-block-path="9.0.0">This is a blockquote</p></blockquote>'
		);

		$this->assertStringContainsString( '<!-- wp:quote -->', $result );
		$this->assertStringContainsString( '<!-- /wp:quote -->', $result );
		$this->assertStringNotContainsString( 'data-dg-block-name', $result );
		$this->assertStringNotContainsString( 'data-dg-block-path', $result );
	}
}

<?php
/**
 * HeadingBlock tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks\Tests;

use DgInterview\Blocks\BlockRegistry;
use DgInterview\Blocks\HeadingBlock;
use DgInterview\Blocks\HtmlElementDto;
use WP_UnitTestCase;

/**
 * Tests for the HeadingBlock class.
 */
class HeadingBlockTest extends WP_UnitTestCase {

	/**
	 * The block instance.
	 *
	 * @var HeadingBlock
	 */
	private HeadingBlock $block;

	/**
	 * Empty registry for serialize_from_html() calls.
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
		$this->block    = new HeadingBlock();
		$this->registry = new BlockRegistry();
	}

	/**
	 * Render a heading with the given content and optional level.
	 *
	 * @param string $content The heading text.
	 * @param int    $level   The heading level (omit to use default).
	 * @return string The rendered HTML.
	 */
	private function render_heading( string $content = '', ?int $level = null ): string {
		$attrs = array( 'content' => $content );
		if ( null !== $level ) {
			$attrs['level'] = $level;
		}
		return $this->block->render( $attrs, '' );
	}

	// ── Render tests ───────────────────────────────────────────────────

	/**
	 * Heading renders empty when no content attribute is set.
	 *
	 * @test
	 */
	public function heading_renders_empty_when_no_content_attribute(): void {
		$this->assertSame( '<h2></h2>', $this->render_heading() );
	}

	/**
	 * Heading renders as h2 when no level specified.
	 *
	 * @test
	 */
	public function heading_renders_as_h2_when_no_level_specified(): void {
		$this->assertSame( '<h2>Hello</h2>', $this->render_heading( 'Hello' ) );
	}

	/**
	 * Heading renders with custom level when level is provided.
	 *
	 * @test
	 */
	public function heading_renders_with_custom_level_when_level_is_provided(): void {
		$this->assertSame( '<h1>Title</h1>', $this->render_heading( 'Title', 1 ) );
	}

	/**
	 * Heading renders content from attribute when content is provided.
	 *
	 * @test
	 */
	public function heading_renders_content_from_attribute_when_content_is_provided(): void {
		$this->assertSame( '<h3>Section Title</h3>', $this->render_heading( 'Section Title', 3 ) );
	}

	/**
	 * Data provider for valid heading levels.
	 *
	 * @return array<string, array{int}>
	 */
	public function valid_levels(): array {
		return array(
			'h1' => array( 1 ),
			'h2' => array( 2 ),
			'h3' => array( 3 ),
			'h4' => array( 4 ),
			'h5' => array( 5 ),
			'h6' => array( 6 ),
		);
	}

	/**
	 * Heading renders correct tag when valid level is used.
	 *
	 * @test
	 * @dataProvider valid_levels
	 *
	 * @param int $level The heading level.
	 */
	public function heading_renders_correct_tag_when_valid_level_is_used( int $level ): void {
		$this->assertSame( "<h{$level}>Test</h{$level}>", $this->render_heading( 'Test', $level ) );
	}

	/**
	 * Data provider for out-of-range heading levels.
	 *
	 * @return array<string, array{int}>
	 */
	public function out_of_range_levels(): array {
		return array(
			'zero'      => array( 0 ),
			'above max' => array( 7 ),
		);
	}

	/**
	 * Heading defaults to h2 when level is out of range.
	 *
	 * @test
	 * @dataProvider out_of_range_levels
	 *
	 * @param int $level The out-of-range level.
	 */
	public function heading_defaults_to_h2_when_level_is_out_of_range( int $level ): void {
		$this->assertSame( '<h2>Fallback</h2>', $this->render_heading( 'Fallback', $level ) );
	}

	/**
	 * Heading renders content with special characters when content has HTML entities.
	 *
	 * @test
	 */
	public function heading_renders_content_with_special_characters_when_content_has_html_entities(): void {
		$this->assertSame( '<h2>Tom &amp; Jerry</h2>', $this->render_heading( 'Tom &amp; Jerry' ) );
	}

	// ── Serialization tests ────────────────────────────────────────────

	/**
	 * Serializes empty content when text content is empty.
	 *
	 * @test
	 */
	public function serializes_empty_content_when_text_is_empty(): void {
		$element = new HtmlElementDto( 'h1', '<h1></h1>', '' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '"content":""', $result );
	}

	/**
	 * Serializes content and level as JSON attributes when heading has non-default level.
	 *
	 * @test
	 */
	public function serializes_content_and_level_when_non_default_level(): void {
		$element = new HtmlElementDto( 'h1', '<h1>Title</h1>', 'Title' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '<!-- wp:dg-interview/heading {"content":"Title","level":1} /-->', $result );
	}

	/**
	 * Omits level attribute when heading is h2.
	 *
	 * @test
	 */
	public function omits_level_attribute_when_h2(): void {
		$element = new HtmlElementDto( 'h2', '<h2>Subtitle</h2>', 'Subtitle' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringNotContainsString( '"level"', $result );
		$this->assertStringContainsString( '"content":"Subtitle"', $result );
	}

	/**
	 * Serializes as self-closing block comment when heading is valid.
	 *
	 * @test
	 */
	public function serializes_as_self_closing_block_comment_when_valid(): void {
		$element = new HtmlElementDto( 'h3', '<h3>Section</h3>', 'Section' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '/-->', $result );
		$this->assertStringNotContainsString( '<!-- /wp:dg-interview/heading -->', $result );
	}

	// ── HTML attribute serialization tests ────────────────────────────

	/**
	 * Serializes html attributes when element has id.
	 *
	 * @test
	 */
	public function serializes_html_attributes_when_element_has_id(): void {
		$element = new HtmlElementDto( 'h1', '<h1>Title</h1>', 'Title', array( 'id' => 'main-title' ) );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '"htmlAttributes":{"id":"main-title"}', $result );
	}

	/**
	 * Omits attributes key when no html attributes.
	 *
	 * @test
	 */
	public function omits_attributes_key_when_no_html_attributes(): void {
		$element = new HtmlElementDto( 'h1', '<h1>Title</h1>', 'Title' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringNotContainsString( '"htmlAttributes"', $result );
	}

	// ── HTML attribute render tests ───────────────────────────────────

	/**
	 * Renders id attribute when attributes has id.
	 *
	 * @test
	 */
	public function renders_id_attribute_when_attributes_has_id(): void {
		$result = $this->block->render(
			array(
				'content' => 'Title',
				'level' => 1,
				'htmlAttributes' => array( 'id' => 'main-title' ),
			),
			''
		);

		$this->assertSame( '<h1 id="main-title">Title</h1>', $result );
	}

	/**
	 * Renders no extra attributes when attributes is empty.
	 *
	 * @test
	 */
	public function renders_no_extra_attributes_when_attributes_is_empty(): void {
		$result = $this->block->render(
			array(
				'content' => 'Title',
				'htmlAttributes' => array(),
			),
			''
		);

		$this->assertSame( '<h2>Title</h2>', $result );
	}

	// ── Round-trip tests (serialize → render) ─────────────────────────

	/**
	 * Heading renders correctly after serialize round-trip.
	 *
	 * @test
	 */
	public function heading_renders_correctly_after_serialize_round_trip(): void {
		$element       = new HtmlElementDto( 'h1', '<h1>Hello</h1>', 'Hello' );
		$block_comment = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '<h1>Hello</h1>', do_blocks( $block_comment ) );
	}

	/**
	 * Heading does not double-wrap after serialize round-trip.
	 *
	 * @test
	 */
	public function heading_does_not_double_wrap_after_serialize_round_trip(): void {
		$element       = new HtmlElementDto( 'h1', '<h1>Title</h1>', 'Title' );
		$block_comment = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertSame( 1, substr_count( do_blocks( $block_comment ), '<h1>' ) );
	}

	/**
	 * Preserves id after serialize round-trip.
	 *
	 * @test
	 */
	public function preserves_id_after_serialize_round_trip(): void {
		$element       = new HtmlElementDto( 'h1', '<h1>Title</h1>', 'Title', array( 'id' => 'main' ) );
		$block_comment = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( 'id="main"', do_blocks( $block_comment ) );
	}

	// ── Dynamic content tests ─────────────────────────────────────────

	/**
	 * Renders resolved expression when content has dynamic data.
	 *
	 * @test
	 */
	public function renders_resolved_expression_when_content_has_dynamic_data(): void {
		$post_id = self::factory()->post->create(
			array( 'post_title' => 'Dynamic Heading' )
		);

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.
		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );

		$block_comment = '<!-- wp:dg-interview/heading {"content":"Title: {this.title}"} /-->';
		$result        = do_blocks( $block_comment );

		$this->assertStringContainsString( 'Title: Dynamic Heading', $result );

		wp_reset_postdata();
	}

	/**
	 * Preserves raw expression in render output when called directly.
	 *
	 * Block render callbacks must NOT resolve expressions — resolution
	 * happens in the render_block filter on the frontend only. This
	 * prevents expressions from being destroyed when the builder loads
	 * rendered content via the REST API.
	 *
	 * @test
	 */
	public function preserves_raw_expression_when_render_called_directly(): void {
		$result = $this->block->render(
			array( 'content' => '{this.title}' ),
			''
		);

		$this->assertStringContainsString( '{this.title}', $result );
	}
}

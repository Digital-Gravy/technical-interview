<?php
/**
 * ParagraphBlock tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks\Tests;

use DgInterview\Blocks\BlockRegistry;
use DgInterview\Blocks\HtmlElementDto;
use DgInterview\Blocks\ParagraphBlock;
use WP_UnitTestCase;

/**
 * Tests for the ParagraphBlock class.
 */
class ParagraphBlockTest extends WP_UnitTestCase {

	/**
	 * The block instance.
	 *
	 * @var ParagraphBlock
	 */
	private ParagraphBlock $block;

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
		$this->block    = new ParagraphBlock();
		$this->registry = new BlockRegistry();
	}

	// ── Render tests ───────────────────────────────────────────────────

	/**
	 * Paragraph renders empty when no content attribute is set.
	 *
	 * @test
	 */
	public function paragraph_renders_empty_when_no_content_attribute(): void {
		$result = $this->block->render( array(), '' );

		$this->assertSame( '<p></p>', $result );
	}

	/**
	 * Paragraph renders content when content is provided.
	 *
	 * @test
	 */
	public function paragraph_renders_content_when_content_is_provided(): void {
		$result = $this->block->render( array( 'content' => 'Hello world' ), '' );

		$this->assertSame( '<p>Hello world</p>', $result );
	}

	// ── Serialization tests ────────────────────────────────────────────

	/**
	 * Serializes empty content when text is empty.
	 *
	 * @test
	 */
	public function serializes_empty_content_when_text_is_empty(): void {
		$element = new HtmlElementDto( 'p', '<p></p>', '' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '"content":""', $result );
	}

	/**
	 * Serializes content as JSON attribute when content is provided.
	 *
	 * @test
	 */
	public function serializes_content_as_json_attribute_when_content_is_provided(): void {
		$element = new HtmlElementDto( 'p', '<p>Hello</p>', 'Hello' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '<!-- wp:dg-interview/paragraph {"content":"Hello"} /-->', $result );
	}

	/**
	 * Serializes as self-closing block comment when valid.
	 *
	 * @test
	 */
	public function serializes_as_self_closing_block_comment_when_valid(): void {
		$element = new HtmlElementDto( 'p', '<p>Test</p>', 'Test' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '/-->', $result );
		$this->assertStringNotContainsString( '<!-- /wp:dg-interview/paragraph -->', $result );
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
				'content' => 'Hello',
				'htmlAttributes' => array( 'id' => 'intro' ),
			),
			''
		);

		$this->assertSame( '<p id="intro">Hello</p>', $result );
	}

	/**
	 * Renders class attribute when attributes has class.
	 *
	 * @test
	 */
	public function renders_class_attribute_when_attributes_has_class(): void {
		$result = $this->block->render(
			array(
				'content' => 'Hello',
				'htmlAttributes' => array( 'class' => 'highlight' ),
			),
			''
		);

		$this->assertSame( '<p class="highlight">Hello</p>', $result );
	}

	/**
	 * Renders data attribute when attributes has data attr.
	 *
	 * @test
	 */
	public function renders_data_attribute_when_attributes_has_data_attr(): void {
		$result = $this->block->render(
			array(
				'content' => 'Hello',
				'htmlAttributes' => array( 'data-section' => 'hero' ),
			),
			''
		);

		$this->assertSame( '<p data-section="hero">Hello</p>', $result );
	}

	/**
	 * Renders multiple attributes when attributes has several.
	 *
	 * @test
	 */
	public function renders_multiple_attributes_when_attributes_has_several(): void {
		$result = $this->block->render(
			array(
				'content'    => 'Hello',
				'htmlAttributes' => array(
					'id'    => 'intro',
					'class' => 'highlight',
				),
			),
			''
		);

		$this->assertStringContainsString( 'id="intro"', $result );
		$this->assertStringContainsString( 'class="highlight"', $result );
	}

	/**
	 * Renders no extra attributes when attributes is empty.
	 *
	 * @test
	 */
	public function renders_no_extra_attributes_when_attributes_is_empty(): void {
		$result = $this->block->render(
			array(
				'content' => 'Hello',
				'htmlAttributes' => array(),
			),
			''
		);

		$this->assertSame( '<p>Hello</p>', $result );
	}

	/**
	 * Escapes attribute values when value has special chars.
	 *
	 * @test
	 */
	public function escapes_attribute_values_when_value_has_special_chars(): void {
		$result = $this->block->render(
			array(
				'content' => 'Hello',
				'htmlAttributes' => array( 'data-x' => '"><script>' ),
			),
			''
		);

		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringContainsString( 'data-x="', $result );
	}

	// ── HTML attribute serialization tests ────────────────────────────

	/**
	 * Serializes html attributes when element has id.
	 *
	 * @test
	 */
	public function serializes_html_attributes_when_element_has_id(): void {
		$element = new HtmlElementDto( 'p', '<p>Hello</p>', 'Hello', array( 'id' => 'test' ) );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '"htmlAttributes":{"id":"test"}', $result );
	}

	/**
	 * Omits attributes key when no html attributes.
	 *
	 * @test
	 */
	public function omits_attributes_key_when_no_html_attributes(): void {
		$element = new HtmlElementDto( 'p', '<p>Hello</p>', 'Hello' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringNotContainsString( '"htmlAttributes"', $result );
	}

	// ── Round-trip tests (serialize → render) ─────────────────────────

	/**
	 * Paragraph renders correctly after serialize round-trip.
	 *
	 * @test
	 */
	public function paragraph_renders_correctly_after_serialize_round_trip(): void {
		$element       = new HtmlElementDto( 'p', '<p>Hello world</p>', 'Hello world' );
		$block_comment = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '<p>Hello world</p>', do_blocks( $block_comment ) );
	}

	/**
	 * Preserves id after serialize round-trip.
	 *
	 * @test
	 */
	public function preserves_id_after_serialize_round_trip(): void {
		$element       = new HtmlElementDto( 'p', '<p>Hello</p>', 'Hello', array( 'id' => 'intro' ) );
		$block_comment = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( 'id="intro"', do_blocks( $block_comment ) );
	}

	// ── Dynamic content tests ─────────────────────────────────────────

	/**
	 * Renders resolved expression when content has dynamic data.
	 *
	 * @test
	 */
	public function renders_resolved_expression_when_content_has_dynamic_data(): void {
		$post_id = self::factory()->post->create(
			array( 'post_title' => 'Dynamic Paragraph' )
		);

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.
		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );

		$block_comment = '<!-- wp:dg-interview/paragraph {"content":"Post: {this.title}"} /-->';
		$result        = do_blocks( $block_comment );

		$this->assertStringContainsString( 'Post: Dynamic Paragraph', $result );

		wp_reset_postdata();
	}

	/**
	 * Preserves raw expression in render output when called directly.
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

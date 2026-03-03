<?php
/**
 * DivBlock tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks\Tests;

use DgInterview\Blocks\BlockRegistry;
use DgInterview\Blocks\DivBlock;
use DgInterview\Blocks\HeadingBlock;
use DgInterview\Blocks\HtmlElementDto;
use DgInterview\Blocks\ParagraphBlock;
use WP_UnitTestCase;

/**
 * Tests for the DivBlock class.
 */
class DivBlockTest extends WP_UnitTestCase {

	/**
	 * The block instance.
	 *
	 * @var DivBlock
	 */
	private DivBlock $block;

	/**
	 * Registry with child blocks registered for serialization tests.
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
		$this->block    = new DivBlock();
		$this->registry = new BlockRegistry();
		$this->registry->register( new ParagraphBlock() );
		$this->registry->register( new HeadingBlock() );
	}

	// ── Render tests ───────────────────────────────────────────────────

	/**
	 * Renders empty div when no inner blocks.
	 *
	 * @test
	 */
	public function renders_empty_div_when_no_inner_blocks(): void {
		$result = $this->block->render( array(), '' );

		$this->assertSame( '<div></div>', $result );
	}

	/**
	 * Renders single child when one inner block.
	 *
	 * @test
	 */
	public function renders_single_child_when_one_inner_block(): void {
		$result = $this->block->render( array(), '<p>Hello</p>' );

		$this->assertSame( '<div><p>Hello</p></div>', $result );
	}

	/**
	 * Renders multiple children when multiple inner blocks.
	 *
	 * @test
	 */
	public function renders_multiple_children_when_multiple_inner_blocks(): void {
		$result = $this->block->render( array(), '<p>One</p><p>Two</p>' );

		$this->assertSame( '<div><p>One</p><p>Two</p></div>', $result );
	}

	/**
	 * Renders class attribute when className is set.
	 *
	 * @test
	 */
	public function renders_class_attribute_when_className_is_set(): void {
		$result = $this->block->render( array( 'className' => 'my-class' ), '' );

		$this->assertSame( '<div class="my-class"></div>', $result );
	}

	/**
	 * Renders no class attribute when className is empty.
	 *
	 * @test
	 */
	public function renders_no_class_attribute_when_className_is_empty(): void {
		$result = $this->block->render( array( 'className' => '' ), '' );

		$this->assertSame( '<div></div>', $result );
	}

	// ── HTML attribute render tests ───────────────────────────────────

	/**
	 * Renders id attribute when attributes has id.
	 *
	 * @test
	 */
	public function renders_id_attribute_when_attributes_has_id(): void {
		$result = $this->block->render( array( 'htmlAttributes' => array( 'id' => 'wrapper' ) ), '' );

		$this->assertSame( '<div id="wrapper"></div>', $result );
	}

	/**
	 * Renders both className and html attributes when both are set.
	 *
	 * @test
	 */
	public function renders_both_className_and_html_attributes_when_both_set(): void {
		$result = $this->block->render(
			array(
				'className'  => 'my-class',
				'htmlAttributes' => array( 'id' => 'wrapper' ),
			),
			''
		);

		$this->assertStringContainsString( 'class="my-class"', $result );
		$this->assertStringContainsString( 'id="wrapper"', $result );
	}

	/**
	 * Renders no extra attributes when attributes is empty.
	 *
	 * @test
	 */
	public function renders_no_extra_attributes_when_attributes_is_empty(): void {
		$result = $this->block->render( array( 'htmlAttributes' => array() ), '' );

		$this->assertSame( '<div></div>', $result );
	}

	// ── Serialization tests ────────────────────────────────────────────

	/**
	 * Serializes empty div when no children.
	 *
	 * @test
	 */
	public function serializes_empty_div_when_no_children(): void {
		$element = new HtmlElementDto( 'div', '', '' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '<!-- wp:dg-interview/div -->', $result );
		$this->assertStringContainsString( '<!-- /wp:dg-interview/div -->', $result );
	}

	/**
	 * Serializes single child when one child element.
	 *
	 * @test
	 */
	public function serializes_single_child_when_one_child_element(): void {
		$element = new HtmlElementDto( 'div', '<p>Hello</p>', 'Hello' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '<!-- wp:dg-interview/paragraph {"content":"Hello"} /-->', $result );
	}

	/**
	 * Serializes multiple children when multiple child elements.
	 *
	 * @test
	 */
	public function serializes_multiple_children_when_multiple_child_elements(): void {
		$element = new HtmlElementDto( 'div', '<h1>Title</h1><p>Body</p>', 'TitleBody' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '<!-- wp:dg-interview/heading {"content":"Title","level":1} /-->', $result );
		$this->assertStringContainsString( '<!-- wp:dg-interview/paragraph {"content":"Body"} /-->', $result );
	}

	/**
	 * Serializes html attributes when element has id.
	 *
	 * @test
	 */
	public function serializes_html_attributes_when_element_has_id(): void {
		$element = new HtmlElementDto( 'div', '', '', array( 'id' => 'wrapper' ) );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringContainsString( '"htmlAttributes":{"id":"wrapper"}', $result );
	}

	/**
	 * Omits attributes key when no html attributes.
	 *
	 * @test
	 */
	public function omits_attributes_key_when_no_html_attributes(): void {
		$element = new HtmlElementDto( 'div', '', '' );
		$result  = $this->block->serialize_from_html( $element, $this->registry );

		$this->assertStringNotContainsString( '"htmlAttributes"', $result );
	}
}

<?php
/**
 * BlockAnnotator tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks\Tests;

use DgInterview\Blocks\BlockAnnotator;
use WP_UnitTestCase;

/**
 * Tests for the BlockAnnotator class.
 *
 * Runs in separate processes because tests define the REST_REQUEST constant,
 * which cannot be undefined once set and would pollute other test classes.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BlockAnnotatorTest extends WP_UnitTestCase {

	/**
	 * Ensure REST_REQUEST is defined for tests that need it.
	 *
	 * @return void
	 */
	private function ensure_rest_request_defined(): void {
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}
	}

	/**
	 * Returns content unchanged when not a REST request.
	 *
	 * @test
	 */
	public function returns_content_unchanged_when_not_a_rest_request(): void {
		$annotator = new BlockAnnotator();

		$result = $annotator->annotate_block(
			'<p>Hello</p>',
			array( 'blockName' => 'core/paragraph' )
		);

		$this->assertSame( '<p>Hello</p>', $result );
	}

	/**
	 * Adds block name attribute when REST request.
	 *
	 * @test
	 */
	public function adds_block_name_attribute_when_rest_request(): void {
		$this->ensure_rest_request_defined();
		$annotator = new BlockAnnotator();

		$annotator->track_depth_before_render( null, array( 'blockName' => 'core/paragraph' ) );

		$result = $annotator->annotate_block(
			'<p>Hello</p>',
			array( 'blockName' => 'core/paragraph' )
		);

		$this->assertStringContainsString( 'data-dg-block-name="core/paragraph"', $result );
	}

	/**
	 * Adds block path for top level blocks when REST request.
	 *
	 * @test
	 */
	public function adds_block_path_for_top_level_blocks_when_rest_request(): void {
		$this->ensure_rest_request_defined();
		$annotator = new BlockAnnotator();

		$annotator->track_depth_before_render( null, array( 'blockName' => 'core/paragraph' ) );
		$result = $annotator->annotate_block(
			'<p>First</p>',
			array( 'blockName' => 'core/paragraph' )
		);

		$this->assertStringContainsString( 'data-dg-block-path="0"', $result );
		$this->assertStringNotContainsString( 'data-dg-block-index', $result );
	}

	/**
	 * Increments block path across multiple top level blocks.
	 *
	 * @test
	 */
	public function increments_block_path_across_multiple_top_level_blocks(): void {
		$this->ensure_rest_request_defined();
		$annotator = new BlockAnnotator();

		$annotator->track_depth_before_render( null, array( 'blockName' => 'core/paragraph' ) );
		$annotator->annotate_block( '<p>First</p>', array( 'blockName' => 'core/paragraph' ) );

		$annotator->track_depth_before_render( null, array( 'blockName' => 'core/paragraph' ) );
		$result = $annotator->annotate_block( '<p>Second</p>', array( 'blockName' => 'core/paragraph' ) );

		$this->assertStringContainsString( 'data-dg-block-path="1"', $result );
		$this->assertStringNotContainsString( 'data-dg-block-index', $result );
	}

	/**
	 * Does not emit block index for nested blocks.
	 *
	 * @test
	 */
	public function does_not_emit_block_index_for_nested_blocks(): void {
		$this->ensure_rest_request_defined();
		$annotator = new BlockAnnotator();

		// Outer block enters.
		$annotator->track_depth_before_render( null, array( 'blockName' => 'dg-interview/div' ) );
		// Inner block enters.
		$annotator->track_depth_before_render( null, array( 'blockName' => 'core/paragraph' ) );

		// Inner block renders (depth goes from 2 to 1 — not top-level).
		$result = $annotator->annotate_block(
			'<p>Nested</p>',
			array( 'blockName' => 'core/paragraph' )
		);

		$this->assertStringContainsString( 'data-dg-block-name="core/paragraph"', $result );
		$this->assertStringNotContainsString( 'data-dg-block-index', $result );
	}

	/**
	 * Adds nested block path for nested blocks.
	 *
	 * @test
	 */
	public function adds_nested_block_path_for_nested_blocks(): void {
		$this->ensure_rest_request_defined();
		$annotator = new BlockAnnotator();

		// Outer block enters.
		$annotator->track_depth_before_render( null, array( 'blockName' => 'dg-interview/div' ) );
		// Inner block enters.
		$annotator->track_depth_before_render( null, array( 'blockName' => 'core/paragraph' ) );

		$inner_result = $annotator->annotate_block(
			'<p>Nested</p>',
			array( 'blockName' => 'core/paragraph' )
		);
		$annotator->annotate_block(
			'<div><p>Nested</p></div>',
			array( 'blockName' => 'dg-interview/div' )
		);

		$this->assertStringContainsString( 'data-dg-block-path="0.0"', $inner_result );
	}

	/**
	 * Annotates first element even when content starts with leading whitespace.
	 *
	 * @test
	 */
	public function annotates_first_element_when_content_has_leading_whitespace(): void {
		$this->ensure_rest_request_defined();
		$annotator = new BlockAnnotator();

		$annotator->track_depth_before_render( null, array( 'blockName' => 'core/quote' ) );
		$result = $annotator->annotate_block(
			"\n\t<blockquote class=\"wp-block-quote\"><p>Antani</p></blockquote>",
			array( 'blockName' => 'core/quote' )
		);

		$this->assertStringContainsString( 'data-dg-block-name="core/quote"', $result );
		$this->assertStringContainsString( 'data-dg-block-path="0"', $result );
		$this->assertStringNotContainsString( 'data-dg-block-index', $result );
	}

	/**
	 * Returns content unchanged when block name is empty.
	 *
	 * @test
	 */
	public function returns_content_unchanged_when_block_name_is_empty(): void {
		$this->ensure_rest_request_defined();
		$annotator = new BlockAnnotator();

		$annotator->track_depth_before_render( null, array( 'blockName' => null ) );
		$result = $annotator->annotate_block( "\n\n", array( 'blockName' => null ) );

		$this->assertSame( "\n\n", $result );
	}
}

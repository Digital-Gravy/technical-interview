<?php
/**
 * BuilderSaveRoute integration tests.
 *
 * Tests the save_content callback's orchestration: data-attribute-driven matching
 * of HTML elements to original blocks, native block passthrough, tag-based
 * fallback for new elements, and error handling.
 *
 * Block-specific serialization and round-trip logic is tested in each block's
 * own test file.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Builder\Tests;

use DgInterview\Blocks\BlockRegistry;
use DgInterview\Blocks\DivBlock;
use DgInterview\Blocks\Tests\FakeBlock;
use DgInterview\Builder\BuilderSaveRoute;
use WP_UnitTestCase;

/**
 * Integration tests for the BuilderSaveRoute class.
 */
class BuilderSaveRouteTest extends WP_UnitTestCase {

	/**
	 * The route under test.
	 *
	 * @var BuilderSaveRoute
	 */
	private BuilderSaveRoute $route;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$registry = new BlockRegistry();
		$registry->register( new FakeBlock( 'dg-interview/alpha', array( 'p' ), "<!-- alpha /-->\n\n" ) );
		$registry->register( new FakeBlock( 'dg-interview/beta', array( 'h1', 'h2', 'h3' ), "<!-- beta /-->\n\n" ) );
		$this->route = new BuilderSaveRoute( $registry );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
	}

	/**
	 * Create a page with original block content.
	 *
	 * @param string $original_blocks The original Gutenberg block markup.
	 * @return int The post ID.
	 */
	private function setup_page( string $original_blocks ): int {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => $original_blocks,
			)
		);
		return $post_id;
	}

	/**
	 * Save new HTML through the route.
	 *
	 * @param int    $post_id      The post ID.
	 * @param string $builder_html The HTML submitted from the builder.
	 */
	private function save_content( int $post_id, string $builder_html ) {
		$request = new \WP_REST_Request( 'POST', '/dg-interview/v1/posts/' . $post_id . '/content' );
		$request->set_param( 'id', $post_id );
		$request->set_param( 'html', $builder_html );
		$this->route->save_content( $request );
	}

	/**
	 * Saves empty content when builder HTML is empty.
	 *
	 * @test
	 */
	public function saves_empty_content_when_builder_html_is_empty(): void {
		$original_blocks = '<!-- wp:dg-interview/alpha {"content":"Hello"} /-->';
		$post_id         = $this->setup_page( $original_blocks );
		$builder_html    = '';

		$this->save_content( $post_id, $builder_html );

		$this->assertEmpty( trim( get_post( $post_id )->post_content ) );
	}

	/**
	 * Delegates to registered block serializer when data-dg-block-name matches.
	 *
	 * @test
	 */
	public function delegates_to_registered_block_serializer_when_data_attribute_matches(): void {
		$original_blocks = '<!-- wp:dg-interview/alpha {"content":"Hello"} /-->';
		$post_id         = $this->setup_page( $original_blocks );
		$builder_html    = '<p data-dg-block-name="dg-interview/alpha" data-dg-block-path="0">Hello</p>';

		$this->save_content( $post_id, $builder_html );

		$this->assertStringContainsString( '<!-- alpha /-->', get_post( $post_id )->post_content );
	}

	/**
	 * Native block passes through unchanged when data-dg-block-path is provided.
	 *
	 * @test
	 */
	public function native_block_passes_through_unchanged_via_data_dg_block_path(): void {
		$original_blocks = "<!-- wp:paragraph -->\n<p>Native paragraph</p>\n<!-- /wp:paragraph -->";
		$post_id         = $this->setup_page( $original_blocks );
		$builder_html    = '<p data-dg-block-name="core/paragraph" data-dg-block-path="0">Native paragraph</p>';

		$this->save_content( $post_id, $builder_html );

		$this->assertEquals( $original_blocks, get_post( $post_id )->post_content );
	}

	/**
	 * Nested native blocks are preserved via data-dg-block-path when saving containers.
	 *
	 * @test
	 */
	public function nested_native_blocks_are_preserved_via_data_dg_block_path(): void {
		$original_blocks = <<<HTML
<!-- wp:dg-interview/div -->
<!-- wp:quote -->
<blockquote class="wp-block-quote is-layout-flow wp-block-quote-is-layout-flow"><p>Antani</p></blockquote>
<!-- /wp:quote -->
<!-- /wp:dg-interview/div -->
HTML;

		$post_id      = $this->setup_page( $original_blocks );
		$builder_html = <<<HTML
<div data-dg-block-name="dg-interview/div" data-dg-block-path="0">
<blockquote data-dg-block-name="core/quote" data-dg-block-path="0.0" class="wp-block-quote is-layout-flow wp-block-quote-is-layout-flow"><p>Antani</p></blockquote>
</div>
HTML;

		$registry = new BlockRegistry();
		$registry->register( new DivBlock() );

		$route   = new BuilderSaveRoute( $registry );
		$request = new \WP_REST_Request( 'POST', '/dg-interview/v1/posts/' . $post_id . '/content' );
		$request->set_param( 'id', $post_id );
		$request->set_param( 'html', $builder_html );
		$route->save_content( $request );

		$content = get_post( $post_id )->post_content;
		$this->assertStringContainsString( '<!-- wp:quote -->', $content );
		$this->assertStringContainsString( '<!-- /wp:quote -->', $content );
		$this->assertStringNotContainsString( 'data-dg-block-path', $content );
	}

	/**
	 * Preserves block order when multiple blocks saved with data attributes.
	 *
	 * @test
	 */
	public function preserves_block_order_when_multiple_blocks_saved(): void {
		$original_blocks = "<!-- wp:dg-interview/beta {\"content\":\"First\"} /-->\n\n<!-- wp:dg-interview/alpha {\"content\":\"Second\"} /-->\n\n<!-- wp:dg-interview/beta {\"content\":\"Third\"} /-->";
		$post_id         = $this->setup_page( $original_blocks );
		$builder_html    = '<h1 data-dg-block-name="dg-interview/beta" data-dg-block-path="0">First</h1>'
			. "\n"
			. '<p data-dg-block-name="dg-interview/alpha" data-dg-block-path="1">Second</p>'
			. "\n"
			. '<h2 data-dg-block-name="dg-interview/beta" data-dg-block-path="2">Third</h2>';

		$this->save_content( $post_id, $builder_html );

		$this->assertMatchesRegularExpression(
			'/<!-- beta \/-->.*<!-- alpha \/-->.*<!-- beta \/-->/s',
			get_post( $post_id )->post_content
		);
	}

	/**
	 * Serializes new elements by tag when no original blocks exist.
	 *
	 * @test
	 */
	public function serializes_new_elements_by_tag_when_no_original_blocks_exist(): void {
		$post_id      = $this->setup_page( '' );
		$builder_html = '<p>Hello</p>';

		$this->save_content( $post_id, $builder_html );

		$this->assertStringContainsString( '<!-- alpha /-->', get_post( $post_id )->post_content );
	}

	/**
	 * Serializes new elements appended beyond original block count.
	 *
	 * @test
	 */
	public function serializes_new_elements_appended_beyond_original_blocks(): void {
		$original_blocks = '<!-- wp:dg-interview/alpha {"content":"Existing"} /-->';
		$post_id         = $this->setup_page( $original_blocks );
		$builder_html    = '<p data-dg-block-name="dg-interview/alpha" data-dg-block-path="0">Existing</p>'
			. "\n"
			. '<h1>Brand New</h1>';

		$this->save_content( $post_id, $builder_html );

		$content = get_post( $post_id )->post_content;
		$this->assertStringContainsString( '<!-- alpha /-->', $content );
		$this->assertStringContainsString( '<!-- beta /-->', $content );
	}

	/**
	 * Skips unknown new elements when no tag match exists.
	 *
	 * @test
	 */
	public function skips_unknown_new_elements_when_no_tag_match(): void {
		$post_id      = $this->setup_page( '' );
		$builder_html = '<span>Unknown</span>';

		$this->save_content( $post_id, $builder_html );

		$this->assertEmpty( trim( get_post( $post_id )->post_content ) );
	}

	/**
	 * Does not duplicate unconsumed original blocks when saving a subset.
	 *
	 * @test
	 */
	public function does_not_duplicate_unconsumed_blocks_when_saving_subset(): void {
		$original_blocks = "<!-- wp:dg-interview/alpha {\"content\":\"First\"} /-->\n\n<!-- wp:dg-interview/beta {\"content\":\"Second\"} /-->\n\n<!-- wp:dg-interview/alpha {\"content\":\"Third\"} /-->";
		$post_id         = $this->setup_page( $original_blocks );

		// Only save the second block (path 1), removing the first and third.
		$builder_html = '<h1 data-dg-block-name="dg-interview/beta" data-dg-block-path="1">Second</h1>';

		$this->save_content( $post_id, $builder_html );

		$content = get_post( $post_id )->post_content;
		$this->assertSame( 1, substr_count( $content, '<!-- beta /-->' ) );
		$this->assertStringNotContainsString( 'dg-interview/alpha', $content );
	}

	/**
	 * Returns error when post does not exist.
	 *
	 * @test
	 */
	public function returns_error_when_post_not_found(): void {
		$request = new \WP_REST_Request( 'POST', '/dg-interview/v1/posts/99999/content' );
		$request->set_param( 'id', 99999 );
		$request->set_param( 'html', '<p>test</p>' );

		$this->assertWPError( $this->route->save_content( $request ) );
	}
}

<?php
/**
 * BlockEditorIntegration tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Tests;

use DgInterview\BlockEditorIntegration;
use WP_UnitTestCase;

/**
 * Tests for the BlockEditorIntegration class.
 */
class BlockEditorIntegrationTest extends WP_UnitTestCase {

	/**
	 * Prepends dg-interview category when registering block category.
	 *
	 * @test
	 */
	public function prepends_dg_interview_category_when_registering_block_category(): void {
		$integration = new BlockEditorIntegration();
		$existing    = array(
			array(
				'slug'  => 'text',
				'title' => 'Text',
			),
		);

		$result = $integration->register_block_category( $existing );

		$this->assertSame( 'dg-interview', $result[0]['slug'] );
		$this->assertSame( 'DG Interview', $result[0]['title'] );
	}

	/**
	 * Preserves existing categories when registering block category.
	 *
	 * @test
	 */
	public function preserves_existing_categories_when_registering_block_category(): void {
		$integration = new BlockEditorIntegration();
		$existing    = array(
			array(
				'slug'  => 'text',
				'title' => 'Text',
			),
		);

		$result = $integration->register_block_category( $existing );

		$this->assertCount( 2, $result );
		$this->assertSame( 'text', $result[1]['slug'] );
	}
}

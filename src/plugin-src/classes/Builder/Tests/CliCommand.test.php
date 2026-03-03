<?php
/**
 * CliCommand tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Builder\Tests;

use DgInterview\Builder\CliCommand;
use WP_UnitTestCase;

/**
 * Tests for the CliCommand class.
 *
 * Tests call seed/reset methods directly to avoid requiring WP-CLI
 * in the test environment.
 */
class CliCommandTest extends WP_UnitTestCase {

	// ── seed ─────────────────────────────────────────────────────────

	/**
	 * Creates demo page when seed called.
	 *
	 * @test
	 */
	public function creates_demo_page_when_seed_called(): void {
		$command = new CliCommand();
		$command->seed();

		$pages = get_posts(
			array(
				'post_type'  => 'page',
				'meta_key'   => '_dg_interview_demo',
				'meta_value' => '1',
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'Welcome to the Demo Page', $pages[0]->post_title );
		$this->assertSame( 'publish', $pages[0]->post_status );
		$this->assertStringContainsString( 'wp:dg-interview/heading', $pages[0]->post_content );
		$this->assertStringContainsString( '{this.title.toUpperCase()}', $pages[0]->post_content );
		$this->assertStringContainsString( '<!-- wp:quote -->', $pages[0]->post_content );
	}

	/**
	 * Returns warning message when demo page exists without force.
	 *
	 * @test
	 */
	public function returns_warning_when_demo_page_exists_without_force(): void {
		$command = new CliCommand();
		$command->seed();

		$result = $command->seed();

		$this->assertSame( 'warning', $result['status'] );
		$this->assertStringContainsString( '--force', $result['message'] );
	}

	/**
	 * Overwrites demo page when seed called with force.
	 *
	 * @test
	 */
	public function overwrites_demo_page_when_seed_called_with_force(): void {
		$command = new CliCommand();
		$command->seed();

		// Modify the demo page content to verify overwrite.
		$pages = get_posts(
			array(
				'post_type'  => 'page',
				'meta_key'   => '_dg_interview_demo',
				'meta_value' => '1',
			)
		);
		wp_update_post(
			array(
				'ID'           => $pages[0]->ID,
				'post_content' => 'Modified content',
			)
		);

		$command->seed( true );

		$updated = get_post( $pages[0]->ID );
		$this->assertStringContainsString( 'wp:dg-interview/heading', $updated->post_content );
	}

	// ── reset ────────────────────────────────────────────────────────

	/**
	 * Deletes demo page when reset called.
	 *
	 * @test
	 */
	public function deletes_demo_page_when_reset_called(): void {
		$command = new CliCommand();
		$command->seed();

		$command->reset();

		$pages = get_posts(
			array(
				'post_type'  => 'page',
				'meta_key'   => '_dg_interview_demo',
				'meta_value' => '1',
			)
		);

		$this->assertCount( 0, $pages );
	}

	/**
	 * Returns info message when reset called with no demo pages.
	 *
	 * @test
	 */
	public function returns_info_when_reset_called_with_no_demo_pages(): void {
		$command = new CliCommand();

		$result = $command->reset();

		$this->assertSame( 'info', $result['status'] );
	}
}

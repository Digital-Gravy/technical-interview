<?php
/**
 * BuilderShellView tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Builder\Tests;

use DgInterview\Builder\BuilderShellView;
use WP_UnitTestCase;

/**
 * Tests for the BuilderShellView class.
 */
class BuilderShellViewTest extends WP_UnitTestCase {

	/**
	 * Renders builder shell HTML with assets and config payload.
	 *
	 * @test
	 */
	public function renders_builder_shell_html_with_assets_and_config_payload(): void {
		$view = new BuilderShellView();

		// phpcs:disable WordPress.WP.EnqueuedResources -- test fixture contains literal script/style tags intentionally.
		$html = $view->render(
			array(
				'js'  => '<script type="module" src="/builder.js"></script>',
				'css' => '<link rel="stylesheet" href="/builder.css">',
			),
			array(
				'postId'             => 42,
				'restUrl'            => 'https://example.test/wp-json/wp/v2/pages/',
				'saveUrl'            => 'https://example.test/wp-json/dg-interview/v1/posts/',
				'nonce'              => 'abc123',
				'sources'            => array(
					array(
						'key'    => 'this',
						'source' => array( 'title' => 'Demo' ),
					),
				),
				'languageAttributes' => 'lang="en-US"',
				'charset'            => 'UTF-8',
			)
		);

		$this->assertStringContainsString( '<!DOCTYPE html>', $html );
		$this->assertStringContainsString( '<html lang="en-US">', $html );
		$this->assertStringContainsString( '<meta charset="UTF-8">', $html );
		$this->assertStringContainsString( '<div id="dg-builder"></div>', $html );
		$this->assertStringContainsString( '<link rel="stylesheet" href="/builder.css">', $html );
		$this->assertStringContainsString( '<script type="module" src="/builder.js"></script>', $html );
		$this->assertStringContainsString( '"postId":42', $html );
		$this->assertStringContainsString( '"restUrl":"https:\\/\\/example.test\\/wp-json\\/wp\\/v2\\/pages\\/"', $html );
		$this->assertStringContainsString( '"saveUrl":"https:\\/\\/example.test\\/wp-json\\/dg-interview\\/v1\\/posts\\/"', $html );
		$this->assertStringContainsString( '"nonce":"abc123"', $html );
		// phpcs:enable
	}
}

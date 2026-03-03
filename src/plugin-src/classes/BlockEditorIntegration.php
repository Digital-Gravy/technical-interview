<?php
/**
 * BlockEditorIntegration — registers the block category and enqueues editor assets.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview;

/**
 * Handles Gutenberg block editor integration: custom block category and
 * editor script enqueuing.
 */
class BlockEditorIntegration {

	/**
	 * Hook into WordPress.
	 */
	public function __construct() {
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Add the DG Interview block category.
	 *
	 * @param array<int, array<string, mixed>> $categories Existing categories.
	 * @return array<int, array<string, mixed>>
	 */
	public function register_block_category( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'dg-interview',
				'title' => 'DG Interview',
			)
		);
		return $categories;
	}

	/**
	 * Enqueue the block editor script.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$asset_path = dirname( __DIR__ ) . '/build/index.asset.php';
		if ( ! file_exists( $asset_path ) ) {
			return;
		}
		$asset_file = include $asset_path;
		wp_enqueue_script(
			'dg-interview-blocks',
			plugins_url( 'build/index.js', dirname( __DIR__ ) . '/dg-interview.php' ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
	}
}

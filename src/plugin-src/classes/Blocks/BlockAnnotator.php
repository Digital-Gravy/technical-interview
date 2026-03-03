<?php
/**
 * BlockAnnotator — annotates rendered blocks with data attributes for the builder.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks;

/**
 * Adds data-dg-block-name and data-dg-block-path attributes to rendered
 * blocks during REST API requests so the builder can map HTML elements
 * back to their source Gutenberg blocks.
 */
class BlockAnnotator {

	/**
	 * Tracks nesting depth to identify top-level blocks.
	 *
	 * @var int
	 */
	private int $block_depth = 0;

	/**
	 * Sequential index for top-level blocks.
	 *
	 * @var int
	 */
	private int $top_level_index = 0;

	/**
	 * Current block path by render depth.
	 *
	 * @var array<int, string>
	 */
	private array $path_by_depth = array();

	/**
	 * Next child index per parent path.
	 *
	 * @var array<string, int>
	 */
	private array $next_child_index_by_path = array();

	/**
	 * Hook into WordPress.
	 */
	public function __construct() {
		add_filter( 'pre_render_block', array( $this, 'track_depth_before_render' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'annotate_block' ), 9, 2 );
	}

	/**
	 * Increment block depth before rendering (REST requests only).
	 *
	 * @param string|null          $pre_render   The pre-rendered content.
	 * @param array<string, mixed> $parsed_block The parsed block data.
	 * @return string|null
	 */
	public function track_depth_before_render( ?string $pre_render, array $parsed_block = array() ): ?string {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			++$this->block_depth;

			if ( empty( $parsed_block['blockName'] ) ) {
				return $pre_render;
			}

			$current_depth = $this->block_depth;
			$path          = $this->build_path_for_depth( $current_depth );

			$this->path_by_depth[ $current_depth ]      = $path;
			$this->next_child_index_by_path[ $path ] = 0;
		}
		return $pre_render;
	}

	/**
	 * Annotate rendered block HTML with data attributes (REST requests only).
	 *
	 * @param string               $block_content The rendered block content.
	 * @param array<string, mixed> $block         The parsed block array.
	 * @return string
	 */
	public function annotate_block( string $block_content, array $block ): string {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return $block_content;
		}

		$current_depth = $this->block_depth;
		--$this->block_depth;

		if ( empty( $block['blockName'] ) ) {
			unset( $this->path_by_depth[ $current_depth ] );
			return $block_content;
		}

		$block_name = is_string( $block['blockName'] ) ? $block['blockName'] : '';
		$data_attrs = sprintf( ' data-dg-block-name="%s"', esc_attr( $block_name ) );
		$path       = $this->path_by_depth[ $current_depth ] ?? '';

		if ( '' !== $path ) {
			$data_attrs .= sprintf( ' data-dg-block-path="%s"', esc_attr( $path ) );
		}

		unset( $this->path_by_depth[ $current_depth ] );

		$annotated = preg_replace( '/^(\s*<\w+)/', '$1' . $data_attrs, $block_content, 1 );
		return is_string( $annotated ) ? $annotated : $block_content;
	}

	/**
	 * Build the stable block path for the current render depth.
	 *
	 * Paths are generated from block-tree position:
	 * - top-level blocks: "0", "1", ...
	 * - nested blocks: "0.0", "0.1", "1.0", ...
	 *
	 * @param int $depth Current block depth (1 = top level).
	 * @return string
	 */
	private function build_path_for_depth( int $depth ): string {
		if ( 1 === $depth ) {
			$path = (string) $this->top_level_index;
			++$this->top_level_index;
			return $path;
		}

		$parent_path = $this->find_parent_path( $depth - 1 );
		if ( null === $parent_path ) {
			$path = (string) $this->top_level_index;
			++$this->top_level_index;
			return $path;
		}

		$child_index = $this->next_child_index_by_path[ $parent_path ] ?? 0;
		$this->next_child_index_by_path[ $parent_path ] = $child_index + 1;

		return $parent_path . '.' . $child_index;
	}

	/**
	 * Find the nearest ancestor path for a depth.
	 *
	 * @param int $depth Ancestor search depth.
	 * @return string|null
	 */
	private function find_parent_path( int $depth ): ?string {
		for ( $i = $depth; $i >= 1; --$i ) {
			if ( isset( $this->path_by_depth[ $i ] ) ) {
				return $this->path_by_depth[ $i ];
			}
		}
		return null;
	}
}

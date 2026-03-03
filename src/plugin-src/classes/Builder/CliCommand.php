<?php
/**
 * WP-CLI command for seeding and resetting demo content.
 *
 * Usage:
 *   wp dg-interview seed [--force]
 *   wp dg-interview reset [--force]
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Builder;

/**
 * Manage demo content for the DG Interview plugin.
 */
class CliCommand {

	private const META_KEY    = '_dg_interview_demo';
	private const META_VALUE  = '1';
	private const DEMO_TITLE  = 'Welcome to the Demo Page';

	/**
	 * Dispatch subcommands from WP-CLI.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$subcommand = $args[0] ?? '';
		$force      = isset( $assoc_args['force'] );

		switch ( $subcommand ) {
			case 'seed':
				$result = $this->seed( $force );
				break;
			case 'reset':
				$result = $this->reset();
				break;
			default:
				\WP_CLI::error( "Unknown subcommand: {$subcommand}. Use 'seed' or 'reset'." );
		}

		switch ( $result['status'] ) {
			case 'success':
				\WP_CLI::success( $result['message'] );
				break;
			case 'warning':
				\WP_CLI::warning( $result['message'] );
				break;
			case 'info':
				\WP_CLI::log( $result['message'] );
				break;
		}
	}

	/**
	 * Create a demo page with block content showcasing dynamic expressions.
	 *
	 * @param bool $force Whether to overwrite an existing demo page.
	 * @return array{status: string, message: string}
	 */
	public function seed( bool $force = false ): array {
		$existing = $this->get_demo_pages();

		if ( ! empty( $existing ) && ! $force ) {
			return array(
				'status'  => 'warning',
				'message' => 'Demo page already exists. Use --force to overwrite.',
			);
		}

		$content = $this->get_demo_content();

		if ( ! empty( $existing ) && $force ) {
			wp_update_post(
				array(
					'ID'           => $existing[0]->ID,
					'post_title'   => self::DEMO_TITLE,
					'post_content' => $content,
				)
			);

			return array(
				'status'  => 'success',
				'message' => "Demo page updated (ID {$existing[0]->ID}).",
			);
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => self::DEMO_TITLE,
				'post_name'    => 'demo-page',
				'post_content' => $content,
				'meta_input'   => array(
					self::META_KEY => self::META_VALUE,
				),
			)
		);

		return array(
			'status'  => 'success',
			'message' => "Demo page created (ID {$post_id}).",
		);
	}

	/**
	 * Delete all demo pages.
	 *
	 * @return array{status: string, message: string}
	 */
	public function reset(): array {
		$pages = $this->get_demo_pages();

		if ( empty( $pages ) ) {
			return array(
				'status'  => 'info',
				'message' => 'No demo pages found.',
			);
		}

		$count = 0;
		foreach ( $pages as $page ) {
			wp_delete_post( $page->ID, true );
			++$count;
		}

		return array(
			'status'  => 'success',
			'message' => "Deleted {$count} demo page(s).",
		);
	}

	/**
	 * Query all posts flagged as demo content.
	 *
	 * @return \WP_Post[]
	 */
	private function get_demo_pages(): array {
		return get_posts(
			array(
				'post_type'  => 'page',
				'meta_key'   => self::META_KEY,
				'meta_value' => self::META_VALUE,
				'numberposts' => -1,
			)
		);
	}

	/**
	 * Build the serialized Gutenberg block content for the demo page.
	 */
	private function get_demo_content(): string {
		$blocks = array(
			$this->block(
				'heading',
				array(
					'content' => '{this.title}',
					'level'   => 1,
				)
			),
			$this->block(
				'paragraph',
				array(
					'content' => 'This page demonstrates dynamic expressions and modifiers.',
				)
			),
			$this->div(
				array(
					$this->block(
						'heading',
						array(
							'content' => 'Dynamic Data',
							'level'   => 2,
						)
					),
					$this->block( 'paragraph', array( 'content' => 'Title: {this.title}' ) ),
					$this->block( 'paragraph', array( 'content' => 'Slug: {this.slug}' ) ),
					$this->block( 'paragraph', array( 'content' => 'Post type: {this.type}' ) ),
				),
				array(
					'className'  => 'demo-section',
					'htmlAttributes' => array(
						'style'         => 'padding: 16px; border: 1px solid #ddd; margin-bottom: 16px;',
						'data-section'  => 'dynamic-data',
					),
				)
			),
			$this->div(
				array(
					$this->block(
						'heading',
						array(
							'content' => 'Modifiers',
							'level'   => 2,
						)
					),
					$this->block( 'paragraph', array( 'content' => 'Uppercase: {this.title.toUpperCase()}' ) ),
					$this->block( 'paragraph', array( 'content' => 'Lowercase: {this.title.toLowerCase()}' ) ),
					$this->block( 'paragraph', array( 'content' => 'Truncated: {this.title.truncateWords(2)}' ) ),
				),
				array(
					'className'  => 'demo-section',
					'htmlAttributes' => array(
						'style'         => 'padding: 16px; border: 1px solid #ddd;',
						'data-section'  => 'modifiers',
					),
				)
			),
			$this->div(
				array(
					$this->block(
						'heading',
						array(
							'content' => 'Core Blocks',
							'level'   => 2,
						)
					),
					$this->core_block(
						'quote',
						'<blockquote class="wp-block-quote is-layout-flow wp-block-quote-is-layout-flow"><p>This is a blockquote</p></blockquote>'
					),
				),
				array(
					'className'  => 'demo-section',
					'htmlAttributes' => array(
						'style'         => 'padding: 16px; border: 1px solid #ddd; margin-top: 16px;',
						'data-section'  => 'core-blocks',
					),
				)
			),
		);

		return implode( "\n\n", $blocks ) . "\n";
	}

	/**
	 * Serialize a single self-closing block comment.
	 *
	 * @param string               $name  Block name without namespace prefix.
	 * @param array<string, mixed> $attrs Block attributes.
	 */
	private function block( string $name, array $attrs ): string {
		$json = wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return "<!-- wp:dg-interview/{$name} {$json} /-->";
	}

	/**
	 * Serialize a div block with inner blocks.
	 *
	 * @param string[]             $children   Inner block strings.
	 * @param array<string, mixed> $attrs      Block attributes.
	 */
	private function div( array $children, array $attrs = array() ): string {
		$attrs_json = ! empty( $attrs ) ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
		$inner      = implode( "\n", $children );
		return "<!-- wp:dg-interview/div{$attrs_json} -->\n{$inner}\n<!-- /wp:dg-interview/div -->";
	}

	/**
	 * Serialize a core block with explicit open/close comments.
	 *
	 * @param string               $name       Core block name without namespace prefix.
	 * @param string               $inner_html Inner HTML between block comments.
	 * @param array<string, mixed> $attrs      Block attributes.
	 */
	private function core_block( string $name, string $inner_html, array $attrs = array() ): string {
		$attrs_json = ! empty( $attrs ) ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
		return "<!-- wp:{$name}{$attrs_json} -->\n{$inner_html}\n<!-- /wp:{$name} -->";
	}
}

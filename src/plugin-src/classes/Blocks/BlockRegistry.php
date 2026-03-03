<?php
/**
 * BlockRegistry — central registry for custom blocks.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Blocks;

/**
 * Registry that maps block names and HTML tags to their block implementations.
 */
class BlockRegistry {

	/**
	 * Blocks keyed by block name.
	 *
	 * @var array<string, Block>
	 */
	private array $blocks = array();

	/**
	 * Blocks keyed by HTML tag.
	 *
	 * @var array<string, Block>
	 */
	private array $tag_map = array();

	/**
	 * Original parsed blocks keyed by stable block path.
	 *
	 * Used during builder save to pass through unknown Gutenberg blocks
	 * unchanged, including nested blocks inside container blocks.
	 *
	 * @var array<int|string, array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>}>
	 */
	private array $original_blocks_by_path = array();

	/**
	 * Register a block.
	 *
	 * @param Block $block The block to register.
	 * @return void
	 */
	public function register( Block $block ): void {
		$this->blocks[ $block->block_name() ] = $block;

		foreach ( $block->html_tags() as $tag ) {
			$this->tag_map[ $tag ] = $block;
		}
	}

	/**
	 * Look up a block by Gutenberg block name.
	 *
	 * @param string $name The block name (e.g. 'dg-interview/heading').
	 * @return Block|null
	 */
	public function get_by_block_name( string $name ): ?Block {
		return $this->blocks[ $name ] ?? null;
	}

	/**
	 * Look up a block by HTML tag.
	 *
	 * @param string $tag The HTML tag name (e.g. 'h3', 'p').
	 * @return Block|null
	 */
	public function get_by_tag( string $tag ): ?Block {
		return $this->tag_map[ $tag ] ?? null;
	}

	/**
	 * Internal data attribute names used for block mapping.
	 *
	 * @var string[]
	 */
	private const INTERNAL_ATTRIBUTES = array( 'data-dg-block-name', 'data-dg-block-path' );

	/**
	 * Set original parsed blocks for path-based passthrough lookups.
	 *
	 * @param array<int|string, array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>}> $blocks_by_path Map of block path => parsed block.
	 * @return void
	 */
	public function set_original_blocks_by_path( array $blocks_by_path ): void {
		$this->original_blocks_by_path = $blocks_by_path;
	}

	/**
	 * Serialize inner HTML elements into Gutenberg block comments.
	 *
	 * Walks child elements and delegates to the appropriate block for each.
	 * Unknown elements are passed through as-is.
	 *
	 * @param string $inner_html The raw inner HTML.
	 * @return string The serialized block content.
	 */
	public function serialize_inner_html( string $inner_html ): string {
		$doc = new \DOMDocument();
		$doc->loadHTML(
			'<?xml encoding="UTF-8"><body>' . $inner_html . '</body>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
		);

		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body instanceof \DOMElement ) {
			return trim( $inner_html );
		}

		$result = '';
		foreach ( $body->childNodes as $node ) {
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				continue;
			}

			$tag        = $node->nodeName;
			$outer_html = $doc->saveHTML( $node );
			if ( false === $outer_html ) {
				continue;
			}

			$outer_html = trim( $outer_html );
			$html_attributes = array();
			if ( $node instanceof \DOMElement ) {
				// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- inline type hint.
				/** @var \DOMAttr $attr */
				foreach ( $node->attributes as $attr ) {
					$html_attributes[ $attr->nodeName ] = (string) $attr->nodeValue;
				}
			}

			$block_name = $html_attributes['data-dg-block-name'] ?? null;
			$block_path = $html_attributes['data-dg-block-path'] ?? null;
			$clean_outer_html = self::strip_internal_data_attributes_from_html( $outer_html );

			// Existing block in builder HTML: prefer identity by block name/path
			// over tag lookups, so core blocks are preserved unchanged.
			if ( is_string( $block_name ) && '' !== $block_name ) {
				$named_block = $this->get_by_block_name( $block_name );

				if ( null !== $named_block ) {
					$clean_attrs = array_diff_key( $html_attributes, array_flip( self::INTERNAL_ATTRIBUTES ) );
					$element     = new HtmlElementDto( $tag, $outer_html, $node->textContent, $clean_attrs );
					$result     .= $named_block->serialize_from_html( $element, $this );
					continue;
				}

				if (
					is_string( $block_path )
					&& '' !== $block_path
					&& isset( $this->original_blocks_by_path[ $block_path ] )
				) {
					$result .= serialize_block( $this->original_blocks_by_path[ $block_path ] ) . "\n\n";
					continue;
				}

				// Fallback when path lookup misses: preserve as Gutenberg block
				// comment using cleaned HTML instead of leaking builder data attrs.
				$result .= self::serialize_unknown_block_from_html( $block_name, $clean_outer_html );
				continue;
			}

			$block = $this->get_by_tag( $tag );
			if ( null === $block ) {
				$result .= $clean_outer_html . "\n\n";
				continue;
			}

			$clean_attrs = array_diff_key( $html_attributes, array_flip( self::INTERNAL_ATTRIBUTES ) );
			$element     = new HtmlElementDto( $tag, $outer_html, $node->textContent, $clean_attrs );
			$result     .= $block->serialize_from_html( $element, $this );
		}

		return trim( $result );
	}

	/**
	 * Strip internal builder data attributes from an HTML fragment.
	 *
	 * @param string $html Raw HTML fragment.
	 * @return string
	 */
	private static function strip_internal_data_attributes_from_html( string $html ): string {
		$clean = preg_replace(
			'/\sdata-dg-block-(?:name|path)="[^"]*"/',
			'',
			$html
		);
		return is_string( $clean ) ? $clean : $html;
	}

	/**
	 * Serialize unknown block HTML as a block comment fallback.
	 *
	 * @param string $block_name Gutenberg block name.
	 * @param string $outer_html Cleaned outer HTML for the block.
	 * @return string
	 */
	private static function serialize_unknown_block_from_html( string $block_name, string $outer_html ): string {
		$comment_name = str_starts_with( $block_name, 'core/' )
			? substr( $block_name, 5 )
			: $block_name;

		return sprintf(
			"<!-- wp:%1\$s -->\n%2\$s\n<!-- /wp:%1\$s -->\n\n",
			$comment_name,
			$outer_html
		);
	}
}

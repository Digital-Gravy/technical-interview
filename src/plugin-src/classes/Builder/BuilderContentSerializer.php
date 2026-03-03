<?php
/**
 * Builder Content Serializer — converts builder HTML back to Gutenberg blocks.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Builder;

use DgInterview\Blocks\BlockRegistry;
use DgInterview\Blocks\HtmlElementDto;

/**
 * Rebuilds post_content by mapping builder HTML to original parsed blocks.
 */
class BuilderContentSerializer {

	/**
	 * Internal data attribute names used for block mapping.
	 *
	 * @var string[]
	 */
	private const INTERNAL_ATTRIBUTES = array( 'data-dg-block-name', 'data-dg-block-path' );

	/**
	 * The block registry.
	 *
	 * @var BlockRegistry
	 */
	private BlockRegistry $registry;

	/**
	 * Constructor.
	 *
	 * @param BlockRegistry $registry Block registry.
	 */
	public function __construct( BlockRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Rebuild Gutenberg block content from edited HTML.
	 *
	 * @param string                                                                                                                                                           $html            Edited HTML from builder.
	 * @param array<int, array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>}> $original_blocks Original parsed post blocks.
	 * @return string Serialized Gutenberg block markup.
	 */
	public function rebuild_content( string $html, array $original_blocks ): string {
		$html_elements  = $this->parse_top_level_elements( $html );
		$blocks_by_path = $this->build_blocks_by_path( $original_blocks );

		$this->registry->set_original_blocks_by_path( $blocks_by_path );
		try {
			$result = '';

			foreach ( $html_elements as $element ) {
				$block_name = $element->attributes['data-dg-block-name'] ?? null;
				$block_path = $element->attributes['data-dg-block-path'] ?? null;

				if ( null !== $block_name ) {
					// Element has a block identity — use it.
					$serializer    = $this->registry->get_by_block_name( $block_name );
					$clean_element = self::strip_internal_attributes( $element );

					if ( null !== $serializer ) {
						$result .= $serializer->serialize_from_html( $clean_element, $this->registry );
					} elseif ( is_string( $block_path ) && isset( $blocks_by_path[ $block_path ] ) ) {
						// Existing native block — pass through unchanged via stable path.
						$result .= serialize_block( $blocks_by_path[ $block_path ] ) . "\n\n";
					}
				} else {
					// New element without data attributes — fall back to tag-based lookup.
					$serializer = $this->registry->get_by_tag( $element->tag );

					if ( null !== $serializer ) {
						$result .= $serializer->serialize_from_html( $element, $this->registry );
					}
				}
			}
		} finally {
			$this->registry->set_original_blocks_by_path( array() );
		}

		return trim( $result );
	}

	/**
	 * Build a lookup of parsed blocks keyed by stable block path.
	 *
	 * Paths use sibling indexes among meaningful blocks only:
	 * - top-level blocks: "0", "1", ...
	 * - nested blocks: "0.0", "0.1", ...
	 *
	 * @param array<int, array<string, mixed>> $blocks Blocks to index.
	 * @param string                           $parent_path Current parent path.
	 * @return array<int|string, array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>}>
	 */
	private function build_blocks_by_path( array $blocks, string $parent_path = '' ): array {
		// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- inline type hint.
		/** @var array<int|string, array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>}> $lookup */
		$lookup           = array();
		$meaningful_index = 0;

		foreach ( $blocks as $block ) {
			if ( ! $this->is_parsed_block_shape( $block ) ) {
				continue;
			}
			// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- inline type hint.
			/** @var array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>} $block */

			if ( null === $block['blockName'] ) {
				continue;
			}

			$path            = '' === $parent_path
				? (string) $meaningful_index
				: $parent_path . '.' . $meaningful_index;
			$lookup[ $path ] = $block;

			$nested_blocks = $this->build_blocks_by_path( $block['innerBlocks'], $path );
			// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- inline type hint.
			/** @var array<int|string, array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>}> $nested_blocks */
			foreach ( $nested_blocks as $nested_path => $nested_block ) {
				$lookup[ $nested_path ] = $nested_block;
			}

			++$meaningful_index;
		}

		return $lookup;
	}

	/**
	 * Whether a value has the parsed block shape returned by parse_blocks().
	 *
	 * @param array<string, mixed> $block Candidate value.
	 * @return bool
	 *
	 * @phpstan-assert array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<array<string, mixed>>, innerHTML: string, innerContent: array<string>} $block
	 */
	private function is_parsed_block_shape( array $block ): bool {
		return array_key_exists( 'blockName', $block )
			&& array_key_exists( 'attrs', $block )
			&& array_key_exists( 'innerBlocks', $block )
			&& array_key_exists( 'innerHTML', $block )
			&& array_key_exists( 'innerContent', $block )
			&& ( null === $block['blockName'] || is_string( $block['blockName'] ) )
			&& is_array( $block['attrs'] )
			&& is_array( $block['innerBlocks'] )
			&& is_string( $block['innerHTML'] )
			&& is_array( $block['innerContent'] );
	}

	/**
	 * Parse HTML into top-level HtmlElementDto objects.
	 *
	 * @param string $html HTML to parse.
	 * @return HtmlElementDto[]
	 */
	private function parse_top_level_elements( string $html ): array {
		$doc = new \DOMDocument();
		$doc->loadHTML(
			'<?xml encoding="UTF-8"><body>' . $html . '</body>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
		);

		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body instanceof \DOMElement ) {
			return array();
		}

		$elements = array();
		foreach ( $body->childNodes as $node ) {
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				continue;
			}

			$inner_html = '';
			foreach ( $node->childNodes as $child ) {
				$saved = $doc->saveHTML( $child );
				$inner_html .= false !== $saved ? $saved : '';
			}

			$html_attributes = array();
			if ( $node instanceof \DOMElement ) {
				// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- inline type hint.
				/** @var \DOMAttr $attr */
				foreach ( $node->attributes as $attr ) {
					$html_attributes[ $attr->nodeName ] = (string) $attr->nodeValue;
				}
			}

			$elements[] = new HtmlElementDto(
				$node->nodeName,
				$inner_html,
				trim( $inner_html ),
				$html_attributes,
			);
		}

		return $elements;
	}

	/**
	 * Create a copy of an HtmlElementDto with internal data attributes removed.
	 *
	 * @param HtmlElementDto $element The original element.
	 * @return HtmlElementDto The cleaned element.
	 */
	private static function strip_internal_attributes( HtmlElementDto $element ): HtmlElementDto {
		$clean = array_diff_key( $element->attributes, array_flip( self::INTERNAL_ATTRIBUTES ) );
		return new HtmlElementDto( $element->tag, $element->inner_html, $element->text_content, $clean );
	}
}

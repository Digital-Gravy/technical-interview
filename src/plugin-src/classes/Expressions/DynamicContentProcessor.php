<?php
/**
 * DynamicContentProcessor — resolves dynamic expressions against data sources.
 *
 * Expressions use the syntax {source.path.to.value} and are resolved against
 * a list of sources. Each source has a key (e.g. "this") and a data object.
 *
 * Example:
 *   sources: [['key' => 'this', 'source' => ['title' => 'Hello World']]]
 *   input:   "Title: {this.title}"
 *   output:  "Title: Hello World"
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Expressions;

/**
 * Resolves dynamic expressions in strings against data sources.
 */
class DynamicContentProcessor {

	/**
	 * Replace all {expression} templates in a string with resolved values.
	 *
	 * @param string                                                  $value   The string containing expressions.
	 * @param array<array{key: string, source: array<string, mixed>}> $sources The data sources.
	 * @return string The string with expressions replaced.
	 */
	public static function replace_templates( string $value, array $sources ): string {
		return (string) preg_replace_callback(
			'/\{([^}]+)\}/',
			function ( array $matches ) use ( $sources ): string {
				$resolved = self::resolve_expression( $matches[1], $sources );

				if ( null === $resolved ) {
					return '';
				}

				if ( is_array( $resolved ) ) {
					return (string) wp_json_encode( $resolved );
				}

				if ( is_bool( $resolved ) ) {
					return $resolved ? 'true' : 'false';
				}

				if ( is_int( $resolved ) || is_float( $resolved ) ) {
					return (string) $resolved;
				}

				return is_string( $resolved ) ? $resolved : '';
			},
			$value
		);
	}

	/**
	 * Resolve a single expression against the given sources.
	 *
	 * The first part of the expression matches a source key. Remaining parts
	 * traverse into the source data. Sources are searched in reverse order
	 * (last source wins when keys conflict).
	 *
	 * @param string                                                  $expression The expression (e.g. "this.title").
	 * @param array<array{key: string, source: array<string, mixed>}> $sources The data sources.
	 * @return mixed The resolved value, or null if not found.
	 */
	public static function resolve_expression( string $expression, array $sources ): mixed {
		$parts = self::split_expression( $expression );

		if ( empty( $parts ) ) {
			return null;
		}

		$source_key = $parts[0];

		// Search sources in reverse order — last source wins.
		$value = null;
		$found = false;
		for ( $i = count( $sources ) - 1; $i >= 0; $i-- ) {
			if ( $sources[ $i ]['key'] === $source_key ) {
				$value = $sources[ $i ]['source'];
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return null;
		}

		// Traverse the path; modifier-shaped parts apply as transforms.
		foreach ( array_slice( $parts, 1 ) as $part ) {
			if ( Modifiers::is_modifier( $part ) ) {
				$value = Modifiers::apply_modifier( $value, $part );
				continue;
			}

			if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
				return null;
			}
			$value = $value[ $part ];
		}

		return $value;
	}

	/**
	 * Build sources from the current WordPress post.
	 *
	 * @return array<array{key: string, source: array<string, mixed>}> The sources array.
	 */
	public static function get_post_sources(): array {
		$post = get_post();

		if ( ! $post ) {
			return array();
		}

		return array(
			array(
				'key'    => 'this',
				'source' => array(
					'id'      => $post->ID,
					'title'   => get_the_title( $post ),
					'slug'    => $post->post_name,
					'excerpt' => get_the_excerpt( $post ),
					'date'    => $post->post_date,
					'type'    => $post->post_type,
				),
			),
		);
	}

	/**
	 * Build sources for a specific post by ID.
	 *
	 * Used by the builder to pass post data to the frontend without relying
	 * on the global post context.
	 *
	 * @param int $post_id The post ID.
	 * @return array<array{key: string, source: array<string, mixed>}> The sources array.
	 */
	public static function get_post_sources_for_builder( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		return array(
			array(
				'key'    => 'this',
				'source' => array(
					'id'      => $post->ID,
					'title'   => get_the_title( $post ),
					'slug'    => $post->post_name,
					'excerpt' => get_the_excerpt( $post ),
					'date'    => $post->post_date,
					'type'    => $post->post_type,
				),
			),
		);
	}

	/**
	 * Split an expression into parts on dots, respecting parenthesis depth.
	 *
	 * For example:
	 *   "this.title"                → ['this', 'title']
	 *   "this.title.truncateWords(3)" → ['this', 'title', 'truncateWords(3)']
	 *
	 * @param string $expression The expression to split.
	 * @return string[] The parts.
	 */
	private static function split_expression( string $expression ): array {
		$parts       = array();
		$current     = '';
		$paren_depth = 0;
		$length      = strlen( $expression );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $expression[ $i ];

			if ( '(' === $char ) {
				++$paren_depth;
				$current .= $char;
			} elseif ( ')' === $char ) {
				$paren_depth = max( 0, $paren_depth - 1 );
				$current    .= $char;
			} elseif ( '.' === $char && 0 === $paren_depth ) {
				if ( '' !== $current ) {
					$parts[] = $current;
				}
				$current = '';
			} else {
				$current .= $char;
			}
		}

		if ( '' !== $current ) {
			$parts[] = $current;
		}

		return $parts;
	}
}

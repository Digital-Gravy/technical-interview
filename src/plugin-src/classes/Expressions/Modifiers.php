<?php
/**
 * Modifiers — transform values in dynamic expressions.
 *
 * Each modifier is a callable that receives the current value as its first
 * argument and optional extra arguments from the expression syntax.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Expressions;

/**
 * Registry and parser for dynamic-content modifiers.
 */
class Modifiers {

	/**
	 * Check whether a string looks like a modifier call (e.g. "truncateWords(3)").
	 *
	 * @param string $part The expression part to check.
	 * @return bool
	 */
	public static function is_modifier( string $part ): bool {
		return 1 === preg_match( '/^\w+\(.*\)$/s', $part );
	}

	/**
	 * Parse a modifier string into its method name and typed arguments.
	 *
	 * @param string $modifier_string E.g. "truncateWords(3, \"—\")".
	 * @return array{method: string, args: array<int, mixed>}|null Null if not a modifier.
	 */
	public static function parse_modifier( string $modifier_string ): ?array {
		if ( ! self::is_modifier( $modifier_string ) ) {
			return null;
		}

		$paren_pos = strpos( $modifier_string, '(' );
		if ( false === $paren_pos ) {
			return null;
		}
		$method     = substr( $modifier_string, 0, $paren_pos );
		$arg_string = trim( substr( $modifier_string, $paren_pos + 1, -1 ) );

		if ( '' === $arg_string ) {
			return array(
				'method' => $method,
				'args'   => array(),
			);
		}

		$raw_args = self::split_args( $arg_string );
		$args     = array_map( array( self::class, 'cast_arg' ), $raw_args );

		return array(
			'method' => $method,
			'args'   => $args,
		);
	}

	/**
	 * Apply a modifier from its raw string form to a value.
	 *
	 * @param mixed  $value           The current value.
	 * @param string $modifier_string E.g. "toUpperCase()" or "truncateWords(3)".
	 * @return mixed The transformed value, or the original if modifier is unknown.
	 */
	public static function apply_modifier( mixed $value, string $modifier_string ): mixed {
		$parsed = self::parse_modifier( $modifier_string );
		if ( null === $parsed ) {
			return $value;
		}

		$fn = self::get_modifier( $parsed['method'] );
		if ( null === $fn ) {
			return $value;
		}

		return $fn( $value, ...$parsed['args'] );
	}

	/**
	 * Get the callable for a named modifier.
	 *
	 * @param string $method The modifier name.
	 * @return callable|null The modifier callable, or null if not found.
	 */
	public static function get_modifier( string $method ): ?callable {
		switch ( $method ) {
			case 'truncateWords':
				return function ( mixed $value, mixed ...$args ): mixed {
					if ( ! is_string( $value ) ) {
						return $value;
					}

					$word_count = 0;
					$ellipsis   = '...';

					if ( isset( $args[0] ) && is_numeric( $args[0] ) ) {
						$word_count = (int) $args[0];
					}

					if ( isset( $args[1] ) && is_string( $args[1] ) ) {
						$ellipsis = $args[1];
					}

					$words = explode( ' ', $value );
					if ( count( $words ) <= $word_count ) {
						return $value;
					}

					return implode( ' ', array_slice( $words, 0, $word_count ) ) . $ellipsis;
				};

			case 'toUpperCase':
				return function ( mixed $value ): mixed {
					if ( ! is_string( $value ) ) {
						return $value;
					}
					return strtoupper( $value );
				};

			case 'toLowerCase':
				return function ( mixed $value ): mixed {
					if ( ! is_string( $value ) ) {
						return $value;
					}
					return strtolower( $value );
				};

			case 'truncateChars':
				return function ( mixed $value, mixed ...$args ): mixed {
					if ( ! is_string( $value ) ) {
						return $value;
					}

					$char_count = 0;
					$ellipsis   = '...';

					if ( isset( $args[0] ) && is_numeric( $args[0] ) ) {
						$char_count = (int) $args[0];
					}

					if ( isset( $args[1] ) && is_string( $args[1] ) ) {
						$ellipsis = $args[1];
					}

					if ( strlen( $value ) <= $char_count ) {
						return $value;
					}

					return substr( $value, 0, $char_count ) . $ellipsis;
				};

			default:
				return null;
		}
	}

	/**
	 * Split a comma-separated argument string, respecting quotes and nesting.
	 *
	 * @param string $arg_string The raw argument string (without outer parens).
	 * @return string[] The individual argument strings, trimmed.
	 */
	private static function split_args( string $arg_string ): array {
		$args      = array();
		$current   = '';
		$depth     = 0;
		$in_string = false;
		$str_char  = '';
		$length    = strlen( $arg_string );

		for ( $i = 0; $i < $length; $i++ ) {
			$char      = $arg_string[ $i ];
			$prev_char = $i > 0 ? $arg_string[ $i - 1 ] : '';

			// Handle string boundaries.
			if ( ( '"' === $char || "'" === $char ) && '\\' !== $prev_char ) {
				if ( ! $in_string ) {
					$in_string = true;
					$str_char  = $char;
				} elseif ( $char === $str_char ) {
					$in_string = false;
					$str_char  = '';
				}
			}

			if ( ! $in_string ) {
				if ( '(' === $char || '[' === $char || '{' === $char ) {
					++$depth;
				} elseif ( ')' === $char || ']' === $char || '}' === $char ) {
					--$depth;
				}

				if ( ',' === $char && 0 === $depth ) {
					$args[]  = trim( $current );
					$current = '';
					continue;
				}
			}

			$current .= $char;
		}

		$trimmed = trim( $current );
		if ( '' !== $trimmed ) {
			$args[] = $trimmed;
		}

		return $args;
	}

	/**
	 * Cast a raw argument string to its appropriate PHP type.
	 *
	 * @param string $arg The raw argument string.
	 * @return mixed The typed value.
	 */
	private static function cast_arg( string $arg ): mixed {
		// Quoted string — strip quotes.
		if ( preg_match( '/^(["\'])(.*)\\1$/s', $arg, $m ) ) {
			return $m[2];
		}

		// Boolean.
		if ( 'true' === $arg ) {
			return true;
		}
		if ( 'false' === $arg ) {
			return false;
		}

		// Null.
		if ( 'null' === $arg ) {
			return null;
		}

		// Numeric.
		if ( is_numeric( $arg ) ) {
			return str_contains( $arg, '.' ) ? (float) $arg : (int) $arg;
		}

		return $arg;
	}
}

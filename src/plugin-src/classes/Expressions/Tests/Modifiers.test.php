<?php
/**
 * Modifiers tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Expressions\Tests;

use DgInterview\Expressions\Modifiers;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Modifiers class.
 */
class ModifiersTest extends TestCase {

	// ── truncateWords ────────────────────────────────────────────────

	/**
	 * Truncates to word count when input has simple spaces.
	 *
	 * @test
	 */
	public function truncates_to_word_count_when_input_has_simple_spaces(): void {
		$modifier = Modifiers::get_modifier( 'truncateWords' );

		$this->assertNotNull( $modifier );
		$this->assertSame( 'The quick brown...', $modifier( 'The quick brown fox jumps', 3 ) );
	}

	/**
	 * Returns original when word count not exceeded.
	 *
	 * @test
	 */
	public function returns_original_when_word_count_not_exceeded(): void {
		$modifier = Modifiers::get_modifier( 'truncateWords' );

		$this->assertSame( 'Hello world', $modifier( 'Hello world', 5 ) );
	}

	/**
	 * Returns original when value is not a string.
	 *
	 * @test
	 */
	public function returns_original_when_value_is_not_string(): void {
		$modifier = Modifiers::get_modifier( 'truncateWords' );

		$this->assertSame( 42, $modifier( 42, 3 ) );
	}

	/**
	 * Uses custom ellipsis when provided.
	 *
	 * @test
	 */
	public function uses_custom_ellipsis_when_provided(): void {
		$modifier = Modifiers::get_modifier( 'truncateWords' );

		$this->assertSame( 'The quick—', $modifier( 'The quick brown fox', 2, '—' ) );
	}

	// ── toUpperCase ──────────────────────────────────────────────────

	/**
	 * Converts to uppercase when applied.
	 *
	 * @test
	 */
	public function converts_to_uppercase_when_applied(): void {
		$modifier = Modifiers::get_modifier( 'toUpperCase' );

		$this->assertNotNull( $modifier );
		$this->assertSame( 'HELLO WORLD', $modifier( 'hello world' ) );
	}

	/**
	 * Returns original when value is not a string for toUpperCase.
	 *
	 * @test
	 */
	public function returns_original_when_value_is_not_string_for_to_upper_case(): void {
		$modifier = Modifiers::get_modifier( 'toUpperCase' );

		$this->assertSame( 42, $modifier( 42 ) );
	}

	// ── toLowerCase ──────────────────────────────────────────────────

	/**
	 * Converts to lowercase when applied.
	 *
	 * @test
	 */
	public function converts_to_lowercase_when_applied(): void {
		$modifier = Modifiers::get_modifier( 'toLowerCase' );

		$this->assertNotNull( $modifier );
		$this->assertSame( 'hello world', $modifier( 'HELLO WORLD' ) );
	}

	// ── truncateChars ────────────────────────────────────────────────

	/**
	 * Truncates to character count when input exceeds limit.
	 *
	 * @test
	 */
	public function truncates_to_char_count_when_input_exceeds_limit(): void {
		$modifier = Modifiers::get_modifier( 'truncateChars' );

		$this->assertNotNull( $modifier );
		$this->assertSame( 'Hel...', $modifier( 'Hello World', 3 ) );
	}

	/**
	 * Returns original when character count not exceeded.
	 *
	 * @test
	 */
	public function returns_original_when_char_count_not_exceeded(): void {
		$modifier = Modifiers::get_modifier( 'truncateChars' );

		$this->assertSame( 'Hi', $modifier( 'Hi', 10 ) );
	}

	// ── Unknown modifier ─────────────────────────────────────────────

	/**
	 * Returns null when modifier not found.
	 *
	 * @test
	 */
	public function returns_null_when_modifier_not_found(): void {
		$modifier = Modifiers::get_modifier( 'nonExistent' );

		$this->assertNull( $modifier );
	}

	// ── is_modifier ──────────────────────────────────────────────────

	/**
	 * Detects modifier when part has parentheses.
	 *
	 * @test
	 */
	public function detects_modifier_when_part_has_parentheses(): void {
		$this->assertTrue( Modifiers::is_modifier( 'truncateWords(3)' ) );
		$this->assertTrue( Modifiers::is_modifier( 'toUpperCase()' ) );
		$this->assertTrue( Modifiers::is_modifier( 'default("fallback")' ) );
	}

	/**
	 * Rejects non-modifier when part has no parentheses.
	 *
	 * @test
	 */
	public function rejects_non_modifier_when_part_has_no_parentheses(): void {
		$this->assertFalse( Modifiers::is_modifier( 'title' ) );
		$this->assertFalse( Modifiers::is_modifier( 'user' ) );
	}

	// ── parse_modifier ───────────────────────────────────────────────

	/**
	 * Parses method and args when modifier has arguments.
	 *
	 * @test
	 */
	public function parses_method_and_args_when_modifier_has_arguments(): void {
		$result = Modifiers::parse_modifier( 'truncateWords(3)' );

		$this->assertSame( 'truncateWords', $result['method'] );
		$this->assertSame( array( 3 ), $result['args'] );
	}

	/**
	 * Parses empty args when modifier has no arguments.
	 *
	 * @test
	 */
	public function parses_empty_args_when_modifier_has_no_arguments(): void {
		$result = Modifiers::parse_modifier( 'toUpperCase()' );

		$this->assertSame( 'toUpperCase', $result['method'] );
		$this->assertSame( array(), $result['args'] );
	}

	/**
	 * Parses quoted string args when modifier has string arguments.
	 *
	 * @test
	 */
	public function parses_quoted_string_args_when_modifier_has_string_arguments(): void {
		$result = Modifiers::parse_modifier( 'truncateWords(3, "—")' );

		$this->assertSame( 'truncateWords', $result['method'] );
		$this->assertSame( array( 3, '—' ), $result['args'] );
	}

	/**
	 * Returns null when input is not a modifier.
	 *
	 * @test
	 */
	public function returns_null_when_input_is_not_modifier(): void {
		$this->assertNull( Modifiers::parse_modifier( 'title' ) );
	}

	// ── apply_modifier ───────────────────────────────────────────────

	/**
	 * Applies modifier from raw string when valid.
	 *
	 * @test
	 */
	public function applies_modifier_from_raw_string_when_valid(): void {
		$result = Modifiers::apply_modifier( 'hello world', 'toUpperCase()' );

		$this->assertSame( 'HELLO WORLD', $result );
	}

	/**
	 * Returns original value when modifier string is unknown.
	 *
	 * @test
	 */
	public function returns_original_value_when_modifier_string_is_unknown(): void {
		$result = Modifiers::apply_modifier( 'hello', 'nonExistent()' );

		$this->assertSame( 'hello', $result );
	}

	/**
	 * Applies modifier with args from raw string when valid.
	 *
	 * @test
	 */
	public function applies_modifier_with_args_from_raw_string_when_valid(): void {
		$result = Modifiers::apply_modifier( 'The quick brown fox', 'truncateWords(2)' );

		$this->assertSame( 'The quick...', $result );
	}
}

<?php
/**
 * DynamicContentProcessor tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Expressions\Tests;

use DgInterview\Expressions\DynamicContentProcessor;
use WP_UnitTestCase;

/**
 * Tests for the DynamicContentProcessor class.
 */
class DynamicContentProcessorTest extends WP_UnitTestCase {

	// ── replace_templates ─────────────────────────────────────────────

	/**
	 * Replaces expression when source matches.
	 *
	 * @test
	 */
	public function replaces_expression_when_source_matches(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'name' => 'World' ),
			),
		);

		$result = DynamicContentProcessor::replace_templates( 'Hello {this.name}!', $sources );

		$this->assertSame( 'Hello World!', $result );
	}

	/**
	 * Returns original when no expressions.
	 *
	 * @test
	 */
	public function returns_original_when_no_expressions(): void {
		$result = DynamicContentProcessor::replace_templates( 'Just plain text', array() );

		$this->assertSame( 'Just plain text', $result );
	}

	/**
	 * Replaces multiple expressions when string has several.
	 *
	 * @test
	 */
	public function replaces_multiple_expressions_when_string_has_several(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array(
					'first' => 'Hello',
					'last'  => 'World',
				),
			),
		);

		$result = DynamicContentProcessor::replace_templates( '{this.first} {this.last}', $sources );

		$this->assertSame( 'Hello World', $result );
	}

	/**
	 * Returns empty for expression when source not found.
	 *
	 * @test
	 */
	public function returns_empty_for_expression_when_source_not_found(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'name' => 'World' ),
			),
		);

		$result = DynamicContentProcessor::replace_templates( 'Hello {this.missing}!', $sources );

		$this->assertSame( 'Hello !', $result );
	}

	/**
	 * Resolves nested property when dot path used.
	 *
	 * @test
	 */
	public function resolves_nested_property_when_dot_path_used(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array(
					'user' => array( 'name' => 'Alice' ),
				),
			),
		);

		$result = DynamicContentProcessor::replace_templates( 'Hi {this.user.name}', $sources );

		$this->assertSame( 'Hi Alice', $result );
	}

	// ── resolve_expression ────────────────────────────────────────────

	/**
	 * Resolves simple property when source matches.
	 *
	 * @test
	 */
	public function resolves_simple_property_when_source_matches(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'My Post' ),
			),
		);

		$result = DynamicContentProcessor::resolve_expression( 'this.title', $sources );

		$this->assertSame( 'My Post', $result );
	}

	/**
	 * Returns null when source key not found.
	 *
	 * @test
	 */
	public function returns_null_when_source_key_not_found(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'My Post' ),
			),
		);

		$result = DynamicContentProcessor::resolve_expression( 'other.title', $sources );

		$this->assertNull( $result );
	}

	/**
	 * Returns null when property not found.
	 *
	 * @test
	 */
	public function returns_null_when_property_not_found(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'My Post' ),
			),
		);

		$result = DynamicContentProcessor::resolve_expression( 'this.missing', $sources );

		$this->assertNull( $result );
	}

	/**
	 * Uses last source when keys conflict.
	 *
	 * @test
	 */
	public function uses_last_source_when_keys_conflict(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'First' ),
			),
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'Last' ),
			),
		);

		$result = DynamicContentProcessor::resolve_expression( 'this.title', $sources );

		$this->assertSame( 'Last', $result );
	}

	/**
	 * Resolves deep path when multiple levels.
	 *
	 * @test
	 */
	public function resolves_deep_path_when_multiple_levels(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array(
					'a' => array(
						'b' => array(
							'c' => 'deep',
						),
					),
				),
			),
		);

		$result = DynamicContentProcessor::resolve_expression( 'this.a.b.c', $sources );

		$this->assertSame( 'deep', $result );
	}

	// ── get_post_sources (WP integration) ─────────────────────────────

	/**
	 * Resolves post title when using get_post_sources.
	 *
	 * @test
	 */
	public function resolves_post_title_when_using_get_post_sources(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Test Dynamic Title',
				'post_type'  => 'post',
			)
		);

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.
		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );

		$sources = DynamicContentProcessor::get_post_sources();
		$result  = DynamicContentProcessor::replace_templates( '{this.title}', $sources );

		$this->assertSame( 'Test Dynamic Title', $result );

		wp_reset_postdata();
	}

	// ── Modifier integration ─────────────────────────────────────────

	/**
	 * Applies modifier when expression has dot modifier.
	 *
	 * @test
	 */
	public function applies_modifier_when_expression_has_dot_modifier(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'hello world' ),
			),
		);

		$result = DynamicContentProcessor::replace_templates( '{this.title.toUpperCase()}', $sources );

		$this->assertSame( 'HELLO WORLD', $result );
	}

	/**
	 * Chains modifiers when multiple dot modifiers used.
	 *
	 * @test
	 */
	public function chains_modifiers_when_multiple_dot_modifiers_used(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'the quick brown fox jumps' ),
			),
		);

		$result = DynamicContentProcessor::replace_templates( '{this.title.truncateWords(3).toUpperCase()}', $sources );

		$this->assertSame( 'THE QUICK BROWN...', $result );
	}

	/**
	 * Resolves value without modifiers when no modifier in path.
	 *
	 * @test
	 */
	public function resolves_value_without_modifiers_when_no_modifier_in_path(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'Hello' ),
			),
		);

		$result = DynamicContentProcessor::replace_templates( '{this.title}', $sources );

		$this->assertSame( 'Hello', $result );
	}

	/**
	 * Returns empty when modifier applied to missing source.
	 *
	 * @test
	 */
	public function returns_empty_when_modifier_applied_to_missing_source(): void {
		$sources = array(
			array(
				'key'    => 'this',
				'source' => array( 'title' => 'Hello' ),
			),
		);

		$result = DynamicContentProcessor::replace_templates( '{this.missing.toUpperCase()}', $sources );

		$this->assertSame( '', $result );
	}
}

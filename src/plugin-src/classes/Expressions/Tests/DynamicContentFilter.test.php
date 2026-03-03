<?php
/**
 * DynamicContentFilter tests.
 *
 * @package DgInterview
 */

declare(strict_types=1);

namespace DgInterview\Expressions\Tests;

use DgInterview\Expressions\DynamicContentFilter;
use WP_UnitTestCase;

/**
 * Tests for the DynamicContentFilter class.
 *
 * Runs in separate processes because tests define the REST_REQUEST constant,
 * which cannot be undefined once set and would pollute other test classes.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DynamicContentFilterTest extends WP_UnitTestCase {

	/**
	 * Returns content unchanged when no expressions present.
	 *
	 * @test
	 */
	public function returns_content_unchanged_when_no_expressions_present(): void {
		$filter = new DynamicContentFilter();

		$result = $filter->resolve_expressions( '<p>Hello World</p>' );

		$this->assertSame( '<p>Hello World</p>', $result );
	}

	/**
	 * Returns content unchanged when REST request.
	 *
	 * @test
	 */
	public function returns_content_unchanged_when_rest_request(): void {
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}

		$filter = new DynamicContentFilter();

		$result = $filter->resolve_expressions( '<p>{this.title}</p>' );

		$this->assertSame( '<p>{this.title}</p>', $result );
	}
}

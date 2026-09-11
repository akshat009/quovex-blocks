<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Blocks\QueryGrid\Render\ItemsRenderer;
use QuovexBlocks\Tests\TestCase;
use ReflectionMethod;

/**
 * Regression/sync test for the audit finding that ItemsRenderer::build_page_url()
 * (PHP) and stripPaginationSuffix()/buildPaginatedPath() (src/query-grid/view.js)
 * are two independent implementations of the same pagination-path logic with
 * no guard against them drifting apart -- view.js's own docblock said as much
 * ("keep both in sync") without anything that would actually catch it.
 *
 * This runs every case in tests/fixtures/pagination-paths.json against the
 * PHP side; src/query-grid/test/pagination-url.test.js runs the exact same
 * file against the JS side. A case that only fails on one side is exactly
 * the kind of drift this pair is meant to catch.
 *
 * build_page_url() is `private` -- ReflectionMethod is used deliberately
 * here instead of promoting it to `public` just for testability, since
 * nothing outside ItemsRenderer has a legitimate reason to call it directly
 * (render_pagination() is the real public contract).
 *
 * @covers \QuovexBlocks\Blocks\QueryGrid\Render\ItemsRenderer::build_page_url
 */
class PaginationUrlSyncTest extends TestCase {

	private const ORIGIN = 'https://example.com';

	/** @var ReflectionMethod */
	private $build_page_url;

	protected function setUp(): void {
		parent::setUp();

		$this->build_page_url = new ReflectionMethod( ItemsRenderer::class, 'build_page_url' );
		$this->build_page_url->setAccessible( true );

		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'trailingslashit' )->alias( fn( $string ) => rtrim( $string, '/' ) . '/' );
		Functions\when( 'home_url' )->justReturn( self::ORIGIN );
	}

	/**
	 * @return array<string, array{0: array}> One PHPUnit data-set per fixture case, keyed by its name.
	 */
	public static function pagination_path_cases(): array {
		$fixture = json_decode(
			file_get_contents( dirname( __DIR__ ) . '/fixtures/pagination-paths.json' ),
			true
		);

		$cases = array();
		foreach ( $fixture['cases'] as $case ) {
			$cases[ $case['name'] ] = array( $case );
		}
		return $cases;
	}

	/**
	 * @dataProvider pagination_path_cases
	 */
	public function test_matches_the_shared_fixture( array $case ): void {
		Functions\when( 'get_option' )->justReturn( $case['slug'] );

		$renderer = new ItemsRenderer();
		$result   = $this->build_page_url->invoke( $renderer, self::ORIGIN . $case['pathname'], $case['page'] );

		$this->assertSame( self::ORIGIN . $case['expected'], $result );
	}
}

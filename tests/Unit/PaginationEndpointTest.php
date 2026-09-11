<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint;
use QuovexBlocks\Tests\TestCase;

/**
 * @covers \QuovexBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint
 */
class PaginationEndpointTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		// A close-enough stand-in for WordPress's real sanitize_title() --
		// lowercases and dashes; good enough for what these tests check.
		Functions\when( 'sanitize_title' )->alias(
			fn( $title ) => trim( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $title ) ), '-' )
		);
	}

	public function test_defaults_to_quovex_page_when_nothing_is_stored(): void {
		Functions\when( 'get_option' )->alias(
			fn( $name, $default = false ) => $default
		);

		$this->assertSame( 'quovex-page', PaginationEndpoint::slug() );
	}

	public function test_returns_the_sites_configured_slug_when_one_is_stored(): void {
		Functions\when( 'get_option' )->justReturn( 'My Custom Slug' );

		$this->assertSame( 'my-custom-slug', PaginationEndpoint::slug() );
	}

	public function test_falls_back_to_quovex_page_if_the_stored_value_sanitizes_to_nothing(): void {
		// e.g. a stored value that was pure punctuation/emoji -- sanitize_title()
		// can legitimately reduce that to an empty string.
		Functions\when( 'get_option' )->justReturn( '!!!' );

		$this->assertSame( 'quovex-page', PaginationEndpoint::slug() );
	}

	public function test_reads_from_the_correct_option_name(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( PaginationEndpoint::OPTION, 'quovex-page' )
			->andReturn( 'quovex-page' );

		PaginationEndpoint::slug();
	}
}

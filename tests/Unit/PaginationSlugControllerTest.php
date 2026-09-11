<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint;
use QuovexBlocks\Blocks\QueryGrid\Rest\PaginationSlugController;
use QuovexBlocks\Tests\TestCase;
use WP_REST_Request;

/**
 * @covers \QuovexBlocks\Blocks\QueryGrid\Rest\PaginationSlugController
 */
class PaginationSlugControllerTest extends TestCase {

	/** @var PaginationSlugController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new PaginationSlugController();

		Functions\when( 'sanitize_title' )->alias(
			fn( $title ) => trim( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $title ) ), '-' )
		);
	}

	public function test_check_read_permission_requires_edit_posts(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'edit_posts' )->andReturn( true );

		$this->assertTrue( $this->controller->check_read_permission() );
	}

	public function test_check_write_permission_requires_manage_options_not_edit_posts(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( false );

		$this->assertFalse( $this->controller->check_write_permission() );
	}

	public function test_get_slug_returns_the_currently_stored_slug(): void {
		Functions\when( 'get_option' )->justReturn( 'my-custom-slug' );

		$response = $this->controller->get_slug();

		$this->assertSame( array( 'slug' => 'my-custom-slug' ), $response->get_data() );
	}

	public function test_update_slug_sanitizes_and_persists_the_new_slug(): void {
		Functions\expect( 'update_option' )
			->once()
			->with( PaginationEndpoint::OPTION, 'my-new-slug' )
			->andReturn( true );

		$request  = new WP_REST_Request( array( 'slug' => 'My New Slug!!' ) );
		$response = $this->controller->update_slug( $request );

		$this->assertSame( array( 'slug' => 'my-new-slug' ), $response->get_data() );
	}

	public function test_update_slug_falls_back_to_quovex_page_when_it_sanitizes_to_nothing(): void {
		Functions\expect( 'update_option' )
			->once()
			->with( PaginationEndpoint::OPTION, 'quovex-page' )
			->andReturn( true );

		$request  = new WP_REST_Request( array( 'slug' => '!!!' ) );
		$response = $this->controller->update_slug( $request );

		$this->assertSame( array( 'slug' => 'quovex-page' ), $response->get_data() );
	}
}

<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Blocks\QueryGrid\Render\Renderer;
use QuovexBlocks\Blocks\QueryGrid\Rest\QueryController;
use QuovexBlocks\QueryEngine\QueryArgsBuilder;
use QuovexBlocks\Tests\TestCase;
use Mockery;
use WP_REST_Request;

/**
 * @covers \QuovexBlocks\Blocks\QueryGrid\Rest\QueryController
 */
class QueryControllerTest extends TestCase {

	/** @var Renderer&\Mockery\MockInterface */
	private $renderer;

	/** @var QueryArgsBuilder&\Mockery\MockInterface */
	private $args_builder;

	/** @var QueryController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->renderer     = Mockery::mock( Renderer::class );
		$this->args_builder = Mockery::mock( QueryArgsBuilder::class );
		$this->controller   = new QueryController( $this->renderer, $this->args_builder );

		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'absint' )->alias( fn( $n ) => abs( (int) $n ) );
		Functions\when( 'esc_url_raw' )->returnArg();
	}

	/**
	 * @param array $overrides Params to override on top of a full, valid request.
	 */
	private function make_request( array $overrides = array() ): WP_REST_Request {
		return new WP_REST_Request(
			array_merge(
				array(
					'postType' => 'post',
					'page'     => 1,
					'perPage'  => 9,
					'orderby'  => 'date',
					'order'    => 'desc',
					'taxonomy' => '',
					'terms'    => array(),
					'layout'   => 'grid',
					'search'   => '',
					'showAll'  => false,
					'pageUrl'  => 'https://example.com/blog/',
				),
				$overrides
			)
		);
	}

	public function test_check_permission_delegates_to_the_args_builders_public_post_type_check(): void {
		$this->args_builder->shouldReceive( 'is_public_post_type' )->once()->with( 'post' )->andReturn( true );

		$this->assertTrue( $this->controller->check_permission( $this->make_request() ) );
	}

	public function test_check_permission_rejects_a_non_public_post_type(): void {
		$this->args_builder->shouldReceive( 'is_public_post_type' )->once()->with( 'secret_cpt' )->andReturn( false );

		$this->assertFalse( $this->controller->check_permission( $this->make_request( array( 'postType' => 'secret_cpt' ) ) ) );
	}

	public function test_handle_request_returns_the_renderers_output_in_the_expected_response_shape(): void {
		$this->renderer->shouldReceive( 'query' )
			->once()
			->with( Mockery::type( 'array' ), 1 )
			->andReturn(
				array(
					'items'       => array( array( 'title' => 'A Post' ) ),
					'has_more'    => true,
					'total_pages' => 3,
				)
			);
		$this->renderer->shouldReceive( 'render_items' )->once()->andReturn( '<article>A Post</article>' );
		$this->renderer->shouldReceive( 'render_pagination' )->once()->with( 1, 3, 'https://example.com/blog/' )->andReturn( '<nav>pagination</nav>' );

		$response = $this->controller->handle_request( $this->make_request() );
		$data     = $response->get_data();

		$this->assertSame( '<article>A Post</article>', $data['html'] );
		$this->assertSame( '<nav>pagination</nav>', $data['pagination'] );
		$this->assertTrue( $data['hasMore'] );
		$this->assertSame( 3, $data['totalPages'] );
	}

	public function test_handle_request_leaves_pagination_empty_in_show_all_mode(): void {
		$this->renderer->shouldReceive( 'query' )->once()->andReturn(
			array(
				'items'       => array(),
				'has_more'    => false,
				'total_pages' => 1,
			)
		);
		$this->renderer->shouldReceive( 'render_items' )->once()->andReturn( '' );
		$this->renderer->shouldNotReceive( 'render_pagination' );

		$response = $this->controller->handle_request( $this->make_request( array( 'showAll' => true ) ) );

		$this->assertSame( '', $response->get_data()['pagination'] );
	}

	public function test_handle_request_never_goes_below_page_1_even_for_a_bogus_page_param(): void {
		$this->renderer->shouldReceive( 'query' )->once()->with( Mockery::type( 'array' ), 1 )->andReturn(
			array(
				'items'       => array(),
				'has_more'    => false,
				'total_pages' => 1,
			)
		);
		$this->renderer->shouldReceive( 'render_items' )->once()->andReturn( '' );
		$this->renderer->shouldReceive( 'render_pagination' )->once();

		$this->controller->handle_request( $this->make_request( array( 'page' => 0 ) ) );
	}

	public function test_handle_request_passes_the_taxonomy_filter_through_to_the_query(): void {
		$this->renderer->shouldReceive( 'query' )
			->once()
			->with(
				Mockery::on(
					function ( $attributes ) {
						return 'category' === $attributes['taxonomyFilter']['taxonomy']
							&& array( 3, 7 ) === $attributes['taxonomyFilter']['terms'];
					}
				),
				1
			)
			->andReturn(
				array(
					'items'       => array(),
					'has_more'    => false,
					'total_pages' => 1,
				)
			);
		$this->renderer->shouldReceive( 'render_items' )->once()->andReturn( '' );
		$this->renderer->shouldReceive( 'render_pagination' )->once();

		$this->controller->handle_request(
			$this->make_request(
				array(
					'taxonomy' => 'category',
					'terms'    => array( 3, 7 ),
				)
			)
		);
	}
}

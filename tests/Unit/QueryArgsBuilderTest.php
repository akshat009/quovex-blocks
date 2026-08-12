<?php
/**
 * @package FluxBlocks\Tests
 */

namespace FluxBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use FluxBlocks\Query\QueryArgsBuilder;
use FluxBlocks\Tests\TestCase;
use WP_Post_Type;

/**
 * @covers \FluxBlocks\Query\QueryArgsBuilder
 */
class QueryArgsBuilderTest extends TestCase {

	/** @var QueryArgsBuilder */
	private $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new QueryArgsBuilder();

		// Common mocks every test relies on -- individual tests override
		// get_post_type_object() when they need a different post type shape.
		Functions\when( 'sanitize_key' )->alias(
			fn( $key ) => strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) )
		);
		Functions\when( 'absint' )->alias( fn( $n ) => abs( (int) $n ) );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'get_post_type_object' )->alias(
			fn( $post_type ) => new WP_Post_Type( $post_type, true )
		);
	}

	public function test_defaults_to_post_type_post_when_not_given(): void {
		$args = $this->builder->build( array() );
		$this->assertSame( 'post', $args['post_type'] );
	}

	public function test_falls_back_to_post_when_the_requested_post_type_is_not_public(): void {
		Functions\when( 'get_post_type_object' )->alias(
			fn( $post_type ) => new WP_Post_Type( $post_type, false ) // public = false
		);

		$args = $this->builder->build( array( 'postType' => 'secret_cpt' ) );

		$this->assertSame( 'post', $args['post_type'], 'A non-public post type must never reach WP_Query directly.' );
	}

	public function test_falls_back_to_post_when_the_requested_post_type_is_not_registered(): void {
		Functions\when( 'get_post_type_object' )->justReturn( null );

		$args = $this->builder->build( array( 'postType' => 'does_not_exist' ) );

		$this->assertSame( 'post', $args['post_type'] );
	}

	public function test_post_count_is_used_as_posts_per_page(): void {
		$args = $this->builder->build( array( 'postCount' => 5 ) );
		$this->assertSame( 5, $args['posts_per_page'] );
	}

	public function test_post_count_is_capped_at_50_even_if_a_larger_value_is_requested(): void {
		$args = $this->builder->build( array( 'postCount' => 999 ) );
		$this->assertSame( 50, $args['posts_per_page'] );
	}

	public function test_post_count_can_never_go_below_1(): void {
		$args = $this->builder->build( array( 'postCount' => 0 ) );
		$this->assertSame( 1, $args['posts_per_page'] );
	}

	public function test_defaults_to_9_per_page_when_post_count_is_not_given(): void {
		$args = $this->builder->build( array() );
		$this->assertSame( 9, $args['posts_per_page'] );
	}

	public function test_show_all_posts_uses_the_200_bound_and_ignores_post_count(): void {
		$args = $this->builder->build(
			array(
				'showAllPosts' => true,
				'postCount'    => 3,
			)
		);
		$this->assertSame( 200, $args['posts_per_page'] );
	}

	public function test_manual_ids_bypasses_ordering_and_pagination_entirely(): void {
		$args = $this->builder->build(
			array(
				'postType'  => 'post',
				'manualIds' => array( '5', '9', '2' ),
				// These would normally affect the args below -- manual
				// selection must ignore them completely.
				'postCount' => 1,
				'orderBy'   => 'title',
			)
		);

		$this->assertSame( array( 5, 9, 2 ), $args['post__in'] );
		$this->assertSame( 'post__in', $args['orderby'] );
		$this->assertSame( 3, $args['posts_per_page'] );
		$this->assertArrayNotHasKey( 'paged', $args );
	}

	public function test_order_by_maps_known_values(): void {
		$args = $this->builder->build( array( 'orderBy' => 'title' ) );
		$this->assertSame( 'title', $args['orderby'] );
	}

	public function test_order_by_falls_back_to_date_for_an_unknown_value(): void {
		$args = $this->builder->build( array( 'orderBy' => 'not-a-real-option' ) );
		$this->assertSame( 'date', $args['orderby'] );
	}

	public function test_order_asc_is_recognised_case_insensitively(): void {
		$args = $this->builder->build( array( 'order' => 'ASC' ) );
		$this->assertSame( 'ASC', $args['order'] );
	}

	public function test_order_defaults_to_desc(): void {
		$args = $this->builder->build( array() );
		$this->assertSame( 'DESC', $args['order'] );
	}

	public function test_taxonomy_filter_adds_a_tax_query_when_taxonomy_and_terms_are_both_set(): void {
		$args = $this->builder->build(
			array(
				'taxonomyFilter' => array(
					'taxonomy' => 'category',
					'terms'    => array( '3', '7' ),
				),
			)
		);

		$this->assertArrayHasKey( 'tax_query', $args );
		$this->assertSame( 'category', $args['tax_query'][0]['taxonomy'] );
		$this->assertSame( array( 3, 7 ), $args['tax_query'][0]['terms'] );
	}

	public function test_taxonomy_filter_is_omitted_when_terms_are_empty(): void {
		$args = $this->builder->build(
			array(
				'taxonomyFilter' => array(
					'taxonomy' => 'category',
					'terms'    => array(),
				),
			)
		);

		$this->assertArrayNotHasKey( 'tax_query', $args );
	}

	public function test_search_attribute_is_sanitized_into_the_s_arg(): void {
		$args = $this->builder->build( array( 'search' => 'hello world' ) );
		$this->assertSame( 'hello world', $args['s'] );
	}

	public function test_search_is_omitted_when_not_given(): void {
		$args = $this->builder->build( array() );
		$this->assertArrayNotHasKey( 's', $args );
	}

	public function test_page_number_is_passed_through_as_paged(): void {
		$args = $this->builder->build( array(), 4 );
		$this->assertSame( 4, $args['paged'] );
	}

	public function test_page_number_can_never_go_below_1(): void {
		$args = $this->builder->build( array(), 0 );
		$this->assertSame( 1, $args['paged'] );
	}
}

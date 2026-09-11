<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Blocks\QueryGrid\Render\FacetRenderer;
use QuovexBlocks\Cache\CacheInterface;
use QuovexBlocks\Tests\TestCase;
use Mockery;

/**
 * @covers \QuovexBlocks\Blocks\QueryGrid\Render\FacetRenderer
 */
class FacetRendererTest extends TestCase {

	/** @var CacheInterface&\Mockery\MockInterface */
	private $cache;

	/** @var FacetRenderer */
	private $renderer;

	protected function setUp(): void {
		parent::setUp();
		$this->cache    = Mockery::mock( CacheInterface::class );
		$this->renderer = new FacetRenderer( $this->cache );

		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_attr_e' )->alias(
			function ( $text ) {
				echo $text;
			}
		);
		Functions\when( 'esc_html_e' )->alias(
			function ( $text ) {
				echo $text;
			}
		);
		Functions\when( 'wp_interactivity_data_wp_context' )->alias(
			fn( $context ) => 'data-wp-context="' . wp_json_encode( $context ) . '"'
		);
		Functions\when( 'sanitize_key' )->alias(
			fn( $key ) => strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) )
		);
	}

	public function test_render_search_form_contains_the_search_input_and_submit_button(): void {
		$html = $this->renderer->render_search_form();

		$this->assertStringContainsString( 'qv-query-grid__search-input', $html );
		$this->assertStringContainsString( 'data-wp-on--submit="actions.onSearchSubmit"', $html );
	}

	public function test_render_facet_group_returns_an_empty_string_when_there_are_no_terms(): void {
		$this->cache->shouldReceive( 'remember' )->once()->andReturn( array() );

		$html = $this->renderer->render_facet_group( 'category', 'post', true );

		$this->assertSame( '', $html );
	}

	public function test_render_facet_group_renders_one_pill_per_term(): void {
		$this->cache->shouldReceive( 'remember' )->once()->andReturn(
			array(
				(object) array(
					'term_id' => 3,
					'name'    => 'News',
				),
				(object) array(
					'term_id' => 7,
					'name'    => 'Reviews',
				),
			)
		);
		Functions\when( 'get_taxonomy' )->justReturn(
			(object) array( 'labels' => (object) array( 'name' => 'Categories' ) )
		);

		$html = $this->renderer->render_facet_group( 'category', 'post', true );

		$this->assertSame( 2, substr_count( $html, 'qv-query-grid__filter-btn' ) );
		$this->assertStringContainsString( 'News', $html );
		$this->assertStringContainsString( 'Reviews', $html );
	}

	public function test_render_facet_group_omits_the_heading_when_show_heading_is_false(): void {
		$this->cache->shouldReceive( 'remember' )->once()->andReturn(
			array( (object) array( 'term_id' => 3, 'name' => 'News' ) )
		);
		Functions\when( 'get_taxonomy' )->justReturn(
			(object) array( 'labels' => (object) array( 'name' => 'Categories' ) )
		);

		$html = $this->renderer->render_facet_group( 'category', 'post', false );

		$this->assertStringNotContainsString( 'qv-query-grid__filter-heading', $html );
	}

	public function test_render_facet_group_prefers_a_custom_heading_over_the_taxonomy_label(): void {
		$this->cache->shouldReceive( 'remember' )->once()->andReturn(
			array( (object) array( 'term_id' => 3, 'name' => 'News' ) )
		);
		Functions\when( 'get_taxonomy' )->justReturn(
			(object) array( 'labels' => (object) array( 'name' => 'Categories' ) )
		);

		$html = $this->renderer->render_facet_group( 'category', 'post', true, 'Browse Topics' );

		$this->assertStringContainsString( 'Browse Topics', $html );
		$this->assertStringNotContainsString( 'Categories', $html );
	}

	public function test_get_terms_for_post_type_is_cached_by_a_post_type_and_taxonomy_specific_signature(): void {
		$this->cache->shouldReceive( 'remember' )
			->once()
			->with( 'product', 'product|facet-terms|color', Mockery::type( 'callable' ) )
			->andReturn( array() );

		$this->renderer->render_facet_group( 'color', 'product', false );
	}

	public function test_resolve_facet_taxonomies_returns_the_explicit_list_when_the_block_sets_one(): void {
		$result = $this->renderer->resolve_facet_taxonomies(
			'post',
			array( 'facetTaxonomies' => array( 'category', 'post_tag' ) )
		);

		$this->assertSame( array( 'category', 'post_tag' ), $result );
	}

	public function test_resolve_facet_taxonomies_falls_back_to_every_public_taxonomy_when_none_are_explicit(): void {
		Functions\when( 'get_object_taxonomies' )->justReturn(
			array(
				'category' => (object) array(
					'name'         => 'category',
					'public'       => true,
					'hierarchical' => true,
				),
				'post_tag' => (object) array(
					'name'         => 'post_tag',
					'public'       => true,
					'hierarchical' => false,
				),
				'internal' => (object) array(
					'name'         => 'internal',
					'public'       => false,
					'hierarchical' => true,
				),
			)
		);

		$result = $this->renderer->resolve_facet_taxonomies( 'post', array() );

		$this->assertSame( array( 'category' ), $result, 'Only public hierarchical taxonomies should be included by default.' );
	}
}

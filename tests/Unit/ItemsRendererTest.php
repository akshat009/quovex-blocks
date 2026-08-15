<?php
/**
 * @package FluxBlocks\Tests
 */

namespace FluxBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use FluxBlocks\Blocks\QueryGrid\Render\ItemsRenderContext;
use FluxBlocks\Blocks\QueryGrid\Render\ItemsRenderer;
use FluxBlocks\Tests\TestCase;

/**
 * @covers \FluxBlocks\Blocks\QueryGrid\Render\ItemsRenderer
 */
class ItemsRendererTest extends TestCase {

	/** @var ItemsRenderer */
	private $renderer;

	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new ItemsRenderer();

		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html_e' )->alias(
			function ( $text ) {
				echo $text;
			}
		);
		Functions\when( 'esc_attr_e' )->alias(
			function ( $text ) {
				echo $text;
			}
		);
		Functions\when( 'wp_interactivity_data_wp_context' )->alias(
			fn( $context ) => 'data-wp-context="' . wp_json_encode( $context ) . '"'
		);

		// build_page_url()'s collaborators -- PaginationEndpoint::slug()
		// reads get_option()/sanitize_title(), then build_page_url() itself
		// calls wp_parse_url()/trailingslashit()/home_url().
		Functions\when( 'get_option' )->justReturn( 'flux-page' );
		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'trailingslashit' )->alias( fn( $string ) => rtrim( $string, '/' ) . '/' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
	}

	/**
	 * @return array Minimal transformed-post shape (see PostDataTransformer::transform()).
	 */
	private function make_item( array $overrides = array() ): array {
		return array_merge(
			array(
				'title'     => 'Hello World',
				'excerpt'   => 'An excerpt.',
				'permalink' => 'https://example.com/hello-world/',
				'image'     => null,
				'imageAlt'  => '',
				'author'    => 'Jane Doe',
				'date'      => 'August 15, 2026',
			),
			$overrides
		);
	}

	public function test_render_items_shows_the_empty_state_when_there_are_no_items(): void {
		$html = $this->renderer->render_items( array(), 'grid' );

		$this->assertStringContainsString( 'fb-query-grid__empty', $html );
		$this->assertStringContainsString( 'No items found.', $html );
	}

	public function test_render_items_renders_one_article_per_item(): void {
		$items = array( $this->make_item(), $this->make_item( array( 'title' => 'Second Post' ) ) );

		$html = $this->renderer->render_items( $items, 'grid' );

		$this->assertSame( 2, substr_count( $html, 'fb-query-grid__item ' ) );
		$this->assertStringContainsString( 'Hello World', $html );
		$this->assertStringContainsString( 'Second Post', $html );
	}

	public function test_render_items_shows_an_image_placeholder_when_the_item_has_no_image(): void {
		$html = $this->renderer->render_items( array( $this->make_item() ), 'grid' );

		$this->assertStringContainsString( 'fb-query-grid__item-image-placeholder', $html );
	}

	public function test_render_items_renders_the_image_when_the_item_has_one(): void {
		$html = $this->renderer->render_items(
			array( $this->make_item( array( 'image' => 'https://example.com/cat.jpg' ) ) ),
			'grid'
		);

		$this->assertStringContainsString( 'src="https://example.com/cat.jpg"', $html );
		$this->assertStringNotContainsString( 'fb-query-grid__item-image-placeholder', $html );
	}

	public function test_render_items_hides_slides_beyond_the_carousel_items_per_view(): void {
		$items = array( $this->make_item(), $this->make_item(), $this->make_item() );

		$html = $this->renderer->render_items( $items, 'carousel', 2 );

		// Each item's markup also contains two `aria-hidden="true"` decorative
		// attributes (image placeholder + meta separator dot) -- subtracting
		// those out isolates just the bare `hidden` attribute this test cares
		// about, the one that actually hides a whole slide.
		$bare_hidden_count = substr_count( $html, 'hidden' ) - substr_count( $html, 'aria-hidden' );
		$this->assertSame( 1, $bare_hidden_count, 'Only the 3rd slide (index 2) should be hidden with items_per_view=2.' );
	}

	public function test_render_pagination_returns_nothing_for_a_single_page(): void {
		$this->assertSame( '', $this->renderer->render_pagination( 1, 1 ) );
		$this->assertSame( '', $this->renderer->render_pagination( 1, 0 ) );
	}

	public function test_render_pagination_disables_prev_on_the_first_page(): void {
		$html = $this->renderer->render_pagination( 1, 3, 'https://example.com/blog/' );

		$this->assertMatchesRegularExpression(
			'/<span class="fb-query-grid__page-btn fb-query-grid__page-btn--prev" aria-disabled="true">/',
			$html
		);
		$this->assertStringContainsString( 'fb-query-grid__page-btn--next"', $html );
	}

	public function test_render_pagination_disables_next_on_the_last_page(): void {
		$html = $this->renderer->render_pagination( 3, 3, 'https://example.com/blog/' );

		$this->assertMatchesRegularExpression(
			'/<span class="fb-query-grid__page-btn fb-query-grid__page-btn--next" aria-disabled="true">/',
			$html
		);
	}

	public function test_render_pagination_marks_the_current_page_active_and_not_a_link(): void {
		$html = $this->renderer->render_pagination( 2, 3, 'https://example.com/blog/' );

		$this->assertMatchesRegularExpression( '/<span\s+class="fb-query-grid__page-btn is-active"/', $html );
	}

	public function test_render_pagination_collapses_far_away_pages_into_an_ellipsis(): void {
		$html = $this->renderer->render_pagination( 1, 10, 'https://example.com/blog/' );

		$this->assertStringContainsString( 'fb-query-grid__page-ellipsis', $html );
		// Page 1 (edge) and 2 (nearby current) are shown, but 4-9 are collapsed
		// before page 10 (the other edge) -- page 5 should not appear as a link.
		$this->assertStringNotContainsString( '"pageNum":5', $html );
	}

	public function test_render_pagination_page_1_link_has_no_slug_suffix(): void {
		$html = $this->renderer->render_pagination( 2, 3, 'https://example.com/blog/' );

		$this->assertStringContainsString( 'href="https://example.com/blog/"', $html, 'Page 1 must link back to the plain base URL, no /flux-page/1/ suffix.' );
	}

	public function test_render_pagination_page_2_link_includes_the_slug_suffix(): void {
		$html = $this->renderer->render_pagination( 1, 3, 'https://example.com/blog/' );

		$this->assertStringContainsString( 'href="https://example.com/blog/flux-page/2/"', $html );
	}

	public function test_render_pagination_strips_an_existing_slug_suffix_before_rebuilding_it(): void {
		// Re-rendering pagination FROM page 2 (base_url already has /flux-page/2/
		// in it) must not double up into /flux-page/2/flux-page/3/.
		$html = $this->renderer->render_pagination( 2, 3, 'https://example.com/blog/flux-page/2/' );

		$this->assertStringContainsString( 'href="https://example.com/blog/flux-page/3/"', $html );
		$this->assertStringNotContainsString( 'flux-page/2/flux-page', $html );
	}

	public function test_render_items_and_nav_renders_no_nav_in_show_all_mode(): void {
		$context = new ItemsRenderContext(
			array(
				'items'       => array( $this->make_item() ),
				'has_more'    => false,
				'total_pages' => 1,
			),
			'grid',
			false,
			'numbers',
			3,
			true // show_all_no_nav
		);

		$html = $this->renderer->render_items_and_nav( $context );

		$this->assertStringNotContainsString( 'fb-query-grid__pagination', $html );
		$this->assertStringNotContainsString( 'fb-query-grid__load-more', $html );
		$this->assertStringNotContainsString( 'fb-query-grid__carousel-nav', $html );
	}

	public function test_render_items_and_nav_renders_carousel_controls_for_the_carousel_layout(): void {
		$context = new ItemsRenderContext(
			array(
				'items'       => array( $this->make_item() ),
				'has_more'    => false,
				'total_pages' => 1,
			),
			'carousel',
			true,
			'numbers'
		);

		$html = $this->renderer->render_items_and_nav( $context );

		$this->assertStringContainsString( 'fb-query-grid__carousel-nav', $html );
	}

	public function test_render_items_and_nav_renders_a_load_more_button_when_load_more_style_and_has_more(): void {
		$context = new ItemsRenderContext(
			array(
				'items'       => array( $this->make_item() ),
				'has_more'    => true,
				'total_pages' => 2,
			),
			'grid',
			false,
			'load-more'
		);

		$html = $this->renderer->render_items_and_nav( $context );

		$this->assertStringContainsString( 'fb-query-grid__load-more', $html );
	}

	public function test_render_items_and_nav_renders_nothing_for_load_more_style_when_there_is_no_more(): void {
		$context = new ItemsRenderContext(
			array(
				'items'       => array( $this->make_item() ),
				'has_more'    => false,
				'total_pages' => 1,
			),
			'grid',
			false,
			'load-more'
		);

		$html = $this->renderer->render_items_and_nav( $context );

		$this->assertStringNotContainsString( 'fb-query-grid__load-more', $html );
	}

	public function test_render_items_and_nav_renders_numbered_pagination_for_the_numbers_style(): void {
		$context = new ItemsRenderContext(
			array(
				'items'       => array( $this->make_item() ),
				'has_more'    => true,
				'total_pages' => 3,
			),
			'grid',
			false,
			'numbers',
			3,
			false,
			1,
			'https://example.com/blog/'
		);

		$html = $this->renderer->render_items_and_nav( $context );

		$this->assertStringContainsString( 'fb-query-grid__pagination-slot', $html );
		$this->assertStringContainsString( 'fb-query-grid__pagination', $html );
	}
}

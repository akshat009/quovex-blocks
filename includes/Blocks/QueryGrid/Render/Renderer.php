<?php
/**
 * Orchestrates the Query Grid block: runs the query, resolves
 * layout/colors/typography, and assembles markup by delegating to
 * StyleBuilder (CSS custom properties), FacetRenderer (search + filter
 * pills), and ItemsRenderer (items + pagination/"Load more"/carousel nav).
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Blocks\View\AbstractRenderer;
use FluxBlocks\QueryEngine\ArgsBuilderInterface;
use FluxBlocks\Cache\CacheInterface;
use FluxBlocks\QueryEngine\TransformerInterface;
use FluxBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint;

/**
 * Builds the Query Grid query, transforms results, and renders a layout.
 */
class Renderer extends AbstractRenderer {

	/** Every layout slug Query Grid actually supports as a Style Variation. */
	const KNOWN_LAYOUTS = array( 'grid', 'list', 'masonry', 'carousel' );

	/** @var ArgsBuilderInterface */
	private $args_builder;

	/** @var CacheInterface */
	private $cache;

	/** @var TransformerInterface */
	private $transformer;

	/** @var StyleBuilder */
	private $style_builder;

	/** @var FacetRenderer */
	private $facets;

	/** @var ItemsRenderer */
	private $items;

	/**
	 * StyleBuilder/FacetRenderer/ItemsRenderer are composed internally, not
	 * constructor-injected -- they're pure view helpers with no external
	 * dependencies beyond $cache.
	 *
	 * @param ArgsBuilderInterface $args_builder Turns attributes into WP_Query args.
	 * @param CacheInterface       $cache        Read-through query cache.
	 * @param TransformerInterface $transformer  Turns WP_Post into template-ready data.
	 */
	public function __construct( ArgsBuilderInterface $args_builder, CacheInterface $cache, TransformerInterface $transformer ) {
		$this->args_builder  = $args_builder;
		$this->cache         = $cache;
		$this->transformer   = $transformer;
		$this->style_builder = new StyleBuilder();
		$this->facets        = new FacetRenderer( $cache );
		$this->items         = new ItemsRenderer();
	}

	/**
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block default content -- unused, but required by the dynamic-block render signature.
	 * @param \WP_Block $block      Block instance -- unused, same reason as $content.
	 * @return string
	 */
	public function render( array $attributes, string $content, \WP_Block $block ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $content/$block are part of WordPress's fixed render_callback signature, not optional.
		$post_type               = $attributes['postType'] ?? 'post';
		$layout                  = $this->layout_from_class_name( $attributes['className'] ?? '', self::KNOWN_LAYOUTS, 'grid' );
		$query_id                = ! empty( $attributes['queryId'] ) ? $attributes['queryId'] : wp_unique_id( 'fbq-' );
		$initial_page            = max( 1, absint( get_query_var( PaginationEndpoint::slug() ) ) );
		$show_heading            = ! empty( $attributes['showHeading'] );
		$heading                 = $show_heading ? ( $attributes['heading'] ?? '' ) : '';
		$heading_accent          = $attributes['headingAccent'] ?? '';
		$show_search             = ! empty( $attributes['showSearch'] );
		$show_filter             = ! empty( $attributes['showCategoryFilter'] );
		$show_filter_headings    = ! empty( $attributes['showFilterHeadings'] );
		$facet_headings          = is_array( $attributes['facetHeadings'] ?? null ) ? $attributes['facetHeadings'] : array();
		$is_carousel             = 'carousel' === $layout;
		$colors                  = wp_parse_args( is_array( $attributes['colors'] ?? null ) ? $attributes['colors'] : array(), $this->style_builder->default_colors() );
		$typography              = wp_parse_args( is_array( $attributes['typography'] ?? null ) ? $attributes['typography'] : array(), $this->style_builder->default_typography() );
		$carousel_items_per_view = max( 1, (int) ( $attributes['carouselItemsPerView'] ?? 3 ) );

		$search_align     = in_array( $attributes['searchAlign'] ?? 'center', array( 'left', 'center', 'right' ), true )
			? $attributes['searchAlign']
			: 'center';
		$sidebar_side     = in_array( $attributes['sidebarSide'] ?? 'left', array( 'left', 'right' ), true )
			? $attributes['sidebarSide']
			: 'left';
		$pagination_style = in_array( $attributes['paginationStyle'] ?? 'numbers', array( 'numbers', 'load-more' ), true )
			? $attributes['paginationStyle']
			: 'numbers';
		$show_all_query   = ! empty( $attributes['showAllPosts'] );
		$show_all_no_nav  = $show_all_query && ! $is_carousel;

		$facet_taxonomies = ( $show_filter && ! $is_carousel )
			? $this->facets->resolve_facet_taxonomies( $post_type, $attributes )
			: array();
		$is_sidebar       = ! $is_carousel && ! empty( $attributes['showSidebar'] )
			&& ( $show_search || ! empty( $facet_taxonomies ) );

		$result      = $this->query( array_merge( $attributes, array( 'showAllPosts' => $show_all_query ) ), $initial_page );
		$current_url = home_url( add_query_arg( null, null ) );

		$columns = wp_parse_args(
			is_array( $attributes['columns'] ?? null ) ? $attributes['columns'] : array(),
			array(
				'mobile'  => 1,
				'tablet'  => 2,
				'desktop' => 3,
			)
		);

		$wrapper_attrs = get_block_wrapper_attributes(
			array(
				'class' => trim(
					'fb-query-grid fb-query-grid--' . sanitize_html_class( $layout )
					. ( $is_sidebar ? ' fb-query-grid--filter-sidebar fb-query-grid--sidebar-' . sanitize_html_class( $sidebar_side ) : '' )
				),
				'style' => $this->style_builder->build_inline_style( $columns, $colors, $typography, $carousel_items_per_view ),
			)
		);

		$context = array(
			'queryId'              => $query_id,
			'postType'             => sanitize_key( $post_type ),
			'layout'               => sanitize_key( $layout ),
			'page'                 => $initial_page,
			'paginationSlug'       => PaginationEndpoint::slug(),
			'totalPages'           => $result['total_pages'],
			'hasMore'              => $result['has_more'],
			'carouselIndex'        => 0,
			'carouselItemsPerView' => $carousel_items_per_view,
			'showAllPosts'         => $show_all_query,
			'isLoading'            => false,
			'searchQuery'          => '',
			'activeFacetTaxonomy'  => '',
			'activeTermIds'        => array(),
		);

		$items_and_nav = $this->items->render_items_and_nav(
			new ItemsRenderContext( $result, $layout, $is_carousel, $pagination_style, $carousel_items_per_view, $show_all_no_nav, $initial_page, $current_url )
		);

		return $this->capture(
			function () use (
				$wrapper_attrs,
				$context,
				$heading,
				$heading_accent,
				$is_sidebar,
				$is_carousel,
				$show_search,
				$search_align,
				$facet_taxonomies,
				$post_type,
				$show_filter_headings,
				$facet_headings,
				$items_and_nav
			) {
				?>
			<div
				<?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() already escapes. ?>
				data-wp-interactive="flux-blocks/query-grid"
				<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core helper already escapes/encodes. ?>
			>
				<?php if ( $heading ) : ?>
					<h2 class="fb-query-grid__heading"><?php echo $this->render_heading( $heading, $heading_accent ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_heading() escapes both parts. ?></h2>
				<?php endif; ?>

				<?php if ( $is_sidebar ) : ?>
					<div class="fb-query-grid__layout">
						<aside class="fb-query-grid__sidebar">
							<?php if ( $show_search ) : ?>
								<div class="fb-query-grid__toolbar fb-query-grid__toolbar--align-<?php echo esc_attr( $search_align ); ?>">
									<?php echo $this->facets->render_search_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_search_form() escapes per field. ?>
								</div>
							<?php endif; ?>
							<?php foreach ( $facet_taxonomies as $taxonomy ) : ?>
								<?php echo $this->facets->render_facet_group( $taxonomy, $post_type, $show_filter_headings, $facet_headings[ $taxonomy ] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_facet_group() escapes per field. ?>
							<?php endforeach; ?>
						</aside>
						<div class="fb-query-grid__main">
							<?php echo $items_and_nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ItemsRenderer::render_items_and_nav() escapes per field internally. ?>
						</div>
					</div>
				<?php else : ?>
					<?php if ( ! $is_carousel && ( $show_search || ! empty( $facet_taxonomies ) ) ) : ?>
						<div class="fb-query-grid__toolbar fb-query-grid__toolbar--align-<?php echo esc_attr( $search_align ); ?>">
							<?php if ( $show_search ) : ?>
								<?php echo $this->facets->render_search_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_search_form() escapes per field. ?>
							<?php endif; ?>

							<?php foreach ( $facet_taxonomies as $taxonomy ) : ?>
								<?php echo $this->facets->render_facet_group( $taxonomy, $post_type, $show_filter_headings, $facet_headings[ $taxonomy ] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_facet_group() escapes per field. ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php echo $items_and_nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ItemsRenderer::render_items_and_nav() escapes per field internally. ?>
				<?php endif; ?>
			</div>
				<?php
			}
		);
	}

	/**
	 * @param string $heading Full heading text.
	 * @param string $accent  Substring of $heading to wrap in an accent span.
	 * @return string Escaped HTML.
	 */
	private function render_heading( string $heading, string $accent ): string {
		if ( '' === $accent || false === strpos( $heading, $accent ) ) {
			return esc_html( $heading );
		}

		list( $before, $after ) = explode( $accent, $heading, 2 );

		return esc_html( $before )
			. '<span class="fb-query-grid__heading-accent">' . esc_html( $accent ) . '</span>'
			. esc_html( $after );
	}

	/**
	 * Render item markup -- public so QueryController's REST endpoint can
	 * reuse it for AJAX pagination/search/filter responses.
	 *
	 * @param array[] $items                   Transformed post data.
	 * @param string  $layout                  Layout slug.
	 * @param int     $carousel_items_per_view Cards per view.
	 * @return string Escaped HTML.
	 */
	public function render_items( array $items, string $layout, int $carousel_items_per_view = 3 ): string {
		return $this->items->render_items( $items, $layout, $carousel_items_per_view );
	}

	/**
	 * Renders Prev / page-number / Next controls -- public for the same
	 * reason as render_items() above. See ItemsRenderer::render_pagination()
	 * for the reasoning behind this markup (this just delegates to it).
	 *
	 * @param int    $current_page Page currently displayed.
	 * @param int    $total_pages  Total pages.
	 * @param string $base_url     Base page URL.
	 * @return string Escaped HTML.
	 */
	public function render_pagination( int $current_page, int $total_pages, string $base_url = '' ): string {
		return $this->items->render_pagination( $current_page, $total_pages, $base_url );
	}

	/**
	 * Runs cached query and transforms results.
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $page       Page number.
	 * @return array{items:array[],has_more:bool,total_pages:int}
	 */
	public function query( array $attributes, int $page ): array {
		$post_type = $attributes['postType'] ?? 'post';
		$args      = $this->args_builder->build( $attributes, $page );
		$signature = $post_type . '|grid|' . wp_json_encode( $args );

		return $this->cache->remember(
			$post_type,
			$signature,
			function () use ( $args ) {
				$query = new \WP_Query( $args );
				return array(
					'items'       => $this->transformer->transform_many( $query->posts ),
					'has_more'    => (int) $query->max_num_pages > (int) ( $args['paged'] ?? 1 ),
					'total_pages' => max( 1, (int) $query->max_num_pages ),
				);
			}
		);
	}
}

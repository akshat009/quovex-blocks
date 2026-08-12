<?php
/**
 * Renders the Query Grid block: builds the query, transforms results, and
 * outputs a layout-specific template with a two-tone heading, an optional
 * search bar, one or more taxonomy filter-pill facets, and numbered
 * pagination.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Blocks\AbstractRenderer;
use FluxBlocks\Query\QueryArgsBuilder;
use FluxBlocks\Query\QueryCache;
use FluxBlocks\Query\PostDataTransformer;
use FluxBlocks\PaginationEndpoint;

/**
 * Builds the Query Grid query, transforms results, and renders a layout.
 */
class Renderer extends AbstractRenderer {

	/** @var QueryArgsBuilder */
	private $args_builder;

	/** @var QueryCache */
	private $cache;

	/** @var PostDataTransformer */
	private $transformer;

	/**
	 * @param QueryArgsBuilder    $args_builder Turns attributes into WP_Query args.
	 * @param QueryCache          $cache        Read-through query cache.
	 * @param PostDataTransformer $transformer  Turns WP_Post into template-ready data.
	 */
	public function __construct( QueryArgsBuilder $args_builder, QueryCache $cache, PostDataTransformer $transformer ) {
		$this->args_builder = $args_builder;
		$this->cache        = $cache;
		$this->transformer  = $transformer;
	}

	/**
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block default content -- unused, but required by the dynamic-block render signature.
	 * @param \WP_Block $block      Block instance -- unused, same reason as $content.
	 * @return string
	 */
	public function render( array $attributes, string $content, \WP_Block $block ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $content/$block are part of WordPress's fixed render_callback signature, not optional.
		$post_type               = $attributes['postType'] ?? 'post';
		$layout                  = $this->layout_from_class_name( $attributes['className'] ?? '' );
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
		$colors                  = wp_parse_args( is_array( $attributes['colors'] ?? null ) ? $attributes['colors'] : array(), $this->default_colors() );
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
			? $this->resolve_facet_taxonomies( $post_type, $attributes )
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
				'style' => $this->build_inline_style( $columns, $colors, $carousel_items_per_view ),
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

		return $this->capture(
			function () use (
				$wrapper_attrs,
				$context,
				$heading,
				$heading_accent,
				$is_sidebar,
				$show_search,
				$search_align,
				$facet_taxonomies,
				$post_type,
				$show_filter_headings,
				$facet_headings,
				$result,
				$layout,
				$is_carousel,
				$pagination_style,
				$carousel_items_per_view,
				$show_all_no_nav,
				$initial_page,
				$current_url
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
									<?php echo $this->render_search_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_search_form() escapes per field. ?>
								</div>
							<?php endif; ?>
							<?php foreach ( $facet_taxonomies as $taxonomy ) : ?>
								<?php echo $this->render_facet_group( $taxonomy, $post_type, $show_filter_headings, $facet_headings[ $taxonomy ] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_facet_group() escapes per field. ?>
							<?php endforeach; ?>
						</aside>
						<div class="fb-query-grid__main">
							<?php echo $this->render_items_and_nav( $result, $layout, $is_carousel, $pagination_style, $carousel_items_per_view, $show_all_no_nav, $initial_page, $current_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapes per field internally. ?>
						</div>
					</div>
				<?php else : ?>
					<?php if ( ! $is_carousel && ( $show_search || ! empty( $facet_taxonomies ) ) ) : ?>
						<div class="fb-query-grid__toolbar fb-query-grid__toolbar--align-<?php echo esc_attr( $search_align ); ?>">
							<?php if ( $show_search ) : ?>
								<?php echo $this->render_search_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_search_form() escapes per field. ?>
							<?php endif; ?>

							<?php foreach ( $facet_taxonomies as $taxonomy ) : ?>
								<?php echo $this->render_facet_group( $taxonomy, $post_type, $show_filter_headings, $facet_headings[ $taxonomy ] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_facet_group() escapes per field. ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php echo $this->render_items_and_nav( $result, $layout, $is_carousel, $pagination_style, $carousel_items_per_view, $show_all_no_nav, $initial_page, $current_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapes per field internally. ?>
				<?php endif; ?>
			</div>
				<?php
			}
		);
	}

	/**
	 * The items grid plus its trailing nav.
	 *
	 * @param array{items:array[],has_more:bool,total_pages:int} $result                  Query result (see query()).
	 * @param string                                             $layout                  Layout slug.
	 * @param bool                                               $is_carousel             Whether $layout is 'carousel'.
	 * @param string                                             $pagination_style        'numbers' or 'load-more'.
	 * @param int                                                $carousel_items_per_view Cards visible per carousel "page".
	 * @param bool                                               $show_all_no_nav         When true, no pagination/load-more nav to render.
	 * @param int                                                $current_page            1-based current page.
	 * @param string                                             $current_url             The page's own full current URL.
	 * @return string Escaped HTML.
	 */
	private function render_items_and_nav( array $result, string $layout, bool $is_carousel, string $pagination_style, int $carousel_items_per_view = 3, bool $show_all_no_nav = false, int $current_page = 1, string $current_url = '' ): string {
		ob_start();
		?>
		<div
			class="fb-query-grid__items"
			<?php if ( 'masonry' === $layout ) : ?>
				data-wp-init="callbacks.initMasonryLayout"
			<?php endif; ?>
		>
			<?php echo $this->render_items( $result['items'], $layout, $carousel_items_per_view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_items() escapes per field. ?>
		</div>

		<?php if ( $show_all_no_nav ) : ?>
		<?php elseif ( $is_carousel ) : ?>
			<div class="fb-query-grid__carousel-nav">
				<button type="button" class="fb-query-grid__nav-btn" data-wp-on--click="actions.carouselPrev" aria-label="<?php esc_attr_e( 'Previous', 'flux-blocks' ); ?>">&#8249;</button>
				<button type="button" class="fb-query-grid__nav-btn" data-wp-on--click="actions.carouselNext" aria-label="<?php esc_attr_e( 'Next', 'flux-blocks' ); ?>">&#8250;</button>
			</div>
		<?php elseif ( 'load-more' === $pagination_style ) : ?>
			<?php echo $this->render_load_more_button( $result['has_more'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_load_more_button() escapes per field. ?>
		<?php else : ?>
			<div class="fb-query-grid__pagination-slot" data-wp-init="callbacks.initPaginationDelegation">
				<?php echo $this->render_pagination( $current_page, $result['total_pages'], $current_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_pagination() escapes per field. ?>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * "Load more" button rendering.
	 *
	 * @param bool $has_more Whether a next page exists for the initial query.
	 * @return string Escaped HTML.
	 */
	private function render_load_more_button( bool $has_more ): string {
		if ( ! $has_more ) {
			return '';
		}

		ob_start();
		?>
		<div class="fb-query-grid__load-more">
			<button
				type="button"
				class="fb-query-grid__load-more-btn"
				data-wp-on--click="actions.loadMore"
				data-wp-bind--hidden="!context.hasMore"
				data-wp-bind--disabled="context.isLoading"
			>
				<?php esc_html_e( 'Load more', 'flux-blocks' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * The search `<form>` markup.
	 *
	 * @return string Escaped HTML.
	 */
	private function render_search_form(): string {
		ob_start();
		?>
		<form class="fb-query-grid__search" data-wp-on--submit="actions.onSearchSubmit">
			<input
				type="search"
				class="fb-query-grid__search-input"
				placeholder="<?php esc_attr_e( 'Search Here', 'flux-blocks' ); ?>"
				aria-label="<?php esc_attr_e( 'Search', 'flux-blocks' ); ?>"
				data-wp-bind--value="context.searchQuery"
				data-wp-on--input="actions.onSearchInput"
			/>
			<button type="submit" class="fb-query-grid__search-btn" aria-label="<?php esc_attr_e( 'Search', 'flux-blocks' ); ?>">&#128269;</button>
		</form>
		<?php
		return ob_get_clean();
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
	 * @param array $columns                 Resolved mobile/tablet/desktop column counts.
	 * @param array $colors                  Resolved color map.
	 * @param int   $carousel_items_per_view Cards visible per carousel page.
	 * @return string CSS custom properties.
	 */
	private function build_inline_style( array $columns, array $colors, int $carousel_items_per_view = 3 ): string {
		$style = sprintf(
			'--fb-cols-mobile:%d;--fb-cols-tablet:%d;--fb-cols-desktop:%d;--fb-carousel-items:%d;',
			absint( $columns['mobile'] ),
			absint( $columns['tablet'] ),
			absint( $columns['desktop'] ),
			absint( $carousel_items_per_view )
		);

		$property_map = array(
			'titleColor'          => '--fb-title-color',
			'accentColor'         => '--fb-accent-color',
			'subheadingColor'     => '--fb-subheading-color',
			'cardTitleColor'      => '--fb-card-title-color',
			'cardTitleHoverColor' => '--fb-card-title-hover-color',
			'cardExcerptColor'    => '--fb-card-excerpt-color',
			'readMoreColor'       => '--fb-read-more-color',
			'readMoreHoverColor'  => '--fb-read-more-hover-color',
			'activeAccent'        => '--fb-active-accent',
			'disabledNav'         => '--fb-disabled-nav',
			'inactivePillBg'      => '--fb-inactive-pill-bg',
			'inactivePillText'    => '--fb-inactive-pill-text',
			'metaText'            => '--fb-meta-text',
			'authorText'          => '--fb-author-text',
		);

		foreach ( $property_map as $key => $css_var ) {
			if ( ! empty( $colors[ $key ] ) ) {
				$style .= sprintf( '%s:%s;', $css_var, esc_attr( $colors[ $key ] ) );
			}
		}

		return $style;
	}

	/**
	 * @return array<string,string> Default empty color map.
	 */
	private function default_colors(): array {
		return array(
			'titleColor'          => '',
			'accentColor'         => '',
			'subheadingColor'     => '',
			'cardTitleColor'      => '',
			'cardTitleHoverColor' => '',
			'cardExcerptColor'    => '',
			'readMoreColor'       => '',
			'readMoreHoverColor'  => '',
			'activeAccent'        => '',
			'disabledNav'         => '',
			'inactivePillBg'      => '',
			'inactivePillText'    => '',
			'metaText'            => '',
			'authorText'          => '',
		);
	}

	/**
	 * Reads layout from className attribute.
	 *
	 * @param string $class_name Block's className attribute.
	 * @return string Layout slug.
	 */
	private function layout_from_class_name( string $class_name ): string {
		// [a-z-]+ (not [a-z]+) -- current style slugs (grid/list/masonry/
		// carousel) are all single words so this isn't triggered TODAY, but
		// [a-z]+ alone would silently misdetect any future hyphenated style
		// name (stops matching at the hyphen) instead of erroring loudly.
		// Same fix already applied to ContentShowcase\Renderer's copy of
		// this method, which DOES have one ('two-thirds').
		if ( preg_match( '/is-style-([a-z-]+)/', $class_name, $matches ) ) {
			return $matches[1];
		}
		return 'grid';
	}

	/**
	 * Render item markup.
	 *
	 * @param array[] $items                   Transformed post data.
	 * @param string  $layout                  Layout slug.
	 * @param int     $carousel_items_per_view Cards per view.
	 * @return string Escaped HTML.
	 */
	public function render_items( array $items, string $layout, int $carousel_items_per_view = 3 ): string {
		if ( empty( $items ) ) {
			return '<p class="fb-query-grid__empty">' . esc_html__( 'No items found.', 'flux-blocks' ) . '</p>';
		}

		ob_start();
		foreach ( $items as $index => $item ) {
			$this->render_item( $item, $layout, $index, $carousel_items_per_view );
		}
		return ob_get_clean();
	}

	/**
	 * Single item rendering.
	 *
	 * @param array  $item                    Transformed post data.
	 * @param string $layout                  Layout slug.
	 * @param int    $index                   Position index.
	 * @param int    $carousel_items_per_view Cards per view.
	 */
	private function render_item( array $item, string $layout, int $index, int $carousel_items_per_view = 3 ): void {
		?>
		<article
			class="fb-query-grid__item fb-query-grid__item--<?php echo esc_attr( $layout ); ?>"
			<?php if ( 'carousel' === $layout ) : ?>
				<?php echo $index < $carousel_items_per_view ? '' : 'hidden'; ?>
				<?php echo wp_interactivity_data_wp_context( array( 'slideIndex' => $index ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		>
			<a href="<?php echo esc_url( $item['permalink'] ); ?>" class="fb-query-grid__item-image">
				<?php if ( $item['image'] ) : ?>
					<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['imageAlt'] ); ?>" loading="lazy" />
				<?php else : ?>
					<span class="fb-query-grid__item-image-placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</a>
			<div class="fb-query-grid__item-body">
				<p class="fb-query-grid__item-meta">
					<span class="fb-query-grid__item-date"><?php echo esc_html( $item['date'] ); ?></span>
					<span class="fb-query-grid__item-sep" aria-hidden="true">&#183;</span>
					<span class="fb-query-grid__item-author"><?php echo esc_html( $item['author'] ); ?></span>
				</p>
				<h3 class="fb-query-grid__item-title">
					<a href="<?php echo esc_url( $item['permalink'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
				</h3>
				<p class="fb-query-grid__item-excerpt"><?php echo esc_html( $item['excerpt'] ); ?></p>
				<a href="<?php echo esc_url( $item['permalink'] ); ?>" class="fb-query-grid__item-readmore">
					<?php esc_html_e( 'Read More', 'flux-blocks' ); ?> &#8250;
				</a>
			</div>
		</article>
		<?php
	}

	/**
	 * Renders Prev / page-number / Next controls.
	 *
	 * @param int    $current_page Page currently displayed.
	 * @param int    $total_pages  Total pages.
	 * @param string $base_url     Base page URL.
	 * @return string Escaped HTML.
	 */
	public function render_pagination( int $current_page, int $total_pages, string $base_url = '' ): string {
		if ( $total_pages <= 1 ) {
			return '';
		}

		$page_url = function ( int $page ) use ( $base_url ) {
			return $this->build_page_url( $base_url, $page );
		};

		ob_start();
		?>
		<nav class="fb-query-grid__pagination" aria-label="<?php esc_attr_e( 'Pagination', 'flux-blocks' ); ?>">
			<?php if ( $current_page > 1 ) : ?>
				<a href="<?php echo esc_url( $page_url( $current_page - 1 ) ); ?>" class="fb-query-grid__page-btn fb-query-grid__page-btn--prev">
					<?php esc_html_e( 'Prev', 'flux-blocks' ); ?>
				</a>
			<?php else : ?>
				<span class="fb-query-grid__page-btn fb-query-grid__page-btn--prev" aria-disabled="true">
					<?php esc_html_e( 'Prev', 'flux-blocks' ); ?>
				</span>
			<?php endif; ?>

			<?php
			$last_rendered_page = 0;
			for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) :
				$is_edge   = ( 1 === $page_num || $total_pages === $page_num );
				$is_nearby = abs( $page_num - $current_page ) <= 1;
				if ( ! $is_edge && ! $is_nearby ) {
					continue;
				}
				if ( $page_num - $last_rendered_page > 1 ) :
					?>
					<span class="fb-query-grid__page-ellipsis" aria-hidden="true">&hellip;</span>
					<?php
				endif;
				$last_rendered_page = $page_num;
				$page_context       = wp_interactivity_data_wp_context( array( 'pageNum' => $page_num ) );
				if ( $page_num === $current_page ) :
					?>
					<span
						class="fb-query-grid__page-btn is-active"
						aria-current="page"
						<?php echo $page_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
						<?php echo esc_html( (string) $page_num ); ?>
					</span>
					<?php
				else :
					?>
					<a
						href="<?php echo esc_url( $page_url( $page_num ) ); ?>"
						class="fb-query-grid__page-btn"
						<?php echo $page_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
						<?php echo esc_html( (string) $page_num ); ?>
					</a>
					<?php
				endif;
			endfor;
			?>

			<?php if ( $current_page < $total_pages ) : ?>
				<a href="<?php echo esc_url( $page_url( $current_page + 1 ) ); ?>" class="fb-query-grid__page-btn fb-query-grid__page-btn--next">
					<?php esc_html_e( 'Next', 'flux-blocks' ); ?>
				</a>
			<?php else : ?>
				<span class="fb-query-grid__page-btn fb-query-grid__page-btn--next" aria-disabled="true">
					<?php esc_html_e( 'Next', 'flux-blocks' ); ?>
				</span>
			<?php endif; ?>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * Builds page URL.
	 *
	 * @param string $base_url Base URL.
	 * @param int    $page     Page number.
	 * @return string Unescaped URL.
	 */
	private function build_page_url( string $base_url, int $page ): string {
		$slug   = PaginationEndpoint::slug();
		$parsed = wp_parse_url( $base_url );
		$path   = isset( $parsed['path'] ) ? $parsed['path'] : '/';
		$path   = preg_replace( '#/' . preg_quote( $slug, '#' ) . '/\d+/?$#', '/', $path );
		$path   = trailingslashit( $path );
		if ( $page > 1 ) {
			$path = trailingslashit( $path . $slug . '/' . $page );
		}

		$origin = ( isset( $parsed['scheme'], $parsed['host'] ) )
			? $parsed['scheme'] . '://' . $parsed['host']
			: home_url();
		$url    = $origin . $path;
		if ( ! empty( $parsed['query'] ) ) {
			$url .= '?' . $parsed['query'];
		}
		return $url;
	}

	/**
	 * Taxonomy facet group rendering.
	 *
	 * @param string $taxonomy       Taxonomy slug.
	 * @param string $post_type      Post type slug.
	 * @param bool   $show_heading   Whether to render label.
	 * @param string $custom_heading Custom heading text.
	 * @return string Escaped HTML.
	 */
	private function render_facet_group( string $taxonomy, string $post_type, bool $show_heading, string $custom_heading = '' ): string {
		$terms = $this->get_terms_for_post_type( $taxonomy, $post_type );

		if ( empty( $terms ) ) {
			return '';
		}

		$taxonomy_object = get_taxonomy( $taxonomy );
		$label           = $taxonomy_object ? $taxonomy_object->labels->name : $taxonomy;
		$heading_to_show = '' !== $custom_heading ? $custom_heading : $label;

		ob_start();
		?>
		<div class="fb-query-grid__filter-group">
			<?php if ( $show_heading ) : ?>
				<h4 class="fb-query-grid__filter-heading"><?php echo esc_html( $heading_to_show ); ?></h4>
			<?php endif; ?>
			<div class="fb-query-grid__filters" role="group" aria-label="<?php echo esc_attr( $heading_to_show ); ?>">
				<?php foreach ( $terms as $term ) : ?>
					<?php
					$pill_context = array(
						'taxonomy' => $taxonomy,
						'termId'   => $term->term_id,
					);
					?>
					<button
						type="button"
						class="fb-query-grid__filter-btn"
						data-wp-class--is-active="state.isActiveTerm"
						data-wp-bind--aria-pressed="state.isActiveTerm"
						data-wp-on--click="actions.onFilterClick"
						<?php echo wp_interactivity_data_wp_context( $pill_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
						<?php echo esc_html( $term->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns terms attached to published posts.
	 *
	 * @param string $taxonomy  Taxonomy slug.
	 * @param string $post_type Post type slug.
	 * @return \WP_Term[]
	 */
	private function get_terms_for_post_type( string $taxonomy, string $post_type ): array {
		$signature = $post_type . '|facet-terms|' . $taxonomy;

		return $this->cache->remember(
			$post_type,
			$signature,
			function () use ( $taxonomy, $post_type ) {
				$post_ids = get_posts(
					array(
						'post_type'      => $post_type,
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				);

				if ( empty( $post_ids ) ) {
					return array();
				}

				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'object_ids' => $post_ids,
					)
				);

				return is_wp_error( $terms ) ? array() : $terms;
			}
		);
	}

	/**
	 * Resolves active taxonomies for filtering.
	 *
	 * @param string $post_type  Post type slug.
	 * @param array  $attributes Block attributes.
	 * @return string[]
	 */
	private function resolve_facet_taxonomies( string $post_type, array $attributes ): array {
		$explicit = array_filter( (array) ( $attributes['facetTaxonomies'] ?? array() ) );
		if ( ! empty( $explicit ) ) {
			return array_map( 'sanitize_key', $explicit );
		}

		$taxonomies = array();
		foreach ( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy ) {
			if ( $taxonomy->public ) {
				$taxonomies[] = $taxonomy->name;
			}
		}
		return $taxonomies;
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

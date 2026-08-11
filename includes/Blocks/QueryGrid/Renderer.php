<?php
/**
 * Renders the Query Grid block: builds the query, transforms results, and
 * outputs a layout-specific template with a two-tone heading, an optional
 * search bar, one or more taxonomy filter-pill facets, and numbered
 * pagination.
 *
 * Why: render.php stays a one-line delegate (see docs/technical-spec.md);
 * all Query Grid markup logic lives here so the REST controller can reuse
 * render_items()/query() for paginated/filtered fetches without duplicating
 * markup or query logic.
 * Impact of changing: affects both the initial page render AND every
 * paginated/filtered REST response's HTML.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid;

use FluxBlocks\Query\QueryArgsBuilder;
use FluxBlocks\Query\QueryCache;
use FluxBlocks\Query\PostDataTransformer;
use FluxBlocks\PaginationEndpoint;

/**
 * Builds the Query Grid query, transforms results, and renders a layout.
 */
class Renderer {

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
		$post_type = $attributes['postType'] ?? 'post';
		$layout    = $this->layout_from_class_name( $attributes['className'] ?? '' );
		$query_id  = ! empty( $attributes['queryId'] ) ? $attributes['queryId'] : wp_unique_id( 'fbq-' );
		// A shared/bookmarked/reloaded /<slug>/N/ link should land on that
		// same page (see PaginationEndpoint) -- read it back out here so
		// the FIRST server render is correct too, not just what a
		// pagination click's AJAX fetch + history.pushState() produces
		// (see updateUrlForPage() in view.js).
		$initial_page         = max( 1, absint( get_query_var( PaginationEndpoint::slug() ) ) );
		$show_heading         = ! empty( $attributes['showHeading'] );
		$heading              = $show_heading ? ( $attributes['heading'] ?? '' ) : '';
		$heading_accent       = $attributes['headingAccent'] ?? '';
		$show_search          = ! empty( $attributes['showSearch'] );
		$show_filter          = ! empty( $attributes['showCategoryFilter'] );
		$show_filter_headings = ! empty( $attributes['showFilterHeadings'] );
		$facet_headings       = is_array( $attributes['facetHeadings'] ?? null ) ? $attributes['facetHeadings'] : array();
		$is_carousel          = 'carousel' === $layout;
		$colors               = wp_parse_args( is_array( $attributes['colors'] ?? null ) ? $attributes['colors'] : array(), $this->default_colors() );
		// How many cards show side by side per carousel "page" -- a single
		// visible card at a time read as unpolished, so Carousel pages
		// through groups of N instead (Next/Prev swap the whole group, not
		// slide-by-one). Only meaningful when $is_carousel; harmless to
		// compute otherwise.
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
		// "Show all posts" controls how many posts get QUERIED (bypasses
		// postCount, see QueryArgsBuilder) regardless of layout -- Carousel
		// benefits from this too (cycle through every post via Prev/Next
		// instead of being capped at postCount). What it must NOT do for
		// Carousel is remove nav: Carousel never had pagination/load-more
		// nav to begin with (it has its own Prev/Next), so $show_all_no_nav
		// (which suppresses that nav below) only ever applies to the
		// non-carousel branch.
		$show_all_query  = ! empty( $attributes['showAllPosts'] );
		$show_all_no_nav = $show_all_query && ! $is_carousel;

		$facet_taxonomies = ( $show_filter && ! $is_carousel )
			? $this->resolve_facet_taxonomies( $post_type, $attributes )
			: array();
		// Sidebar holds search AND filters together when enabled -- no
		// separate "which one goes in the sidebar" choice (default is both
		// stay at the top; the toggle just relocates whichever are on).
		$is_sidebar = ! $is_carousel && ! empty( $attributes['showSidebar'] )
			&& ( $show_search || ! empty( $facet_taxonomies ) );

		// queryTaxonomyFilter is built fresh per request from REST params
		// (see QueryController), not from a stored attribute -- Query Grid
		// no longer carries an editor-time taxonomyFilter attribute, the
		// visitor picks a facet term live on the frontend instead.
		//
		// $show_all_query applies to every layout, including Carousel (see
		// its declaration above) -- so this override is really just
		// normalizing a possibly-truthy-but-not-strictly-boolean attribute
		// value before QueryArgsBuilder reads it.
		$result = $this->query( array_merge( $attributes, array( 'showAllPosts' => $show_all_query ) ), $initial_page );

		// The real page currently being viewed -- render_pagination() needs
		// this (not the request's own path alone) to build every `<a
		// href>`, since it must first STRIP any existing /blogpage/N/
		// suffix before appending a new one (see its page_url()).
		// add_query_arg( null, null ) is a common WP idiom for "the current
		// request's own path+query, unmodified".
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

		// Per-instance data lives in the Interactivity API's `context`
		// (nested `data-wp-context` merges with it), not global `state` —
		// keeps multiple Query Grid instances on one page independent.
		$context = array(
			'queryId'              => $query_id,
			'postType'             => sanitize_key( $post_type ),
			'layout'               => sanitize_key( $layout ),
			'page'                 => $initial_page,
			// updateUrlForPage()/the `popstate` handler in view.js need to
			// know this to build/strip the /<slug>/N/ URL segment
			// themselves -- see PaginationEndpoint for what it is and why
			// it's one site-wide value, not a per-attribute one.
			'paginationSlug'       => PaginationEndpoint::slug(),
			'totalPages'           => $result['total_pages'],
			'hasMore'              => $result['has_more'],
			'carouselIndex'        => 0,
			'carouselItemsPerView' => $carousel_items_per_view,
			// So search/filter fetches (which go through the REST endpoint,
			// not this method) keep requesting the same show-all/paginated
			// mode the block was configured with -- see fetchAndApply() in
			// view.js, which reads this back out to build its request.
			// Carousel never triggers a fetch (no search/filter UI for it),
			// so this being layout-agnostic here is harmless.
			'showAllPosts'         => $show_all_query,
			'isLoading'            => false,
			'searchQuery'          => '',
			'activeFacetTaxonomy'  => '',
			// Multiple terms can be active at once WITHIN one taxonomy (e.g.
			// two categories together); switching to a pill from a
			// DIFFERENT taxonomy resets this -- see onFilterClick() in
			// view.js. QueryArgsBuilder already accepts multiple `terms`
			// for one tax_query clause, so this needed no PHP query changes.
			'activeTermIds'        => array(),
		);

		ob_start();
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
		return ob_get_clean();
	}

	/**
	 * The items grid plus its trailing nav (carousel arrows, numbered
	 * pagination, or a "Load more" button) — identical markup regardless of
	 * whether the toolbar above it is in the top-bar or sidebar layout, so
	 * this is shared instead of duplicated in both branches of render().
	 *
	 * @param array{items:array[],has_more:bool,total_pages:int} $result                  Query result (see query()).
	 * @param string                                             $layout                  Layout slug.
	 * @param bool                                               $is_carousel             Whether $layout is 'carousel'.
	 * @param string                                             $pagination_style        'numbers' or 'load-more'.
	 * @param int                                                $carousel_items_per_view Cards visible per carousel "page" -- ignored for every other layout.
	 * @param bool                                               $show_all_no_nav         When true, $result already contains every matching post AND $layout isn't Carousel -- no pagination/load-more nav to render at all.
	 * @param int                                                $current_page            1-based current page, for numbered pagination's active-state and hrefs.
	 * @param string                                             $current_url             The page's own full current URL -- numbered pagination's `<a href>`s are built from it (see render_pagination()).
	 * @return string Escaped HTML.
	 */
	private function render_items_and_nav( array $result, string $layout, bool $is_carousel, string $pagination_style, int $carousel_items_per_view = 3, bool $show_all_no_nav = false, int $current_page = 1, string $current_url = '' ): string {
		ob_start();
		?>
		<div
			class="fb-query-grid__items"
			<?php // Only Masonry needs JS to measure/space items -- see layoutMasonryItems() in view.js and the docblock on style.scss's &--masonry &__items rule for why. ?>
			<?php if ( 'masonry' === $layout ) : ?>
				data-wp-init="callbacks.initMasonryLayout"
			<?php endif; ?>
		>
			<?php echo $this->render_items( $result['items'], $layout, $carousel_items_per_view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_items() escapes per field. ?>
		</div>

		<?php if ( $show_all_no_nav ) : ?>
			<?php // Every matching post is already in $result -- nothing left to page through, so no nav markup at all (not even an empty slot). Never true for Carousel (see the $show_all_no_nav computation in render()) -- it keeps its own Prev/Next below regardless of show-all. ?>
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
	 * "Load more" alternative to numbered pagination -- appends the next
	 * page's items to the grid instead of replacing it (see view.js's
	 * `loadMore` action). `hasMore` is a plain `context` value (not a
	 * `state` getter), so unlike pagination's numbers it resolves correctly
	 * server-side on its own -- no PHP-baked fallback needed here.
	 *
	 * @param bool $has_more Whether a next page exists for the initial (page 1) query.
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
	 * The search `<form>` alone — shared between the top-toolbar and
	 * sidebar-layout positions (see render()).
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
	 * @param string $accent  Substring of $heading to wrap in an accent span (case-sensitive, first match only). Empty/no-match renders the heading plainly.
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
	 * @param array $colors                  Resolved color map (see default_colors()).
	 * @param int   $carousel_items_per_view Cards visible per carousel "page" (see the $is_carousel branch of render()).
	 * @return string CSS custom properties for the wrapper's inline style attribute.
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
			'titleColor'       => '--fb-title-color',
			'accentColor'      => '--fb-accent-color',
			'activeAccent'     => '--fb-active-accent',
			'disabledNav'      => '--fb-disabled-nav',
			'inactivePillBg'   => '--fb-inactive-pill-bg',
			'inactivePillText' => '--fb-inactive-pill-text',
			'metaText'         => '--fb-meta-text',
			'authorText'       => '--fb-author-text',
		);

		foreach ( $property_map as $key => $css_var ) {
			// Only set the property when the editor picked a color -- leaving
			// it unset lets style.scss's var(--x, #fallback) defaults apply.
			if ( ! empty( $colors[ $key ] ) ) {
				$style .= sprintf( '%s:%s;', $css_var, esc_attr( $colors[ $key ] ) );
			}
		}

		return $style;
	}

	/**
	 * @return array<string,string> Every color key defaulted to '' (unset).
	 */
	private function default_colors(): array {
		return array(
			'titleColor'       => '',
			'accentColor'      => '',
			'activeAccent'     => '',
			'disabledNav'      => '',
			'inactivePillBg'   => '',
			'inactivePillText' => '',
			'metaText'         => '',
			'authorText'       => '',
		);
	}

	/**
	 * Query Grid's layout is a native block Style Variation (block.json's
	 * `styles`), not a custom attribute -- WordPress stores the chosen
	 * style as an `is-style-<name>` class inside `attributes.className`,
	 * the same mechanism as core blocks' "Styles" tab.
	 *
	 * @param string $class_name The block's `className` attribute value.
	 * @return string Layout slug, defaulting to 'grid' if no style class is present.
	 */
	private function layout_from_class_name( string $class_name ): string {
		if ( preg_match( '/is-style-([a-z]+)/', $class_name, $matches ) ) {
			return $matches[1];
		}
		return 'grid';
	}

	/**
	 * Render just the item markup for a layout — reused by the REST
	 * controller for paginated/filtered fragment responses.
	 *
	 * @param array[] $items                   Transformed post data (see PostDataTransformer).
	 * @param string  $layout                  One of grid|list|masonry|carousel.
	 * @param int     $carousel_items_per_view Cards visible per carousel "page" -- ignored for every other layout.
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
	 * @param array  $item                    Transformed post data.
	 * @param string $layout                  Layout slug — every layout shares this item
	 *                                        markup, the visual difference is CSS grid/flex
	 *                                        on the container, not different HTML.
	 * @param int    $index                   Position, used for carousel slide visibility.
	 * @param int    $carousel_items_per_view Cards visible per carousel "page" -- see the $is_carousel branch below.
	 */
	private function render_item( array $item, string $layout, int $index, int $carousel_items_per_view = 3 ): void {
		?>
		<article
			class="fb-query-grid__item fb-query-grid__item--<?php echo esc_attr( $layout ); ?>"
			<?php if ( 'carousel' === $layout ) : ?>
				<?php
				/*
				 * Bakes the initial visibility directly (the first GROUP of
				 * $carousel_items_per_view slides shown, e.g. slides 0-2 for
				 * a per-view of 3) -- and, unlike every other spot in this
				 * file that fixed this class of bug, there is NO
				 * data-wp-bind--hidden directive alongside it. That was
				 * tried first and made every slide vanish: WP_Block::render()
				 * calls wp_interactivity_process_directives() on this block's
				 * OWN output server-side (core/class-wp-block.php), which
				 * re-evaluates every data-wp-bind directive right after this
				 * echo runs. `state.isCurrentSlide` is a JS-only derived
				 * getter with no server-side registration, so it resolves to
				 * undefined there; `!undefined` is `true`, so WordPress
				 * itself re-added `hidden` to EVERY article regardless of
				 * what this echo produced. Removing the directive stops that
				 * second, wrong pass from ever running -- this echo is now
				 * the only thing that ever sets `hidden` here. Client-side
				 * carouselNext/Prev no longer relies on directive reactivity
				 * either; see applyCarouselVisibility() in view.js for the
				 * plain DOM-toggle replacement (same escape hatch already
				 * used for pagination clicks, see that docblock in view.js).
				 */
				echo $index < $carousel_items_per_view ? '' : 'hidden';
				?>
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
	 * Renders Prev / page-number / Next controls, fully computed server-side
	 * for the given page — NOT via `data-wp-bind` against a client-only
	 * `state` getter, and NOT via `data-wp-on--click` on the buttons
	 * themselves.
	 *
	 * Why no `data-wp-bind`/`state` getter: the Interactivity API processes
	 * those directives server-side too (so the very first HTML already
	 * matches what JS would compute), using whatever `wp_interactivity_state()`
	 * registered — but a *derived* getter like the old `isPageButtonVisible`
	 * only existed in JS, so on the server it resolved to `undefined`, and
	 * `!undefined` is `true`, hiding every single page button/ellipsis on
	 * first paint. Baking hidden/active/disabled directly in PHP instead
	 * sidesteps that gap entirely.
	 *
	 * Why no `data-wp-on--click` either (a *second*, different bug fixed in
	 * the same pass): `view.js`'s `goToPage()` REPLACES this whole markup
	 * (via `.fb-query-grid__pagination-slot`'s innerHTML) on every page
	 * change. The Interactivity API only binds `data-wp-on--*` directives
	 * during its one-time hydration walk at page load -- HTML injected
	 * afterwards via plain `innerHTML`/`insertAdjacentHTML` is invisible to
	 * it, so a freshly-inserted button's `data-wp-on--click` is inert (this
	 * is why clicking "Next" worked once, then silently did nothing on the
	 * next click). Fixed by NOT binding directives on these buttons at all
	 * -- `.fb-query-grid__pagination-slot` instead gets a `data-wp-init`
	 * that sets up a plain `addEventListener` (event delegation) once, on
	 * an element that itself is never replaced, only its children are;
	 * see `initPaginationDelegation()` in view.js.
	 *
	 * Impact of changing: keep the button class names (`fb-query-grid__page-
	 * btn`, `--prev`, `--next`) and the `data-wp-context` pageNum shape in
	 * sync with the delegated handler in view.js, which parses them by hand.
	 *
	 * Why Prev/page-numbers/Next are real `<a href>` links, not `<button>`
	 * (changed after shipping, for SEO/crawlability -- a search engine's
	 * crawler discovers and follows pages primarily via real `<a href>`
	 * elements, not via a JS `data-wp-on--click` with no href at all):
	 * `page_url()` below builds each one's href as `<current path>/blogpage/
	 * N/` -- see PaginationEndpoint's docblock for why that exact segment
	 * (registered via `add_rewrite_endpoint()`) and not `/page/N/` (already
	 * claimed by two OTHER WordPress features). `Renderer::render()` reads
	 * that same `blogpage` query var back out for the initial page load, so
	 * a crawler (or a no-JS visitor, or someone opening a page-2 link in a
	 * new tab) gets a real, correct server render of that exact page -- not
	 * just page 1. JS still intercepts the click (`event.preventDefault()`
	 * in initPaginationDelegation()) for the instant AJAX experience, then
	 * calls `history.pushState()` to keep the address bar in sync -- see
	 * `updateUrlForPage()` in view.js.
	 *
	 * @param int    $current_page Page currently being displayed (1-based).
	 * @param int    $total_pages  Total number of pages for the current query.
	 * @param string $base_url     Absolute URL pagination hrefs are built against -- required whenever $total_pages > 1 (only defaults to '' because $current_page/$total_pages come first positionally). Renderer::render() passes the real current-page URL for the initial server render; QueryController passes the `pageUrl` its REST request received from view.js (`window.location.href`) instead, since "the current request" there is the REST endpoint itself, not the page a visitor is actually looking at.
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
			// Ellipsis is emitted inline wherever a gap actually falls
			// (not fixed "one before the loop, one after") so it works no
			// matter how the visible window shifts around $current_page.
			$last_rendered_page = 0;
			for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) :
				$is_edge   = ( 1 === $page_num || $total_pages === $page_num );
				$is_nearby = abs( $page_num - $current_page ) <= 1;
				if ( ! $is_edge && ! $is_nearby ) {
					continue; // Outside the visible window -- skip the link entirely, nothing to hide client-side.
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
	 * Builds one pagination `<a href>` as `<$base_url's path>/<slug>/N/`
	 * (page 1 omits the segment entirely -- one canonical "no suffix" URL
	 * rather than an equivalent-but-different `/<slug>/1/`), preserving
	 * $base_url's scheme/host/query string untouched. Strips any EXISTING
	 * `/<slug>/N/` suffix from $base_url first -- without that, clicking
	 * from page 2 to page 3 would nest into `/<slug>/2/<slug>/3/` instead
	 * of replacing it, since $base_url is always "wherever the visitor
	 * currently is", which already has last page's suffix on it.
	 *
	 * @param string $base_url Absolute URL to build against (see render_pagination()'s docblock for where this comes from).
	 * @param int    $page     Target page (1-based).
	 * @return string Unescaped URL -- caller is responsible for esc_url().
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
	 * One taxonomy's terms as a row of filter pills, with its own optional
	 * heading (the taxonomy's label, e.g. "Categories"/"Tags") above the
	 * pills -- each rendered facet group gets its own, not one combined
	 * heading for all of them, since a sidebar can show several groups
	 * stacked and each needs its own label to tell them apart. Each pill
	 * carries its OWN `{taxonomy, termId}` context; the click handler
	 * (view.js) reads both to toggle `termId` in `activeTermIds` -- multiple
	 * terms can be active together WITHIN one taxonomy's group, but
	 * clicking a pill from a DIFFERENT taxonomy (e.g. a Tag after Category
	 * pills were selected) resets the selection to just that one, since
	 * only one taxonomy's `tax_query` clause is sent per request.
	 *
	 * @param string $taxonomy       Taxonomy slug.
	 * @param string $post_type      Post type the grid is querying -- terms with no *published post of this type* are left out entirely (see get_terms_for_post_type()).
	 * @param bool   $show_heading   Whether to render a label above this group's pills.
	 * @param string $custom_heading Editor-entered text for this group's heading; falls back to the taxonomy's own label (e.g. "Categories") when empty.
	 * @return string Escaped HTML, or '' if no terms qualify.
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
						<?php echo wp_interactivity_data_wp_context( $pill_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core helper already escapes/encodes. ?>
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
	 * Terms of a taxonomy that have at least one PUBLISHED post of the
	 * given post type.
	 *
	 * Why: `get_terms( ['hide_empty' => true] )` only checks a term's
	 * global `count` -- how many published posts use it *across every post
	 * type that shares the taxonomy*, not scoped to the one this block is
	 * actually querying. A taxonomy shared between `post` and some other
	 * CPT could show a pill for a term that has zero `post`-type items,
	 * which would just render an empty grid when clicked. `object_ids`
	 * scopes `get_terms()` to only terms actually attached to the given
	 * posts, which is what we want here instead.
	 * Impact of changing: this result is cached per post type (like every
	 * other query in this plugin, see QueryCache/CacheInvalidator) --
	 * changing the underlying query without also considering cache
	 * invalidation could show stale pills after a post is edited.
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
	 * Which taxonomies get a filter-pill row: the editor's explicit
	 * `facetTaxonomies` selection if any, otherwise every public taxonomy
	 * registered for the post type (so a facet row appears automatically
	 * for e.g. `post` without extra setup).
	 *
	 * @param string $post_type  Post type slug.
	 * @param array  $attributes Block attributes.
	 * @return string[] Taxonomy slugs.
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
	 * Run the (cached) query and transform the results. Public so the REST
	 * controller can call it directly for paginated/filtered fetches.
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $page       1-based page number.
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

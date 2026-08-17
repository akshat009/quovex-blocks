/**
 * Query Grid frontend behavior (Interactivity API).
 *
 * Pagination/search/filter fetch from /flux-blocks/v1/query and REPLACE
 * `.fb-query-grid__items` + `.fb-query-grid__pagination-slot`; "Load more"
 * APPENDS instead (see fetchAndApply()'s `append` arg). Carousel nav is
 * pure client-side -- every slide is already server-rendered.
 *
 * Pagination is fully server-rendered, not a `state` getter: the
 * Interactivity API evaluates `data-wp-bind` server-side too, and a
 * JS-only derived getter resolves to `undefined` there, hiding everything
 * on first paint (a bug that shipped once). For the same reason, carousel
 * slide visibility is a plain DOM toggle (applyCarouselVisibility), not a
 * `data-wp-bind--hidden` directive.
 *
 * Pagination buttons use plain event delegation
 * (initPaginationDelegation), not `data-wp-on--click` --
 * `.fb-query-grid__pagination-slot`'s innerHTML gets replaced on every
 * page change, and the Interactivity API only binds directives during its
 * one-time hydration walk, so a freshly-inserted button's directive would
 * be inert.
 *
 * Masonry needs layoutMasonryItems() (a JS row-span calculation) because
 * pure-CSS `column-count` masonry reads column-major, which breaks
 * left-to-right reading order for variable-height cards.
 *
 * Per-instance data lives in Interactivity `context` (seeded by
 * QueryGrid\Renderer::render()), not global `state`, so multiple Query
 * Grid instances on one page stay independent.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { nextIndex, prevIndex } from '../shared/carousel-utils';
import { buildPaginatedPath } from './pagination-url';

/**
 * Query Grid instances with numbered pagination, keyed by queryId -- lets
 * the module-level `popstate` listener re-sync an instance after browser
 * back/forward, since there's no other way to reach a live `context` from
 * outside an action/callback.
 *
 * @type {Map<string, {rootEl: HTMLElement, context: Object}>}
 */
const instancesByQueryId = new Map();

/**
 * Core fetch + DOM update logic, shared by the directive-driven actions
 * below and the delegated pagination click handler (see file docblock for
 * why pagination needs different event wiring).
 *
 * @param {HTMLElement} rootEl  The `.fb-query-grid` root element for this instance.
 * @param {Object}      context This instance's Interactivity context (mutated in place).
 * @param {number}      page    Page to fetch.
 * @param {boolean}     append  true = grow items (load-more); false = replace (pagination/search/filter).
 */
async function fetchAndApply( rootEl, context, page, append ) {
	if ( context.isLoading ) {
		return;
	}
	context.isLoading = true;

	try {
		const params = new URLSearchParams( {
			postType: context.postType,
			page,
			layout: context.layout,
			// ItemsRenderer::render_pagination() needs the REAL page URL
			// (not the REST endpoint's own) to build correct `<a href>`s
			// when pagination gets re-rendered here -- see its docblock.
			pageUrl: window.location.href,
		} );
		if ( context.searchQuery ) {
			params.set( 'search', context.searchQuery );
		}
		if ( context.activeTermIds?.length ) {
			params.set( 'taxonomy', context.activeFacetTaxonomy );
			context.activeTermIds.forEach( ( id ) =>
				params.append( 'terms[]', id )
			);
		}
		// Keeps a search/filter refetch in the same show-all-vs-paginated
		// mode the block was configured with -- see Renderer::render()'s
		// `showAllPosts` context value this is read from.
		if ( context.showAllPosts ) {
			params.set( 'showAll', '1' );
		}

		const response = await fetch(
			`/wp-json/flux-blocks/v1/query?${ params.toString() }`
		);
		const data = await response.json();

		const itemsEl = rootEl.querySelector( '.fb-query-grid__items' );
		if ( itemsEl ) {
			if ( append ) {
				itemsEl.insertAdjacentHTML( 'beforeend', data.html );
			} else {
				itemsEl.innerHTML = data.html;
			}
			// Search/filter/pagination/load-more all replace or grow the
			// items this same way regardless of layout -- Masonry's spans
			// need recomputing for whichever items are now in the DOM every
			// time this runs, not just on first hydration.
			if ( rootEl.classList.contains( 'fb-query-grid--masonry' ) ) {
				layoutMasonryItems( rootEl );
			}
		}

		// Load-more never shows numbered pagination, so there is no slot
		// to refresh in that mode.
		if ( ! append ) {
			const paginationEl = rootEl.querySelector(
				'.fb-query-grid__pagination-slot'
			);
			if ( paginationEl ) {
				paginationEl.innerHTML = data.pagination;
			}
		}

		context.page = page;
		context.hasMore = data.hasMore;
		context.totalPages = data.totalPages;
	} finally {
		context.isLoading = false;
	}
}

store( 'flux-blocks/query-grid', {
	state: {
		get isActiveTerm() {
			const { taxonomy, termId, activeFacetTaxonomy, activeTermIds } =
				getContext();
			return (
				taxonomy === activeFacetTaxonomy &&
				!! activeTermIds?.includes( termId )
			);
		},
	},
	actions: {
		onSearchInput( event ) {
			const context = getContext();
			const hadQuery = !! context.searchQuery;
			context.searchQuery = event.target.value;

			// Clearing the field refetches immediately (not on submit) so
			// results don't stay filtered next to an empty search box.
			if ( hadQuery && ! context.searchQuery ) {
				const { ref } = getElement();
				fetchAndApply(
					ref.closest( '.fb-query-grid' ),
					context,
					1,
					false
				);
			}
		},
		onSearchSubmit( event ) {
			event.preventDefault();
			const { ref } = getElement();
			fetchAndApply(
				ref.closest( '.fb-query-grid' ),
				getContext(),
				1,
				false
			);
		},
		/**
		 * Terms toggle within one taxonomy's pill group; clicking a pill
		 * from a different taxonomy resets the selection, since only one
		 * taxonomy's tax_query clause is sent per request.
		 */
		onFilterClick() {
			const context = getContext();
			const { taxonomy, termId } = context;
			const currentIds = context.activeTermIds || [];

			if ( context.activeFacetTaxonomy !== taxonomy ) {
				// Switching taxonomy groups -- start a fresh selection.
				context.activeFacetTaxonomy = taxonomy;
				context.activeTermIds = [ termId ];
			} else if ( currentIds.includes( termId ) ) {
				// Toggling this term off within the same group.
				const remaining = currentIds.filter( ( id ) => id !== termId );
				context.activeTermIds = remaining;
				if ( remaining.length === 0 ) {
					context.activeFacetTaxonomy = '';
				}
			} else {
				// Adding another term within the same group.
				context.activeTermIds = [ ...currentIds, termId ];
			}

			const { ref } = getElement();
			fetchAndApply( ref.closest( '.fb-query-grid' ), context, 1, false );
		},
		/**
		 * "Load more" alternative to numbered pagination -- appends the
		 * next page instead of replacing (see paginationStyle in
		 * Renderer::render()).
		 */
		loadMore() {
			const context = getContext();
			if ( context.isLoading || ! context.hasMore ) {
				return;
			}
			const { ref } = getElement();
			fetchAndApply(
				ref.closest( '.fb-query-grid' ),
				context,
				context.page + 1,
				true
			);
		},
		carouselNext() {
			const context = getContext();
			const rootEl = getElement().ref.closest( '.fb-query-grid' );
			const totalGroups = countCarouselGroups( rootEl, context );
			context.carouselIndex = nextIndex(
				context.carouselIndex,
				totalGroups
			);
			applyCarouselVisibility( rootEl, context );
		},
		carouselPrev() {
			const context = getContext();
			const rootEl = getElement().ref.closest( '.fb-query-grid' );
			const totalGroups = countCarouselGroups( rootEl, context );
			context.carouselIndex = prevIndex(
				context.carouselIndex,
				totalGroups
			);
			applyCarouselVisibility( rootEl, context );
		},
	},
	callbacks: {
		/**
		 * Attaches ONE delegated click listener on
		 * `.fb-query-grid__pagination-slot` (see file docblock for why) --
		 * `context` is captured at hydration but stays live as its
		 * properties change.
		 */
		initPaginationDelegation() {
			const { ref } = getElement();
			const context = getContext();
			const rootEl = ref.closest( '.fb-query-grid' );

			// Registered so a browser back/forward navigation (popstate,
			// below) can look this instance up by its queryId and re-sync
			// it -- there is no other way to reach a specific element's
			// live reactive `context` from outside an Interactivity
			// action/callback.
			instancesByQueryId.set( context.queryId, { rootEl, context } );

			ref.addEventListener( 'click', ( event ) => {
				// Page numbers/Prev/Next are real <a href> now (see
				// ItemsRenderer::render_pagination()'s docblock -- SEO
				// crawlability) -- .closest() still finds them the same
				// way a <button> was found before, `aria-disabled` is the
				// disabled-Prev/Next marker now instead of `.disabled`
				// (which only ever worked for real <button> elements).
				const button = event.target.closest(
					'.fb-query-grid__page-btn'
				);
				if (
					! button ||
					button.getAttribute( 'aria-disabled' ) === 'true'
				) {
					return;
				}
				event.preventDefault();

				let targetPage;
				if (
					button.classList.contains( 'fb-query-grid__page-btn--prev' )
				) {
					targetPage = context.page - 1;
				} else if (
					button.classList.contains( 'fb-query-grid__page-btn--next' )
				) {
					targetPage = context.page + 1;
				} else {
					try {
						targetPage = JSON.parse(
							button.dataset.wpContext || '{}'
						).pageNum;
					} catch {
						return;
					}
				}

				if ( targetPage && targetPage !== context.page ) {
					fetchAndApply( rootEl, context, targetPage, false ).then(
						() => scrollFirstItemIntoView( rootEl )
					);
					updateUrlForPage( context.paginationSlug, targetPage );
				}
			} );
		},
		/**
		 * Sizes the FIRST batch of server-rendered Masonry items at
		 * hydration; later batches are re-laid-out inside fetchAndApply().
		 */
		initMasonryLayout() {
			const { ref } = getElement();
			layoutMasonryItems( ref.closest( '.fb-query-grid' ) );
		},
	},
} );

/**
 * Re-runs Masonry layout on resize (column count changes at breakpoints),
 * debounced so a drag-resize doesn't thrash layout.
 */
let masonryResizeTimeout;
window.addEventListener( 'resize', () => {
	clearTimeout( masonryResizeTimeout );
	masonryResizeTimeout = setTimeout( () => {
		document
			.querySelectorAll( '.fb-query-grid--masonry' )
			.forEach( ( rootEl ) => layoutMasonryItems( rootEl ) );
	}, 150 );
} );

/**
 * Keeps the address bar in sync with a pagination click's AJAX-fetched
 * page, as a `/<slug>/N/` path suffix (matching
 * ItemsRenderer::render_pagination()'s `<a href>`s). `pushState` (not
 * `replaceState`) so Back steps through visited pages one at a time -- see
 * the `popstate` listener below.
 *
 * @param {string} slug `context.paginationSlug`.
 * @param {number} page The page just navigated to.
 */
function updateUrlForPage( slug, page ) {
	const url = new URL( window.location.href );
	url.pathname = buildPaginatedPath( url.pathname, slug, page );
	window.history.pushState( { page }, '', url );
}

/**
 * Makes Back/Forward actually do something -- `pushState` only changes
 * the URL, it doesn't reload. Re-derives the page from the URL itself, not
 * `event.state`, since navigating back past the first pagination click has
 * no state object at all.
 */
window.addEventListener( 'popstate', () => {
	instancesByQueryId.forEach( ( { rootEl, context } ) => {
		const match = window.location.pathname.match(
			new RegExp( `/${ context.paginationSlug }/(\\d+)/?$` )
		);
		const page = match ? Math.max( 1, parseInt( match[ 1 ], 10 ) ) : 1;
		if ( page !== context.page ) {
			fetchAndApply( rootEl, context, page, false ).then( () =>
				scrollFirstItemIntoView( rootEl )
			);
		}
	} );
} );

/**
 * @param {HTMLElement} rootEl  The `.fb-query-grid` root element for this instance.
 * @param {Object}      context This instance's context (for `carouselItemsPerView`).
 * @return {number} How many carousel "pages" (groups of `carouselItemsPerView` cards) exist.
 */
function countCarouselGroups( rootEl, context ) {
	const total =
		rootEl?.querySelectorAll( '.fb-query-grid__item' ).length || 0;
	const perView = context.carouselItemsPerView || 1;
	return Math.max( 1, Math.ceil( total / perView ) );
}

/**
 * Shows/hides carousel slides for the current `carouselIndex` window via
 * plain DOM toggling, not `data-wp-bind--hidden` (see file docblock for
 * why). Items are toggled by DOM position, matching the `slideIndex` each
 * was rendered with.
 *
 * @param {HTMLElement} rootEl  The `.fb-query-grid` root element for this instance.
 * @param {Object}      context This instance's context (`carouselIndex` + `carouselItemsPerView`).
 */
function applyCarouselVisibility( rootEl, context ) {
	const perView = context.carouselItemsPerView || 1;
	const groupStart = context.carouselIndex * perView;
	const items = rootEl?.querySelectorAll( '.fb-query-grid__item' ) || [];
	items.forEach( ( item, index ) => {
		item.hidden = ! ( index >= groupStart && index < groupStart + perView );
	} );
}

/**
 * Scrolls the first item of a freshly-loaded page into view -- pagination
 * controls sit below the results, so without this a page change looks
 * like nothing happened. Only called on explicit pagination clicks, not
 * search/filter/load-more.
 *
 * @param {HTMLElement} rootEl The `.fb-query-grid` root element for this instance.
 */
function scrollFirstItemIntoView( rootEl ) {
	const firstItem = rootEl?.querySelector( '.fb-query-grid__item' );
	firstItem?.scrollIntoView( { behavior: 'smooth', block: 'start' } );
}

/**
 * Sizes each Masonry item's grid row-span from its real rendered height,
 * keeping items in DOM order with variable heights (see file docblock).
 *
 * @param {HTMLElement} rootEl The `.fb-query-grid` root element for this instance.
 */
function layoutMasonryItems( rootEl ) {
	const itemsEl = rootEl?.querySelector( '.fb-query-grid__items' );
	if ( ! itemsEl ) {
		return;
	}

	// Overridden here, not in style.scss, so the editor's ServerSideRender
	// preview (which never runs this file) keeps its CSS-only fallback.
	itemsEl.style.gridAutoRows = '1px';

	const styles = window.getComputedStyle( itemsEl );
	const rowHeight = parseFloat( styles.gridAutoRows ) || 1;
	const rowGap = parseFloat( styles.rowGap ) || 0;

	itemsEl
		.querySelectorAll( ':scope > .fb-query-grid__item' )
		.forEach( ( item ) => {
			const height = item.getBoundingClientRect().height;
			const span = Math.max(
				1,
				Math.ceil( ( height + rowGap ) / ( rowHeight + rowGap ) )
			);
			item.style.gridRowEnd = `span ${ span }`;

			// The image is what usually drives an item's final height, but
			// loads asynchronously -- re-measure just this one item once it
			// is actually in, rather than re-running the whole grid.
			const img = item.querySelector( 'img' );
			if ( img && ! img.complete ) {
				img.addEventListener(
					'load',
					() => layoutMasonryItems( rootEl ),
					{ once: true }
				);
			}
		} );
}

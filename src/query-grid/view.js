/**
 * Query Grid frontend behavior (Interactivity API).
 *
 * Why: numbered pagination, search, category-facet filtering, and "Load
 * more" (an alternative to numbered pagination, see paginationStyle in
 * Renderer::render()) all fetch from /flux-blocks/v1/query (this block
 * isn't tied to the main WP query, so it can't use core's Query Loop
 * "enhanced pagination"). Pagination/search/filter REPLACE both the
 * `.fb-query-grid__items` AND `.fb-query-grid__pagination-slot` content;
 * "Load more" APPENDS to items instead — see fetchAndApply()'s `append` arg,
 * the one place that distinction lives.
 * Carousel navigation is pure client-side -- every slide is already
 * server-rendered, nav just shows/hides via the `isCurrentSlide` state.
 *
 * Why pagination is NOT a `state` getter (unlike isCurrentSlide/isActiveTerm):
 * the Interactivity API evaluates `data-wp-bind` directives server-side too,
 * using whatever `wp_interactivity_state()` registered -- a *derived* JS
 * getter has no server equivalent, so it resolves to `undefined` there and
 * `!undefined` hides everything on first paint. That bug shipped once
 * already (see Renderer::render_pagination()'s docblock). Pagination is
 * fully server-rendered instead, both initially and on every fetch here.
 *
 * Why the Prev/Next/page-number buttons are NOT wired with `data-wp-on--click`
 * (a *second*, different bug fixed in the same pass as the one above):
 * `.fb-query-grid__pagination-slot`'s innerHTML gets REPLACED on every page
 * change. The Interactivity API only binds `data-wp-on--*` directives during
 * its one-time hydration walk at page load -- HTML injected afterwards via
 * plain `innerHTML`/`insertAdjacentHTML` is invisible to it, so a freshly
 * inserted button's `data-wp-on--click` is inert. That is why clicking
 * "Next" worked once and then silently did nothing on the next click.
 * Fixed with plain event delegation instead: `initPaginationDelegation()`
 * (wired via `data-wp-init` on `.fb-query-grid__pagination-slot`, which is
 * itself never replaced, only its children are) attaches ONE native
 * `addEventListener` that keeps working no matter how many times the
 * buttons underneath it get swapped out.
 *
 * Why carousel slide visibility is a plain DOM toggle (applyCarouselVisibility)
 * and NOT a `data-wp-bind--hidden` directive (a *third*, related bug -- the
 * carousel shipped once already with `data-wp-bind--hidden="!state.isCurrentSlide"`
 * and every slide came out hidden, arrows-only, no cards): `WP_Block::render()`
 * calls WordPress's OWN `wp_interactivity_process_directives()` on this
 * block's rendered HTML server-side, which re-evaluates every `data-wp-bind`
 * directive right after Renderer::render_item() runs. A derived `state`
 * getter like `isCurrentSlide` has no server-side registration, so it
 * resolves to `undefined` there; `!undefined` is `true`, so WordPress itself
 * force-added `hidden` back onto every article, overwriting whatever
 * Renderer.php had correctly baked in. There is no directive-based fix for
 * this -- `state` getters are JS-only by design, full stop -- so carousel
 * visibility after the first paint is now handled the same way pagination
 * clicks are: plain DOM manipulation, never a directive WordPress could
 * re-process.
 *
 * Why Masonry needs layoutMasonryItems() (a JS row-span calculation, not a
 * pure-CSS layout): plain CSS `column-count` masonry reads column-major
 * (fills column 1 top-to-bottom completely, THEN starts column 2) -- with
 * many posts that puts e.g. post 2 nowhere near post 1 visually, which
 * reads as "wrong order" even though the query itself is correctly sorted.
 * There is no pure-CSS fix that keeps both variable card heights AND
 * left-to-right/row-major reading order (native `grid-template-rows:
 * masonry` isn't baseline yet) -- the standard workaround is CSS Grid
 * with each item's `grid-row-end: span N` set from its OWN measured
 * height, which is what layoutMasonryItems() does. See style.scss's
 * `&--masonry &__items` docblock for why `grid-auto-rows` itself is left
 * at the CSS default there (ServerSideRender's editor preview never runs
 * this file at all) and only overridden here once real JS is running.
 *
 * Plain-JS equivalent: `store()` + `data-wp-*` directives replace what
 * you'd otherwise write as `document.querySelectorAll(...)` +
 * `addEventListener('click', ...)` handlers that manually `fetch()`, set
 * `.innerHTML`, and toggle a `hidden` attribute/class after every state
 * change — the directives just declare "this attribute reflects this
 * state", so the DOM updates automatically whenever the state does. The
 * pagination delegation code is the one place here that IS written the
 * plain-JS way on purpose, for the reason above.
 *
 * Per-instance data (queryId/postType/layout/page/totalPages/searchQuery/
 * activeFacetTaxonomy/activeTermIds/...) lives in Interactivity `context`,
 * seeded by QueryGrid\Renderer::render() — not global `state` — so multiple
 * Query Grid instances on one page stay independent. Nested item/button
 * context (a carousel slide's `slideIndex`, a filter pill's `taxonomy`+
 * `termId`) merges with this parent context automatically, which is how
 * the state getters below can read both.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { nextIndex, prevIndex } from '../shared/carousel-utils';

/**
 * Registered by initPaginationDelegation() (one entry per Query Grid
 * instance that has numbered pagination) so the module-level `popstate`
 * listener further down can look an instance up by its queryId and re-sync
 * it after a browser back/forward navigation -- there is no Interactivity
 * API way to reach a specific element's live reactive `context` from
 * outside an action/callback, so this is the plain-JS workaround.
 *
 * @type {Map<string, {rootEl: HTMLElement, context: Object}>}
 */
const instancesByQueryId = new Map();

/**
 * Core fetch + DOM update logic, shared between the directive-driven
 * actions below (search/filter/load-more, whose own elements are never
 * replaced so keep working via normal `data-wp-on` binding) and the plain
 * delegated pagination click handler (whose buttons DO get replaced, see
 * the file docblock for why that needs a different wiring approach).
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
			blockId: context.queryId,
			// Renderer::render_pagination() needs the REAL page URL (not
			// the REST endpoint's own) to build correct `<a href>`s when
			// pagination gets re-rendered here -- see its docblock.
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

			// Clearing the field back to empty refetches immediately
			// instead of waiting for an explicit Enter/submit -- otherwise
			// the last filtered results stay on screen next to an empty
			// search box, which reads as "search doesn't come back" when
			// testing it. Typing (non-empty -> non-empty) still only
			// searches on submit, so this isn't a fetch-per-keystroke.
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
		 * Multiple terms can be toggled on together WITHIN one taxonomy's
		 * pill group (e.g. two categories at once) -- but clicking a pill
		 * from a DIFFERENT taxonomy than the currently active one resets
		 * the selection to just that pill, since only one taxonomy's
		 * tax_query clause is sent per request (see fetchAndApply()).
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
		 * "Load more" alternative to numbered pagination: fetches the next
		 * page and APPENDS it instead of replacing -- see paginationStyle in
		 * Renderer::render(). The load-more button itself is never replaced
		 * (only `.fb-query-grid__items` is appended to), so a normal
		 * `data-wp-on--click` directive stays bound and works indefinitely.
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
		 * Runs once (via `data-wp-init`) on `.fb-query-grid__pagination-slot`
		 * -- see the file docblock for why pagination clicks need plain
		 * event delegation instead of `data-wp-on--click`. `context` is
		 * captured here at hydration time; it stays a valid, live reference
		 * to this instance's reactive context object even as its properties
		 * keep changing on every later fetch.
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
				// Renderer::render_pagination()'s docblock -- SEO
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
		 * Runs once (via `data-wp-init`, only present on Masonry instances
		 * -- see Renderer::render_items_and_nav()) to size the FIRST batch
		 * of server-rendered items. Later batches (pagination/search/filter/
		 * load-more) are re-laid-out directly inside fetchAndApply() instead,
		 * since this only fires once at hydration.
		 */
		initMasonryLayout() {
			const { ref } = getElement();
			layoutMasonryItems( ref.closest( '.fb-query-grid' ) );
		},
	},
} );

/**
 * A column-count-per-breakpoint change (viewport resize crossing 600px/
 * 960px) changes how many rows each item's height maps to just as much as
 * a height change does -- re-run every Masonry instance on the page,
 * debounced so a drag-resize doesn't thrash layout on every pixel.
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
 * Strips any existing `/<slug>/N/` suffix from a pathname -- shared by
 * updateUrlForPage() (before appending a NEW page) and the `popstate`
 * reader (to isolate the number). Mirrors Renderer::build_page_url()'s PHP
 * side of the same logic; keep both in sync.
 *
 * @param {string} pathname `window.location.pathname`.
 * @param {string} slug     `context.paginationSlug` (see PaginationEndpoint.php).
 * @return {string} `pathname` with any trailing `/<slug>/N/` removed, always ending in `/`.
 */
function stripPaginationSuffix( pathname, slug ) {
	const stripped = pathname.replace(
		new RegExp( `/${ slug }/\\d+/?$` ),
		'/'
	);
	return stripped.endsWith( '/' ) ? stripped : `${ stripped }/`;
}

/**
 * Keeps the address bar in sync with a pagination click's AJAX-fetched page
 * -- `/<slug>/N/` is a path suffix (not a query string), matching
 * Renderer::render_pagination()'s `<a href>`s and what
 * Renderer::render()'s `get_query_var( PaginationEndpoint::slug() )` reads
 * back out on the next real page load. This is ONE SITE-WIDE segment
 * (`slug` is a site setting, not per-block), so multiple Query Grid
 * instances with numbered pagination on one page necessarily share it --
 * see PaginationEndpoint's docblock for why. `pushState` (not
 * `replaceState`) so the browser's own Back button steps back through
 * visited pages one at a time -- see the `popstate` listener below for how
 * Back/Forward gets the content to actually match.
 *
 * @param {string} slug `context.paginationSlug`.
 * @param {number} page The page just navigated to.
 */
function updateUrlForPage( slug, page ) {
	const url = new URL( window.location.href );
	let path = stripPaginationSuffix( url.pathname, slug );
	if ( page > 1 ) {
		path = `${ path }${ slug }/${ page }/`;
	}
	url.pathname = path;
	window.history.pushState( { page }, '', url );
}

/**
 * Browser Back/Forward doesn't re-run any of the code above (the page
 * itself never reloads, `pushState` only changes the URL) -- this makes
 * that navigation actually DO something. Re-derives the target page from
 * the URL itself, not from `event.state`, since a `popstate` fired by
 * navigating back past the very first pagination click has no state object
 * from this code at all (the original page-load entry predates it).
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
 * Shows/hides carousel slides to match the current `carouselIndex` window --
 * plain DOM toggling, deliberately NOT a `data-wp-bind--hidden` directive.
 * See the file docblock's "Why carousel slide visibility is a plain DOM
 * toggle" section for why a directive bound to a derived `state` getter
 * cannot work here (WordPress re-evaluates it server-side and gets it
 * wrong). Items are toggled by their DOM position, which matches the
 * `slideIndex` each one was rendered with (see Renderer::render_items()'s
 * `foreach ( $items as $index => $item )`).
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
 * After a pagination click (Prev/Next/a page number -- see
 * initPaginationDelegation()), the pagination controls themselves sit
 * below a full page of results, so without this the next page's items
 * load in ABOVE the current scroll position and look like nothing
 * happened. Scrolls the first item of the freshly-loaded page into view
 * instead, on every viewport size. Search/filter/load-more do NOT call
 * this -- only an explicit pagination click should jump the scroll
 * position, staying still is the expected behavior for those.
 *
 * @param {HTMLElement} rootEl The `.fb-query-grid` root element for this instance.
 */
function scrollFirstItemIntoView( rootEl ) {
	const firstItem = rootEl?.querySelector( '.fb-query-grid__item' );
	firstItem?.scrollIntoView( { behavior: 'smooth', block: 'start' } );
}

/**
 * Sizes each Masonry item's grid row-span from its own real rendered
 * height, so items stay in DOM/query order (left-to-right, top row first)
 * while still getting variable heights -- see the file docblock's "Why
 * Masonry needs layoutMasonryItems()" section for the full explanation of
 * why plain CSS `column-count` can't do both at once.
 *
 * @param {HTMLElement} rootEl The `.fb-query-grid` root element for this instance.
 */
function layoutMasonryItems( rootEl ) {
	const itemsEl = rootEl?.querySelector( '.fb-query-grid__items' );
	if ( ! itemsEl ) {
		return;
	}

	// Overridden here (not in style.scss) so the editor's ServerSideRender
	// preview -- which never runs this file at all -- still gets a sane
	// CSS-only fallback (`grid-auto-rows: auto`, a plain responsive grid)
	// instead of every item being squashed to a near-0px row on the
	// frontend before this callback's first run.
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

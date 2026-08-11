/**
 * Featured CPT Section frontend behavior (Interactivity API).
 *
 * Why: carousel nav + "load more" reveal are both pure client-side — every
 * item is already server-rendered, some just start hidden — no REST call,
 * since the item set here is bounded/curated by definition (see PRD §5.3 /
 * docs/architecture.md §6). Hotspot hover shows the author+excerpt overlay.
 *
 * Item-level elements have their own `itemIndex`/`itemId` context, but
 * nested `data-wp-context` merges with the section-level ancestor context
 * (seeded by FeaturedCptSection\Renderer::render()) — so `getContext()`
 * inside an item still sees `visibleCount`/`total`/`hoveredId`/
 * `carouselIndex` too, without those keys being repeated per item.
 */
import { store, getContext } from '@wordpress/interactivity';
import { nextIndex, prevIndex } from '../shared/carousel-utils';

const REVEAL_STEP = 3;

store( 'flux-blocks/featured-cpt-section', {
	state: {
		get isItemVisible() {
			const { itemIndex, visibleCount, carouselIndex, isExpanded } =
				getContext();
			// Carousel mode (default): only the current slide shows.
			// Expanded mode (after "Load more"): a progressively-revealed grid.
			return isExpanded
				? itemIndex < visibleCount
				: itemIndex === carouselIndex;
		},
		get isHotspotVisible() {
			const { itemId, hoveredId } = getContext();
			return hoveredId === itemId;
		},
	},
	actions: {
		revealMore() {
			const context = getContext();
			context.isExpanded = true;
			context.visibleCount = Math.min(
				context.visibleCount + REVEAL_STEP,
				context.total
			);
		},
		carouselNext() {
			const context = getContext();
			context.carouselIndex = nextIndex(
				context.carouselIndex,
				context.total
			);
		},
		carouselPrev() {
			const context = getContext();
			context.carouselIndex = prevIndex(
				context.carouselIndex,
				context.total
			);
		},
		showHotspot() {
			const context = getContext();
			context.hoveredId = context.itemId;
		},
		hideHotspot() {
			getContext().hoveredId = 0;
		},
		/**
		 * Touch devices have no real hover — tap toggles the hotspot
		 * instead. Devices that DO support hover already get it from
		 * showHotspot/hideHotspot, so this bails out for them rather than
		 * fighting the hover state on every tap.
		 *
		 * @param {MouseEvent} event Click event on the item.
		 */
		toggleHotspotTouch( event ) {
			if ( window.matchMedia( '(hover: hover)' ).matches ) {
				return;
			}
			const context = getContext();
			context.hoveredId =
				context.hoveredId === context.itemId ? 0 : context.itemId;
			event.preventDefault();
		},
	},
} );

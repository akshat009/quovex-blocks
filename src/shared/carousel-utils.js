/**
 * Shared carousel index math, used by Query Grid's Carousel layout (see
 * query-grid/view.js) -- kept in its own file rather than inline so the
 * "what's the next index" logic has one obvious home if any other block
 * ever needs the same carousel wrap-around behavior.
 *
 * Plain-JS equivalent: `next = (current + 1) % total` is the same wrap-
 * around math you'd write for a plain `<div>` slider with vanilla JS +
 * manual `classList.add('active')` swapping — Interactivity API state just
 * makes the DOM update declarative (`data-wp-bind`) instead of imperative
 * (you don't manually loop elements and toggle a class after calling this).
 */

/**
 * @param {number} current Current index.
 * @param {number} total   Total slide count.
 * @return {number} Next index, wrapping to 0 past the end.
 */
export function nextIndex( current, total ) {
	if ( total <= 0 ) {
		return 0;
	}
	return ( current + 1 ) % total;
}

/**
 * @param {number} current Current index.
 * @param {number} total   Total slide count.
 * @return {number} Previous index, wrapping to the last slide before 0.
 */
export function prevIndex( current, total ) {
	if ( total <= 0 ) {
		return 0;
	}
	return ( current - 1 + total ) % total;
}

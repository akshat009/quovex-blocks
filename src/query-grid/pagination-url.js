/**
 * Query Grid's numbered-pagination path logic -- split out of view.js into
 * its own dependency-free module for two reasons: view.js imports
 * `@wordpress/interactivity` (ESM-only, no CommonJS build), which a plain
 * Jest unit test can't resolve without extra config; this module has zero
 * imports, so pagination-url.test.js can import it directly. It also makes
 * the "same logic exists twice" relationship with ItemsRenderer::build_page_url()
 * (PHP) explicit -- one small file instead of two functions buried in a
 * 700-line frontend script.
 *
 * Mirrors ItemsRenderer::build_page_url()'s PHP side; keep both in sync --
 * see tests/fixtures/pagination-paths.json, which both
 * tests/Unit/PaginationUrlSyncTest.php (PHP) and test/pagination-url.test.js
 * (here) run the exact same cases against.
 */

/**
 * Strips any existing `/<slug>/N/` suffix from a pathname -- shared by
 * buildPaginatedPath() (before appending a NEW page) and view.js's
 * `popstate` reader (to isolate the number).
 *
 * @param {string} pathname `window.location.pathname`.
 * @param {string} slug     `context.paginationSlug` (see PaginationEndpoint.php).
 * @return {string} `pathname` with any trailing `/<slug>/N/` removed, always ending in `/`.
 */
export function stripPaginationSuffix( pathname, slug ) {
	const stripped = pathname.replace(
		new RegExp( `/${ slug }/\\d+/?$` ),
		'/'
	);
	return stripped.endsWith( '/' ) ? stripped : `${ stripped }/`;
}

/**
 * Builds the pathname for a given page.
 *
 * @param {string} pathname `window.location.pathname`.
 * @param {string} slug     `context.paginationSlug`.
 * @param {number} page     Target page.
 * @return {string} New pathname for `page`, always ending in `/`.
 */
export function buildPaginatedPath( pathname, slug, page ) {
	const stripped = stripPaginationSuffix( pathname, slug );
	return page > 1 ? `${ stripped }${ slug }/${ page }/` : stripped;
}

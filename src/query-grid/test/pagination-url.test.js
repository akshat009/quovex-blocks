/**
 * Sync test for the audit finding that stripPaginationSuffix()/
 * buildPaginatedPath() (here) and ItemsRenderer::build_page_url() (PHP)
 * are two independent implementations of the same pagination-path logic
 * with no guard against them drifting apart.
 *
 * This runs every case in tests/fixtures/pagination-paths.json against the
 * JS side; tests/Unit/PaginationUrlSyncTest.php runs the exact same file
 * against the PHP side. A case that only fails on one side is exactly the
 * kind of drift this pair is meant to catch.
 */

/**
 * External dependencies
 */
const fixture = require( '../../../tests/fixtures/pagination-paths.json' );

/**
 * Internal dependencies
 */
import {
	stripPaginationSuffix,
	buildPaginatedPath,
} from '../pagination-url.js';

describe( 'buildPaginatedPath (pagination-paths.json fixture)', () => {
	it.each( fixture.cases.map( ( c ) => [ c.name, c ] ) )(
		'%s',
		( name, { pathname, slug, page, expected } ) => {
			expect( buildPaginatedPath( pathname, slug, page ) ).toBe(
				expected
			);
		}
	);
} );

describe( 'stripPaginationSuffix', () => {
	it( 'is idempotent -- stripping an already-stripped pathname is a no-op', () => {
		const once = stripPaginationSuffix( '/blog/flux-page/4/', 'flux-page' );
		const twice = stripPaginationSuffix( once, 'flux-page' );

		expect( twice ).toBe( once );
	} );
} );

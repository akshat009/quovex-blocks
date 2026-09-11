<?php
/**
 * Registers the `/<slug>/N/` URL suffix Query Grid's numbered pagination
 * uses instead of a `?query=N` query string. `<slug>` defaults to
 * `quovex-page` (namespaced to avoid colliding with a real page of that
 * name) and is a site-wide setting, editable via Rest\PaginationSlugController
 * from any Query Grid block's Inspector.
 *
 * Uses `add_rewrite_endpoint()` (WordPress's mechanism for appending a path
 * segment to any permalink, like WooCommerce's `/my-account/orders/`)
 * rather than `/page/N/`, which is already claimed by archive pagination
 * and the `<!--nextpage-->` tag.
 *
 * Changing the stored slug breaks existing bookmarked pagination links and
 * requires a rewrite-rules flush -- PaginationSlugController::update_slug()
 * triggers that automatically; the default is only flushed once, on
 * activation (see quovex-blocks.php).
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Blocks\QueryGrid\Routing;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the (site-configurable) pagination rewrite endpoint.
 */
class PaginationEndpoint {

	/** The wp_options key the chosen slug is stored under. */
	const OPTION = 'quovex_blocks_pagination_slug';

	/**
	 * The configured slug -- also the `get_query_var()` key it's
	 * accessible under.
	 *
	 * @return string
	 */
	public static function slug(): string {
		$value = sanitize_title( (string) get_option( self::OPTION, 'quovex-page' ) );
		return $value ? $value : 'quovex-page';
	}

	/**
	 * Register the WordPress hooks.
	 */
	public function init_hooks(): void {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		// Only fires when the value actually changes (update_option() is a
		// no-op, and doesn't fire this hook, if unchanged) -- rare, not a
		// per-request cost.
		add_action( 'update_option_' . self::OPTION, 'flush_rewrite_rules' );
	}

	/**
	 * Maps the endpoint to a query var for this request only -- the actual
	 * rewrite-rules flush (making the URL pattern resolve at all) happens
	 * separately, on activation and whenever the stored slug changes (see
	 * init_hooks() above).
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function add_endpoint(): void {
		add_rewrite_endpoint( self::slug(), EP_ALL );
	}
}

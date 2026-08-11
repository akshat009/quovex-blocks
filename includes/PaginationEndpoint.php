<?php
/**
 * Registers the `/<slug>/N/` URL suffix Query Grid's numbered pagination
 * uses instead of a `?query=N` query string -- `<slug>` defaults to
 * `flux-page` but is a site-wide setting editable from any Query Grid
 * block's own Inspector (see `Rest\PaginationSlugController` +
 * `src/query-grid/edit.js`'s "Pagination URL Segment" control, right next
 * to where Pagination Style is already chosen).
 *
 * Why `add_rewrite_endpoint()` and not a hand-written `add_rewrite_rule()`:
 * this is WordPress's own mechanism for appending an extra path segment to
 * ANY existing permalink -- posts, pages, archives, even the homepage --
 * without writing (and maintaining) a separate matching rule per post
 * type/archive combination. WooCommerce's `/my-account/orders/` works the
 * same way.
 * Why NOT `/page/N/` (what was tried first, and what most pagination UIs
 * default to): that exact segment is already claimed by TWO other
 * WordPress features -- archive pagination, and the `<!--nextpage-->` tag
 * that splits a single post/page's own content across multiple views.
 * Reusing it for Query Grid's own, unrelated pagination would silently
 * conflict with either one on a page that also uses them -- confirmed as a
 * real, not just theoretical, risk.
 * Why `flux-page` as the DEFAULT (not a shorter/plainer name like
 * `blogpage`, tried second): an EP_ALL endpoint's name can ALSO collide
 * with a real page/post that happens to have that exact slug (e.g. an
 * actual page at `/blogpage/` on some site) -- `flux-page` is prefixed
 * with the plugin's own namespace specifically so that collision becomes
 * implausible by default, while still being changeable (see above) if a
 * site owner's own content genuinely collides with it too.
 * Why it is ONE SITE-WIDE value, not a per-block-instance attribute: the
 * rewrite rule itself has to be registered with one fixed segment name at
 * `init`, before WordPress has parsed any specific post's block content --
 * there is no way to know a per-instance attribute value that early, and a
 * Query Grid using numbered pagination is meant to work no matter which
 * post it is placed on.
 * Impact of changing: `slug()` is read by `Renderer::render()` (via
 * `get_query_var()`) and rebuilt into every pagination `<a href>` by
 * `Renderer::render_pagination()` -- changing the stored value breaks
 * every already-shared/bookmarked pagination link out in the wild built
 * against the OLD value. Also requires a rewrite-rules flush to take
 * effect, which `Rest\PaginationSlugController::update_slug()` triggers
 * automatically on save (via the `update_option_{option}` hook below) --
 * unlike the DEFAULT value, which is only flushed once, on plugin
 * activation (see flux-blocks.php; flushing on every request would be a
 * real performance problem, it is an expensive operation).
 *
 * @package FluxBlocks
 */

namespace FluxBlocks;

/**
 * Registers the (site-configurable) pagination rewrite endpoint.
 */
class PaginationEndpoint {

	/**
	 * The wp_options key the chosen slug is stored under -- also used by
	 * Rest\PaginationSlugController, kept here since this class owns what
	 * the value actually means/is used for.
	 */
	const OPTION = 'flux_blocks_pagination_slug';

	/**
	 * The endpoint name -- also the URL segment itself (`/<slug>/N/`) and
	 * the `get_query_var()` key it becomes accessible under.
	 *
	 * @return string
	 */
	public static function slug(): string {
		$value = sanitize_title( (string) get_option( self::OPTION, 'flux-page' ) );
		return $value ? $value : 'flux-page';
	}

	/**
	 * Register the WordPress hooks.
	 */
	public function init_hooks(): void {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		// Fires only when the stored value actually changes (WordPress's
		// own update_option() no-ops -- and does not fire this hook -- if
		// the new value equals the old one), so this stays a rare event,
		// not a per-request cost.
		add_action( 'update_option_' . self::OPTION, 'flush_rewrite_rules' );
	}

	/**
	 * Must run on every `init` (not just activation) -- this only tells
	 * WordPress how to MAP the endpoint to a query var for THIS request;
	 * it does not persist anywhere on its own. The one-time, expensive
	 * step (regenerating cached rewrite rules so the URL pattern actually
	 * resolves at all) happens separately: once on plugin activation (see
	 * flux-blocks.php) for the untouched default, and again automatically
	 * whenever the stored value changes (see register() above).
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function add_endpoint(): void {
		add_rewrite_endpoint( self::slug(), EP_ALL );
	}
}

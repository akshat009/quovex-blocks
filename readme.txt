=== Flux Blocks ===
Contributors:      developerakshat
Tags:               gutenberg, blocks, query, grid, carousel
Requires at least:  6.8
Tested up to:       7.0
Requires PHP:       7.4
Stable tag:         0.1.0
License:            GPL-2.0-or-later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

A filterable Query Grid and a Featured CPT Section block for any public post type -- built with the native block editor and the Interactivity API, no page-builder layer.

== Description ==

Flux Blocks adds two focused, modern Gutenberg blocks to the native block editor -- a filterable/paginated posts grid, and a curated featured-posts section with a carousel and hover hotspots. Both work with **any public post type**, not just Posts.

= Why this plugin? =

* **No separate builder layer:** everything is a native block, edited with the block editor you already use -- no new page-building interface to learn.
* **Works with any post type:** pick Posts, Pages, or any custom post type registered on your site, right from the block's own settings.
* **Real interactivity, no jQuery:** search, filtering, pagination, and carousels are all built on WordPress's own Interactivity API -- lightweight, no extra JS framework loaded.
* **SEO-friendly pagination:** Query Grid's numbered pagination uses real, crawlable links with a configurable URL segment (e.g. `/your-site/flux-page/2/`) -- not JavaScript-only buttons search engines can't follow.
* **Cache-aware by design:** every query is cached and automatically invalidated the moment a post is created, edited, or deleted -- fast without ever showing stale content.

== Blocks ==

= Query Grid =

A filterable, paginated grid of posts.

* Four layouts as native Style Variations: Grid, List, Masonry, and Carousel.
* Optional search bar and category/taxonomy filter pills (multi-select within a taxonomy).
* Optional sidebar layout for search + filters, with left/right placement.
* Numbered pagination or a "Load more" button -- your choice.
* "Show all posts" toggle to disable pagination entirely for smaller lists.
* Two-tone heading with a colored accent word, full color controls for pills/pagination/meta text, and responsive column counts (mobile/tablet/desktop).

= Featured CPT Section =

A curated or automatic selection of posts, presented as a carousel.

* Manual mode (hand-pick specific posts) or automatic mode (latest N posts, optionally filtered by taxonomy).
* Carousel navigation with a "Load more" button that expands into a full grid.
* Hover/focus "hotspot" overlay showing the author and an excerpt on each item's image.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/flux-blocks` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Add a "Query Grid" or "Featured CPT Section" block from the "Flux Blocks" category in the block inserter.

== Frequently Asked Questions ==

= Does this plugin work with custom post types? =

Yes. Both blocks let you choose any public post type registered on your site from the block's own Inspector settings -- not just Posts.

= Does Query Grid's pagination reload the page? =

No. Clicking a page number fetches and swaps in the new results via the Interactivity API, without a full page reload. The address bar still updates to a real, shareable URL (and that URL correctly server-renders the right page if opened directly, e.g. by a search engine crawler).

= Can I change how the pagination URL looks? =

Yes. Query Grid's Inspector has a "Pagination URL Segment" field (shown when Pagination Style is set to "Page numbers") -- it's a site-wide setting, so changing it updates every Query Grid block on the site at once.

= Does Featured CPT Section make extra requests when I click "Load more" or the carousel arrows? =

No. Featured CPT Section's item set is bounded and curated by design, so every item is rendered once on page load; "Load more" and the carousel just reveal/hide what's already there.

= Is there a Pro version? =

Not currently -- every feature described above is included.

== Screenshots ==

1. Query Grid in the Grid layout, with search and category filter pills.
2. Query Grid in the Carousel style.
3. Query Grid's Inspector settings -- layout, pagination, and color controls.
4. Featured CPT Section with the hover hotspot showing an author and excerpt.

== Changelog ==

= 0.1.0 =
* Initial release: Query Grid (Grid/List/Masonry/Carousel layouts, search, taxonomy filters, sidebar layout, numbered/load-more pagination, show-all option, SEO-friendly pagination URLs) and Featured CPT Section (manual/automatic selection, carousel, load more, hover hotspots).

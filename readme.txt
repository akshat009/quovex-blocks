=== Flux Blocks ===
Contributors:      developerakshat
Tags:               gutenberg, blocks, query, posts, showcase
Requires at least:  6.8
Tested up to:       7.0
Requires PHP:       7.4
Stable tag:         0.1.0
License:            GPL-2.0-or-later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Three native Gutenberg blocks for showcasing content: a filterable Query Grid, a magazine-style Content Showcase, and a dismissible Notification Banner.

== Description ==

Flux Blocks adds three focused, modern Gutenberg blocks to the native block editor -- a filterable/paginated posts grid, a magazine-style Content Showcase with four layouts and interactive image hotspots, and a dismissible Notification Banner for announcements and alerts. Query Grid and Content Showcase both work with **any public post type**, not just Posts.

= Why this plugin? =

* **No separate builder layer:** everything is a native block, edited with the block editor you already use -- no new page-building interface to learn, no plugin-specific UI to fight.
* **Works with any post type:** pick Posts, Pages, or any custom post type registered on your site, right from the block's own settings -- not hardcoded to Posts.
* **Real interactivity, no jQuery:** search, filtering, pagination, carousels, and image hotspots are all built on WordPress's own Interactivity API -- lightweight, no extra JS framework loaded on your frontend.
* **SEO-friendly pagination:** Query Grid's numbered pagination uses real, crawlable links with a configurable URL segment (e.g. `/your-site/flux-page/2/`) -- not JavaScript-only buttons search engines can't follow.
* **Cache-aware by design:** every query is cached and automatically invalidated the moment a post, category, or tag changes -- fast without ever showing stale content.
* **Clean uninstall:** deactivating leaves your data untouched; deleting the plugin removes every option and cached value it created. Nothing lingers in your database.

== Blocks ==

= Query Grid =

A filterable, paginated grid of posts.

* Four layouts as native Style Variations: Grid, List, Masonry, and Carousel.
* Works with any public post type -- Posts, Pages, or any custom post type registered on your site.
* Optional search bar and category/taxonomy filter pills (multi-select within a taxonomy).
* Optional sidebar layout for search + filters, with left/right placement.
* Numbered pagination or a "Load more" button -- your choice.
* "Show all posts" toggle to disable pagination entirely for smaller lists.
* Two-tone heading with a colored accent word, full color controls for pills/pagination/meta text, and responsive column counts (mobile/tablet/desktop).
* Font family and font weight controls (pulled from your active theme, no separate fonts to install) for the heading, card titles, excerpts, meta text, filter pills, and pagination.

= Content Showcase =

A magazine-style showcase of up to three posts, automatic or hand-picked.

* Four layouts as native Style Variations: Magazine (one large card + smaller ones beside it), Split (alternating text/image rows), Overlay (text floating on the image), and Two-Thirds/One-Third (a large featured post beside a stacked column, divided by a vertical rule).
* Works with any public post type -- Posts, Pages, or any custom post type registered on your site.
* Automatic mode (latest posts, optionally filtered by category/taxonomy) or manual mode (hand-pick a specific post for each slot).
* Optional section heading with a colored accent word and a subheading.
* Per-slot control over which post shows its image, date, and excerpt.
* Interactive image hotspots -- add clickable pins anywhere on a post's image, each opening a small popover with its own title, text, and optional link.
* Optional "Explore More" button with a custom label, link, and colors.
* Color controls for the heading, card titles, dates, excerpts, and hotspot pins/tooltips.
* Font family and font weight controls (pulled from your active theme, no separate fonts to install) for the heading, subheading, card titles, excerpts, dates, and the Explore button.

= Notification Banner =

A dismissible announcement bar or alert box for promos, notices, and updates.

* Four presets (Announcement, Info, Success, Warning) and five style variations (Gradient Background, Left Accent, Soft Card, Glassmorphism, Outline).
* Icon picker: emoji, a built-in dashicon, or a small SVG icon set.
* Editable title and message, an optional CTA button with its own label, link, and open-in-new-tab option.
* Dismiss button with "remember dismissal" (stored in the visitor's browser, so a dismissed banner stays hidden on their next visit).
* Color and typography controls (font family/weight, pulled from your active theme) for the background, text, accent, and button.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/flux-blocks` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Add a "Query Grid", "Content Showcase", or "Notification Banner" block from the "Flux Blocks" category in the block inserter.

== Frequently Asked Questions ==

= Does this plugin work with custom post types? =

Yes. Both blocks let you choose any public post type registered on your site from the block's own Inspector settings -- not just Posts.

= Does Query Grid's pagination reload the page? =

No. Clicking a page number fetches and swaps in the new results via the Interactivity API, without a full page reload. The address bar still updates to a real, shareable URL (and that URL correctly server-renders the right page if opened directly, e.g. by a search engine crawler).

= Can I change how the pagination URL looks? =

Yes. Query Grid's Inspector has a "Pagination URL Segment" field (shown when Pagination Style is set to "Page numbers") -- it's a site-wide setting, so changing it updates every Query Grid block on the site at once.

= How many posts can Content Showcase show? =

Up to three -- it's designed as a compact, curated showcase rather than a full listing. Use Query Grid instead for a larger, paginated set of posts.

= What are Content Showcase's image hotspots? =

Small clickable pins you position anywhere on a post's image, each opening a popover with its own title, description, and an optional link -- useful for calling out a detail in the photo without cluttering the card itself.

= Will this slow down my site? =

No. Query results are cached automatically (and cleared the moment relevant content changes), and the frontend uses the lightweight Interactivity API instead of loading jQuery or another JS framework. Nothing is loaded on pages that don't use these blocks.

= What happens to my data if I delete the plugin? =

Everything Flux Blocks created -- cached query results and the pagination URL setting -- is removed on deletion. Deactivating the plugin, on the other hand, leaves everything untouched in case you reactivate later.

= Is there a Pro version? =

Not currently -- every feature described above is included.

== Screenshots ==

1. Query Grid in the Grid layout, with search, category filter pills, and numbered SEO-friendly pagination.
2. Query Grid in the Carousel style.
3. Query Grid in the Masonry style.
4. Query Grid in the List style.
5. Query Grid with the filter sidebar layout.
6. Query Grid with the "Load more" button.
7. Content Showcase in the Magazine layout, with the "Explore More" button.
8. Content Showcase in the Split layout.
9. Content Showcase in the Overlay layout.
10. Content Showcase in the Two-Thirds layout.
11. Content Showcase's image hotspot popover.
12. Notification Banner, default style.
13. Notification Banner with the Warning preset and Glassmorphism style.
14. Notification Banner with the Success preset and Left Border Accent style.
15. Query Grid's block editor Settings panel -- post type, pagination, and column controls.
16. Content Showcase's block editor Settings panel -- manual post selection.
17. Notification Banner's block editor Settings panel -- title, message, and icon picker.

== Changelog ==

= 0.1.0 =
* Initial release: Query Grid (Grid/List/Masonry/Carousel layouts, search, taxonomy filters, sidebar layout, numbered/load-more pagination, show-all option, SEO-friendly pagination URLs) and Content Showcase (Magazine/Split/Overlay/Two-Thirds layouts, automatic/manual post selection, section heading, image hotspots, Explore More button, color controls).

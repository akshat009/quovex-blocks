<?php
/**
 * Plugin Name:       Flux Blocks
 * Description:       A filterable Query Grid and a customizable Content Showcase block, built with the native block editor and the Interactivity API.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Akshat
 * Author URI:        https://github.com/akshat009
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flux-blocks
 *
 * @package           flux-blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
 * Why: brings in Composer's PSR-4 autoloader so `FluxBlocks\...` classes
 * under includes/ resolve without individual `require` statements.
 * Impact of changing: removing/moving this breaks every class in includes/ —
 * `composer install` must have been run at least once for vendor/ to exist.
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
 * based on the registered block metadata. Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function flux_blocks_block_init() {
	wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
}
add_action( 'init', 'flux_blocks_block_init' );

/*
 * Why: boots the plugin's composition root (category registration, cache
 * invalidation hooks, REST route) — see includes/Plugin.php.
 */
add_action( 'init', array( \FluxBlocks\Plugin::class, 'boot' ) );

/*
 * Why: FluxBlocks\PaginationEndpoint::add_endpoint() (called every 'init'
 * via Plugin::boot()) only registers the `/flux-page/N/` query-var mapping
 * in memory for the CURRENT request -- WordPress still needs its cached
 * rewrite rules regenerated once for that URL pattern to actually resolve
 * at all, which only happens on a rules flush. Doing that flush on every
 * request would be a real performance problem (it is an expensive
 * operation), so it happens exactly once here, on activation, instead.
 * Impact of changing: if the endpoint is ever renamed (see
 * PaginationEndpoint::VAR) or this hook is removed, anyone who already had
 * the plugin active needs to deactivate + reactivate once (or manually
 * flush permalinks under Settings > Permalinks) to pick up the change --
 * it will not happen automatically otherwise.
 */
register_activation_hook(
	__FILE__,
	function () {
		( new \FluxBlocks\PaginationEndpoint() )->add_endpoint();
		flush_rewrite_rules();
	}
);
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

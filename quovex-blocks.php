<?php
/**
 * Plugin Name:       Quovex Blocks
 * Plugin URI:        https://github.com/akshat009/quovex-blocks
 * Description:       A filterable Query Grid, a customizable Content Showcase, and a dismissible Notification Banner block, built with the native block editor and the Interactivity API.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Akshat
 * Author URI:        https://github.com/akshat009
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quovex-blocks
 *
 * @package           quovex-blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
 * Autoload `QuovexBlocks\...` classes from includes/ (PSR-4).
 *
 * Prefer Composer's autoloader when vendor/ is present (dev checkouts that
 * ran `composer install`); otherwise fall back to a minimal hand-rolled
 * PSR-4 loader so the shipped plugin needs no vendor/ directory at all.
 * The plugin has zero runtime Composer dependencies — composer.json is dev
 * tooling only — so the fallback is the path real installs take.
 */
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( $class_name ) {
			$prefix   = 'QuovexBlocks\\';
			$base_dir = __DIR__ . '/includes/';
			$len      = strlen( $prefix );

			if ( 0 !== strncmp( $prefix, $class_name, $len ) ) {
				return;
			}

			$relative_class = substr( $class_name, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

/**
 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
 * based on the registered block metadata. Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function quovex_blocks_block_init() {
	wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
}
add_action( 'init', 'quovex_blocks_block_init' );

/*
 * Why: boots the plugin's composition root (category registration, cache
 * invalidation hooks, REST routes) — see includes/Plugin.php.
 */
add_action( 'init', array( \QuovexBlocks\Plugin::class, 'boot' ) );

/*
 * Why: QuovexBlocks\PaginationEndpoint::add_endpoint() (called every 'init'
 * via Plugin::boot()) only registers the query-var mapping for whatever
 * slug PaginationEndpoint::slug() currently resolves to (`/quovex-page/N/`
 * by default, but site-configurable -- see that class's docblock) in
 * memory for the CURRENT request -- WordPress still needs its cached
 * rewrite rules regenerated once for that URL pattern to actually resolve
 * at all, which only happens on a rules flush. Doing that flush on every
 * request would be a real performance problem (it is an expensive
 * operation), so it happens exactly once here, on activation, instead.
 * Impact of changing: if the endpoint is ever renamed (see
 * PaginationEndpoint::OPTION) or this hook is removed, anyone who already
 * had the plugin active needs to deactivate + reactivate once (or manually
 * flush permalinks under Settings > Permalinks) to pick up the change --
 * it will not happen automatically otherwise.
 */
register_activation_hook(
	__FILE__,
	function () {
		( new \QuovexBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint() )->add_endpoint();
		flush_rewrite_rules();
	}
);
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

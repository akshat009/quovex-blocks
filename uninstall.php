<?php
/**
 * Uninstall cleanup.
 *
 * WordPress only loads this file on actual plugin DELETION (via the admin's
 * "Delete" link or `wp plugin uninstall`), never on plain deactivation --
 * see https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/.
 *
 * Why this is needed: CacheInvalidator/QueryCache/PaginationEndpoint leave
 * data in wp_options that plugin deactivation never touches --
 * `quovex_blocks_pagination_slug` (PaginationEndpoint::OPTION), and per post
 * type ever queried, a `_quovex_blocks_cache_keys_{post_type}` registry
 * option (QueryCache::REGISTRY_PREFIX) plus every `qv_q_*` transient it
 * points at. Left alone, all of it survives the plugin being deleted.
 *
 * @package QuovexBlocks
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'quovex_blocks_pagination_slug' );

/**
 * Deletes the per-post-type cache-key registry options and every transient
 * they reference. Wrapped in a function so its locals stay function-scoped --
 * uninstall.php otherwise runs in the global scope, where every variable
 * reads as an unprefixed global.
 */
function quovex_blocks_uninstall_cleanup(): void {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time uninstall cleanup, no option API equivalent for "find options by prefix".
	$registry_options = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_quovex_blocks_cache_keys_' ) . '%'
		)
	);

	foreach ( $registry_options as $registry_option ) {
		$keys = get_option( $registry_option, array() );
		foreach ( (array) $keys as $key ) {
			delete_transient( $key );
		}
		delete_option( $registry_option );
	}
}

quovex_blocks_uninstall_cleanup();

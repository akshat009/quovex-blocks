<?php
/**
 * Transient-backed read-through cache for query results, keyed by a
 * signature (post type + normalized args hash). Uses WordPress's
 * Transients API directly rather than a custom driver -- it already picks
 * Redis/Memcached over wp_options transparently when available. See
 * CacheInvalidator for how entries get cleared.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Transients-backed read-through cache for query results.
 */
class QueryCache implements CacheInterface {

	const TTL               = HOUR_IN_SECONDS;
	const REGISTRY_PREFIX   = '_flux_blocks_cache_keys_';
	const MAX_REGISTRY_SIZE = 200;

	/**
	 * Get a cached value, or compute + cache it via the callback.
	 *
	 * @param string   $post_type Post type this query is scoped to (for the invalidation registry).
	 * @param string   $signature Unique signature for this specific query.
	 * @param callable $callback  Produces the value to cache on a miss.
	 * @return mixed
	 */
	public function remember( string $post_type, string $signature, callable $callback ) {
		$key    = 'fb_q_' . md5( $signature );
		$cached = get_transient( $key );

		// Wrapped in array('value' => ...) -- get_transient() returns bare
		// `false` for both "miss" and "cached value is legitimately false",
		// and wrapping is what tells them apart.
		if ( is_array( $cached ) && array_key_exists( 'value', $cached ) ) {
			return $cached['value'];
		}

		$value = $callback();
		set_transient( $key, array( 'value' => $value ), self::TTL );
		$this->register_key( $post_type, $key );

		return $value;
	}

	/**
	 * Records a cache key against its post type so CacheInvalidator can
	 * find it later -- a targeted alternative to a `LIKE`-based transient
	 * sweep, which misses sites using a persistent object cache. Capped at
	 * MAX_REGISTRY_SIZE, oldest-first; a dropped key just means that
	 * transient lingers until its own TTL instead of being proactively
	 * cleared -- a bounded delay, not a correctness issue.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $key       Transient key that was just written.
	 */
	private function register_key( string $post_type, string $key ): void {
		$registry_option = self::REGISTRY_PREFIX . $post_type;
		$keys            = get_option( $registry_option, array() );

		if ( ! in_array( $key, $keys, true ) ) {
			$keys[] = $key;
			if ( count( $keys ) > self::MAX_REGISTRY_SIZE ) {
				$keys = array_slice( $keys, -self::MAX_REGISTRY_SIZE );
			}
			update_option( $registry_option, $keys, false ); // autoload=false: not needed on every page load.
		}
	}

	/**
	 * Clear every cached query for a post type.
	 *
	 * @param string $post_type Post type slug.
	 */
	public function forget_for_post_type( string $post_type ): void {
		$registry_option = self::REGISTRY_PREFIX . $post_type;
		$keys            = get_option( $registry_option, array() );

		foreach ( $keys as $key ) {
			delete_transient( $key );
		}

		delete_option( $registry_option );
	}
}

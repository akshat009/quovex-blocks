<?php
/**
 * Transient-backed read-through cache for query results, keyed by a
 * signature (post type + normalized args hash).
 *
 * Why: WP_Query + post-data transformation runs on every page load for
 * every Query Grid/Featured CPT Section instance — caching avoids repeating
 * that work between requests until the underlying content actually changes.
 * Impact of changing: changing the signature format or TTL changes
 * cache-hit behavior for every block instance at once; see CacheInvalidator
 * for how entries get cleared.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Query;

use FluxBlocks\Cache\CacheInterface;

/**
 * Transient/Redis-backed read-through cache for query results.
 */
class QueryCache {

	const TTL             = HOUR_IN_SECONDS;
	const REGISTRY_PREFIX = '_flux_blocks_cache_keys_';

	/** @var CacheInterface */
	private $driver;

	/**
	 * @param CacheInterface|null $driver Cache driver implementation.
	 */
	public function __construct( ?CacheInterface $driver = null ) {
		$this->driver = $driver ?? \FluxBlocks\Services::cache_driver();
	}

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
		$cached = $this->driver->get( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = $callback();
		$this->driver->set( $key, $value, self::TTL );
		$this->register_key( $post_type, $key );

		return $value;
	}

	/**
	 * Record a cache key against its post type so CacheInvalidator can find
	 * it later — a targeted alternative to a `LIKE`-based transient sweep,
	 * which silently misses sites where transients live in a persistent
	 * object cache (Redis/Memcached) rather than wp_options.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $key       Transient key that was just written.
	 */
	private function register_key( string $post_type, string $key ): void {
		$registry_option = self::REGISTRY_PREFIX . $post_type;
		$keys            = get_option( $registry_option, array() );

		if ( ! in_array( $key, $keys, true ) ) {
			$keys[] = $key;
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
			$this->driver->delete( $key );
		}

		delete_option( $registry_option );
	}
}

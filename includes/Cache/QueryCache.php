<?php
/**
 * Transient-backed read-through cache for query results, keyed by a
 * signature (post type + normalized args hash).
 *
 * Why: WP_Query + post-data transformation runs on every page load for
 * every Query Grid/Content Showcase instance — caching avoids repeating
 * that work between requests until the underlying content actually changes.
 * Uses WordPress's own Transients API directly (get_transient()/
 * set_transient()/delete_transient()) rather than a custom cache-driver
 * abstraction: the Transients API already transparently uses a persistent
 * object cache (Redis/Memcached) instead of wp_options when one is active
 * (wp_using_ext_object_cache()) -- that decision is made INSIDE
 * WordPress core's own transient functions, so a separate driver class to
 * pick between "Redis" and "database" would just be redundantly
 * re-implementing something the Transients API already does for free. See
 * https://developer.wordpress.org/apis/transients/.
 * Impact of changing: changing the signature format or TTL changes
 * cache-hit behavior for every block instance at once; see CacheInvalidator
 * for how entries get cleared.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Logger\LoggerInterface;

/**
 * Transients-backed read-through cache for query results.
 */
class QueryCache {

	const TTL             = HOUR_IN_SECONDS;
	const REGISTRY_PREFIX = '_flux_blocks_cache_keys_';

	/** @var LoggerInterface|null */
	private $logger;

	/**
	 * @param LoggerInterface|null $logger Logger instance.
	 */
	public function __construct( ?LoggerInterface $logger = null ) {
		$this->logger = $logger;
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
		$cached = get_transient( $key );

		// Wrapped in array('value' => ...) rather than storing $value
		// directly -- get_transient() returns bare `false` on BOTH "cache
		// miss" and "the cached value legitimately IS false", with no way
		// to tell them apart. Wrapping guarantees a hit is always an array
		// (even when $value itself is false/null/0/''), so `false !==
		// $cached` alone can never misreport a real cache hit as a miss.
		if ( is_array( $cached ) && array_key_exists( 'value', $cached ) ) {
			if ( $this->logger ) {
				$this->logger->log( 'Cache hit for key: ' . $key . ' (postType: ' . $post_type . ')' );
			}
			return $cached['value'];
		}

		if ( $this->logger ) {
			$this->logger->log( 'Cache miss for key: ' . $key . ' (postType: ' . $post_type . '). Re-evaluating query.' );
		}

		$value = $callback();
		set_transient( $key, array( 'value' => $value ), self::TTL );
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
			delete_transient( $key );
		}

		delete_option( $registry_option );
	}
}

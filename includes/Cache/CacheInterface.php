<?php
/**
 * Cache Interface Contract.
 *
 * Defines the contract for all caching drivers (Transients, Redis, etc.).
 *
 * @package FluxBlocks\Cache
 */

namespace FluxBlocks\Cache;

/**
 * Interface CacheInterface
 */
interface CacheInterface {

	/**
	 * Retrieve a cached value by key.
	 *
	 * @param string $key Unique cache key.
	 * @return mixed Cached value or false if missing/expired.
	 */
	public function get( string $key );

	/**
	 * Set a cached value by key with an expiration.
	 *
	 * @param string $key        Unique cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Expiration time in seconds.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value, int $expiration = 3600 ): bool;

	/**
	 * Delete a cached value by key.
	 *
	 * @param string $key Unique cache key.
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key ): bool;
}

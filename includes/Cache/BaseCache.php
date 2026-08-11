<?php
/**
 * Abstract Base Cache Class.
 *
 * Demonstrates Inheritance & Abstract class implementation for Cache drivers.
 *
 * @package FluxBlocks\Cache
 */

namespace FluxBlocks\Cache;

/**
 * Class BaseCache
 */
abstract class BaseCache implements CacheInterface {

	/**
	 * Retrieve a cached value by key.
	 *
	 * @param string $key Unique cache key.
	 * @return mixed
	 */
	abstract public function get( string $key );

	/**
	 * Set a cached value by key with an expiration.
	 *
	 * @param string $key        Unique cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Expiration time in seconds.
	 * @return bool
	 */
	abstract public function set( string $key, $value, int $expiration = 3600 ): bool;

	/**
	 * Delete a cached value by key.
	 *
	 * @param string $key Unique cache key.
	 * @return bool
	 */
	abstract public function delete( string $key ): bool;
}

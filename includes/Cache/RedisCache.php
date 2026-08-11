<?php
/**
 * Redis / Object Cache Driver Class.
 *
 * Implements WordPress Object Cache API (Redis/Memcached).
 *
 * @package FluxBlocks\Cache
 */

namespace FluxBlocks\Cache;

/**
 * Class RedisCache
 */
class RedisCache extends BaseCache {

	/** @var string Cache group name */
	private $group = 'flux_blocks';

	/**
	 * Retrieve value from WP Object Cache (Redis).
	 *
	 * @param string $key Cache key.
	 * @return mixed
	 */
	public function get( string $key ) {
		return wp_cache_get( $key, $this->group );
	}

	/**
	 * Save value in WP Object Cache (Redis).
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to store.
	 * @param int    $expiration Expiration in seconds.
	 * @return bool
	 */
	public function set( string $key, $value, int $expiration = 3600 ): bool {
		return wp_cache_set( $key, $value, $this->group, $expiration );
	}

	/**
	 * Delete value from WP Object Cache (Redis).
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return wp_cache_delete( $key, $this->group );
	}
}

<?php
/**
 * Transient Cache Driver Class.
 *
 * Implements WordPress Transients API caching.
 *
 * @package FluxBlocks\Cache
 */

namespace FluxBlocks\Cache;

/**
 * Class TransientCache
 */
class TransientCache extends BaseCache {

	/**
	 * Retrieve value from WP Transient.
	 *
	 * @param string $key Cache key.
	 * @return mixed
	 */
	public function get( string $key ) {
		return get_transient( $key );
	}

	/**
	 * Save value in WP Transient.
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to store.
	 * @param int    $expiration Expiration in seconds.
	 * @return bool
	 */
	public function set( string $key, $value, int $expiration = 3600 ): bool {
		return set_transient( $key, $value, $expiration );
	}

	/**
	 * Delete WP Transient.
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return delete_transient( $key );
	}
}

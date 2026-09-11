<?php
/**
 * Contract for a read-through cache keyed by post type + query signature.
 * Lets both Renderers depend on this abstraction instead of the concrete
 * QueryCache class.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Read-through cache contract used by both blocks' Renderers.
 */
interface CacheInterface {

	/**
	 * Get a cached value, or compute + cache it via the callback.
	 *
	 * @param string   $post_type Post type this query is scoped to (for the invalidation registry).
	 * @param string   $signature Unique signature for this specific query.
	 * @param callable $callback  Produces the value to cache on a miss.
	 * @return mixed
	 */
	public function remember( string $post_type, string $signature, callable $callback );

	/**
	 * Clear every cached query for a post type.
	 *
	 * @param string $post_type Post type slug.
	 */
	public function forget_for_post_type( string $post_type ): void;
}

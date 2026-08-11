<?php
/**
 * Hooks post write events and clears the query cache for the affected
 * post type.
 *
 * Why: cached query results (QueryCache) go stale the moment a post of
 * that type is created, edited, deleted, or changes status — this is what
 * keeps the cache correct instead of just fast.
 * Impact of changing: removing/narrowing these hooks risks blocks showing
 * stale content after edits; widening them (e.g. to every post type on
 * every save) makes the cache effectively useless.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Query;

/**
 * Clears cached query results when a post changes.
 */
class CacheInvalidator {

	/** @var QueryCache */
	private $cache;

	/**
	 * @param QueryCache $cache Cache instance to invalidate against.
	 */
	public function __construct( QueryCache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Register the WordPress hooks.
	 */
	public function init_hooks(): void {
		add_action( 'save_post', array( $this, 'handle_save' ) );
		add_action( 'before_delete_post', array( $this, 'handle_delete' ) );
		add_action( 'transition_post_status', array( $this, 'handle_status_transition' ), 10, 3 );
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public function handle_save( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post ) {
			$this->cache->forget_for_post_type( $post->post_type );
		}
	}

	/**
	 * Handles the `before_delete_post` hook, which fires while the post row
	 * still exists -- the `deleted_post` hook fires after, by which point
	 * get_post() here would already return null.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_delete( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post ) {
			$this->cache->forget_for_post_type( $post->post_type );
		}
	}

	/**
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 */
	public function handle_status_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( $new_status !== $old_status ) {
			$this->cache->forget_for_post_type( $post->post_type );
		}
	}
}

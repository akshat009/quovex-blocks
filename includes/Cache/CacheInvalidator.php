<?php
/**
 * Clears the query cache when a post is saved/deleted/status-changed, or
 * a term is added/renamed/deleted (FacetRenderer caches term lists through
 * the same QueryCache, so term changes need invalidating too).
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Clears cached query results when a post changes.
 */
class CacheInvalidator {

	/** @var QueryCache */
	private $cache;

	/**
	 * Post types already cleared during this request -- see forget()'s
	 * docblock for why de-duping matters.
	 *
	 * @var array<string, true>
	 */
	private $cleared_this_request = array();

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
		add_action( 'created_term', array( $this, 'handle_term_change' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'handle_term_change' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'handle_term_change' ), 10, 3 );
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public function handle_save( int $post_id ): void {
		if ( $this->should_skip( $post_id ) ) {
			return;
		}
		$post = get_post( $post_id );
		if ( $post ) {
			$this->forget( $post->post_type );
		}
	}

	/**
	 * Hooked to `before_delete_post` (not `deleted_post`) -- the post row
	 * still exists here, so get_post() can resolve its post_type.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_delete( int $post_id ): void {
		if ( $this->should_skip( $post_id ) ) {
			return;
		}
		$post = get_post( $post_id );
		if ( $post ) {
			$this->forget( $post->post_type );
		}
	}

	/**
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 */
	public function handle_status_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( $new_status !== $old_status && ! $this->should_skip( $post->ID ) ) {
			$this->forget( $post->post_type );
		}
	}

	/**
	 * Clears every post type the changed taxonomy is registered against.
	 *
	 * @param int    $term_id  Unused -- the whole taxonomy is cleared, not just this term.
	 * @param int    $tt_id    Unused, same reason.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function handle_term_change( int $term_id, int $tt_id, string $taxonomy ): void {
		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_object ) {
			return;
		}
		foreach ( (array) $taxonomy_object->object_type as $post_type ) {
			$this->forget( $post_type );
		}
	}

	/**
	 * Skips autosaves and revisions -- both fire the same save/delete
	 * hooks as a real save but would otherwise waste an invalidation on
	 * post_type 'revision', which nothing queries.
	 *
	 * @param int $post_id Post ID.
	 */
	private function should_skip( int $post_id ): bool {
		return wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id );
	}

	/**
	 * Clears a post type's cache, de-duped per request -- `save_post` and
	 * `transition_post_status` both fire for one ordinary status change,
	 * but `transition_post_status` alone also covers WP-Cron publishing a
	 * scheduled post (which skips `save_post` entirely), so neither hook
	 * can just be dropped.
	 *
	 * @param string $post_type Post type slug.
	 */
	private function forget( string $post_type ): void {
		if ( isset( $this->cleared_this_request[ $post_type ] ) ) {
			return;
		}
		$this->cleared_this_request[ $post_type ] = true;

		$this->cache->forget_for_post_type( $post_type );
	}
}

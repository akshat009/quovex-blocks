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

namespace FluxBlocks\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Logger\LoggerInterface;

/**
 * Clears cached query results when a post changes.
 */
class CacheInvalidator {

	/** @var QueryCache */
	private $cache;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * Post types already cleared during THIS request -- both `save_post`
	 * AND `transition_post_status` legitimately fire for one status-
	 * changing save (see forget()'s docblock for why we keep both hooks
	 * rather than dropping one). This is a plain instance property, not
	 * static -- CacheInvalidator itself is only ever constructed once per
	 * request (Plugin::boot() runs once, on 'init'), so it naturally
	 * starts empty every request without needing to reset it manually.
	 *
	 * @var array<string, true>
	 */
	private $cleared_this_request = array();

	/**
	 * @param QueryCache      $cache  Cache instance to invalidate against.
	 * @param LoggerInterface $logger Where invalidation events get logged (see Services::logger()).
	 */
	public function __construct( QueryCache $cache, LoggerInterface $logger ) {
		$this->cache  = $cache;
		$this->logger = $logger;
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
		if ( $this->should_skip( $post_id ) ) {
			return;
		}
		$post = get_post( $post_id );
		if ( $post ) {
			$this->forget( $post->post_type, 'save_post' );
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
		if ( $this->should_skip( $post_id ) ) {
			return;
		}
		$post = get_post( $post_id );
		if ( $post ) {
			$this->forget( $post->post_type, 'before_delete_post' );
		}
	}

	/**
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 */
	public function handle_status_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( $new_status !== $old_status && ! $this->should_skip( $post->ID ) ) {
			$this->forget( $post->post_type, 'transition_post_status' );
		}
	}

	/**
	 * Autosaves and revisions fire the same hooks as a real save (with
	 * post_type 'revision', which Query Grid never queries) -- an editor
	 * drafting a new post autosaves every ~10-60s, and every normal save
	 * creates a revision row, so without this guard a single "click
	 * Publish" clears the cache for a post type TWICE more than needed via
	 * a completely wasted 'revision' post-type invalidation. See audit
	 * notes (session log) for the measured hook-fire counts.
	 *
	 * @param int $post_id Post ID.
	 */
	private function should_skip( int $post_id ): bool {
		return wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id );
	}

	/**
	 * Clears a post type's cache and logs why -- shared by all three
	 * handlers above so the log line only needs writing once.
	 *
	 * Why de-duped per request: `save_post` and `transition_post_status`
	 * both legitimately fire for one ordinary status-changing save (e.g.
	 * clicking Publish) -- clearing the SAME post type's cache twice in
	 * one request is pure waste, the second clear finds nothing new.
	 * `transition_post_status` still can't just be dropped to avoid this:
	 * a scheduled post going live via WP-Cron calls wp_publish_post()
	 * directly, which fires `transition_post_status` WITHOUT `save_post`
	 * -- dropping it would leave that case's cache stale until TTL expiry.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $trigger   Which WordPress hook caused this (for the log line).
	 */
	private function forget( string $post_type, string $trigger ): void {
		if ( isset( $this->cleared_this_request[ $post_type ] ) ) {
			return;
		}
		$this->cleared_this_request[ $post_type ] = true;

		$this->cache->forget_for_post_type( $post_type );
		$this->logger->log(
			'Query cache cleared for post type.',
			array(
				'post_type' => $post_type,
				'trigger'   => $trigger,
			)
		);
	}
}

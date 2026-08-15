<?php
/**
 * @package FluxBlocks\Tests
 */

namespace FluxBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use FluxBlocks\Cache\CacheInvalidator;
use FluxBlocks\Cache\QueryCache;
use FluxBlocks\Tests\TestCase;
use Mockery;
use WP_Post;

/**
 * @covers \FluxBlocks\Cache\CacheInvalidator
 */
class CacheInvalidatorTest extends TestCase {

	/**
	 * @param bool $is_autosave
	 * @param bool $is_revision
	 * @return array{0: CacheInvalidator, 1: QueryCache&\Mockery\MockInterface}
	 */
	private function make_invalidator( bool $is_autosave = false, bool $is_revision = false ): array {
		Functions\when( 'wp_is_post_autosave' )->justReturn( $is_autosave );
		Functions\when( 'wp_is_post_revision' )->justReturn( $is_revision );

		$cache = Mockery::mock( QueryCache::class );

		return array( new CacheInvalidator( $cache ), $cache );
	}

	public function test_handle_save_clears_the_cache_for_a_normal_post(): void {
		[ $invalidator, $cache ] = $this->make_invalidator();
		Functions\when( 'get_post' )->justReturn( new WP_Post( array( 'post_type' => 'post' ) ) );

		$cache->shouldReceive( 'forget_for_post_type' )->once()->with( 'post' );

		$invalidator->handle_save( 42 );
	}

	/**
	 * Regression test for the audit fix: without the autosave/revision
	 * guard, every autosave while drafting a new post (every ~10-60s)
	 * cleared the whole post type's cache for no reason.
	 */
	public function test_handle_save_does_nothing_for_an_autosave(): void {
		[ $invalidator, $cache ] = $this->make_invalidator( true, false );
		Functions\when( 'get_post' )->justReturn( new WP_Post( array( 'post_type' => 'post' ) ) );

		$cache->shouldNotReceive( 'forget_for_post_type' );

		$invalidator->handle_save( 42 );
	}

	/**
	 * Regression test for the same fix: every ordinary save also creates a
	 * revision row, which used to trigger a second, wasted invalidation
	 * for post_type 'revision' (a post type nothing ever queries).
	 */
	public function test_handle_save_does_nothing_for_a_revision(): void {
		[ $invalidator, $cache ] = $this->make_invalidator( false, true );
		Functions\when( 'get_post' )->justReturn( new WP_Post( array( 'post_type' => 'revision' ) ) );

		$cache->shouldNotReceive( 'forget_for_post_type' );

		$invalidator->handle_save( 42 );
	}

	public function test_handle_delete_clears_the_cache_for_a_normal_post(): void {
		[ $invalidator, $cache ] = $this->make_invalidator();
		Functions\when( 'get_post' )->justReturn( new WP_Post( array( 'post_type' => 'page' ) ) );

		$cache->shouldReceive( 'forget_for_post_type' )->once()->with( 'page' );

		$invalidator->handle_delete( 7 );
	}

	public function test_handle_status_transition_clears_the_cache_when_status_actually_changes(): void {
		[ $invalidator, $cache ] = $this->make_invalidator();
		$post = new WP_Post( array( 'ID' => 5, 'post_type' => 'post' ) );

		$cache->shouldReceive( 'forget_for_post_type' )->once()->with( 'post' );

		$invalidator->handle_status_transition( 'publish', 'draft', $post );
	}

	public function test_handle_status_transition_does_nothing_when_status_is_unchanged(): void {
		[ $invalidator, $cache ] = $this->make_invalidator();
		$post = new WP_Post( array( 'ID' => 5, 'post_type' => 'post' ) );

		$cache->shouldNotReceive( 'forget_for_post_type' );

		$invalidator->handle_status_transition( 'publish', 'publish', $post );
	}

	/**
	 * Regression test for the request-scoped dedup fix: `save_post` AND
	 * `transition_post_status` both legitimately fire for one ordinary
	 * status-changing save -- the SAME post type's cache must only be
	 * cleared (and logged) once per request, not twice.
	 */
	public function test_save_post_and_transition_post_status_for_the_same_post_type_only_clear_once(): void {
		[ $invalidator, $cache ] = $this->make_invalidator();
		$post = new WP_Post( array( 'ID' => 9, 'post_type' => 'post' ) );
		Functions\when( 'get_post' )->justReturn( $post );

		$cache->shouldReceive( 'forget_for_post_type' )->once()->with( 'post' );

		$invalidator->handle_status_transition( 'publish', 'draft', $post );
		$invalidator->handle_save( 9 );
	}

	public function test_a_different_post_type_in_the_same_request_still_clears_independently(): void {
		[ $invalidator, $cache ] = $this->make_invalidator();
		$post_call_count = 0;
		Functions\when( 'get_post' )->alias(
			function () use ( &$post_call_count ) {
				++$post_call_count;
				return new WP_Post( array( 'post_type' => 1 === $post_call_count ? 'post' : 'page' ) );
			}
		);

		$cache->shouldReceive( 'forget_for_post_type' )->once()->with( 'post' );
		$cache->shouldReceive( 'forget_for_post_type' )->once()->with( 'page' );

		$invalidator->handle_save( 1 ); // post_type 'post'
		$invalidator->handle_save( 2 ); // post_type 'page'
	}

	/**
	 * Regression test for the audit fix: term add/rename/delete used to
	 * fire no invalidation hooks at all, so FacetRenderer's cached facet
	 * term lists stayed stale until TTL after e.g. a category rename.
	 */
	public function test_handle_term_change_clears_the_cache_for_every_post_type_the_taxonomy_is_registered_against(): void {
		[ $invalidator, $cache ] = $this->make_invalidator();
		Functions\when( 'get_taxonomy' )->justReturn(
			(object) array( 'object_type' => array( 'post', 'page' ) )
		);

		$cache->shouldReceive( 'forget_for_post_type' )->once()->with( 'post' );
		$cache->shouldReceive( 'forget_for_post_type' )->once()->with( 'page' );

		$invalidator->handle_term_change( 3, 3, 'category' );
	}

	public function test_handle_term_change_does_nothing_for_an_unregistered_taxonomy(): void {
		[ $invalidator, $cache ] = $this->make_invalidator();
		Functions\when( 'get_taxonomy' )->justReturn( false );

		$cache->shouldNotReceive( 'forget_for_post_type' );

		$invalidator->handle_term_change( 3, 3, 'not-a-real-taxonomy' );
	}
}

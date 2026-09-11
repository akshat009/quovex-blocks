<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Cache\QueryCache;
use QuovexBlocks\Tests\TestCase;

/**
 * @covers \QuovexBlocks\Cache\QueryCache
 */
class QueryCacheTest extends TestCase {

	/** @var QueryCache */
	private $cache;

	/** @var array In-memory fake standing in for the wp_options table. */
	private $options;

	/** @var array In-memory fake standing in for transients. */
	private $transients;

	protected function setUp(): void {
		parent::setUp();
		$this->cache      = new QueryCache();
		$this->options    = array();
		$this->transients = array();

		// Fakes real enough for QueryCache to round-trip through -- same
		// idea as testing against SQLite instead of mocking every $wpdb
		// call individually.
		Functions\when( 'get_transient' )->alias(
			fn( $key ) => $this->transients[ $key ] ?? false
		);
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value ) {
				$this->transients[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_transient' )->alias(
			function ( $key ) {
				unset( $this->transients[ $key ] );
				return true;
			}
		);
		Functions\when( 'get_option' )->alias(
			fn( $name, $default = false ) => $this->options[ $name ] ?? $default
		);
		Functions\when( 'update_option' )->alias(
			function ( $name, $value ) {
				$this->options[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_option' )->alias(
			function ( $name ) {
				unset( $this->options[ $name ] );
				return true;
			}
		);
	}

	public function test_remember_runs_the_callback_on_a_cache_miss(): void {
		$calls = 0;
		$value = $this->cache->remember(
			'post',
			'sig-a',
			function () use ( &$calls ) {
				++$calls;
				return array( 'items' => array( 1, 2, 3 ) );
			}
		);

		$this->assertSame( array( 'items' => array( 1, 2, 3 ) ), $value );
		$this->assertSame( 1, $calls );
	}

	public function test_remember_does_not_rerun_the_callback_on_a_cache_hit(): void {
		$calls    = 0;
		$callback = function () use ( &$calls ) {
			++$calls;
			return 'computed-value';
		};

		$this->cache->remember( 'post', 'sig-b', $callback );
		$second = $this->cache->remember( 'post', 'sig-b', $callback );

		$this->assertSame( 1, $calls, 'A cache hit must not re-run the callback.' );
		$this->assertSame( 'computed-value', $second );
	}

	/**
	 * Regression test for the audit fix: get_transient() returns bare
	 * `false` for BOTH "cache miss" and "the cached value legitimately IS
	 * false" -- before wrapping cached values in array('value' => ...),
	 * remember() could never actually cache a `false` result; the
	 * callback would re-run on every single call.
	 */
	public function test_a_callback_returning_false_is_still_correctly_cached(): void {
		$calls    = 0;
		$callback = function () use ( &$calls ) {
			++$calls;
			return false;
		};

		$first  = $this->cache->remember( 'post', 'sig-false', $callback );
		$second = $this->cache->remember( 'post', 'sig-false', $callback );

		$this->assertFalse( $first );
		$this->assertFalse( $second );
		$this->assertSame( 1, $calls, 'A callback returning false must still only run once (the false-value caching regression).' );
	}

	public function test_forget_for_post_type_clears_every_key_registered_for_that_post_type(): void {
		$this->cache->remember( 'post', 'sig-c', fn() => 'value-c' );
		$this->cache->remember( 'post', 'sig-d', fn() => 'value-d' );

		$this->assertCount( 2, $this->transients, 'Sanity check: both entries should be cached before forgetting.' );

		$this->cache->forget_for_post_type( 'post' );

		$this->assertCount( 0, $this->transients, 'forget_for_post_type() should have cleared every transient it registered.' );
	}

	public function test_forget_for_post_type_does_not_touch_a_different_post_types_cache(): void {
		$this->cache->remember( 'post', 'sig-e', fn() => 'post-value' );
		$this->cache->remember( 'page', 'sig-f', fn() => 'page-value' );

		$this->cache->forget_for_post_type( 'post' );

		$calls = 0;
		$value = $this->cache->remember(
			'page',
			'sig-f',
			function () use ( &$calls ) {
				++$calls;
				return 'page-value';
			}
		);

		$this->assertSame( 0, $calls, "Clearing 'post' must not have evicted 'page' entries." );
		$this->assertSame( 'page-value', $value );
	}

	public function test_the_same_key_is_not_registered_twice_in_the_invalidation_registry(): void {
		// Same post_type + same underlying key computed twice (e.g. two
		// identical requests) should not grow the registry option
		// unboundedly.
		$this->cache->remember( 'post', 'sig-g', fn() => 'v1' );
		$this->cache->remember( 'post', 'sig-g', fn() => 'v1' ); // cache hit, but even so...

		$registry = $this->options['_quovex_blocks_cache_keys_post'];
		$this->assertCount( 1, $registry );
	}

	/**
	 * Regression test for the audit fix: the registry option used to grow
	 * without bound -- every unique query signature added one more entry,
	 * forever. Now it's capped at QueryCache::MAX_REGISTRY_SIZE, dropping
	 * the oldest entries first.
	 */
	public function test_the_invalidation_registry_is_capped_and_drops_the_oldest_entries_first(): void {
		$cap = QueryCache::MAX_REGISTRY_SIZE;

		// One more unique signature than the cap allows.
		for ( $i = 0; $i <= $cap; $i++ ) {
			$this->cache->remember( 'post', "sig-cap-$i", fn() => "v$i" );
		}

		$registry = $this->options['_quovex_blocks_cache_keys_post'];
		$this->assertCount( $cap, $registry, 'Registry must never exceed MAX_REGISTRY_SIZE.' );

		$first_key = 'qv_q_' . md5( 'sig-cap-0' );
		$this->assertNotContains( $first_key, $registry, 'The OLDEST entry must be the one dropped once over the cap.' );

		$last_key = 'qv_q_' . md5( "sig-cap-$cap" );
		$this->assertContains( $last_key, $registry, 'The newest entry must still be present.' );
	}
}

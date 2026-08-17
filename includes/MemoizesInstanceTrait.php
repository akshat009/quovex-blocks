<?php
/**
 * Shared "compute once, remember it" helper for classes with only static
 * factory methods (e.g. Services, a `final class`, so there's no instance
 * to inherit through the way Renderers inherit capture() from
 * AbstractRenderer -- a trait can still hand down real method code here).
 *
 * @package FluxBlocks
 */

namespace FluxBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gives a class a self::once() helper that memoizes a value per key.
 */
trait MemoizesInstanceTrait {

	/** @var array Memoized values, keyed by whatever caller passes as $key. */
	private static $instances = array();

	/**
	 * Runs $factory once per $key, returning the cached value on every
	 * later call -- what makes Services::query_cache() etc. singletons.
	 *
	 * @param string   $key     Unique key for this memoized value (callers pass __METHOD__).
	 * @param callable $factory Produces the value on a cache miss.
	 * @return mixed
	 */
	protected static function once( string $key, callable $factory ) {
		// array_key_exists(), not isset() -- isset() misses a stored NULL
		// value, which would re-run a factory that legally returns null.
		if ( ! array_key_exists( $key, self::$instances ) ) {
			self::$instances[ $key ] = $factory();
		}
		return self::$instances[ $key ];
	}

	/**
	 * Forgets every memoized value (or just one, by key) -- needed for
	 * PHPUnit, which runs every test in the same process, so without this
	 * the first test to call e.g. Services::query_cache() would pin its
	 * return value for every later test too. Each class `use`-ing this
	 * trait gets its own copy of self::$instances.
	 *
	 * @param string|null $key Specific memoized key to forget, or null to forget all.
	 */
	public static function reset( ?string $key = null ): void {
		if ( null === $key ) {
			self::$instances = array();
			return;
		}
		unset( self::$instances[ $key ] );
	}
}

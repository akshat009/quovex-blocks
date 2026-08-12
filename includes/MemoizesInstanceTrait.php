<?php
/**
 * Shared "compute once, remember it" helper for classes with only static
 * factory methods.
 *
 * Why a TRAIT and not another abstract class: Services is a `final class`
 * with only `public static` methods -- there's no object to inherit
 * anything through the way QueryGrid\Renderer inherits capture() from
 * AbstractRenderer (see that file). Every one of Services' factory methods
 * was repeating the same "static $instance = null; if ( null === $instance )
 * {...}" boilerplate -- a trait can hand down actual, reusable method CODE
 * (unlike an interface, which could only demand the method exist, never
 * supply this implementation).
 * Impact of changing: every Services factory method depends on once()
 * behaving correctly -- a bug here would make every "singleton" in
 * Services.php stop being one (a new instance on every call instead of one
 * shared instance).
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
	 * Runs $factory the FIRST time a given $key is asked for, then returns
	 * that same cached value on every later call with the same $key --
	 * this is what makes Services::query_cache() etc. singletons.
	 *
	 * @param string   $key     Unique key for this memoized value (Services passes __METHOD__).
	 * @param callable $factory Produces the value on a cache miss.
	 * @return mixed
	 */
	protected static function once( string $key, callable $factory ) {
		// array_key_exists(), NOT isset() -- isset() returns false for a
		// stored NULL value, which would make a factory that ever legally
		// returns null re-run on every single call instead of memoizing
		// (no current Services.php factory does this, but the trait itself
		// shouldn't assume that of every future caller).
		if ( ! array_key_exists( $key, self::$instances ) ) {
			self::$instances[ $key ] = $factory();
		}
		return self::$instances[ $key ];
	}
}

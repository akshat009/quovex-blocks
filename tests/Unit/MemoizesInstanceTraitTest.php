<?php
/**
 * @package FluxBlocks\Tests
 */

namespace FluxBlocks\Tests\Unit;

use FluxBlocks\MemoizesInstanceTrait;
use FluxBlocks\Tests\TestCase;

/**
 * Tiny fixture class -- `once()` is `protected`, so a real (if trivial)
 * class using the trait is needed to call it, same as Services.php does.
 */
class MemoizesInstanceTraitFixture {
	use MemoizesInstanceTrait;

	public static $factory_call_count = 0;

	public static function reset(): void {
		self::$instances          = array();
		self::$factory_call_count = 0;
	}

	public static function get( string $key, callable $factory ) {
		return self::once( $key, $factory );
	}
}

/**
 * Second, plainer fixture -- deliberately does NOT override reset() (unlike
 * MemoizesInstanceTraitFixture above, whose own reset() would shadow the
 * trait's and defeat these specific tests) -- so calling ::reset() here
 * exercises MemoizesInstanceTrait's OWN public reset() method.
 */
class MemoizesInstanceTraitResetFixture {
	use MemoizesInstanceTrait;

	public static function get( string $key, callable $factory ) {
		return self::once( $key, $factory );
	}
}

/**
 * @covers \FluxBlocks\MemoizesInstanceTrait
 */
class MemoizesInstanceTraitTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		MemoizesInstanceTraitFixture::reset();
		// MemoizesInstanceTraitResetFixture deliberately has no reset() of
		// its own (see its docblock) -- this calls the TRAIT's public
		// reset() instead, both for test isolation AND as a sanity check
		// that reset() actually works (a no-op reset() would leave state
		// leaking between tests and fail them, same as no reset at all).
		MemoizesInstanceTraitResetFixture::reset();
	}

	public function test_factory_runs_on_first_call(): void {
		$calls = 0;
		$value = MemoizesInstanceTraitFixture::get(
			'key-a',
			function () use ( &$calls ) {
				++$calls;
				return 'first-value';
			}
		);

		$this->assertSame( 'first-value', $value );
		$this->assertSame( 1, $calls );
	}

	public function test_factory_does_not_rerun_on_second_call_with_same_key(): void {
		$calls   = 0;
		$factory = function () use ( &$calls ) {
			++$calls;
			return 'value';
		};

		MemoizesInstanceTraitFixture::get( 'key-b', $factory );
		MemoizesInstanceTraitFixture::get( 'key-b', $factory );
		MemoizesInstanceTraitFixture::get( 'key-b', $factory );

		$this->assertSame( 1, $calls, 'Factory should only run once no matter how many times the same key is requested.' );
	}

	public function test_different_keys_are_memoized_independently(): void {
		$value_a = MemoizesInstanceTraitFixture::get( 'key-c', fn() => 'value-c' );
		$value_b = MemoizesInstanceTraitFixture::get( 'key-d', fn() => 'value-d' );

		$this->assertSame( 'value-c', $value_a );
		$this->assertSame( 'value-d', $value_b );
	}

	/**
	 * Regression test for the audit fix: once() used to check
	 * `isset( self::$instances[$key] )`, which is FALSE for a stored NULL
	 * value -- meaning a factory that legitimately returns null would
	 * re-run on every single call instead of being memoized. The fix
	 * switched to array_key_exists(), which this test locks in.
	 */
	public function test_a_null_return_value_is_still_memoized(): void {
		$calls   = 0;
		$factory = function () use ( &$calls ) {
			++$calls;
			return null;
		};

		$first  = MemoizesInstanceTraitFixture::get( 'key-null', $factory );
		$second = MemoizesInstanceTraitFixture::get( 'key-null', $factory );

		$this->assertNull( $first );
		$this->assertNull( $second );
		$this->assertSame( 1, $calls, 'A factory returning null must still only run once (isset() vs array_key_exists() regression).' );
	}

	/**
	 * Regression coverage for the audit fix: memoized state previously had
	 * no way to be cleared, which is harmless in production (a fresh
	 * per-request PHP process starts empty anyway) but meant the FIRST
	 * PHPUnit test to memoize a key would silently pin it for every later
	 * test in the same process, regardless of what that later test itself
	 * configures.
	 */
	public function test_reset_with_no_key_forgets_every_memoized_value(): void {
		$calls = 0;
		$again = function () use ( &$calls ) {
			++$calls;
			return 'value';
		};

		MemoizesInstanceTraitResetFixture::get( 'key-x', $again );
		MemoizesInstanceTraitResetFixture::get( 'key-y', $again );
		MemoizesInstanceTraitResetFixture::reset();
		MemoizesInstanceTraitResetFixture::get( 'key-x', $again );
		MemoizesInstanceTraitResetFixture::get( 'key-y', $again );

		$this->assertSame( 4, $calls, 'reset() with no key must forget every memoized key, forcing both factories to re-run.' );
	}

	public function test_reset_with_a_specific_key_only_forgets_that_key(): void {
		$calls_x = 0;
		$calls_y = 0;

		MemoizesInstanceTraitResetFixture::get(
			'key-x',
			function () use ( &$calls_x ) {
				++$calls_x;
				return 'x';
			}
		);
		MemoizesInstanceTraitResetFixture::get(
			'key-y',
			function () use ( &$calls_y ) {
				++$calls_y;
				return 'y';
			}
		);

		MemoizesInstanceTraitResetFixture::reset( 'key-x' );

		MemoizesInstanceTraitResetFixture::get(
			'key-x',
			function () use ( &$calls_x ) {
				++$calls_x;
				return 'x';
			}
		);
		MemoizesInstanceTraitResetFixture::get(
			'key-y',
			function () use ( &$calls_y ) {
				++$calls_y;
				return 'y';
			}
		);

		$this->assertSame( 2, $calls_x, 'key-x was reset, so its factory must run again.' );
		$this->assertSame( 1, $calls_y, 'key-y was never reset, so its factory must NOT run again.' );
	}
}

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
 * @covers \FluxBlocks\MemoizesInstanceTrait
 */
class MemoizesInstanceTraitTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		MemoizesInstanceTraitFixture::reset();
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
}

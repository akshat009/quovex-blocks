<?php
/**
 * Shared base class for every test in this suite.
 *
 * Why: Brain Monkey needs setUp()/tearDown() wired around EVERY test (it
 * resets WordPress function mocks between tests so one test's
 * Functions::when()/expect() calls can never leak into the next) -- one
 * shared base class means every test file gets this for free instead of
 * repeating the same two calls everywhere.
 *
 * @package FluxBlocks\Tests
 */

namespace FluxBlocks\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case: wires Brain Monkey's WordPress function mocking in/out
 * of every test automatically. MockeryPHPUnitIntegration (bundled with
 * Mockery, which Brain Monkey itself depends on) does the equivalent for
 * Mockery::mock() -- without it, a test whose only checks are
 * $mock->shouldReceive(...)->once() calls gets flagged "risky: this test
 * did not perform any assertions", since PHPUnit's own assertion counter
 * has no way to know Mockery verified anything.
 */
abstract class TestCase extends PHPUnitTestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}

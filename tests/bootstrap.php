<?php
/**
 * PHPUnit bootstrap.
 *
 * Why: just the Composer autoloader -- it already knows how to resolve
 * both `QuovexBlocks\...` (includes/, from composer.json's "autoload") and
 * `QuovexBlocks\Tests\...` (tests/, from "autoload-dev"), so no manual
 * requires are needed here. No WordPress core is loaded at all; see
 * tests/TestCase.php for how individual WP functions get mocked instead.
 *
 * Why ABSPATH is defined here: every includes/ file guards itself with
 * `if ( ! defined( 'ABSPATH' ) ) { exit; }` (a WordPress.org security
 * convention -- blocks direct URL access to the file on a real site).
 * That guard has no way to tell "a real unauthorized web request" apart
 * from "PHPUnit autoloading this class" -- WITHOUT this define, the very
 * first `use QuovexBlocks\...;` in any test would trigger that exit and
 * kill the whole test run. Defining a dummy value here (this is not a
 * real WordPress install, so the exact string doesn't matter) is the
 * standard way plugins reconcile "guard against direct access" with
 * "still be unit-testable" -- it does not weaken the guard's real
 * purpose, since a real unauthorized request still never defines it.
 *
 * @package QuovexBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/stubs.php';

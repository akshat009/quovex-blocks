<?php
/**
 * Contract every logger implementation must follow.
 *
 * Why an INTERFACE here: this plugin needs to log a message from contexts
 * that don't care HOW the message ends up logged -- while WP_DEBUG is on
 * (write it somewhere useful) and while it's off (do nothing, but callers
 * shouldn't have to check WP_DEBUG themselves every time they want to log
 * something). Two genuinely different implementations, FileLogger and
 * NullLogger, share this one contract -- see Services::logger(), which
 * decides which one every caller actually gets.
 * Impact of changing: any new logger implementation (e.g. a future
 * DatabaseLogger) must implement this exact signature.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Contract for anything that can log a message.
 */
interface LoggerInterface {

	/**
	 * @param string $message Human-readable log message.
	 * @param array  $context Optional structured data to log alongside the message.
	 */
	public function log( string $message, array $context = array() ): void;
}

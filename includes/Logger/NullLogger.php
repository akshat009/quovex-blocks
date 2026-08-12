<?php
/**
 * No-op logger -- the Null Object pattern. Services::logger() hands this
 * out instead of FileLogger when WP_DEBUG is off, so every caller can
 * unconditionally call $this->logger->log(...) without ever checking
 * WP_DEBUG itself.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Does nothing -- used when logging is disabled.
 */
class NullLogger implements LoggerInterface {

	/**
	 * @param string $message Unused -- this implementation intentionally does nothing.
	 * @param array  $context Unused -- this implementation intentionally does nothing.
	 */
	public function log( string $message, array $context = array() ): void {
		// Intentionally empty.
	}
}

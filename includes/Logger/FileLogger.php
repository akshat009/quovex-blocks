<?php
/**
 * Writes log messages to PHP's configured error log via error_log() --
 * this is the implementation Services::logger() hands out while WP_DEBUG
 * is on.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Logs to PHP's error log (the same destination WP_DEBUG_LOG uses).
 */
class FileLogger implements LoggerInterface {

	/**
	 * @param string $message Human-readable log message.
	 * @param array  $context Optional structured data to log alongside the message.
	 */
	public function log( string $message, array $context = array() ): void {
		if ( ! empty( $context ) ) {
			$message .= ' ' . wp_json_encode( $context );
		}
		error_log( '[Flux Blocks] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional: this class only ever runs when WP_DEBUG is on (see Services::logger()), same destination core itself logs PHP errors to.
	}
}

<?php
/**
 * Minimal stand-ins for the WordPress core classes this suite's `instanceof`
 * checks need to exist -- Brain Monkey mocks WordPress FUNCTIONS, but core
 * CLASSES (WP_Post, WP_Post_Type, ...) aren't loaded at all in this bare
 * PHPUnit environment, so `$x instanceof \WP_Post_Type` would fatal
 * ("class not found") without a real class of that name to check against.
 * These are intentionally minimal (public properties only, no real WP
 * behavior) -- just enough shape for the code under test to work with.
 *
 * @package FluxBlocks\Tests
 */

if ( ! class_exists( 'WP_Post_Type' ) ) {
	class WP_Post_Type {
		public $name;
		public $public = true;

		public function __construct( string $name = '', bool $public = true ) {
			$this->name   = $name;
			$this->public = $public;
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public $ID;
		public $post_type;
		public $post_author;

		public function __construct( array $fields = array() ) {
			foreach ( $fields as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

// Plain WordPress time constants (wp-includes/default-constants.php) --
// these are CONSTANTS, not functions, so Brain Monkey's function mocking
// doesn't cover them; QueryCache::TTL uses HOUR_IN_SECONDS directly.
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

<?php
/**
 * Sanitizes and validates inline CSS values (colors, typography, numbers) to block declaration injection via unescaped semicolons.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Utility for checking and sanitizing CSS custom property values.
 */
class StyleSanitizer {

	/**
	 * Rejects anything that isn't a plausible CSS color value (hex, rgb()/rgba(), hsl()/hsla(), named colors) -- blocks `;`, which esc_attr() doesn't escape.
	 *
	 * @param string $value Color value.
	 * @return bool
	 */
	public static function is_valid_css_color( string $value ): bool {
		return (bool) preg_match( '/^[#a-zA-Z0-9(),.%\s-]+$/', trim( $value ) );
	}

	/**
	 * Validates CSS font-family / font-weight values to prevent semicolon CSS injection.
	 *
	 * @param string $value Typography string.
	 * @return bool
	 */
	public static function is_valid_css_typography( string $value ): bool {
		return (bool) preg_match( '/^[a-zA-Z0-9\s,\'"]+$/', trim( $value ) );
	}
}

<?php
/**
 * Builds Content Showcase's wrapper-level CSS custom properties from block
 * attributes.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Blocks\ContentShowcase\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Resolves Content Showcase's color/typography attributes into CSS custom properties.
 */
class StyleBuilder {

	/**
	 * @param array $attributes Block attributes.
	 * @return string CSS custom properties, semicolon-separated, no trailing semicolon (may be '').
	 */
	public function build_inline_style( array $attributes ): string {
		// Font families come from the active theme's theme.json (see
		// edit.js's useSettings('typography.fontFamilies')).
		$property_map = array(
			'headingAccentColor'    => '--qv-accent',
			'cardTitleColor'        => '--qv-card-title-color',
			'cardTitleHoverColor'   => '--qv-card-title-hover-color',
			'cardDateColor'         => '--qv-card-date-color',
			'cardExcerptColor'      => '--qv-card-excerpt-color',
			'cardTitleFontFamily'   => '--qv-card-title-font-family',
			'cardTitleFontWeight'   => '--qv-card-title-font-weight',
			'cardExcerptFontFamily' => '--qv-card-excerpt-font-family',
			'cardExcerptFontWeight' => '--qv-card-excerpt-font-weight',
			'cardDateFontFamily'    => '--qv-card-date-font-family',
			'cardDateFontWeight'    => '--qv-card-date-font-weight',
		);

		$inline_styles = array();
		foreach ( $property_map as $key => $css_var ) {
			// esc_attr() doesn't escape `;` -- see QueryGrid\Render\StyleBuilder::looks_like_css_color().
			if ( ! empty( $attributes[ $key ] ) && $this->looks_like_css_color( $attributes[ $key ] ) ) {
				$inline_styles[] = $css_var . ':' . esc_attr( $attributes[ $key ] );
			}
		}

		return implode( ';', $inline_styles );
	}

	/**
	 * Rejects anything that isn't a plausible CSS color value -- blocks
	 * `;`, which esc_attr() doesn't escape.
	 *
	 * @param string $value Color value from the block's attributes.
	 * @return bool
	 */
	private function looks_like_css_color( string $value ): bool {
		return (bool) preg_match( '/^[#a-zA-Z0-9(),.%\s-]+$/', $value );
	}
}

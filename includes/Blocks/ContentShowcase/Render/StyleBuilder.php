<?php
/**
 * Builds Content Showcase's wrapper-level CSS custom properties from block
 * attributes.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\ContentShowcase\Render;

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
			'headingAccentColor'    => '--fb-accent',
			'cardTitleColor'        => '--fb-card-title-color',
			'cardTitleHoverColor'   => '--fb-card-title-hover-color',
			'cardDateColor'         => '--fb-card-date-color',
			'cardExcerptColor'      => '--fb-card-excerpt-color',
			'cardTitleFontFamily'   => '--fb-card-title-font-family',
			'cardTitleFontWeight'   => '--fb-card-title-font-weight',
			'cardExcerptFontFamily' => '--fb-card-excerpt-font-family',
			'cardExcerptFontWeight' => '--fb-card-excerpt-font-weight',
			'cardDateFontFamily'    => '--fb-card-date-font-family',
			'cardDateFontWeight'    => '--fb-card-date-font-weight',
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

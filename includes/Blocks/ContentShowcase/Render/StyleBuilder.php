<?php
/**
 * Builds Content Showcase's wrapper-level CSS custom properties from block
 * attributes.
 *
 * Why its own class: this was 11 near-identical `if ( ! empty( $attributes[...] ) )`
 * blocks sitting inline inside Renderer::render() -- pure "resolve some
 * attributes into a CSS string" logic with nothing to do with querying,
 * caching, or HTML templating.
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
		// Font-family values come from the active theme's own theme.json
		// (see edit.js's useSettings('typography.fontFamilies')) -- never a
		// hardcoded list this plugin would need to load/enqueue itself.
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
			if ( ! empty( $attributes[ $key ] ) ) {
				$inline_styles[] = $css_var . ':' . esc_attr( $attributes[ $key ] );
			}
		}

		return implode( ';', $inline_styles );
	}
}

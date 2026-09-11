<?php
/**
 * Builds Query Grid's wrapper-level CSS custom properties from resolved
 * columns/colors/typography maps.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Blocks\QueryGrid\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use QuovexBlocks\Utils\StyleSanitizer;

/**
 * Resolves Query Grid's color/typography attributes into CSS custom properties.
 */
class StyleBuilder {

	/**
	 * Attribute key => CSS custom property name, for colors. Single source
	 * of truth -- default_colors() derives its keys from this.
	 *
	 * @var array<string,string>
	 */
	private const COLOR_PROPERTY_MAP = array(
		'titleColor'          => '--qv-title-color',
		'accentColor'         => '--qv-accent-color',
		'subheadingColor'     => '--qv-subheading-color',
		'cardTitleColor'      => '--qv-card-title-color',
		'cardTitleHoverColor' => '--qv-card-title-hover-color',
		'cardExcerptColor'    => '--qv-card-excerpt-color',
		'readMoreColor'       => '--qv-read-more-color',
		'readMoreHoverColor'  => '--qv-read-more-hover-color',
		'activeAccent'        => '--qv-active-accent',
		'disabledNav'         => '--qv-disabled-nav',
		'inactivePillBg'      => '--qv-inactive-pill-bg',
		'inactivePillText'    => '--qv-inactive-pill-text',
		'metaText'            => '--qv-meta-text',
		'authorText'          => '--qv-author-text',
	);

	/**
	 * Attribute key => CSS custom property name, for typography. Font
	 * families come from the active theme's theme.json (see edit.js's
	 * useSettings('typography.fontFamilies')), never a hardcoded list.
	 *
	 * @var array<string,string>
	 */
	private const TYPOGRAPHY_PROPERTY_MAP = array(
		'headingTitleFontFamily'     => '--qv-title-font-family',
		'headingTitleFontWeight'     => '--qv-title-font-weight',
		'cardTitleFontFamily'        => '--qv-card-title-font-family',
		'cardTitleFontWeight'        => '--qv-card-title-font-weight',
		'cardExcerptFontFamily'      => '--qv-card-excerpt-font-family',
		'cardExcerptFontWeight'      => '--qv-card-excerpt-font-weight',
		'metaFontFamily'             => '--qv-meta-font-family',
		'metaFontWeight'             => '--qv-meta-font-weight',
		'filterPaginationFontFamily' => '--qv-filter-pagination-font-family',
		'filterPaginationFontWeight' => '--qv-filter-pagination-font-weight',
	);

	/**
	 * @param array $columns                 Resolved mobile/tablet/desktop column counts.
	 * @param array $colors                  Resolved color map.
	 * @param array $typography              Resolved font-family/font-weight map.
	 * @param int   $carousel_items_per_view Cards visible per carousel page.
	 * @return string CSS custom properties.
	 */
	public function build_inline_style( array $columns, array $colors, array $typography, int $carousel_items_per_view = 3 ): string {
		$style = sprintf(
			'--qv-cols-mobile:%d;--qv-cols-tablet:%d;--qv-cols-desktop:%d;--qv-carousel-items:%d;',
			absint( $columns['mobile'] ),
			absint( $columns['tablet'] ),
			absint( $columns['desktop'] ),
			absint( $carousel_items_per_view )
		);

		foreach ( self::COLOR_PROPERTY_MAP as $key => $css_var ) {
			if ( ! empty( $colors[ $key ] ) && StyleSanitizer::is_valid_css_color( (string) $colors[ $key ] ) ) {
				$style .= sprintf( '%s:%s;', $css_var, esc_attr( $colors[ $key ] ) );
			}
		}

		foreach ( self::TYPOGRAPHY_PROPERTY_MAP as $key => $css_var ) {
			if ( ! empty( $typography[ $key ] ) && StyleSanitizer::is_valid_css_typography( (string) $typography[ $key ] ) ) {
				$style .= sprintf( '%s:%s;', $css_var, esc_attr( $typography[ $key ] ) );
			}
		}

		return $style;
	}

	/**
	 * @return array<string,string> Default empty color map.
	 */
	public function default_colors(): array {
		return array_fill_keys( array_keys( self::COLOR_PROPERTY_MAP ), '' );
	}

	/**
	 * @return array<string,string> Default empty font-family/font-weight map.
	 */
	public function default_typography(): array {
		return array_fill_keys( array_keys( self::TYPOGRAPHY_PROPERTY_MAP ), '' );
	}

	/**
	 * Rejects anything that isn't a plausible CSS color value (hex, rgb()/
	 * rgba(), hsl()/hsla(), named colors) -- blocks `;`, which esc_attr()
	 * doesn't escape.
	 *
	 * @param string $value Color value from the block's `colors` attribute.
	 * @return bool
	 */
	private function looks_like_css_color( string $value ): bool {
		return (bool) preg_match( '/^[#a-zA-Z0-9(),.%\s-]+$/', $value );
	}
}

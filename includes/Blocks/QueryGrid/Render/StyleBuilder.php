<?php
/**
 * Builds Query Grid's wrapper-level CSS custom properties from resolved
 * columns/colors/typography maps.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Utils\StyleSanitizer;

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
		'titleColor'          => '--fb-title-color',
		'accentColor'         => '--fb-accent-color',
		'subheadingColor'     => '--fb-subheading-color',
		'cardTitleColor'      => '--fb-card-title-color',
		'cardTitleHoverColor' => '--fb-card-title-hover-color',
		'cardExcerptColor'    => '--fb-card-excerpt-color',
		'readMoreColor'       => '--fb-read-more-color',
		'readMoreHoverColor'  => '--fb-read-more-hover-color',
		'activeAccent'        => '--fb-active-accent',
		'disabledNav'         => '--fb-disabled-nav',
		'inactivePillBg'      => '--fb-inactive-pill-bg',
		'inactivePillText'    => '--fb-inactive-pill-text',
		'metaText'            => '--fb-meta-text',
		'authorText'          => '--fb-author-text',
	);

	/**
	 * Attribute key => CSS custom property name, for typography. Font
	 * families come from the active theme's theme.json (see edit.js's
	 * useSettings('typography.fontFamilies')), never a hardcoded list.
	 *
	 * @var array<string,string>
	 */
	private const TYPOGRAPHY_PROPERTY_MAP = array(
		'headingTitleFontFamily'     => '--fb-title-font-family',
		'headingTitleFontWeight'     => '--fb-title-font-weight',
		'cardTitleFontFamily'        => '--fb-card-title-font-family',
		'cardTitleFontWeight'        => '--fb-card-title-font-weight',
		'cardExcerptFontFamily'      => '--fb-card-excerpt-font-family',
		'cardExcerptFontWeight'      => '--fb-card-excerpt-font-weight',
		'metaFontFamily'             => '--fb-meta-font-family',
		'metaFontWeight'             => '--fb-meta-font-weight',
		'filterPaginationFontFamily' => '--fb-filter-pagination-font-family',
		'filterPaginationFontWeight' => '--fb-filter-pagination-font-weight',
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
			'--fb-cols-mobile:%d;--fb-cols-tablet:%d;--fb-cols-desktop:%d;--fb-carousel-items:%d;',
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

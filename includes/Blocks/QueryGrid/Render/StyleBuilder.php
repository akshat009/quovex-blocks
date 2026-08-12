<?php
/**
 * Builds Query Grid's wrapper-level CSS custom properties from resolved
 * columns/colors/typography maps.
 *
 * Why its own class: this was three private methods living inside
 * Renderer (build_inline_style(), default_colors(), default_typography())
 * that had nothing to do with querying, caching, or HTML templating --
 * pure "resolve some data into a CSS string" logic, which is exactly the
 * kind of thing that's easy to unit test in isolation once it's not
 * entangled with a 700-line class (see tests/Unit/QueryGrid/StyleBuilderTest.php).
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Resolves Query Grid's color/typography attributes into CSS custom properties.
 */
class StyleBuilder {

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

		$property_map = array(
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

		foreach ( $property_map as $key => $css_var ) {
			if ( ! empty( $colors[ $key ] ) ) {
				$style .= sprintf( '%s:%s;', $css_var, esc_attr( $colors[ $key ] ) );
			}
		}

		// Font-family values come from the active theme's own theme.json
		// (see edit.js's useSettings('typography.fontFamilies')) -- never a
		// hardcoded list the plugin would need to load/enqueue itself, so
		// whatever gets picked here is already available on the frontend.
		$typography_property_map = array(
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

		foreach ( $typography_property_map as $key => $css_var ) {
			if ( ! empty( $typography[ $key ] ) ) {
				$style .= sprintf( '%s:%s;', $css_var, esc_attr( $typography[ $key ] ) );
			}
		}

		return $style;
	}

	/**
	 * @return array<string,string> Default empty color map.
	 */
	public function default_colors(): array {
		return array(
			'titleColor'          => '',
			'accentColor'         => '',
			'subheadingColor'     => '',
			'cardTitleColor'      => '',
			'cardTitleHoverColor' => '',
			'cardExcerptColor'    => '',
			'readMoreColor'       => '',
			'readMoreHoverColor'  => '',
			'activeAccent'        => '',
			'disabledNav'         => '',
			'inactivePillBg'      => '',
			'inactivePillText'    => '',
			'metaText'            => '',
			'authorText'          => '',
		);
	}

	/**
	 * @return array<string,string> Default empty font-family/font-weight map.
	 */
	public function default_typography(): array {
		return array(
			'headingTitleFontFamily'     => '',
			'headingTitleFontWeight'     => '',
			'cardTitleFontFamily'        => '',
			'cardTitleFontWeight'        => '',
			'cardExcerptFontFamily'      => '',
			'cardExcerptFontWeight'      => '',
			'metaFontFamily'             => '',
			'metaFontWeight'             => '',
			'filterPaginationFontFamily' => '',
			'filterPaginationFontWeight' => '',
		);
	}
}

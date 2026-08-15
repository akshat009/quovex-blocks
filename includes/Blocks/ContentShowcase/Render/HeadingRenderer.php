<?php
/**
 * Renders Content Showcase's section heading (with its optional accent
 * highlight span) and the small `style="..."` attribute helper it shares
 * with the subheading paragraph in Renderer::render().
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\ContentShowcase\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renders the section heading + accent span.
 */
class HeadingRenderer {

	/**
	 * Renders the section heading, wrapping $accent (if present) in its own
	 * span -- the accent span only takes a color, inheriting the title's font.
	 *
	 * @param string       $heading Full heading text.
	 * @param string       $accent  Substring of $heading to wrap in an accent span.
	 * @param HeadingStyle $style   Title/accent color + title font.
	 * @return string Escaped HTML.
	 */
	public function render_heading( string $heading, string $accent, HeadingStyle $style ): string {
		$title_style  = $this->build_style_attr(
			array(
				'color'       => $style->title_color,
				'font-family' => $style->title_font_family,
				'font-weight' => $style->title_font_weight,
			)
		);
		$accent_style = $this->build_style_attr( array( 'color' => $style->accent_color ) );

		if ( '' === $accent || false === strpos( $heading, $accent ) ) {
			return sprintf( '<span%s>%s</span>', $title_style, esc_html( $heading ) );
		}

		list( $before, $after ) = explode( $accent, $heading, 2 );

		return sprintf(
			'<span%s>%s</span><span class="fb-content-showcase__heading-accent"%s>%s</span><span%s>%s</span>',
			$title_style,
			esc_html( $before ),
			$accent_style,
			esc_html( $accent ),
			$title_style,
			esc_html( $after )
		);
	}

	/**
	 * Builds an escaped ` style="..."` attribute (leading space included)
	 * from a property => value map, skipping empty values. Returns '' if
	 * all values are empty.
	 *
	 * @param array<string,string> $properties CSS property => value map.
	 * @return string
	 */
	public function build_style_attr( array $properties ): string {
		$declarations = array();
		foreach ( $properties as $property => $value ) {
			if ( ! empty( $value ) ) {
				$declarations[] = $property . ':' . esc_attr( $value );
			}
		}
		return ! empty( $declarations ) ? ' style="' . implode( ';', $declarations ) . '"' : '';
	}
}

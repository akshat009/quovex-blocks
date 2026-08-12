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
	 * Renders section heading with title/accent color + title font support.
	 * The accent span deliberately only takes a color, not its own font --
	 * it is an inline highlight of the same heading text, not a separate
	 * element, so it always inherits the title's font-family/font-weight.
	 *
	 * @param string       $heading Full heading text.
	 * @param string       $accent  Substring of $heading to wrap in an accent span.
	 * @param HeadingStyle $style    Title/accent color + title font.
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
	 * Builds an escaped ` style="..."` attribute (with leading space) from a
	 * property => value map, skipping any empty values. Returns '' if every
	 * value is empty, so callers can echo it directly onto a tag with no
	 * extra empty `style=""` cruft.
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

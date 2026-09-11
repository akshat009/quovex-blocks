<?php
/**
 * Renders Content Showcase's optional "Explore More" button.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Blocks\ContentShowcase\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renders the "Explore More" button.
 */
class ExploreButtonRenderer {

	/**
	 * @param array $attributes Block attributes.
	 * @return string Escaped HTML, '' if the button is off or has no URL.
	 */
	public function render( array $attributes ): string {
		if ( empty( $attributes['showExploreButton'] ) || empty( $attributes['exploreButtonUrl'] ) ) {
			return '';
		}
		$text        = ! empty( $attributes['exploreButtonText'] ) ? $attributes['exploreButtonText'] : __( 'Explore More', 'quovex-blocks' );
		$text_color  = $attributes['exploreButtonTextColor'] ?? '';
		$bg_color    = $attributes['exploreButtonBgColor'] ?? '';
		$hover_bg    = $attributes['exploreButtonHoverBg'] ?? '';
		$font_family = $attributes['exploreButtonFontFamily'] ?? '';
		$font_weight = $attributes['exploreButtonFontWeight'] ?? '';
		$styles      = array();

		if ( ! empty( $text_color ) ) {
			$styles[] = '--qv-btn-color:' . esc_attr( $text_color );
		}
		if ( ! empty( $bg_color ) ) {
			$styles[] = '--qv-btn-bg:' . esc_attr( $bg_color );
		}
		if ( ! empty( $hover_bg ) ) {
			$styles[] = '--qv-btn-hover-bg:' . esc_attr( $hover_bg );
		}
		// Font-family values come from the active theme's own theme.json
		// (see edit.js's useSettings('typography.fontFamilies')) -- never a
		// hardcoded list the plugin would need to load/enqueue itself.
		if ( ! empty( $font_family ) ) {
			$styles[] = '--qv-btn-font-family:' . esc_attr( $font_family );
		}
		if ( ! empty( $font_weight ) ) {
			$styles[] = '--qv-btn-font-weight:' . esc_attr( $font_weight );
		}

		$style_attr = ! empty( $styles ) ? ' style="' . esc_attr( implode( ';', $styles ) ) . '"' : '';
		ob_start();
		?>
		<p class="qv-content-showcase__explore">
			<a class="qv-content-showcase__explore-button" href="<?php echo esc_url( $attributes['exploreButtonUrl'] ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo esc_html( $text ); ?>
			</a>
		</p>
		<?php
		return ob_get_clean();
	}
}

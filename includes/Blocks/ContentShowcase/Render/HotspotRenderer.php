<?php
/**
 * Renders Content Showcase's interactive image hotspot pins.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\ContentShowcase\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Utils\StyleSanitizer;

/**
 * Renders clickable hotspot pins + their popovers for one post's image.
 */
class HotspotRenderer {

	/**
	 * Renders hotspot pins for a specific post index if enableHotspots is on.
	 *
	 * @param int   $post_index Zero-based post index in the layout.
	 * @param array $attributes Block attributes.
	 * @return string Escaped HTML, '' if hotspots are off or none target this post.
	 */
	public function render_hotspots_for_post( int $post_index, array $attributes ): string {
		if ( empty( $attributes['enableHotspots'] ) || empty( $attributes['hotspots'] ) || ! is_array( $attributes['hotspots'] ) ) {
			return '';
		}

		ob_start();

		$raw_pin_color  = (string) ( $attributes['hotspotPinColor'] ?? '#d1372d' );
		$raw_tooltip_bg = (string) ( $attributes['hotspotTooltipBg'] ?? '#141414' );
		$raw_text_color = (string) ( $attributes['hotspotTooltipTextColor'] ?? '#ffffff' );

		$pin_color  = StyleSanitizer::is_valid_css_color( $raw_pin_color ) ? $raw_pin_color : '#d1372d';
		$tooltip_bg = StyleSanitizer::is_valid_css_color( $raw_tooltip_bg ) ? $raw_tooltip_bg : '#141414';
		$text_color = StyleSanitizer::is_valid_css_color( $raw_text_color ) ? $raw_text_color : '#ffffff';
		$pin_size   = ! empty( $attributes['hotspotPinSize'] ) ? (int) $attributes['hotspotPinSize'] : 28;

		foreach ( $attributes['hotspots'] as $hs ) {
			$target_index = (int) ( $hs['postIndex'] ?? 0 );
			if ( $target_index !== $post_index ) {
				continue;
			}

			$x        = max( 0, min( 100, (float) ( $hs['x'] ?? 50 ) ) );
			$y        = max( 0, min( 100, (float) ( $hs['y'] ?? 50 ) ) );
			$title    = ! empty( $hs['title'] ) ? $hs['title'] : '';
			$content  = ! empty( $hs['content'] ) ? $hs['content'] : '';
			$link_url = ! empty( $hs['linkUrl'] ) ? $hs['linkUrl'] : '';

			$style_attr = sprintf(
				'left: %s%%; top: %s%%; --fb-hotspot-pin-color: %s; --fb-hotspot-tooltip-bg: %s; --fb-hotspot-tooltip-color: %s; --fb-hotspot-pin-size: %dpx;',
				esc_attr( $x ),
				esc_attr( $y ),
				esc_attr( $pin_color ),
				esc_attr( $tooltip_bg ),
				esc_attr( $text_color ),
				absint( $pin_size )
			);

			?>
			<div
				class="fb-hotspot"
				style="<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
				data-wp-interactive="flux-blocks/content-showcase"
				<?php echo wp_interactivity_data_wp_context( array( 'isOpen' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
				<button
					type="button"
					class="fb-hotspot__pin"
					data-wp-on--click="actions.toggleHotspot"
					data-wp-bind--aria-expanded="context.isOpen"
					aria-label="<?php echo esc_attr( $title ? $title : __( 'Hotspot details', 'flux-blocks' ) ); ?>"
				>
					<span class="fb-hotspot__pin-pulse"></span>
					<span class="fb-hotspot__pin-icon">+</span>
				</button>

				<div
					class="fb-hotspot__popover"
					data-wp-bind--hidden="!context.isOpen"
				>
					<?php if ( $title ) : ?>
						<h4 class="fb-hotspot__title"><?php echo esc_html( $title ); ?></h4>
					<?php endif; ?>
					<?php if ( $content ) : ?>
						<p class="fb-hotspot__content"><?php echo esc_html( $content ); ?></p>
					<?php endif; ?>
					<?php if ( $link_url ) : ?>
						<a class="fb-hotspot__link" href="<?php echo esc_url( $link_url ); ?>">
							<?php esc_html_e( 'Learn More →', 'flux-blocks' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
		return ob_get_clean();
	}
}

<?php
/**
 * Renders Content Showcase's per-layout card markup -- Magazine/Two-Thirds,
 * Split, and Overlay -- plus the per-post visibility rules (image/date/
 * excerpt) every layout shares. String-return, matching ItemsRenderer's
 * contract (the equivalent class for Query Grid).
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\ContentShowcase\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renders Magazine/Two-Thirds/Split/Overlay layout markup.
 */
class CardRenderer {

	/** @var HotspotRenderer */
	private $hotspots;

	/**
	 * @param HotspotRenderer $hotspots Renders each card's hotspot pins.
	 */
	public function __construct( HotspotRenderer $hotspots ) {
		$this->hotspots = $hotspots;
	}

	/**
	 * Shared by Magazine (1 big + up to 2 small in a grid) and Two-Thirds/One-Third.
	 *
	 * @param array[] $items      Transformed post data.
	 * @param string  $variant    'magazine' or 'two-thirds'.
	 * @param array   $attributes Block attributes.
	 * @return string Escaped HTML.
	 */
	public function render_featured_plus_list( array $items, string $variant, array $attributes ): string {
		$featured = array_shift( $items );
		ob_start();
		?>
		<div class="fb-content-showcase__<?php echo esc_attr( $variant ); ?>">
			<?php echo $this->render_card( $featured, 'featured', 0, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_card() escapes per field. ?>
			<?php if ( $items ) : ?>
				<div class="fb-content-showcase__<?php echo esc_attr( $variant ); ?>-list">
					<?php foreach ( $items as $index => $item ) : ?>
						<?php echo $this->render_card( $item, 'small', $index + 1, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_card() escapes per field. ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Split: full-width text/image rows, alternating sides (zig-zag).
	 *
	 * @param array[] $items      Transformed post data.
	 * @param array   $attributes Block attributes.
	 * @return string Escaped HTML.
	 */
	public function render_split( array $items, array $attributes ): string {
		ob_start();
		?>
		<div class="fb-content-showcase__split">
			<?php foreach ( $items as $index => $item ) : ?>
				<?php $vis = $this->get_post_visibility( $index, $attributes ); ?>
				<article class="fb-content-showcase__split-row <?php echo 0 === $index % 2 ? 'fb-content-showcase__split-row--image-right' : 'fb-content-showcase__split-row--image-left'; ?>">
					<?php if ( $vis['show_image'] && $item['image'] ) : ?>
						<div class="fb-content-showcase__image-wrap">
							<a class="fb-content-showcase__split-image" href="<?php echo esc_url( $item['permalink'] ); ?>">
								<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['imageAlt'] ); ?>" loading="lazy" />
							</a>
							<?php echo $this->hotspots->render_hotspots_for_post( $index, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_hotspots_for_post() escapes per field. ?>
						</div>
					<?php endif; ?>
					<div class="fb-content-showcase__split-text">
						<?php if ( $vis['show_date'] && ! empty( $item['date'] ) ) : ?>
							<span class="fb-content-showcase__date"><?php echo esc_html( $item['date'] ); ?></span>
						<?php endif; ?>
						<h3><a href="<?php echo esc_url( $item['permalink'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a></h3>
						<?php if ( $vis['show_excerpt'] && ! empty( $item['excerpt'] ) ) : ?>
							<p><?php echo esc_html( $item['excerpt'] ); ?></p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Overlay: image as card background, text box floating on top of it.
	 *
	 * @param array[] $items      Transformed post data.
	 * @param array   $attributes Block attributes.
	 * @return string Escaped HTML.
	 */
	public function render_overlay( array $items, array $attributes ): string {
		ob_start();
		?>
		<div class="fb-content-showcase__overlay-grid">
			<?php foreach ( $items as $index => $item ) : ?>
				<?php $vis = $this->get_post_visibility( $index, $attributes ); ?>
				<div class="fb-content-showcase__image-wrap fb-content-showcase__overlay-wrap">
					<a
						class="fb-content-showcase__overlay-card"
						href="<?php echo esc_url( $item['permalink'] ); ?>"
						<?php if ( $vis['show_image'] && $item['image'] ) : ?>
							style="background-image:url(<?php echo esc_url( $item['image'] ); ?>);"
						<?php endif; ?>
					>
						<span class="fb-content-showcase__overlay-text">
							<?php if ( $vis['show_date'] && ! empty( $item['date'] ) ) : ?>
								<span class="fb-content-showcase__date" style="color:rgba(255,255,255,0.8);"><?php echo esc_html( $item['date'] ); ?></span>
							<?php endif; ?>
							<?php echo esc_html( $item['title'] ); ?>
							<?php if ( $vis['show_excerpt'] && ! empty( $item['excerpt'] ) ) : ?>
								<p style="font-weight:400;font-size:0.85em;margin:0.35em 0 0;opacity:0.9;"><?php echo esc_html( $item['excerpt'] ); ?></p>
							<?php endif; ?>
						</span>
					</a>
					<?php echo $this->hotspots->render_hotspots_for_post( $index, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_hotspots_for_post() escapes per field. ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * One card's markup -- shared by Magazine's "featured" (big) and "small" cards.
	 *
	 * @param array  $item       Transformed post data for one post.
	 * @param string $size       'featured' or 'small'.
	 * @param int    $index      Post index.
	 * @param array  $attributes Block attributes.
	 * @return string Escaped HTML.
	 */
	private function render_card( array $item, string $size, int $index, array $attributes ): string {
		$vis = $this->get_post_visibility( $index, $attributes );
		ob_start();
		?>
		<article class="fb-content-showcase__card fb-content-showcase__card--<?php echo esc_attr( $size ); ?>">
			<?php if ( $vis['show_image'] && ( ! empty( $item['imageId'] ) || $item['image'] ) ) : ?>
				<div class="fb-content-showcase__image-wrap">
					<a class="fb-content-showcase__card-image" href="<?php echo esc_url( $item['permalink'] ); ?>">
						<?php if ( ! empty( $item['imageId'] ) ) : ?>
							<?php
							$img_attr = 0 === $index
								? array( 'fetchpriority' => 'high' )
								: array( 'loading' => 'lazy' );
							echo wp_get_attachment_image( (int) $item['imageId'], 'large', false, $img_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image escapes internally.
							?>
						<?php else : ?>
							<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['imageAlt'] ); ?>" <?php echo 0 === $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?> />
						<?php endif; ?>
					</a>
					<?php echo $this->hotspots->render_hotspots_for_post( $index, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_hotspots_for_post() escapes per field. ?>
				</div>
			<?php endif; ?>
			<div class="fb-content-showcase__card-body">
				<?php if ( $vis['show_date'] && ! empty( $item['date'] ) ) : ?>
					<span class="fb-content-showcase__date"><?php echo esc_html( $item['date'] ); ?></span>
				<?php endif; ?>

				<h3><a href="<?php echo esc_url( $item['permalink'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a></h3>

				<?php if ( $vis['show_excerpt'] && ! empty( $item['excerpt'] ) ) : ?>
					<p><?php echo esc_html( $item['excerpt'] ); ?></p>
				<?php endif; ?>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Calculates individual element visibility for a specific post index (0, 1, 2).
	 *
	 * @param int   $index      Zero-based post index.
	 * @param array $attributes Block attributes.
	 * @return array Array with keys 'show_image', 'show_date', 'show_excerpt'.
	 */
	private function get_post_visibility( int $index, array $attributes ): array {
		$slot     = $index + 1;
		$img_key  = "post{$slot}Image";
		$date_key = "post{$slot}Date";
		$exc_key  = "post{$slot}Excerpt";

		$default_excerpt = ( 0 === $index );

		return array(
			'show_image'   => isset( $attributes[ $img_key ] ) ? ! empty( $attributes[ $img_key ] ) : true,
			'show_date'    => isset( $attributes[ $date_key ] ) ? ! empty( $attributes[ $date_key ] ) : true,
			'show_excerpt' => isset( $attributes[ $exc_key ] ) ? ! empty( $attributes[ $exc_key ] ) : $default_excerpt,
		);
	}
}

<?php
/**
 * Renders the Content Showcase block: queries posts and outputs one of
 * four native Style Variations -- Magazine (1 big + up to 2 small cards),
 * Split (alternating text/image rows), Overlay (text floating on the
 * image), and Two-Thirds/One-Third (1 big beside smaller posts stacked in
 * a column). postCount maxes out at 3 (see block.json + edit.js's
 * RangeControl), so "small cards" here is never more than 2.
 * Also renders an optional "Explore More" button and interactive image hotspots.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\ContentShowcase\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Blocks\View\AbstractRenderer;
use FluxBlocks\QueryEngine\QueryArgsBuilder;
use FluxBlocks\Cache\QueryCache;
use FluxBlocks\QueryEngine\PostDataTransformer;

/**
 * Builds the Content Showcase query and renders one of its layouts.
 */
class Renderer extends AbstractRenderer {

	/** @var QueryArgsBuilder */
	private $args_builder;

	/** @var QueryCache */
	private $cache;

	/** @var PostDataTransformer */
	private $transformer;

	/**
	 * @param QueryArgsBuilder    $args_builder Turns attributes into WP_Query args.
	 * @param QueryCache          $cache        Read-through query cache.
	 * @param PostDataTransformer $transformer  Turns WP_Post into template-ready data.
	 */
	public function __construct( QueryArgsBuilder $args_builder, QueryCache $cache, PostDataTransformer $transformer ) {
		$this->args_builder = $args_builder;
		$this->cache        = $cache;
		$this->transformer  = $transformer;
	}

	/**
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block default content -- unused, but required by the dynamic-block render signature.
	 * @param \WP_Block $block      Block instance -- unused, same reason as $content.
	 * @return string
	 */
	public function render( array $attributes, string $content, \WP_Block $block ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $content/$block are part of WordPress's fixed render_callback signature, not optional.
		$layout = $this->layout_from_class_name( $attributes['className'] ?? '' );
		$items  = $this->query( $attributes );

		$inline_styles = array();
		if ( ! empty( $attributes['headingAccentColor'] ) ) {
			$inline_styles[] = '--fb-accent:' . esc_attr( $attributes['headingAccentColor'] );
		}
		if ( ! empty( $attributes['cardTitleColor'] ) ) {
			$inline_styles[] = '--fb-card-title-color:' . esc_attr( $attributes['cardTitleColor'] );
		}
		if ( ! empty( $attributes['cardTitleHoverColor'] ) ) {
			$inline_styles[] = '--fb-card-title-hover-color:' . esc_attr( $attributes['cardTitleHoverColor'] );
		}
		if ( ! empty( $attributes['cardDateColor'] ) ) {
			$inline_styles[] = '--fb-card-date-color:' . esc_attr( $attributes['cardDateColor'] );
		}
		if ( ! empty( $attributes['cardExcerptColor'] ) ) {
			$inline_styles[] = '--fb-card-excerpt-color:' . esc_attr( $attributes['cardExcerptColor'] );
		}
		// Font-family values come from the active theme's own theme.json
		// (see edit.js's useSettings('typography.fontFamilies')) -- never a
		// hardcoded list this plugin would need to load/enqueue itself.
		if ( ! empty( $attributes['cardTitleFontFamily'] ) ) {
			$inline_styles[] = '--fb-card-title-font-family:' . esc_attr( $attributes['cardTitleFontFamily'] );
		}
		if ( ! empty( $attributes['cardTitleFontWeight'] ) ) {
			$inline_styles[] = '--fb-card-title-font-weight:' . esc_attr( $attributes['cardTitleFontWeight'] );
		}
		if ( ! empty( $attributes['cardExcerptFontFamily'] ) ) {
			$inline_styles[] = '--fb-card-excerpt-font-family:' . esc_attr( $attributes['cardExcerptFontFamily'] );
		}
		if ( ! empty( $attributes['cardExcerptFontWeight'] ) ) {
			$inline_styles[] = '--fb-card-excerpt-font-weight:' . esc_attr( $attributes['cardExcerptFontWeight'] );
		}
		if ( ! empty( $attributes['cardDateFontFamily'] ) ) {
			$inline_styles[] = '--fb-card-date-font-family:' . esc_attr( $attributes['cardDateFontFamily'] );
		}
		if ( ! empty( $attributes['cardDateFontWeight'] ) ) {
			$inline_styles[] = '--fb-card-date-font-weight:' . esc_attr( $attributes['cardDateFontWeight'] );
		}

		$wrapper_attrs = get_block_wrapper_attributes(
			array(
				'class' => 'fb-content-showcase fb-content-showcase--' . sanitize_html_class( $layout ),
				'style' => ! empty( $inline_styles ) ? implode( ';', $inline_styles ) : null,
			)
		);

		$show_heading           = ! empty( $attributes['showHeading'] );
		$heading                = $show_heading ? ( $attributes['heading'] ?? '' ) : '';
		$subheading             = $show_heading ? ( $attributes['subheading'] ?? '' ) : '';
		$heading_accent         = $attributes['headingAccent'] ?? '';
		$heading_title_color    = $attributes['headingTitleColor'] ?? '';
		$heading_accent_color   = $attributes['headingAccentColor'] ?? '';
		$heading_title_font_fam = $attributes['headingTitleFontFamily'] ?? '';
		$heading_title_font_wt  = $attributes['headingTitleFontWeight'] ?? '';
		$subheading_color       = $attributes['subheadingColor'] ?? '';
		$subheading_style       = $this->build_style_attr(
			array(
				'color'       => $subheading_color,
				'font-family' => $attributes['subheadingFontFamily'] ?? '',
				'font-weight' => $attributes['subheadingFontWeight'] ?? '',
			)
		);

		return $this->capture(
			function () use ( $wrapper_attrs, $items, $layout, $attributes, $heading, $subheading, $heading_accent, $heading_title_color, $heading_accent_color, $heading_title_font_fam, $heading_title_font_wt, $subheading_style ) {
				?>
				<div
					<?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() already escapes. ?>
				>
					<?php if ( $heading || $subheading ) : ?>
						<header class="fb-content-showcase__header">
							<?php if ( $heading ) : ?>
								<h2 class="fb-content-showcase__heading">
									<?php echo $this->render_heading( $heading, $heading_accent, $heading_title_color, $heading_accent_color, $heading_title_font_fam, $heading_title_font_wt ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</h2>
							<?php endif; ?>
							<?php if ( $subheading ) : ?>
								<p class="fb-content-showcase__subheading"<?php echo $subheading_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- build_style_attr() already escapes. ?>>
									<?php echo esc_html( $subheading ); ?>
								</p>
							<?php endif; ?>
						</header>
					<?php endif; ?>

					<?php if ( empty( $items ) ) : ?>
						<p class="fb-content-showcase__empty"><?php esc_html_e( 'No posts found.', 'flux-blocks' ); ?></p>
					<?php else : ?>
						<?php if ( 'split' === $layout ) : ?>
							<?php $this->render_split( $items, $attributes ); ?>
						<?php elseif ( 'overlay' === $layout ) : ?>
							<?php $this->render_overlay( $items, $attributes ); ?>
						<?php elseif ( 'two-thirds' === $layout ) : ?>
							<?php $this->render_featured_plus_list( $items, 'two-thirds', $attributes ); ?>
						<?php else : ?>
							<?php $this->render_featured_plus_list( $items, 'magazine', $attributes ); ?>
						<?php endif; ?>
						<?php $this->render_explore_button( $attributes ); ?>
					<?php endif; ?>
				</div>
				<?php
			}
		);
	}

	/**
	 * Renders section heading with title/accent color + title font support.
	 * The accent span deliberately only takes a color, not its own font --
	 * it is an inline highlight of the same heading text, not a separate
	 * element, so it always inherits the title's font-family/font-weight.
	 *
	 * @param string $heading          Full heading text.
	 * @param string $accent           Substring of $heading to wrap in an accent span.
	 * @param string $title_color      Custom color for title text.
	 * @param string $accent_color     Custom color for accent highlight text.
	 * @param string $title_font_fam   Custom font-family for title text.
	 * @param string $title_font_wt    Custom font-weight for title text.
	 * @return string Escaped HTML.
	 */
	private function render_heading( string $heading, string $accent, string $title_color = '', string $accent_color = '', string $title_font_fam = '', string $title_font_wt = '' ): string {
		$title_style  = $this->build_style_attr(
			array(
				'color'       => $title_color,
				'font-family' => $title_font_fam,
				'font-weight' => $title_font_wt,
			)
		);
		$accent_style = $this->build_style_attr( array( 'color' => $accent_color ) );

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
	private function build_style_attr( array $properties ): string {
		$declarations = array();
		foreach ( $properties as $property => $value ) {
			if ( ! empty( $value ) ) {
				$declarations[] = $property . ':' . esc_attr( $value );
			}
		}
		return ! empty( $declarations ) ? ' style="' . implode( ';', $declarations ) . '"' : '';
	}

	/**
	 * Reads the active Style Variation off the block's className.
	 *
	 * @param string $class_name Block's className attribute.
	 * @return string
	 */
	private function layout_from_class_name( string $class_name ): string {
		if ( preg_match( '/is-style-([a-z-]+)/', $class_name, $matches ) ) {
			return $matches[1];
		}
		return 'magazine';
	}

	/**
	 * Queries + caches this block's posts.
	 *
	 * @param array $attributes Block attributes.
	 * @return array[] Transformed post data (see PostDataTransformer).
	 */
	private function query( array $attributes ): array {
		$is_manual_enabled = ! empty( $attributes['enableManualSelection'] );
		$manual_ids        = array();

		if ( $is_manual_enabled ) {
			if ( ! empty( $attributes['manualPost1'] ) ) {
				$manual_ids[0] = (int) $attributes['manualPost1'];
			}
			if ( ! empty( $attributes['manualPost2'] ) ) {
				$manual_ids[1] = (int) $attributes['manualPost2'];
			}
			if ( ! empty( $attributes['manualPost3'] ) ) {
				$manual_ids[2] = (int) $attributes['manualPost3'];
			}
		}

		$post_type = $attributes['postType'] ?? 'post';
		$args      = $this->args_builder->build( $attributes, 1 );

		if ( $is_manual_enabled && ! empty( $manual_ids ) ) {
			$args['post__not_in'] = array_values( $manual_ids );
		}

		$signature = $post_type . '|content-showcase|' . wp_json_encode( $args ) . '|' . ( $is_manual_enabled ? wp_json_encode( $manual_ids ) : 'off' );

		return $this->cache->remember(
			$post_type,
			$signature,
			function () use ( $args, $manual_ids, $is_manual_enabled, $post_type ) {
				$query         = new \WP_Query( $args );
				$queried_posts = $query->posts;

				if ( ! $is_manual_enabled ) {
					return $this->transformer->transform_many( $queried_posts );
				}

				$final_posts    = array();
				$queried_offset = 0;

				for ( $i = 0; $i < 3; $i++ ) {
					if ( isset( $manual_ids[ $i ] ) && $manual_ids[ $i ] > 0 ) {
						$manual_post = get_post( $manual_ids[ $i ] );
						// $post_type cross-check: a manually-picked post ID
						// can go stale if the block's postType is changed
						// AFTER picking (edit.js now resets the picks when
						// that happens, but this is the safety net for any
						// already-saved content from before that fix, or
						// attributes edited outside the editor UI). A
						// stale/mismatched id falls through to the
						// automatic query below instead of rendering the
						// wrong post type.
						if ( $manual_post instanceof \WP_Post && $post_type === $manual_post->post_type ) {
							$final_posts[] = $manual_post;
							continue;
						}
					}

					if ( isset( $queried_posts[ $queried_offset ] ) ) {
						$final_posts[] = $queried_posts[ $queried_offset ];
						$queried_offset++;
					}
				}

				return $this->transformer->transform_many( $final_posts );
			}
		);
	}

	/**
	 * Shared by Magazine (1 big + up to 2 small in a grid) and Two-Thirds/One-Third.
	 *
	 * @param array[] $items      Transformed post data.
	 * @param string  $variant    'magazine' or 'two-thirds'.
	 * @param array   $attributes Block attributes.
	 */
	private function render_featured_plus_list( array $items, string $variant, array $attributes ): void {
		$featured = array_shift( $items );
		?>
		<div class="fb-content-showcase__<?php echo esc_attr( $variant ); ?>">
			<?php $this->render_card( $featured, 'featured', 0, $attributes ); ?>
			<?php if ( $items ) : ?>
				<div class="fb-content-showcase__<?php echo esc_attr( $variant ); ?>-list">
					<?php foreach ( $items as $index => $item ) : ?>
						<?php $this->render_card( $item, 'small', $index + 1, $attributes ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Split: full-width text/image rows, alternating sides (zig-zag).
	 *
	 * @param array[] $items      Transformed post data.
	 * @param array   $attributes Block attributes.
	 */
	private function render_split( array $items, array $attributes ): void {
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
							<?php $this->render_hotspots_for_post( $index, $attributes ); ?>
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
	}

	/**
	 * Overlay: image as card background, text box floating on top of it.
	 *
	 * @param array[] $items      Transformed post data.
	 * @param array   $attributes Block attributes.
	 */
	private function render_overlay( array $items, array $attributes ): void {
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
					<?php $this->render_hotspots_for_post( $index, $attributes ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * One card's markup -- shared by Magazine's "featured" (big) and "small" cards.
	 *
	 * @param array  $item       Transformed post data for one post.
	 * @param string $size       'featured' or 'small'.
	 * @param int    $index      Post index.
	 * @param array  $attributes Block attributes.
	 */
	private function render_card( array $item, string $size, int $index, array $attributes ): void {
		$vis = $this->get_post_visibility( $index, $attributes );
		?>
		<article class="fb-content-showcase__card fb-content-showcase__card--<?php echo esc_attr( $size ); ?>">
			<?php if ( $vis['show_image'] && $item['image'] ) : ?>
				<div class="fb-content-showcase__image-wrap">
					<a class="fb-content-showcase__card-image" href="<?php echo esc_url( $item['permalink'] ); ?>">
						<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['imageAlt'] ); ?>" loading="lazy" />
					</a>
					<?php $this->render_hotspots_for_post( $index, $attributes ); ?>
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

	/**
	 * Renders hotspot pins for a specific post index if enableHotspots is on.
	 *
	 * @param int   $post_index Zero-based post index in the layout.
	 * @param array $attributes Block attributes.
	 */
	private function render_hotspots_for_post( int $post_index, array $attributes ): void {
		if ( empty( $attributes['enableHotspots'] ) || empty( $attributes['hotspots'] ) || ! is_array( $attributes['hotspots'] ) ) {
			return;
		}

		$pin_color  = ! empty( $attributes['hotspotPinColor'] ) ? $attributes['hotspotPinColor'] : '#d1372d';
		$tooltip_bg = ! empty( $attributes['hotspotTooltipBg'] ) ? $attributes['hotspotTooltipBg'] : '#141414';
		$text_color = ! empty( $attributes['hotspotTooltipTextColor'] ) ? $attributes['hotspotTooltipTextColor'] : '#ffffff';
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
	}

	/**
	 * "Explore More" button.
	 *
	 * @param array $attributes Block attributes.
	 */
	private function render_explore_button( array $attributes ): void {
		if ( empty( $attributes['showExploreButton'] ) || empty( $attributes['exploreButtonUrl'] ) ) {
			return;
		}
		$text        = ! empty( $attributes['exploreButtonText'] ) ? $attributes['exploreButtonText'] : __( 'Explore More', 'flux-blocks' );
		$text_color  = $attributes['exploreButtonTextColor'] ?? '';
		$bg_color    = $attributes['exploreButtonBgColor'] ?? '';
		$hover_bg    = $attributes['exploreButtonHoverBg'] ?? '';
		$font_family = $attributes['exploreButtonFontFamily'] ?? '';
		$font_weight = $attributes['exploreButtonFontWeight'] ?? '';
		$styles      = array();

		if ( ! empty( $text_color ) ) {
			$styles[] = '--fb-btn-color:' . esc_attr( $text_color );
		}
		if ( ! empty( $bg_color ) ) {
			$styles[] = '--fb-btn-bg:' . esc_attr( $bg_color );
		}
		if ( ! empty( $hover_bg ) ) {
			$styles[] = '--fb-btn-hover-bg:' . esc_attr( $hover_bg );
		}
		// Font-family values come from the active theme's own theme.json
		// (see edit.js's useSettings('typography.fontFamilies')) -- never a
		// hardcoded list the plugin would need to load/enqueue itself.
		if ( ! empty( $font_family ) ) {
			$styles[] = '--fb-btn-font-family:' . esc_attr( $font_family );
		}
		if ( ! empty( $font_weight ) ) {
			$styles[] = '--fb-btn-font-weight:' . esc_attr( $font_weight );
		}

		$style_attr = ! empty( $styles ) ? ' style="' . esc_attr( implode( ';', $styles ) ) . '"' : '';
		?>
		<p class="fb-content-showcase__explore">
			<a class="fb-content-showcase__explore-button" href="<?php echo esc_url( $attributes['exploreButtonUrl'] ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo esc_html( $text ); ?>
			</a>
		</p>
		<?php
	}
}

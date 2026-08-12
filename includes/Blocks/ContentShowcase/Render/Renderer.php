<?php
/**
 * Orchestrates the Content Showcase block: runs the query (via the
 * injected collaborators), resolves layout/heading/style attributes, and
 * assembles the final markup by delegating to StyleBuilder (wrapper CSS
 * custom properties), HeadingRenderer (section heading + accent),
 * CardRenderer (Magazine/Two-Thirds/Split/Overlay layouts, which in turn
 * delegates hotspot pins to HotspotRenderer), and ExploreButtonRenderer.
 *
 * Why split this way: same reasoning as QueryGrid\Render\Renderer's
 * docblock -- this class used to own ALL of that rendering logic itself
 * (580+ lines, one class doing querying and several unrelated pieces of
 * HTML templating). Splitting it doesn't change any OUTPUT (see the
 * live-render snapshot diff run before/after this refactor), only which
 * class each piece of logic lives in.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\ContentShowcase\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Blocks\View\AbstractRenderer;
use FluxBlocks\QueryEngine\ArgsBuilderInterface;
use FluxBlocks\Cache\CacheInterface;
use FluxBlocks\QueryEngine\TransformerInterface;

/**
 * Builds the Content Showcase query and renders one of its layouts.
 */
class Renderer extends AbstractRenderer {

	/** Every layout slug Content Showcase actually supports as a Style Variation. */
	const KNOWN_LAYOUTS = array( 'magazine', 'split', 'overlay', 'two-thirds' );

	/** @var ArgsBuilderInterface */
	private $args_builder;

	/** @var CacheInterface */
	private $cache;

	/** @var TransformerInterface */
	private $transformer;

	/** @var StyleBuilder */
	private $style_builder;

	/** @var HeadingRenderer */
	private $heading_renderer;

	/** @var CardRenderer */
	private $cards;

	/** @var ExploreButtonRenderer */
	private $explore_button;

	/**
	 * StyleBuilder/HeadingRenderer/CardRenderer/ExploreButtonRenderer are
	 * composed internally (not constructor-injected) -- they're pure view
	 * helpers with no external dependencies of their own, so injecting
	 * them here would just re-introduce a long-parameter-list constructor
	 * to fix the exact code smell this refactor is removing elsewhere.
	 *
	 * @param ArgsBuilderInterface $args_builder Turns attributes into WP_Query args.
	 * @param CacheInterface       $cache        Read-through query cache.
	 * @param TransformerInterface $transformer  Turns WP_Post into template-ready data.
	 */
	public function __construct( ArgsBuilderInterface $args_builder, CacheInterface $cache, TransformerInterface $transformer ) {
		$this->args_builder     = $args_builder;
		$this->cache            = $cache;
		$this->transformer      = $transformer;
		$this->style_builder    = new StyleBuilder();
		$this->heading_renderer = new HeadingRenderer();
		$this->cards            = new CardRenderer( new HotspotRenderer() );
		$this->explore_button   = new ExploreButtonRenderer();
	}

	/**
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block default content -- unused, but required by the dynamic-block render signature.
	 * @param \WP_Block $block      Block instance -- unused, same reason as $content.
	 * @return string
	 */
	public function render( array $attributes, string $content, \WP_Block $block ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $content/$block are part of WordPress's fixed render_callback signature, not optional.
		$layout = $this->layout_from_class_name( $attributes['className'] ?? '', self::KNOWN_LAYOUTS, 'magazine' );
		$items  = $this->query( $attributes );

		$style = $this->style_builder->build_inline_style( $attributes );

		$wrapper_attrs = get_block_wrapper_attributes(
			array(
				'class' => 'fb-content-showcase fb-content-showcase--' . sanitize_html_class( $layout ),
				'style' => '' !== $style ? $style : null,
			)
		);

		$show_heading     = ! empty( $attributes['showHeading'] );
		$heading          = $show_heading ? ( $attributes['heading'] ?? '' ) : '';
		$subheading       = $show_heading ? ( $attributes['subheading'] ?? '' ) : '';
		$heading_accent   = $attributes['headingAccent'] ?? '';
		$heading_style    = new HeadingStyle(
			$attributes['headingTitleColor'] ?? '',
			$attributes['headingAccentColor'] ?? '',
			$attributes['headingTitleFontFamily'] ?? '',
			$attributes['headingTitleFontWeight'] ?? ''
		);
		$subheading_style = $this->heading_renderer->build_style_attr(
			array(
				'color'       => $attributes['subheadingColor'] ?? '',
				'font-family' => $attributes['subheadingFontFamily'] ?? '',
				'font-weight' => $attributes['subheadingFontWeight'] ?? '',
			)
		);
		$heading_html     = $heading ? $this->heading_renderer->render_heading( $heading, $heading_accent, $heading_style ) : '';

		return $this->capture(
			function () use ( $wrapper_attrs, $items, $layout, $attributes, $heading, $subheading, $heading_html, $subheading_style ) {
				?>
				<div
					<?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() already escapes. ?>
				>
					<?php if ( $heading || $subheading ) : ?>
						<header class="fb-content-showcase__header">
							<?php if ( $heading ) : ?>
								<h2 class="fb-content-showcase__heading">
									<?php echo $heading_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HeadingRenderer::render_heading() escapes both parts. ?>
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
							<?php $this->cards->render_split( $items, $attributes ); ?>
						<?php elseif ( 'overlay' === $layout ) : ?>
							<?php $this->cards->render_overlay( $items, $attributes ); ?>
						<?php elseif ( 'two-thirds' === $layout ) : ?>
							<?php $this->cards->render_featured_plus_list( $items, 'two-thirds', $attributes ); ?>
						<?php else : ?>
							<?php $this->cards->render_featured_plus_list( $items, 'magazine', $attributes ); ?>
						<?php endif; ?>
						<?php $this->explore_button->render( $attributes ); ?>
					<?php endif; ?>
				</div>
				<?php
			}
		);
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
}

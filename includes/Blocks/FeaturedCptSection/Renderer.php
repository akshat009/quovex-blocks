<?php
/**
 * Renders the Featured CPT Section block.
 *
 * Why: render.php stays a one-line delegate; all markup logic for both
 * selection modes (manual/automatic) and the carousel/load-more/hotspot
 * Interactivity API wiring lives here.
 * Impact of changing: affects every page using this block — there's no REST
 * controller involved, this is the sole render path (see docs/architecture.md).
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\FeaturedCptSection;

use FluxBlocks\Query\QueryArgsBuilder;
use FluxBlocks\Query\QueryCache;
use FluxBlocks\Query\PostDataTransformer;

/**
 * Builds the Featured CPT Section query, transforms results, and renders
 * the carousel/expanded-grid markup.
 */
class Renderer {

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
		$items       = $this->query( $attributes );
		$heading     = $attributes['heading'] ?? '';
		$description = $attributes['description'] ?? '';

		// Show the first 3 up front, the rest behind "load more" — pre-rendered
		// and hidden, not fetched, because the item set here is always
		// bounded/curated (see docs/architecture.md §6).
		$initial_visible = min( 3, count( $items ) );

		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'fb-featured' ) );

		$context = array(
			'visibleCount'  => $initial_visible,
			'total'         => count( $items ),
			'carouselIndex' => 0,
			'hoveredId'     => 0,
			// Starts as a carousel (one item at a time); "Load more" flips
			// this to true and switches to a progressively-revealed grid —
			// see src/featured-cpt-section/view.js's isItemVisible getter.
			'isExpanded'    => false,
		);

		ob_start();
		?>
		<div
			<?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() already escapes. ?>
			data-wp-interactive="flux-blocks/featured-cpt-section"
			<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core helper already escapes/encodes. ?>
		>
			<?php if ( $heading ) : ?>
				<h2 class="fb-featured__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $description ) : ?>
				<p class="fb-featured__description"><?php echo wp_kses_post( $description ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() IS the escaping function here; RichText allows bold/italic marks (see edit.js), so raw esc_html() would show literal <strong> tags instead of rendering them. ?></p>
			<?php endif; ?>

			<div class="fb-featured__items">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php $this->render_item( $item, $index ); ?>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $items ) > $initial_visible ) : ?>
				<button
					type="button"
					class="fb-featured__load-more"
					data-wp-on--click="actions.revealMore"
					data-wp-bind--hidden="context.visibleCount >= context.total"
				>
					<?php esc_html_e( 'Load more', 'flux-blocks' ); ?>
				</button>
			<?php endif; ?>

			<?php if ( count( $items ) > 1 ) : ?>
				<div class="fb-featured__carousel-nav" data-wp-bind--hidden="context.isExpanded">
					<button type="button" class="fb-featured__nav-btn" data-wp-on--click="actions.carouselPrev" aria-label="<?php esc_attr_e( 'Previous', 'flux-blocks' ); ?>">&#8249;</button>
					<button type="button" class="fb-featured__nav-btn" data-wp-on--click="actions.carouselNext" aria-label="<?php esc_attr_e( 'Next', 'flux-blocks' ); ?>">&#8250;</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array $item  Transformed post data.
	 * @param int   $index Position in the list — becomes this item's `itemIndex`
	 *                     context, which the section-level `visibleCount`
	 *                     ("load more" reveal) and `carouselIndex` state getters
	 *                     compare against.
	 */
	private function render_item( array $item, int $index ): void {
		$item_context = array(
			'itemIndex' => $index,
			'itemId'    => $item['id'],
		);
		?>
		<article
			class="fb-featured__item"
			<?php echo wp_interactivity_data_wp_context( $item_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php
			/*
			 * Bakes the initial visibility directly (only item 0 shown,
			 * matching the starting isExpanded=false/carouselIndex=0
			 * context) rather than relying solely on data-wp-bind against
			 * state.isItemVisible -- that getter is JS-only and resolves to
			 * undefined server-side, which would otherwise hide every item
			 * on first paint (same class of bug documented in
			 * QueryGrid\Renderer::render_pagination()). The directive stays
			 * for genuine client-side revealMore/carouselNext/Prev clicks.
			 */
			echo 0 === $index ? '' : 'hidden';
			?>
			data-wp-bind--hidden="!state.isItemVisible"
			data-wp-on--mouseenter="actions.showHotspot"
			data-wp-on--mouseleave="actions.hideHotspot"
			data-wp-on--focus="actions.showHotspot"
			data-wp-on--blur="actions.hideHotspot"
			data-wp-on--click="actions.toggleHotspotTouch"
		>
			<?php if ( $item['image'] ) : ?>
				<div class="fb-featured__item-image">
					<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['imageAlt'] ); ?>" loading="lazy" />
					<div class="fb-featured__hotspot" data-wp-class--is-visible="state.isHotspotVisible">
						<p class="fb-featured__hotspot-author"><?php echo esc_html( $item['author'] ); ?></p>
						<p class="fb-featured__hotspot-text"><?php echo esc_html( $item['excerpt'] ); ?></p>
					</div>
				</div>
			<?php endif; ?>
			<h3 class="fb-featured__item-title">
				<a href="<?php echo esc_url( $item['permalink'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
			</h3>
		</article>
		<?php
	}

	/**
	 * @param array $attributes Block attributes.
	 * @return array[] Transformed post data.
	 */
	private function query( array $attributes ): array {
		$post_type = $attributes['postType'] ?? 'post';
		$args      = $this->args_builder->build( $attributes );
		$signature = $post_type . '|featured|' . wp_json_encode( $args );

		return $this->cache->remember(
			$post_type,
			$signature,
			function () use ( $args ) {
				$query = new \WP_Query( $args );
				return $this->transformer->transform_many( $query->posts );
			}
		);
	}
}

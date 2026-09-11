<?php
/**
 * Renders Query Grid's item grid, "Load more" button, and numbered
 * pagination -- the block's biggest single rendering concern, now
 * separated from querying/caching/facets/heading so each stays
 * independently readable and testable.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Blocks\QueryGrid\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use QuovexBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint;

/**
 * Renders items, "Load more", and Prev/page-number/Next pagination.
 */
class ItemsRenderer {

	/**
	 * The items grid plus its trailing nav.
	 *
	 * @param ItemsRenderContext $context Everything needed to render this page's items + nav.
	 * @return string Escaped HTML.
	 */
	public function render_items_and_nav( ItemsRenderContext $context ): string {
		ob_start();
		?>
		<div
			class="qv-query-grid__items"
			<?php if ( 'masonry' === $context->layout ) : ?>
				data-wp-init="callbacks.initMasonryLayout"
			<?php endif; ?>
		>
			<?php echo $this->render_items( $context->result['items'], $context->layout, $context->carousel_items_per_view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_items() escapes per field. ?>
		</div>

		<?php if ( $context->show_all_no_nav ) : ?>
		<?php elseif ( $context->is_carousel ) : ?>
			<div class="qv-query-grid__carousel-nav">
				<button type="button" class="qv-query-grid__nav-btn" data-wp-on--click="actions.carouselPrev" aria-label="<?php esc_attr_e( 'Previous', 'quovex-blocks' ); ?>">&#8249;</button>
				<button type="button" class="qv-query-grid__nav-btn" data-wp-on--click="actions.carouselNext" aria-label="<?php esc_attr_e( 'Next', 'quovex-blocks' ); ?>">&#8250;</button>
			</div>
		<?php elseif ( 'load-more' === $context->pagination_style ) : ?>
			<?php echo $this->render_load_more_button( $context->result['has_more'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_load_more_button() escapes per field. ?>
		<?php else : ?>
			<div class="qv-query-grid__pagination-slot" data-wp-init="callbacks.initPaginationDelegation">
				<?php echo $this->render_pagination( $context->current_page, $context->result['total_pages'], $context->current_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_pagination() escapes per field. ?>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * "Load more" button rendering.
	 *
	 * @param bool $has_more Whether a next page exists for the initial query.
	 * @return string Escaped HTML.
	 */
	private function render_load_more_button( bool $has_more ): string {
		if ( ! $has_more ) {
			return '';
		}

		ob_start();
		?>
		<div class="qv-query-grid__load-more">
			<button
				type="button"
				class="qv-query-grid__load-more-btn"
				data-wp-on--click="actions.loadMore"
				data-wp-bind--hidden="!context.hasMore"
				data-wp-bind--disabled="context.isLoading"
			>
				<?php esc_html_e( 'Load more', 'quovex-blocks' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render item markup.
	 *
	 * @param array[] $items                   Transformed post data.
	 * @param string  $layout                  Layout slug.
	 * @param int     $carousel_items_per_view Cards per view.
	 * @return string Escaped HTML.
	 */
	public function render_items( array $items, string $layout, int $carousel_items_per_view = 3 ): string {
		if ( empty( $items ) ) {
			return '<p class="qv-query-grid__empty">' . esc_html__( 'No items found.', 'quovex-blocks' ) . '</p>';
		}

		ob_start();
		foreach ( $items as $index => $item ) {
			$this->render_item( $item, $layout, $index, $carousel_items_per_view );
		}
		return ob_get_clean();
	}

	/**
	 * Single item rendering.
	 *
	 * @param array  $item                    Transformed post data.
	 * @param string $layout                  Layout slug.
	 * @param int    $index                   Position index.
	 * @param int    $carousel_items_per_view Cards per view.
	 */
	private function render_item( array $item, string $layout, int $index, int $carousel_items_per_view = 3 ): void {
		?>
		<article
			class="qv-query-grid__item qv-query-grid__item--<?php echo esc_attr( $layout ); ?>"
			<?php if ( 'carousel' === $layout ) : ?>
				<?php echo $index < $carousel_items_per_view ? '' : 'hidden'; ?>
				<?php echo wp_interactivity_data_wp_context( array( 'slideIndex' => $index ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		>
			<a href="<?php echo esc_url( $item['permalink'] ); ?>" class="qv-query-grid__item-image">
				<?php if ( ! empty( $item['imageId'] ) ) : ?>
					<?php
					$img_attr = 0 === $index
						? array( 'fetchpriority' => 'high' )
						: array( 'loading' => 'lazy' );
					echo wp_get_attachment_image( (int) $item['imageId'], 'large', false, $img_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image escapes internally.
					?>
				<?php elseif ( $item['image'] ) : ?>
					<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['imageAlt'] ); ?>" <?php echo 0 === $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?> />
				<?php else : ?>
					<span class="qv-query-grid__item-image-placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</a>
			<div class="qv-query-grid__item-body">
				<p class="qv-query-grid__item-meta">
					<span class="qv-query-grid__item-date"><?php echo esc_html( $item['date'] ); ?></span>
					<span class="qv-query-grid__item-sep" aria-hidden="true">&#183;</span>
					<span class="qv-query-grid__item-author"><?php echo esc_html( $item['author'] ); ?></span>
				</p>
				<h3 class="qv-query-grid__item-title">
					<a href="<?php echo esc_url( $item['permalink'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
				</h3>
				<p class="qv-query-grid__item-excerpt"><?php echo esc_html( $item['excerpt'] ); ?></p>
				<a href="<?php echo esc_url( $item['permalink'] ); ?>" class="qv-query-grid__item-readmore">
					<?php esc_html_e( 'Read More', 'quovex-blocks' ); ?> &#8250;
				</a>
			</div>
		</article>
		<?php
	}

	/**
	 * Renders Prev / page-number / Next controls as real `<a href>` links
	 * (crawlable, unlike a client-only control) and fully server-rendered
	 * rather than a client `state` getter -- the Interactivity API
	 * evaluates `data-wp-bind` server-side too, and a JS-only derived
	 * getter resolves to `undefined` there, breaking first paint (see
	 * view.js's file docblock for the incident this avoids).
	 *
	 * @param int    $current_page Page currently displayed.
	 * @param int    $total_pages  Total pages.
	 * @param string $base_url     Base page URL.
	 * @return string Escaped HTML.
	 */
	public function render_pagination( int $current_page, int $total_pages, string $base_url = '' ): string {
		if ( $total_pages <= 1 ) {
			return '';
		}

		$page_url = function ( int $page ) use ( $base_url ) {
			return $this->build_page_url( $base_url, $page );
		};

		ob_start();
		?>
		<nav class="qv-query-grid__pagination" aria-label="<?php esc_attr_e( 'Pagination', 'quovex-blocks' ); ?>">
			<?php if ( $current_page > 1 ) : ?>
				<a href="<?php echo esc_url( $page_url( $current_page - 1 ) ); ?>" class="qv-query-grid__page-btn qv-query-grid__page-btn--prev">
					<?php esc_html_e( 'Prev', 'quovex-blocks' ); ?>
				</a>
			<?php else : ?>
				<span class="qv-query-grid__page-btn qv-query-grid__page-btn--prev" aria-disabled="true">
					<?php esc_html_e( 'Prev', 'quovex-blocks' ); ?>
				</span>
			<?php endif; ?>

			<?php
			$last_rendered_page = 0;
			for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) :
				$is_edge   = ( 1 === $page_num || $total_pages === $page_num );
				$is_nearby = abs( $page_num - $current_page ) <= 1;
				if ( ! $is_edge && ! $is_nearby ) {
					continue;
				}
				if ( $page_num - $last_rendered_page > 1 ) :
					?>
					<span class="qv-query-grid__page-ellipsis" aria-hidden="true">&hellip;</span>
					<?php
				endif;
				$last_rendered_page = $page_num;
				$page_context       = wp_interactivity_data_wp_context( array( 'pageNum' => $page_num ) );
				if ( $page_num === $current_page ) :
					?>
					<span
						class="qv-query-grid__page-btn is-active"
						aria-current="page"
						<?php echo $page_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
						<?php echo esc_html( (string) $page_num ); ?>
					</span>
					<?php
				else :
					?>
					<a
						href="<?php echo esc_url( $page_url( $page_num ) ); ?>"
						class="qv-query-grid__page-btn"
						<?php echo $page_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
						<?php echo esc_html( (string) $page_num ); ?>
					</a>
					<?php
				endif;
			endfor;
			?>

			<?php if ( $current_page < $total_pages ) : ?>
				<a href="<?php echo esc_url( $page_url( $current_page + 1 ) ); ?>" class="qv-query-grid__page-btn qv-query-grid__page-btn--next">
					<?php esc_html_e( 'Next', 'quovex-blocks' ); ?>
				</a>
			<?php else : ?>
				<span class="qv-query-grid__page-btn qv-query-grid__page-btn--next" aria-disabled="true">
					<?php esc_html_e( 'Next', 'quovex-blocks' ); ?>
				</span>
			<?php endif; ?>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * Builds page URL.
	 *
	 * @param string $base_url Base URL.
	 * @param int    $page     Page number.
	 * @return string Unescaped URL.
	 */
	private function build_page_url( string $base_url, int $page ): string {
		$slug   = PaginationEndpoint::slug();
		$parsed = wp_parse_url( $base_url );
		$path   = isset( $parsed['path'] ) ? $parsed['path'] : '/';
		$path   = preg_replace( '#/' . preg_quote( $slug, '#' ) . '/\d+/?$#', '/', $path );
		$path   = trailingslashit( $path );
		if ( $page > 1 ) {
			$path = trailingslashit( $path . $slug . '/' . $page );
		}

		$port   = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';
		$origin = ( isset( $parsed['scheme'], $parsed['host'] ) )
			? $parsed['scheme'] . '://' . $parsed['host'] . $port
			: home_url();
		$url    = $origin . $path;
		if ( ! empty( $parsed['query'] ) ) {
			$url .= '?' . $parsed['query'];
		}
		return $url;
	}
}

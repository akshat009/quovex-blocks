<?php
/**
 * Renders Query Grid's search form and taxonomy filter-pill facets.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Cache\CacheInterface;

/**
 * Renders the search form and taxonomy facet groups.
 */
class FacetRenderer {

	/** @var CacheInterface */
	private $cache;

	/**
	 * @param CacheInterface $cache Read-through cache for term lookups.
	 */
	public function __construct( CacheInterface $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @return string Escaped HTML.
	 */
	public function render_search_form(): string {
		ob_start();
		?>
		<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="fb-query-grid__search" data-wp-on--submit="actions.onSearchSubmit">
			<input
				type="search"
				name="s"
				class="fb-query-grid__search-input"
				placeholder="<?php esc_attr_e( 'Search Here', 'flux-blocks' ); ?>"
				aria-label="<?php esc_attr_e( 'Search', 'flux-blocks' ); ?>"
				data-wp-bind--value="context.searchQuery"
				data-wp-on--input="actions.onSearchInput"
			/>
			<button type="submit" class="fb-query-grid__search-btn" aria-label="<?php esc_attr_e( 'Search', 'flux-blocks' ); ?>"><span aria-hidden="true">&#128269;</span></button>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param string $taxonomy       Taxonomy slug.
	 * @param string $post_type      Post type slug.
	 * @param bool   $show_heading   Whether to render label.
	 * @param string $custom_heading Custom heading text.
	 * @return string Escaped HTML.
	 */
	public function render_facet_group( string $taxonomy, string $post_type, bool $show_heading, string $custom_heading = '' ): string {
		$terms = $this->get_terms_for_post_type( $taxonomy, $post_type );

		if ( empty( $terms ) ) {
			return '';
		}

		$taxonomy_object = get_taxonomy( $taxonomy );
		$label           = $taxonomy_object ? $taxonomy_object->labels->name : $taxonomy;
		$heading_to_show = '' !== $custom_heading ? $custom_heading : $label;

		ob_start();
		?>
		<div class="fb-query-grid__filter-group">
			<?php if ( $show_heading ) : ?>
				<h4 class="fb-query-grid__filter-heading"><?php echo esc_html( $heading_to_show ); ?></h4>
			<?php endif; ?>
			<div class="fb-query-grid__filters" role="group" aria-label="<?php echo esc_attr( $heading_to_show ); ?>">
				<?php foreach ( $terms as $term ) : ?>
					<?php
					$pill_context = array(
						'taxonomy' => $taxonomy,
						'termId'   => $term->term_id,
					);
					?>
					<button
						type="button"
						class="fb-query-grid__filter-btn"
						data-wp-class--is-active="state.isActiveTerm"
						data-wp-bind--aria-pressed="state.isActiveTerm"
						data-wp-on--click="actions.onFilterClick"
						<?php echo wp_interactivity_data_wp_context( $pill_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
						<?php echo esc_html( $term->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns terms attached to published posts without -1 post scans.
	 *
	 * @param string $taxonomy  Taxonomy slug.
	 * @param string $post_type Post type slug.
	 * @return \WP_Term[]
	 */
	private function get_terms_for_post_type( string $taxonomy, string $post_type ): array {
		$signature = $post_type . '|facet-terms|' . $taxonomy;

		return $this->cache->remember(
			$post_type,
			$signature,
			function () use ( $taxonomy ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => true,
						'number'     => 100,
					)
				);

				return is_wp_error( $terms ) ? array() : $terms;
			}
		);
	}

	/**
	 * Resolves active taxonomies for filtering.
	 *
	 * @param string $post_type  Post type slug.
	 * @param array  $attributes Block attributes.
	 * @return string[]
	 */
	public function resolve_facet_taxonomies( string $post_type, array $attributes ): array {
		$explicit = array_filter( (array) ( $attributes['facetTaxonomies'] ?? array() ) );
		if ( ! empty( $explicit ) ) {
			return array_map( 'sanitize_key', $explicit );
		}

		$taxonomies = array();
		foreach ( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy ) {
			if ( ! empty( $taxonomy->public ) && ! empty( $taxonomy->hierarchical ) ) {
				$taxonomies[] = $taxonomy->name;
			}
		}
		return $taxonomies;
	}
}

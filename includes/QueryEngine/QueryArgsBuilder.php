<?php
/**
 * Builds WP_Query args from block attributes. Single source of truth for
 * query logic -- used by both Renderers and the REST controller.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\QueryEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Turns block attributes into WP_Query args.
 */
class QueryArgsBuilder implements ArgsBuilderInterface {

	/**
	 * Build WP_Query args from a normalized set of block attributes.
	 *
	 * @param array $attributes Block attributes (postType, postCount, orderBy, order, taxonomyFilter, manualIds...).
	 * @param int   $page       1-based page number (ignored for manual selection, which is a fixed list).
	 * @return array WP_Query args.
	 */
	public function build( array $attributes, int $page = 1 ): array {
		$post_type = isset( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';

		// Only public post types are queryable -- prevents probing
		// private/internal post types via attributes or REST params.
		if ( ! $this->is_public_post_type( $post_type ) ) {
			$post_type = 'post';
		}

		// Manual selection (`manualIds`) bypasses ordering/pagination
		// entirely. Not currently used by either shipped block -- Content
		// Showcase's manual-pick UI (manualPost1/2/3) is a separate
		// mechanism layered on top in its own Renderer::query().
		if ( ! empty( $attributes['manualIds'] ) && is_array( $attributes['manualIds'] ) ) {
			return array(
				'post_type'      => $post_type,
				'post__in'       => array_map( 'absint', $attributes['manualIds'] ),
				'orderby'        => 'post__in',
				'posts_per_page' => count( $attributes['manualIds'] ),
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			);
		}

		// "Show all posts" ignores `postCount` and defaults to a bounded
		// 200 instead of a literal -1, to avoid an unbounded query on
		// large sites. Filterable per site:
		// `add_filter( 'flux_blocks_show_all_posts_limit', fn() => -1 )`.
		if ( ! empty( $attributes['showAllPosts'] ) ) {
			$per_page = (int) apply_filters( 'flux_blocks_show_all_posts_limit', 200 );
		} else {
			$per_page = isset( $attributes['postCount'] ) ? max( 1, absint( $attributes['postCount'] ) ) : 9;
			// Hard cap on items PER PAGE, not on the total matching pool --
			// `paged` below still reaches every post via WP_Query's own
			// max_num_pages, regardless of this number.
			$per_page = min( $per_page, 50 );
		}

		$order_by_map = array(
			'date'       => 'date',
			'title'      => 'title',
			'menu_order' => 'menu_order',
		);
		$order_by     = isset( $attributes['orderBy'], $order_by_map[ $attributes['orderBy'] ] )
			? $order_by_map[ $attributes['orderBy'] ]
			: 'date';
		$order        = isset( $attributes['order'] ) && 'asc' === strtolower( $attributes['order'] ) ? 'ASC' : 'DESC';

		$args = array(
			'post_type'           => $post_type,
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $page ),
			'orderby'             => $order_by,
			'order'               => $order,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		);

		$taxonomy_filter = $attributes['taxonomyFilter'] ?? null;
		if ( ! empty( $taxonomy_filter['taxonomy'] ) && ! empty( $taxonomy_filter['terms'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- editor-controlled, not user input at scale.
				array(
					'taxonomy' => sanitize_key( $taxonomy_filter['taxonomy'] ),
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', (array) $taxonomy_filter['terms'] ),
				),
			);
		}

		// Frontend search bar (Query Grid only) -- opt-in per request.
		if ( ! empty( $attributes['search'] ) ) {
			$args['s'] = sanitize_text_field( (string) $attributes['search'] );
		}

		return $args;
	}

	/**
	 * Whether a post type slug is registered and public.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function is_public_post_type( string $post_type ): bool {
		$object = get_post_type_object( $post_type );
		return $object instanceof \WP_Post_Type && $object->public;
	}
}

<?php
/**
 * Builds WP_Query args from block attributes.
 *
 * Why: single source of truth for query logic — both block renderers and
 * the REST controller call this, so query behavior only lives in one place.
 * Impact of changing: affects Query Grid, Content Showcase, AND the
 * /flux-blocks/v1/query REST response simultaneously.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Turns block attributes into WP_Query args.
 */
class QueryArgsBuilder {

	/**
	 * Build WP_Query args from a normalized set of block attributes.
	 *
	 * @param array $attributes Block attributes (postType, postCount, orderBy, order, taxonomyFilter, manualIds...).
	 * @param int   $page       1-based page number (ignored for manual selection, which is a fixed list).
	 * @return array WP_Query args.
	 */
	public function build( array $attributes, int $page = 1 ): array {
		$post_type = isset( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';

		// Guard: only public post types are queryable through this builder —
		// prevents probing private/internal post types via attributes or REST params.
		if ( ! $this->is_public_post_type( $post_type ) ) {
			$post_type = 'post';
		}

		// Manual selection (an explicit `manualIds` list) bypasses ordering/
		// pagination entirely -- a general capability of this builder, not
		// currently exercised by either shipped block: Content Showcase's
		// manual-selection UI (manualPost1/2/3) is a separate mechanism
		// layered on top in its own Renderer::query(), not this attribute.
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

		// "Show all posts" (no pagination) ignores `postCount` entirely and
		// uses its own bounded cap instead of a literal `-1` -- an
		// editor-configured toggle is safer than the old unconditional-50
		// cap, but a site with thousands of posts in one Query Grid would
		// still be a real performance/Plugin-Check concern with a truly
		// unlimited query. 200 is generous enough to read as "all" for any
		// normal blog/portfolio while staying bounded.
		if ( ! empty( $attributes['showAllPosts'] ) ) {
			$per_page = 200;
		} else {
			$per_page = isset( $attributes['postCount'] ) ? max( 1, absint( $attributes['postCount'] ) ) : 9;
			// REST callers can request a page size, but never past this hard
			// cap. This only bounds items PER PAGE, not the total pool of
			// matching posts -- `paged` below already lets pagination reach
			// every post via WP_Query's own max_num_pages, regardless of
			// this number.
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

		// Frontend search bar (Query Grid only) -- a visitor-typed query,
		// not an editor-time attribute default, so it is opt-in per request.
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

<?php
/**
 * Maps a WP_Post into the plain array shape shared by PHP templates and
 * the REST fragment response.
 *
 * Why: keeps formatting decisions (excerpt length, image size, date format)
 * in one place instead of duplicated per template.
 * Impact of changing: changes what data every layout template AND the REST
 * response has access to — check both before renaming a key.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Turns a WP_Post into the plain array shape templates/REST responses use.
 */
class PostDataTransformer {

	/**
	 * Transform a single post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array{id:int,title:string,excerpt:string,permalink:string,image:?string,imageAlt:string,author:string,date:string,terms:string[]}
	 */
	public function transform( \WP_Post $post ): array {
		$image_id = get_post_thumbnail_id( $post );

		return array(
			'id'        => $post->ID,
			'title'     => get_the_title( $post ),
			'excerpt'   => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 24 ),
			'permalink' => get_permalink( $post ),
			'image'     => $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : null,
			'imageAlt'  => $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '',
			'author'    => get_the_author_meta( 'display_name', $post->post_author ),
			'date'      => get_the_date( '', $post ),
			'terms'     => $this->primary_terms( $post ),
			'term_ids'  => $this->all_term_ids( $post ),
		);
	}

	/**
	 * Transform a list of posts.
	 *
	 * @param \WP_Post[] $posts Post objects.
	 * @return array[]
	 */
	public function transform_many( array $posts ): array {
		return array_map( array( $this, 'transform' ), $posts );
	}

	/**
	 * All term IDs for a post across all public taxonomies.
	 *
	 * @param \WP_Post $post Post object.
	 * @return int[]
	 */
	private function all_term_ids( \WP_Post $post ): array {
		$taxonomies = get_object_taxonomies( $post->post_type, 'names' );
		$term_ids   = array();
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post, $taxonomy );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_ids[] = (int) $term->term_id;
				}
			}
		}
		return array_unique( $term_ids );
	}

	/**
	 * First taxonomy's term names attached to the post, for a small
	 * badge/label — not exhaustive, just whatever public taxonomy the post
	 * type has that's actually populated on this post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string[]
	 */
	private function primary_terms( \WP_Post $post ): array {
		$taxonomies = get_object_taxonomies( $post->post_type, 'names' );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post, $taxonomy );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				return wp_list_pluck( $terms, 'name' );
			}
		}
		return array();
	}
}

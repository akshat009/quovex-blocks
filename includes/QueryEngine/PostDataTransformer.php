<?php
/**
 * Maps a WP_Post into the plain array shape shared by PHP templates and
 * the REST fragment response. Keeps formatting decisions (excerpt length,
 * image size, date format) in one place.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\QueryEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Turns a WP_Post into the plain array shape templates/REST responses use.
 */
class PostDataTransformer implements TransformerInterface {

	/**
	 * Transform a single post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array{title:string,excerpt:string,permalink:string,image:?string,imageAlt:string,author:string,date:string}
	 */
	public function transform( \WP_Post $post ): array {
		$image_id = get_post_thumbnail_id( $post );

		return array(
			'title'     => get_the_title( $post ),
			'excerpt'   => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 24 ),
			'permalink' => get_permalink( $post ),
			'imageId'   => $image_id ? (int) $image_id : 0,
			'image'     => $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : null,
			'imageAlt'  => $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '',
			'author'    => get_the_author_meta( 'display_name', $post->post_author ),
			'date'      => get_the_date( '', $post ),
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
}

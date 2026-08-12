<?php
/**
 * Contract for turning WP_Post objects into template-ready arrays.
 *
 * Why: same reasoning as ArgsBuilderInterface -- both Renderers only ever
 * call transform()/transform_many() on whatever's injected, never
 * anything PostDataTransformer-specific.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\QueryEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Turns WP_Post objects into the plain arrays every Renderer template uses.
 */
interface TransformerInterface {

	/**
	 * @param \WP_Post $post Post object.
	 * @return array Template-ready post data.
	 */
	public function transform( \WP_Post $post ): array;

	/**
	 * @param \WP_Post[] $posts Post objects.
	 * @return array[]
	 */
	public function transform_many( array $posts ): array;
}

<?php
/**
 * Contract for turning block attributes into WP_Query args. Lets both
 * Renderers depend on this abstraction instead of the concrete
 * QueryArgsBuilder class.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\QueryEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Builds WP_Query-ready args from block attributes.
 */
interface ArgsBuilderInterface {

	/**
	 * @param array $attributes Block attributes.
	 * @param int   $page       Requested page number (1-indexed).
	 * @return array WP_Query-ready args.
	 */
	public function build( array $attributes, int $page = 1 ): array;

	/**
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function is_public_post_type( string $post_type ): bool;
}

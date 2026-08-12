<?php
/**
 * Contract for turning block attributes into WP_Query args.
 *
 * Why: both Renderers only ever call build()/is_public_post_type() on
 * whatever they're given -- they never need QueryArgsBuilder's concrete
 * implementation details. Depending on this interface instead of the
 * concrete class is what lets a Renderer's constructor param count stay
 * meaningful ("I need something that builds query args", not "I need
 * THIS specific class").
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

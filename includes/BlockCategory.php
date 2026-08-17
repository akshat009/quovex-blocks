<?php
/**
 * Registers the "Flux Blocks" custom block category, grouping both blocks
 * together in the inserter instead of falling back to "Text".
 *
 * @package FluxBlocks
 */

namespace FluxBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the "Flux Blocks" inserter category.
 */
class BlockCategory {

	const SLUG = 'flux-blocks';

	/**
	 * Register the WordPress hook.
	 */
	public function init_hooks(): void {
		add_filter( 'block_categories_all', array( $this, 'add_category' ) );
	}

	/**
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public function add_category( array $categories ): array {
		return array_merge(
			array(
				array(
					'slug'  => self::SLUG,
					'title' => __( 'Flux Blocks', 'flux-blocks' ),
					'icon'  => 'layout',
				),
			),
			$categories
		);
	}
}

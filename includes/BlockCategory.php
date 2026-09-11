<?php
/**
 * Registers the "Quovex Blocks" custom block category, grouping both blocks
 * together in the inserter instead of falling back to "Text".
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the "Quovex Blocks" inserter category.
 */
class BlockCategory {

	const SLUG = 'quovex-blocks';

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
					'title' => __( 'Quovex Blocks', 'quovex-blocks' ),
					'icon'  => 'layout',
				),
			),
			$categories
		);
	}
}

<?php
/**
 * Registers the "Flux Blocks" custom block category.
 *
 * Why: both blocks set `"category": "flux-blocks"` in their block.json, but
 * a category slug isn't recognized in the inserter unless something also
 * registers it via this filter — without this, the blocks fall back to
 * WordPress's default "Text" category instead of grouping together.
 * Impact of changing: renaming the slug here without updating both
 * block.json files un-groups the blocks in the inserter.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks;

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

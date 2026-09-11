<?php
/**
 * Plugin bootloader -- the composition root. Wires every class together
 * and registers their WordPress hooks during init; forgetting an
 * `init_hooks()` call here means that class's hooks never fire.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use QuovexBlocks\Cache\CacheInvalidator;
use QuovexBlocks\Blocks\QueryGrid\Rest\QueryController;
use QuovexBlocks\Blocks\QueryGrid\Rest\PaginationSlugController;
use QuovexBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint;

/**
 * Static bootloader that registers all plugin hooks and services.
 */
final class Plugin {

	/**
	 * Wire every class together and register their WordPress hooks.
	 */
	public static function boot(): void {
		( new BlockCategory() )->init_hooks(); // Registers 'Quovex Blocks' category in Gutenberg block inserter.
		( new CacheInvalidator( Services::query_cache() ) )->init_hooks(); // Auto-clears query cache when posts are saved or deleted.
		( new PaginationEndpoint() )->init_hooks(); // Registers `/quovex-page/N/` permalink rewrite endpoint for pagination.

		( new QueryController( Services::query_grid_renderer(), Services::query_args_builder() ) )->init_hooks(); // Registers `/quovex-blocks/v1/query` REST API for live AJAX search & filtering.
		( new PaginationSlugController() )->init_hooks(); // Registers `/quovex-blocks/v1/pagination-slug` REST API for block inspector settings.
	}
}

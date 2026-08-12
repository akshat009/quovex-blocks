<?php
/**
 * Plugin bootloader.
 *
 * Why: Boots every class and registers their WordPress hooks during init.
 * Impact of changing: this is the plugin's composition root — every class
 * gets wired together here; forgetting a `register()` call here means that
 * class's hooks simply never fire.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Cache\CacheInvalidator;
use FluxBlocks\Blocks\QueryGrid\Rest\QueryController;
use FluxBlocks\Blocks\QueryGrid\Rest\PaginationSlugController;
use FluxBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint;

/**
 * Static bootloader that registers all plugin hooks and services.
 */
final class Plugin {

	/**
	 * Wire every class together and register their WordPress hooks.
	 */
	public static function boot(): void {
		( new BlockCategory() )->init_hooks(); // Registers 'Flux Blocks' category in Gutenberg block inserter.
		( new CacheInvalidator( Services::query_cache(), Services::logger() ) )->init_hooks(); // Auto-clears query cache when posts are saved or deleted.
		( new PaginationEndpoint() )->init_hooks(); // Registers `/flux-page/N/` permalink rewrite endpoint for pagination.

		( new QueryController( Services::query_grid_renderer(), Services::query_args_builder(), Services::logger() ) )->init_hooks(); // Registers `/flux-blocks/v1/query` REST API for live AJAX search & filtering.
		( new PaginationSlugController( Services::logger() ) )->init_hooks(); // Registers `/flux-blocks/v1/pagination-slug` REST API for block inspector settings.
	}
}

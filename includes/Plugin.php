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

use FluxBlocks\Query\CacheInvalidator;
use FluxBlocks\Rest\QueryController;
use FluxBlocks\Rest\PaginationSlugController;

/**
 * Static bootloader that registers all plugin hooks and services.
 */
final class Plugin {

	/**
	 * Wire every class together and register their WordPress hooks.
	 */
	public static function boot(): void {
		( new BlockCategory() )->init_hooks();
		( new CacheInvalidator( Services::query_cache() ) )->init_hooks();
		( new PaginationEndpoint() )->init_hooks();

		( new QueryController( Services::query_grid_renderer(), Services::query_args_builder() ) )->init_hooks();
		( new PaginationSlugController() )->init_hooks();
	}
}

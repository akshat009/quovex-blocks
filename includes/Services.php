<?php
/**
 * Service container providing static singletons for the plugin's shared
 * dependencies. Uses MemoizesInstanceTrait so each factory below is one
 * line instead of its own boilerplate.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use QuovexBlocks\QueryEngine\QueryArgsBuilder;
use QuovexBlocks\Cache\QueryCache;
use QuovexBlocks\QueryEngine\PostDataTransformer;
use QuovexBlocks\Blocks\QueryGrid\Render\Renderer as QueryGridRenderer;
use QuovexBlocks\Blocks\ContentShowcase\Render\Renderer as ContentShowcaseRenderer;

/**
 * Service container providing singleton instances of plugin services.
 */
final class Services {

	use MemoizesInstanceTrait;

	/**
	 * @return QueryArgsBuilder
	 */
	public static function query_args_builder(): QueryArgsBuilder {
		return self::once( __METHOD__, fn() => new QueryArgsBuilder() );
	}

	/**
	 * @return QueryCache
	 */
	public static function query_cache(): QueryCache {
		return self::once( __METHOD__, fn() => new QueryCache() );
	}

	/**
	 * @return PostDataTransformer
	 */
	public static function post_data_transformer(): PostDataTransformer {
		return self::once( __METHOD__, fn() => new PostDataTransformer() );
	}

	/**
	 * @return QueryGridRenderer
	 */
	public static function query_grid_renderer(): QueryGridRenderer {
		return self::once(
			__METHOD__,
			fn() => new QueryGridRenderer(
				self::query_args_builder(),
				self::query_cache(),
				self::post_data_transformer()
			)
		);
	}

	/**
	 * @return ContentShowcaseRenderer
	 */
	public static function content_showcase_renderer(): ContentShowcaseRenderer {
		return self::once(
			__METHOD__,
			fn() => new ContentShowcaseRenderer(
				self::query_args_builder(),
				self::query_cache(),
				self::post_data_transformer()
			)
		);
	}
}

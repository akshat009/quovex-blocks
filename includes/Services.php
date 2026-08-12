<?php
/**
 * Service container for Flux Blocks.
 *
 * Provides static singletons for shared dependencies across the plugin
 * (QueryArgsBuilder, QueryCache, PostDataTransformer, Renderers, Logger,
 * etc.). Uses MemoizesInstanceTrait (see that file) so every factory
 * method below is one line instead of each repeating its own
 * "static $instance = null; if (...) {...}" boilerplate.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Query\QueryArgsBuilder;
use FluxBlocks\Query\QueryCache;
use FluxBlocks\Query\PostDataTransformer;
use FluxBlocks\Blocks\QueryGrid\Renderer as QueryGridRenderer;
use FluxBlocks\Blocks\ContentShowcase\Renderer as ContentShowcaseRenderer;
use FluxBlocks\Logger\LoggerInterface;
use FluxBlocks\Logger\FileLogger;
use FluxBlocks\Logger\NullLogger;

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
	 * Which LoggerInterface implementation every caller gets -- FileLogger
	 * only while BOTH WP_DEBUG and WP_DEBUG_LOG are on, NullLogger
	 * otherwise. Checking WP_DEBUG_LOG too (not just WP_DEBUG) matches
	 * WordPress's own convention: WP_DEBUG alone means "show errors on
	 * screen", WP_DEBUG_LOG is the separate flag for "also write to a
	 * file" -- a site with WP_DEBUG on but WP_DEBUG_LOG off does not
	 * expect this plugin to write to the error log either. This is the
	 * ONLY place that decision gets made (see LoggerInterface's docblock
	 * for why callers like CacheInvalidator never check these constants
	 * themselves).
	 *
	 * @return LoggerInterface
	 */
	public static function logger(): LoggerInterface {
		return self::once(
			__METHOD__,
			fn() => self::file_logging_enabled() ? new FileLogger() : new NullLogger()
		);
	}

	/**
	 * @return bool
	 */
	private static function file_logging_enabled(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG
			&& defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
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

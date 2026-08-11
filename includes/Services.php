<?php
/**
 * Service container for Flux Blocks.
 *
 * Provides static singletons for shared dependencies across the plugin
 * (QueryArgsBuilder, QueryCache, PostDataTransformer, Renderers, etc.).
 *
 * @package FluxBlocks
 */

namespace FluxBlocks;

use FluxBlocks\Query\QueryArgsBuilder;
use FluxBlocks\Query\QueryCache;
use FluxBlocks\Query\PostDataTransformer;
use FluxBlocks\Blocks\QueryGrid\Renderer as QueryGridRenderer;
use FluxBlocks\Blocks\FeaturedCptSection\Renderer as FeaturedCptSectionRenderer;
use FluxBlocks\Cache\CacheInterface;
use FluxBlocks\Cache\TransientCache;
use FluxBlocks\Cache\RedisCache;

/**
 * Service container providing singleton instances of plugin services.
 */
final class Services {

	/**
	 * Returns active Cache Driver: RedisCache if external object cache is enabled, else TransientCache.
	 *
	 * @return CacheInterface
	 */
	public static function cache_driver(): CacheInterface {
		static $instance = null;
		if ( null === $instance ) {
			if ( function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache() ) {
				$instance = new RedisCache();
			} else {
				$instance = new TransientCache();
			}
		}
		return $instance;
	}

	/**
	 * @return QueryArgsBuilder
	 */
	public static function query_args_builder(): QueryArgsBuilder {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new QueryArgsBuilder();
		}
		return $instance;
	}

	/**
	 * @return QueryCache
	 */
	public static function query_cache(): QueryCache {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new QueryCache();
		}
		return $instance;
	}

	/**
	 * @return PostDataTransformer
	 */
	public static function post_data_transformer(): PostDataTransformer {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new PostDataTransformer();
		}
		return $instance;
	}

	/**
	 * @return QueryGridRenderer
	 */
	public static function query_grid_renderer(): QueryGridRenderer {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new QueryGridRenderer(
				self::query_args_builder(),
				self::query_cache(),
				self::post_data_transformer()
			);
		}
		return $instance;
	}

	/**
	 * @return FeaturedCptSectionRenderer
	 */
	public static function featured_cpt_section_renderer(): FeaturedCptSectionRenderer {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new FeaturedCptSectionRenderer(
				self::query_args_builder(),
				self::query_cache(),
				self::post_data_transformer()
			);
		}
		return $instance;
	}
}

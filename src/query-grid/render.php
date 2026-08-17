<?php
/**
 * Delegates rendering to FluxBlocks\Blocks\QueryGrid\Render\Renderer --
 * the ONLY place Query Grid's PHP output starts from.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 *
 * @package FluxBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \FluxBlocks\Services::query_grid_renderer()->render( $attributes, $content, $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer::render() escapes all dynamic output internally.

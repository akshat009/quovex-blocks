<?php
/**
 * Delegates rendering to FluxBlocks\Blocks\ContentShowcase\Render\Renderer
 * -- the ONLY place Content Showcase's PHP output starts from.
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

echo \FluxBlocks\Services::content_showcase_renderer()->render( $attributes, $content, $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer::render() escapes all dynamic output internally.

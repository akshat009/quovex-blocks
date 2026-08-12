<?php
/**
 * Delegates rendering to FluxBlocks\Blocks\ContentShowcase\Renderer.
 *
 * Why: keeps this create-block-owned file thin — actual markup/query logic
 * lives in includes/Blocks/ContentShowcase/Renderer.php, hand-written and
 * independent of the block registration mechanics.
 * Impact of changing: this is the ONLY place Content Showcase's PHP output
 * starts from — swapping the renderer class here changes what every
 * instance of this block outputs.
 *
 * The following variables are exposed to this file by WordPress:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @package FluxBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables provided by WordPress block rendering context.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

echo \FluxBlocks\Services::content_showcase_renderer()->render( $attributes, $content, $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer::render() escapes all dynamic output internally.

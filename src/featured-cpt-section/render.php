<?php
/**
 * Delegates rendering to FluxBlocks\Blocks\FeaturedCptSection\Renderer.
 *
 * Why: keeps this create-block-owned file thin — actual markup/query logic
 * lives in includes/, hand-written and independent of the block
 * registration mechanics. See docs/architecture.md.
 * Impact of changing: this is the ONLY place Featured CPT Section's PHP
 * output starts from — swapping the renderer class here changes what every
 * instance of this block outputs.
 *
 * The following variables are exposed to this file by WordPress:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @package FluxBlocks
 */

echo \FluxBlocks\Services::featured_cpt_section_renderer()->render( $attributes, $content, $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer::render() escapes all dynamic output internally.

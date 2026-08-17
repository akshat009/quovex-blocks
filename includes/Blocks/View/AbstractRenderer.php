<?php
/**
 * Shared base class for every block's Renderer (QueryGrid\Render\Renderer,
 * ContentShowcase\Render\Renderer). Provides capture() so neither has to
 * write its own ob_start()/ob_get_clean() pair; render() stays abstract
 * since each block's markup is genuinely different.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base class every block's Renderer extends for HTML view rendering.
 */
abstract class AbstractRenderer {

	/**
	 * Captures $template's echoed output into a string.
	 *
	 * @param callable $template Function that echoes HTML.
	 * @return string Whatever $template printed.
	 */
	protected function capture( callable $template ): string {
		ob_start();
		$template();
		return ob_get_clean();
	}

	/**
	 * Reads a block's active Style Variation slug off its `className`
	 * attribute (`is-style-<slug>`, see block.json's `styles`), falling
	 * back to $fallback if unset or not in $known_layouts.
	 *
	 * @param string   $class_name    Block's className attribute.
	 * @param string[] $known_layouts Every layout slug this block supports.
	 * @param string   $fallback      Fallback when className has no valid match.
	 * @return string Layout slug -- always one of $known_layouts.
	 */
	protected function layout_from_class_name( string $class_name, array $known_layouts, string $fallback ): string {
		if ( preg_match( '/is-style-([a-z-]+)/', $class_name, $matches ) && in_array( $matches[1], $known_layouts, true ) ) {
			return $matches[1];
		}
		return $fallback;
	}

	/**
	 * Renders the block. Signature matches WordPress's dynamic-block
	 * `render_callback` (see block.json's `"render"` key).
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block default content.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	abstract public function render( array $attributes, string $content, \WP_Block $block ): string;
}

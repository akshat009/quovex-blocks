<?php
/**
 * Shared base class for every block's Renderer (QueryGrid\Renderer,
 * ContentShowcase\Renderer, ...).
 *
 * Why an ABSTRACT CLASS and not an interface: an interface could only
 * force every Renderer to HAVE a render() method with the right signature
 * -- it can't share actual working code. capture() below IS real,
 * duplicated-until-now code (every Renderer wrote its own ob_start()/
 * ob_get_clean() pair) -- an abstract class can hand that down to every
 * child for free, while still forcing render() itself to be written
 * per-block (each block's HTML is genuinely different, so render() stays
 * `abstract` -- no shared implementation makes sense for it).
 * Modeled on WordPress core's own pattern for a "family of related
 * handler classes with one shared contract" -- e.g. WP_REST_Controller
 * (WP_REST_Posts_Controller, WP_REST_Users_Controller, ... all extend it)
 * or the Walker class (Walker_Category, Walker_Comment, ... extend it).
 * Impact of changing: both Renderer classes extend this -- a signature
 * change to render() here must be mirrored in every child, and a bug in
 * capture() affects every block's markup output at once.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base class every block's Renderer extends.
 */
abstract class AbstractRenderer {

	/**
	 * Runs $template (which is expected to `echo`/print HTML directly, the
	 * same way any `<?php ... ?>` template block does) and returns
	 * whatever it printed as a string instead of letting it reach the
	 * browser immediately -- the standard WordPress "capture this template
	 * into a variable" pattern, previously duplicated as a raw
	 * ob_start()/ob_get_clean() pair in every Renderer's render() method.
	 *
	 * @param callable $template A function that echoes HTML (usually a `?> ... <?php` block wrapped in a closure).
	 * @return string Whatever $template printed, captured as a string.
	 */
	protected function capture( callable $template ): string {
		ob_start();
		$template();
		return ob_get_clean();
	}

	/**
	 * Every block's Renderer must implement this with the EXACT signature
	 * WordPress's dynamic-block `render_callback` requires -- see
	 * block.json's `"render"` key, which points at a render.php that calls
	 * this method.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block default content.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	abstract public function render( array $attributes, string $content, \WP_Block $block ): string;
}

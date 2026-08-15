<?php
/**
 * Parameter object for HeadingRenderer::render_heading() -- groups the
 * title's color/font values.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\ContentShowcase\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plain data holder for HeadingRenderer::render_heading()'s style inputs.
 */
class HeadingStyle {

	/** @var string */
	public $title_color;

	/** @var string */
	public $accent_color;

	/** @var string */
	public $title_font_family;

	/** @var string */
	public $title_font_weight;

	/**
	 * @param string $title_color       Custom color for title text.
	 * @param string $accent_color      Custom color for accent highlight text.
	 * @param string $title_font_family Custom font-family for title text.
	 * @param string $title_font_weight Custom font-weight for title text.
	 */
	public function __construct( string $title_color = '', string $accent_color = '', string $title_font_family = '', string $title_font_weight = '' ) {
		$this->title_color       = $title_color;
		$this->accent_color      = $accent_color;
		$this->title_font_family = $title_font_family;
		$this->title_font_weight = $title_font_weight;
	}
}

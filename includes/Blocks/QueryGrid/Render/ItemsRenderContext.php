<?php
/**
 * Parameter object for ItemsRenderer::render_items_and_nav() -- groups
 * everything needed to render one page's items + nav.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Blocks\QueryGrid\Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plain data holder for ItemsRenderer::render_items_and_nav()'s inputs.
 */
class ItemsRenderContext {

	/** @var array{items:array[],has_more:bool,total_pages:int} */
	public $result;

	/** @var string */
	public $layout;

	/** @var bool */
	public $is_carousel;

	/** @var string */
	public $pagination_style;

	/** @var int */
	public $carousel_items_per_view;

	/** @var bool */
	public $show_all_no_nav;

	/** @var int */
	public $current_page;

	/** @var string */
	public $current_url;

	/**
	 * @param array  $result                  Query result (see Renderer::query()).
	 * @param string $layout                  Layout slug.
	 * @param bool   $is_carousel             Whether $layout is 'carousel'.
	 * @param string $pagination_style        'numbers' or 'load-more'.
	 * @param int    $carousel_items_per_view Cards visible per carousel "page".
	 * @param bool   $show_all_no_nav         When true, no pagination/load-more nav to render.
	 * @param int    $current_page            1-based current page.
	 * @param string $current_url             The page's own full current URL.
	 */
	public function __construct(
		array $result,
		string $layout,
		bool $is_carousel,
		string $pagination_style,
		int $carousel_items_per_view = 3,
		bool $show_all_no_nav = false,
		int $current_page = 1,
		string $current_url = ''
	) {
		$this->result                  = $result;
		$this->layout                  = $layout;
		$this->is_carousel             = $is_carousel;
		$this->pagination_style        = $pagination_style;
		$this->carousel_items_per_view = $carousel_items_per_view;
		$this->show_all_no_nav         = $show_all_no_nav;
		$this->current_page            = $current_page;
		$this->current_url             = $current_url;
	}
}

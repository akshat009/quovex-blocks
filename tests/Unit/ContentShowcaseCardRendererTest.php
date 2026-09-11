<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Blocks\ContentShowcase\Render\CardRenderer;
use QuovexBlocks\Blocks\ContentShowcase\Render\HotspotRenderer;
use QuovexBlocks\Tests\TestCase;

/**
 * @covers \QuovexBlocks\Blocks\ContentShowcase\Render\CardRenderer
 */
class ContentShowcaseCardRendererTest extends TestCase {

	/** @var CardRenderer */
	private $card_renderer;

	protected function setUp(): void {
		parent::setUp();
		$this->card_renderer = new CardRenderer( new HotspotRenderer() );

		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'wp_get_attachment_image' )->alias(
			function ( $id, $size, $icon, $attr ) {
				$priority = isset( $attr['fetchpriority'] ) ? ' fetchpriority="' . $attr['fetchpriority'] . '"' : '';
				$loading  = isset( $attr['loading'] ) ? ' loading="' . $attr['loading'] . '"' : '';
				return '<img src="img-' . $id . '.jpg"' . $priority . $loading . ' />';
			}
		);
	}

	public function test_renders_featured_plus_list_with_high_fetchpriority_on_hero(): void {
		$items = array(
			array(
				'title'     => 'Featured Item',
				'excerpt'   => 'Excerpt 1',
				'permalink' => 'https://example.com/1',
				'imageId'   => 10,
				'image'     => 'https://example.com/img1.jpg',
				'imageAlt'  => 'Alt 1',
				'date'      => 'Jan 1',
			),
			array(
				'title'     => 'Small Item',
				'excerpt'   => 'Excerpt 2',
				'permalink' => 'https://example.com/2',
				'imageId'   => 11,
				'image'     => 'https://example.com/img2.jpg',
				'imageAlt'  => 'Alt 2',
				'date'      => 'Jan 2',
			),
		);

		$attributes = array(
			'showFeaturedImage'   => true,
			'showFeaturedDate'    => true,
			'showFeaturedExcerpt' => true,
			'showListImage'       => true,
			'showListDate'        => true,
			'showListExcerpt'     => true,
		);

		$html = $this->card_renderer->render_featured_plus_list( $items, 'magazine', $attributes );
		$this->assertStringContainsString( 'Featured Item', $html );
		$this->assertStringContainsString( 'fetchpriority="high"', $html );
		$this->assertStringContainsString( 'Small Item', $html );
	}
}

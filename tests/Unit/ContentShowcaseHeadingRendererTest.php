<?php
/**
 * @package FluxBlocks\Tests
 */

namespace FluxBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use FluxBlocks\Blocks\ContentShowcase\Render\HeadingRenderer;
use FluxBlocks\Blocks\ContentShowcase\Render\HeadingStyle;
use FluxBlocks\Tests\TestCase;

/**
 * @covers \FluxBlocks\Blocks\ContentShowcase\Render\HeadingRenderer
 * @covers \FluxBlocks\Blocks\ContentShowcase\Render\HeadingStyle
 */
class ContentShowcaseHeadingRendererTest extends TestCase {

	/** @var HeadingRenderer */
	private $renderer;

	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new HeadingRenderer();

		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
	}

	public function test_no_accent_wraps_the_whole_heading_in_one_span(): void {
		$html = $this->renderer->render_heading( 'Latest Posts', '', new HeadingStyle() );

		$this->assertSame( '<span>Latest Posts</span>', $html );
	}

	public function test_an_accent_substring_not_present_in_the_heading_is_ignored(): void {
		$html = $this->renderer->render_heading( 'Latest Posts', 'Nowhere', new HeadingStyle() );

		$this->assertSame( '<span>Latest Posts</span>', $html );
	}

	public function test_a_matching_accent_is_split_into_its_own_span(): void {
		$html = $this->renderer->render_heading( 'Latest Posts', 'Latest', new HeadingStyle() );

		$this->assertSame(
			'<span></span><span class="fb-content-showcase__heading-accent">Latest</span><span> Posts</span>',
			$html
		);
	}

	public function test_title_color_and_font_apply_to_both_non_accent_spans_but_not_the_accent_span(): void {
		$style = new HeadingStyle( '#123456', '', 'Georgia', '700' );
		$html  = $this->renderer->render_heading( 'Latest Posts', 'Latest', $style );

		$this->assertSame(
			'<span style="color:#123456;font-family:Georgia;font-weight:700"></span>'
				. '<span class="fb-content-showcase__heading-accent">Latest</span>'
				. '<span style="color:#123456;font-family:Georgia;font-weight:700"> Posts</span>',
			$html
		);
	}

	public function test_accent_color_applies_only_to_the_accent_span(): void {
		$style = new HeadingStyle( '', '#654321' );
		$html  = $this->renderer->render_heading( 'Latest Posts', 'Latest', $style );

		$this->assertStringContainsString( '<span class="fb-content-showcase__heading-accent" style="color:#654321">', $html );
	}

	public function test_build_style_attr_returns_empty_string_when_every_value_is_empty(): void {
		$this->assertSame( '', $this->renderer->build_style_attr( array( 'color' => '', 'font-family' => '' ) ) );
	}

	public function test_build_style_attr_skips_empty_values_but_keeps_set_ones(): void {
		$attr = $this->renderer->build_style_attr(
			array(
				'color'       => '',
				'font-weight' => '600',
			)
		);

		$this->assertSame( ' style="font-weight:600"', $attr );
	}
}

<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Blocks\QueryGrid\Render\StyleBuilder;
use QuovexBlocks\Tests\TestCase;

/**
 * @covers \QuovexBlocks\Blocks\QueryGrid\Render\StyleBuilder
 */
class QueryGridStyleBuilderTest extends TestCase {

	/** @var StyleBuilder */
	private $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new StyleBuilder();

		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'absint' )->alias( fn( $n ) => abs( (int) $n ) );
	}

	public function test_default_colors_are_all_empty_strings(): void {
		foreach ( $this->builder->default_colors() as $value ) {
			$this->assertSame( '', $value );
		}
	}

	public function test_default_typography_is_all_empty_strings(): void {
		foreach ( $this->builder->default_typography() as $value ) {
			$this->assertSame( '', $value );
		}
	}

	public function test_columns_are_always_present_regardless_of_colors_or_typography(): void {
		$style = $this->builder->build_inline_style(
			array(
				'mobile'  => 1,
				'tablet'  => 2,
				'desktop' => 3,
			),
			$this->builder->default_colors(),
			$this->builder->default_typography()
		);

		$this->assertStringContainsString( '--qv-cols-mobile:1;', $style );
		$this->assertStringContainsString( '--qv-cols-tablet:2;', $style );
		$this->assertStringContainsString( '--qv-cols-desktop:3;', $style );
		$this->assertStringContainsString( '--qv-carousel-items:3;', $style );
	}

	public function test_an_empty_color_produces_no_css_custom_property_for_it(): void {
		$style = $this->builder->build_inline_style(
			array(
				'mobile'  => 1,
				'tablet'  => 2,
				'desktop' => 3,
			),
			$this->builder->default_colors(),
			$this->builder->default_typography()
		);

		$this->assertStringNotContainsString( '--qv-title-color', $style );
	}

	public function test_a_set_color_produces_its_css_custom_property(): void {
		$colors               = $this->builder->default_colors();
		$colors['titleColor'] = '#123456';

		$style = $this->builder->build_inline_style(
			array(
				'mobile'  => 1,
				'tablet'  => 2,
				'desktop' => 3,
			),
			$colors,
			$this->builder->default_typography()
		);

		$this->assertStringContainsString( '--qv-title-color:#123456;', $style );
	}

	public function test_a_set_typography_value_produces_its_css_custom_property(): void {
		$typography                             = $this->builder->default_typography();
		$typography['cardTitleFontFamily'] = 'Georgia';
		$typography['cardTitleFontWeight'] = '700';

		$style = $this->builder->build_inline_style(
			array(
				'mobile'  => 1,
				'tablet'  => 2,
				'desktop' => 3,
			),
			$this->builder->default_colors(),
			$typography
		);

		$this->assertStringContainsString( '--qv-card-title-font-family:Georgia;', $style );
		$this->assertStringContainsString( '--qv-card-title-font-weight:700;', $style );
	}
}

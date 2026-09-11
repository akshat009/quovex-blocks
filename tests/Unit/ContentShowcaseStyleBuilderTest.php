<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\Blocks\ContentShowcase\Render\StyleBuilder;
use QuovexBlocks\Tests\TestCase;

/**
 * @covers \QuovexBlocks\Blocks\ContentShowcase\Render\StyleBuilder
 */
class ContentShowcaseStyleBuilderTest extends TestCase {

	/** @var StyleBuilder */
	private $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new StyleBuilder();

		Functions\when( 'esc_attr' )->returnArg();
	}

	public function test_no_matching_attributes_produces_an_empty_string(): void {
		$this->assertSame( '', $this->builder->build_inline_style( array() ) );
	}

	public function test_a_single_set_attribute_produces_its_css_custom_property_with_no_trailing_semicolon(): void {
		$style = $this->builder->build_inline_style( array( 'headingAccentColor' => '#654321' ) );

		$this->assertSame( '--qv-accent:#654321', $style );
	}

	public function test_multiple_set_attributes_are_semicolon_joined_in_property_map_order(): void {
		$style = $this->builder->build_inline_style(
			array(
				'cardTitleColor' => '#111222',
				'cardDateColor'  => '#333444',
			)
		);

		// Declaration ORDER matters here -- it must follow the property
		// map's order (title before date), not the order attributes
		// happened to be passed in the array.
		$this->assertSame( '--qv-card-title-color:#111222;--qv-card-date-color:#333444', $style );
	}

	public function test_an_empty_attribute_value_is_skipped(): void {
		$style = $this->builder->build_inline_style(
			array(
				'cardTitleColor' => '',
				'cardDateColor'  => '#333444',
			)
		);

		$this->assertSame( '--qv-card-date-color:#333444', $style );
	}

	public function test_typography_attributes_produce_their_css_custom_properties(): void {
		$style = $this->builder->build_inline_style(
			array(
				'cardTitleFontFamily' => 'Georgia',
				'cardTitleFontWeight' => '600',
			)
		);

		$this->assertSame( '--qv-card-title-font-family:Georgia;--qv-card-title-font-weight:600', $style );
	}
}

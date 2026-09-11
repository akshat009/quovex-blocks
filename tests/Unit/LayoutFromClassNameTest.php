<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use QuovexBlocks\Blocks\View\AbstractRenderer;
use QuovexBlocks\Tests\TestCase;

/**
 * Tiny concrete fixture -- layout_from_class_name() is `protected`, and
 * AbstractRenderer itself can't be instantiated (render() is abstract).
 */
class LayoutFromClassNameFixture extends AbstractRenderer {

	public function render( array $attributes, string $content, \WP_Block $block ): string {
		return '';
	}

	public function resolve( string $class_name, array $known_layouts, string $fallback ): string {
		return $this->layout_from_class_name( $class_name, $known_layouts, $fallback );
	}
}

/**
 * @covers \QuovexBlocks\Blocks\View\AbstractRenderer
 */
class LayoutFromClassNameTest extends TestCase {

	/** @var LayoutFromClassNameFixture */
	private $renderer;

	/** @var string[] */
	private $known = array( 'grid', 'list', 'masonry', 'carousel' );

	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new LayoutFromClassNameFixture();
	}

	public function test_no_style_class_falls_back_to_the_default(): void {
		$this->assertSame( 'grid', $this->renderer->resolve( '', $this->known, 'grid' ) );
	}

	public function test_a_known_style_class_is_returned(): void {
		$this->assertSame( 'masonry', $this->renderer->resolve( 'is-style-masonry', $this->known, 'grid' ) );
	}

	public function test_a_style_class_among_other_classes_is_still_found(): void {
		$this->assertSame( 'list', $this->renderer->resolve( 'wp-block-quovex-blocks-query-grid is-style-list', $this->known, 'grid' ) );
	}

	/**
	 * Regression test for the audit fix: an is-style-<slug> value NOT in
	 * $known_layouts previously flowed through as-is (any regex match was
	 * trusted blindly) -- now it must fall back to $fallback instead.
	 */
	public function test_an_unrecognized_style_class_falls_back_to_the_default_instead_of_being_trusted(): void {
		$this->assertSame( 'grid', $this->renderer->resolve( 'is-style-totally-made-up', $this->known, 'grid' ) );
	}

	public function test_a_hyphenated_known_layout_slug_is_matched_in_full(): void {
		$this->assertSame(
			'two-thirds',
			$this->renderer->resolve( 'is-style-two-thirds', array( 'magazine', 'split', 'overlay', 'two-thirds' ), 'magazine' )
		);
	}
}

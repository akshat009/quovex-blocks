<?php
/**
 * @package FluxBlocks\Tests
 */

namespace FluxBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use FluxBlocks\Blocks\ContentShowcase\Render\HotspotRenderer;
use FluxBlocks\Tests\TestCase;

/**
 * @covers \FluxBlocks\Blocks\ContentShowcase\Render\HotspotRenderer
 */
class HotspotRendererTest extends TestCase {

	/** @var HotspotRenderer */
	private $renderer;

	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new HotspotRenderer();

		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'absint' )->alias( fn( $val ) => abs( (int) $val ) );
	}

	public function test_returns_empty_string_when_hotspots_disabled(): void {
		$attributes = array(
			'enableHotspots' => false,
			'hotspots'       => array(
				array( 'postIndex' => 0, 'x' => 20, 'y' => 30 ),
			),
		);

		$result = $this->renderer->render_hotspots_for_post( 0, $attributes );
		$this->assertSame( '', $result );
	}

	public function test_renders_hotspots_matching_post_index(): void {
		$attributes = array(
			'enableHotspots' => true,
			'hotspots'       => array(
				array(
					'postIndex' => 0,
					'x'         => 25.5,
					'y'         => 40.0,
					'title'     => 'Spot 1',
					'content'   => 'Content 1',
				),
			),
			'hotspotPinColor' => '#ff0000',
		);

		$result = $this->renderer->render_hotspots_for_post( 0, $attributes );
		$this->assertStringContainsString( 'fb-hotspot-pin', $result );
		$this->assertStringContainsString( '--fb-hotspot-pin-color: #ff0000', $result );
		$this->assertStringContainsString( 'Spot 1', $result );
	}
}

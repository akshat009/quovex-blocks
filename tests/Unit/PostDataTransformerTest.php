<?php
/**
 * @package QuovexBlocks\Tests
 */

namespace QuovexBlocks\Tests\Unit;

use Brain\Monkey\Functions;
use QuovexBlocks\QueryEngine\PostDataTransformer;
use QuovexBlocks\Tests\TestCase;
use WP_Post;

/**
 * @covers \QuovexBlocks\QueryEngine\PostDataTransformer
 */
class PostDataTransformerTest extends TestCase {

	/** @var PostDataTransformer */
	private $transformer;

	protected function setUp(): void {
		parent::setUp();
		$this->transformer = new PostDataTransformer();

		Functions\when( 'get_the_title' )->justReturn( 'A Post Title' );
		Functions\when( 'wp_strip_all_tags' )->returnArg();
		Functions\when( 'wp_trim_words' )->alias( fn( $text ) => $text );
		Functions\when( 'get_the_excerpt' )->justReturn( 'The excerpt.' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/a-post/' );
		Functions\when( 'get_the_author_meta' )->justReturn( 'Jane Doe' );
		Functions\when( 'get_the_date' )->justReturn( 'August 15, 2026' );
	}

	public function test_transform_returns_only_the_keys_templates_and_rest_responses_actually_use(): void {
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );

		$post = new WP_Post( array( 'ID' => 1, 'post_type' => 'post', 'post_author' => 1 ) );
		$data = $this->transformer->transform( $post );

		// Regression test for the audit fix: transform() used to also
		// return 'id', 'terms', and 'term_ids', none of which any template
		// or REST response consumed -- and 'terms'/'term_ids' each ran a
		// full get_object_taxonomies() + get_the_terms() loop on every post
		// to compute a value nothing read.
		$this->assertSame(
			array( 'title', 'excerpt', 'permalink', 'imageId', 'image', 'imageAlt', 'author', 'date' ),
			array_keys( $data )
		);
	}

	public function test_transform_has_no_image_when_the_post_has_no_thumbnail(): void {
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );

		$post = new WP_Post( array( 'ID' => 1, 'post_type' => 'post', 'post_author' => 1 ) );
		$data = $this->transformer->transform( $post );

		$this->assertNull( $data['image'] );
		$this->assertSame( '', $data['imageAlt'] );
	}

	public function test_transform_resolves_the_image_url_and_alt_text_when_a_thumbnail_exists(): void {
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 42 );
		Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://example.com/image-large.jpg' );
		Functions\when( 'get_post_meta' )->justReturn( 'A cat.' );

		$post = new WP_Post( array( 'ID' => 1, 'post_type' => 'post', 'post_author' => 1 ) );
		$data = $this->transformer->transform( $post );

		$this->assertSame( 'https://example.com/image-large.jpg', $data['image'] );
		$this->assertSame( 'A cat.', $data['imageAlt'] );
	}

	public function test_transform_many_maps_transform_over_every_post(): void {
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );

		$posts = array(
			new WP_Post( array( 'ID' => 1, 'post_type' => 'post', 'post_author' => 1 ) ),
			new WP_Post( array( 'ID' => 2, 'post_type' => 'post', 'post_author' => 1 ) ),
		);

		$result = $this->transformer->transform_many( $posts );

		$this->assertCount( 2, $result );
		$this->assertSame( 'A Post Title', $result[0]['title'] );
		$this->assertSame( 'A Post Title', $result[1]['title'] );
	}
}

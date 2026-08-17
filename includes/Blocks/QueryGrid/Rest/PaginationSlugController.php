<?php
/**
 * REST endpoint backing the "Pagination URL Segment" Inspector control in
 * src/query-grid/edit.js. A dedicated route (not core's `/wp/v2/settings`,
 * which requires `manage_options` for both reading and writing) so GET can
 * stay `edit_posts` while POST -- which changes a site-wide URL segment
 * for every Query Grid on the site -- requires `manage_options`, matching
 * core's own Permalinks screen.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint;

/**
 * REST route the Query Grid editor reads/writes the pagination URL slug through.
 */
class PaginationSlugController implements UsesQueryGridNamespace {

	/**
	 * Register the WordPress hook.
	 */
	public function init_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers `GET/POST /flux-blocks/v1/pagination-slug`.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE_,
			'/pagination-slug',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_slug' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_slug' ),
					'permission_callback' => array( $this, 'check_write_permission' ),
					'args'                => array(
						'slug' => array(
							'type'     => 'string',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * `edit_posts` -- the lowest capability that can use the block editor;
	 * reading the slug is low-risk (see this file's docblock).
	 *
	 * @return bool
	 */
	public function check_read_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * `manage_options` -- writing changes a site-wide setting and triggers
	 * a rewrite-rules flush (see this file's docblock for why).
	 *
	 * @return bool
	 */
	public function check_write_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @return \WP_REST_Response
	 */
	public function get_slug() {
		return new \WP_REST_Response( array( 'slug' => PaginationEndpoint::slug() ) );
	}

	/**
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update_slug( \WP_REST_Request $request ) {
		$slug = sanitize_title( (string) $request->get_param( 'slug' ) );
		if ( ! $slug ) {
			$slug = 'flux-page';
		}
		// update_option() only fires update_option_{option} (which
		// PaginationEndpoint::init_hooks() hooks the flush onto) when the
		// value actually changes -- re-saving the same slug doesn't re-flush.
		update_option( PaginationEndpoint::OPTION, $slug );
		return new \WP_REST_Response( array( 'slug' => $slug ) );
	}
}

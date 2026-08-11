<?php
/**
 * REST endpoint backing the "Pagination URL Segment" Inspector control in
 * src/query-grid/edit.js -- lets someone reading/writing this ONE site-wide
 * setting without needing Administrator (`manage_options`) access.
 *
 * Why a dedicated route instead of core's `/wp/v2/settings`: that endpoint
 * (and `register_setting()` generally) enforces `manage_options` for every
 * field it exposes, no per-setting override -- an Editor-role user who can
 * fully edit the post/page a Query Grid block lives on would get a 403
 * trying to change this purely cosmetic URL preference through it. This
 * route uses `edit_posts` instead, matching "if you're allowed to edit a
 * page with this block on it, you're allowed to configure how its
 * pagination URL looks."
 * Impact of changing: `update_slug()` is the ONLY place
 * `PaginationEndpoint::OPTION` should ever be written -- writing it any
 * other way skips this route's sanitization AND its automatic
 * `flush_rewrite_rules()` (triggered by PaginationEndpoint::register()'s
 * `update_option_{option}` hook), leaving pagination links pointing at a
 * URL pattern WordPress does not actually have a rewrite rule for yet.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Rest;

use FluxBlocks\PaginationEndpoint;

/**
 * REST route the Query Grid editor reads/writes the pagination URL slug through.
 */
class PaginationSlugController {

	const NAMESPACE_ = 'flux-blocks/v1';

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
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_slug' ),
					'permission_callback' => array( $this, 'check_permission' ),
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
	 * `edit_posts` (not `manage_options`) -- see this file's docblock for why.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'edit_posts' );
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
		// PaginationEndpoint::register() hooks the rewrite-rules flush
		// onto) when the value actually CHANGES -- saving the same slug
		// twice in a row correctly does not re-flush.
		update_option( PaginationEndpoint::OPTION, $slug );
		return new \WP_REST_Response( array( 'slug' => $slug ) );
	}
}

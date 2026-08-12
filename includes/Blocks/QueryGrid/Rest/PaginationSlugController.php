<?php
/**
 * REST endpoint backing the "Pagination URL Segment" Inspector control in
 * src/query-grid/edit.js.
 *
 * Why a dedicated route instead of core's `/wp/v2/settings`: that endpoint
 * (and `register_setting()` generally) enforces `manage_options` for
 * BOTH reading and writing every field it exposes, no per-method
 * override. Reading the current slug is harmless (it's just displayed in
 * the Inspector so an Editor knows what it's currently set to), but this
 * setting is SITE-WIDE -- it's the URL segment for every Query Grid
 * instance across the whole site, not something scoped to the one block
 * being edited -- so an Editor able to WRITE it could silently change
 * pagination URLs (and trigger a rewrite-rules flush) for every other
 * Query Grid on the site, including ones on pages they don't otherwise
 * have access to. This route therefore splits the two: GET stays
 * `edit_posts` (matches core's own read-friendliness for low-risk data),
 * POST requires `manage_options` -- the same capability WordPress core's
 * own Permalinks settings screen requires for changing site-wide URL
 * structure.
 * Impact of changing: `update_slug()` is the ONLY place
 * `PaginationEndpoint::OPTION` should ever be written -- writing it any
 * other way skips this route's sanitization AND its automatic
 * `flush_rewrite_rules()` (triggered by PaginationEndpoint::register()'s
 * `update_option_{option}` hook), leaving pagination links pointing at a
 * URL pattern WordPress does not actually have a rewrite rule for yet.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluxBlocks\Blocks\QueryGrid\Routing\PaginationEndpoint;
use FluxBlocks\Logger\LoggerInterface;

/**
 * REST route the Query Grid editor reads/writes the pagination URL slug through.
 */
class PaginationSlugController {

	const NAMESPACE_ = 'flux-blocks/v1';

	/** @var LoggerInterface|null */
	private $logger;

	/**
	 * @param LoggerInterface|null $logger Optional logger instance.
	 */
	public function __construct( ?LoggerInterface $logger = null ) {
		$this->logger = $logger;
	}

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
	 * Reading the current slug is low-risk (see this file's docblock) --
	 * `edit_posts` is the lowest capability that can use the block editor
	 * at all.
	 *
	 * @return bool
	 */
	public function check_read_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Writing changes a SITE-WIDE setting (and triggers a rewrite-rules
	 * flush) -- `manage_options`, same as core's own Permalinks settings
	 * screen, not `edit_posts` (see this file's docblock for why).
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
		// PaginationEndpoint::register() hooks the rewrite-rules flush
		// onto) when the value actually CHANGES -- saving the same slug
		// twice in a row correctly does not re-flush.
		update_option( PaginationEndpoint::OPTION, $slug );
		if ( $this->logger ) {
			$this->logger->log( 'Pagination URL segment slug updated to: ' . $slug );
		}
		return new \WP_REST_Response( array( 'slug' => $slug ) );
	}
}

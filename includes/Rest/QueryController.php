<?php
/**
 * REST endpoint used by Query Grid for paginated/filtered fetches after
 * the initial server render.
 *
 * Why: Query Grid isn't tied to the main WP query (it can appear anywhere,
 * multiple times per page), so it needs its own endpoint rather than core's
 * Query Loop "enhanced pagination" (which only works for the main query).
 * Impact of changing: the response shape (`html`/`pagination`/`hasMore`/
 * `totalPages`) is consumed directly by src/query-grid/view.js — keep them
 * in sync.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Rest;

use FluxBlocks\Blocks\QueryGrid\Renderer;
use FluxBlocks\Query\QueryArgsBuilder;

/**
 * REST route Query Grid's frontend calls for paginated/filtered fetches.
 */
class QueryController {

	const NAMESPACE_ = 'flux-blocks/v1';

	/** @var Renderer */
	private $renderer;

	/** @var QueryArgsBuilder */
	private $args_builder;

	/**
	 * @param Renderer         $renderer     Query Grid renderer -- reused for query() + render_items().
	 * @param QueryArgsBuilder $args_builder Used only for its is_public_post_type() permission check.
	 */
	public function __construct( Renderer $renderer, QueryArgsBuilder $args_builder ) {
		$this->renderer     = $renderer;
		$this->args_builder = $args_builder;
	}

	/**
	 * Register the WordPress hook.
	 */
	public function init_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers `GET /flux-blocks/v1/query`.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE_,
			'/query',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'postType' => array(
						'type'     => 'string',
						'required' => true,
					),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
					),
					'perPage'  => array(
						'type'    => 'integer',
						'default' => 9,
					),
					'orderby'  => array(
						'type'    => 'string',
						'default' => 'date',
					),
					'order'    => array(
						'type'    => 'string',
						'default' => 'desc',
					),
					'taxonomy' => array(
						'type'     => 'string',
						'required' => false,
					),
					'terms'    => array(
						'type'     => 'array',
						'required' => false,
						'items'    => array( 'type' => 'integer' ),
					),
					'layout'   => array(
						'type'    => 'string',
						'default' => 'grid',
					),
					'blockId'  => array(
						'type'     => 'string',
						'required' => false,
					),
					'search'   => array(
						'type'     => 'string',
						'required' => false,
					),
					'showAll'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'pageUrl'  => array(
						'type'     => 'string',
						'required' => false,
					),
				),
			)
		);
	}

	/**
	 * Only public (queryable) post types can be requested — same visibility
	 * as the page the block is embedded on, so no auth check beyond that.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public function check_permission( \WP_REST_Request $request ): bool {
		return $this->args_builder->is_public_post_type( (string) $request->get_param( 'postType' ) );
	}

	/**
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle_request( \WP_REST_Request $request ) {
		$layout   = sanitize_key( (string) $request->get_param( 'layout' ) );
		$show_all = (bool) $request->get_param( 'showAll' );

		$attributes = array(
			'postType'       => sanitize_key( (string) $request->get_param( 'postType' ) ),
			'postCount'      => absint( $request->get_param( 'perPage' ) ),
			'orderBy'        => sanitize_key( (string) $request->get_param( 'orderby' ) ),
			'order'          => sanitize_key( (string) $request->get_param( 'order' ) ),
			'taxonomyFilter' => array(
				'taxonomy' => sanitize_key( (string) $request->get_param( 'taxonomy' ) ),
				'terms'    => array_map( 'absint', (array) $request->get_param( 'terms' ) ),
			),
			'search'         => (string) $request->get_param( 'search' ),
			// QueryArgsBuilder ignores `postCount` entirely and uses its own
			// bounded cap when this is set -- see its docblock.
			'showAllPosts'   => $show_all,
		);

		$page = max( 1, absint( $request->get_param( 'page' ) ) );
		// The real page URL the visitor is looking at, sent by view.js
		// (window.location.href) -- see render_pagination()'s docblock for
		// why this can't just fall back to the current REQUEST_URI here
		// like Renderer::render() does (that would build hrefs pointing at
		// THIS REST endpoint, not the actual page).
		$page_url = esc_url_raw( (string) $request->get_param( 'pageUrl' ) );
		$result   = $this->renderer->query( $attributes, $page );

		return new \WP_REST_Response(
			array(
				'html'       => $this->renderer->render_items( $result['items'], $layout ),
				// Pagination is re-rendered server-side for the requested
				// page too -- see Renderer::render_pagination()'s docblock
				// for why it can't be left to a client-only state getter.
				// Show-all mode has no pagination nav at all (see
				// Renderer::render_items_and_nav()), so there is nothing to
				// render here -- an empty slot, not a stale/misleading one.
				'pagination' => $show_all ? '' : $this->renderer->render_pagination( $page, $result['total_pages'], $page_url ),
				'hasMore'    => $result['has_more'],
				'totalPages' => $result['total_pages'],
			)
		);
	}
}

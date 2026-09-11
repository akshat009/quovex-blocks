<?php
/**
 * REST endpoint Query Grid uses for paginated/filtered fetches after the
 * initial server render -- needed because Query Grid isn't tied to the
 * main WP query, unlike core's Query Loop "enhanced pagination". Response
 * shape (`html`/`pagination`/`hasMore`/`totalPages`) is consumed by
 * src/query-grid/view.js -- keep them in sync.
 *
 * @package QuovexBlocks
 */

namespace QuovexBlocks\Blocks\QueryGrid\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use QuovexBlocks\Blocks\QueryGrid\Render\Renderer;
use QuovexBlocks\QueryEngine\QueryArgsBuilder;

/**
 * REST route Query Grid's frontend calls for paginated/filtered fetches.
 */
class QueryController implements UsesQueryGridNamespace {

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
	 * Registers `GET /quovex-blocks/v1/query`.
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
					'postType'             => array(
						'type'     => 'string',
						'required' => true,
					),
					'page'                 => array(
						'type'    => 'integer',
						'default' => 1,
					),
					'perPage'              => array(
						'type'    => 'integer',
						'default' => 9,
					),
					'orderby'              => array(
						'type'    => 'string',
						'default' => 'date',
					),
					'order'                => array(
						'type'    => 'string',
						'default' => 'desc',
					),
					'taxonomy'             => array(
						'type'     => 'string',
						'required' => false,
					),
					'terms'                => array(
						'type'     => 'array',
						'required' => false,
						'items'    => array( 'type' => 'integer' ),
					),
					'layout'               => array(
						'type'    => 'string',
						'default' => 'grid',
					),
					'search'               => array(
						'type'     => 'string',
						'required' => false,
					),
					'showAll'              => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'pageUrl'              => array(
						'type'     => 'string',
						'required' => false,
					),
					'carouselItemsPerView' => array(
						'type'    => 'integer',
						'default' => 3,
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
		$layout                  = sanitize_key( (string) $request->get_param( 'layout' ) );
		$show_all                = (bool) $request->get_param( 'showAll' );
		$carousel_items_per_view = max( 1, absint( $request->get_param( 'carouselItemsPerView' ) ? $request->get_param( 'carouselItemsPerView' ) : 3 ) );

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

		$requested_page = max( 1, absint( $request->get_param( 'page' ) ) );

		// Real page URL the visitor is looking at (sent by view.js) -- can't
		// fall back to REQUEST_URI here, that would point at this endpoint.
		$page_url = esc_url_raw( (string) $request->get_param( 'pageUrl' ) );
		if ( ! empty( $page_url ) && 0 !== strpos( $page_url, home_url() ) ) {
			$page_url = home_url();
		}

		$result = $this->renderer->query( $attributes, $requested_page );
		$page   = min( $requested_page, $result['total_pages'] );

		return new \WP_REST_Response(
			array(
				'html'       => $this->renderer->render_items( $result['items'], $layout, $carousel_items_per_view ),
				// Show-all mode has no pagination nav (see
				// ItemsRenderer::render_items_and_nav()) -- empty, not stale.
				'pagination' => $show_all ? '' : $this->renderer->render_pagination( $page, $result['total_pages'], $page_url ),
				'hasMore'    => $result['has_more'],
				'totalPages' => $result['total_pages'],
			)
		);
	}
}

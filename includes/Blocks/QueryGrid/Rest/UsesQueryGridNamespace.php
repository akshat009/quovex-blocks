<?php
/**
 * Shares the `flux-blocks/v1` REST namespace between Query Grid's two REST
 * controllers. An interface, not a trait -- trait constants need PHP 8.1+,
 * below this plugin's PHP 7.4 minimum.
 *
 * @package FluxBlocks
 */

namespace FluxBlocks\Blocks\QueryGrid\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Provides the shared REST namespace constant.
 */
interface UsesQueryGridNamespace {

	/** The REST namespace every Query Grid REST route registers under. */
	const NAMESPACE_ = 'flux-blocks/v1';
}

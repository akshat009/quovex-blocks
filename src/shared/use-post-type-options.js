/**
 * Editor-side hook: lists every public post type for the CPT picker, via
 * the `core` data store rather than a hardcoded list. Used by both blocks'
 * post type dropdown.
 */
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * @return {{options: Array<{label: string, value: string}>, isResolving: boolean}} Post type options for a SelectControl, plus whether the list is still loading.
 */
export default function usePostTypeOptions() {
	return useSelect( ( select ) => {
		const { getPostTypes, isResolving } = select( coreStore );
		const types = getPostTypes( { per_page: -1 } ) || [];

		const options = types
			.filter( ( type ) => type.viewable && type.slug !== 'attachment' )
			.map( ( type ) => ( {
				label: type.labels?.singular_name || type.slug,
				value: type.slug,
			} ) );

		return {
			options,
			isResolving: isResolving( 'getPostTypes', [ { per_page: -1 } ] ),
		};
	}, [] );
}

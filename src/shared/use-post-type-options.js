/**
 * Editor-side hook: lists every public post type for the CPT picker.
 *
 * Why: the PRD requires letting the editor choose ANY public post type at
 * edit time, not a hardcoded list — this queries the `core` data store
 * (the same source the block editor itself uses) instead of baking in
 * ['post', 'page', ...].
 * Plain-JS equivalent: this replaces a manual
 * `fetch('/wp-json/wp/v2/types').then(r => r.json())` + `useState` +
 * `useEffect` — `useSelect` just re-runs the selector and re-renders
 * whenever the underlying `core` store data changes, no manual wiring.
 * Impact of changing: both Query Grid's and Content Showcase's CPT
 * picker import this — a bug here affects both blocks' post type dropdown.
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

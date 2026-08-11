/**
 * Editor-side hooks: list the taxonomies registered for a given post type,
 * and the terms within a given taxonomy — for the taxonomy-filter control.
 *
 * Why: taxonomyFilter must offer only taxonomies that actually apply to the
 * currently selected CPT — a hardcoded 'category' dropdown would silently
 * do nothing on a CPT that doesn't support categories.
 * Plain-JS equivalent: same idea as usePostTypeOptions — replaces a manual
 * `fetch('/wp-json/wp/v2/taxonomies?type=' + postType)` + state juggling
 * every time `postType` changes.
 * Impact of changing: both blocks' taxonomy filter control depends on this.
 */
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * @param {string} postType Post type slug to find taxonomies for.
 * @return {{taxonomyOptions: Array<{label: string, value: string}>}} Taxonomy options for a SelectControl.
 */
export default function useTaxonomyOptions( postType ) {
	return useSelect(
		( select ) => {
			const taxonomies =
				select( coreStore ).getTaxonomies( {
					type: postType,
					per_page: -1,
				} ) || [];

			const taxonomyOptions = taxonomies
				.filter(
					( taxonomy ) => taxonomy.visibility?.publicly_queryable
				)
				.map( ( taxonomy ) => ( {
					label: taxonomy.name,
					value: taxonomy.slug,
				} ) );

			return { taxonomyOptions };
		},
		[ postType ]
	);
}

/**
 * @param {string} taxonomySlug Taxonomy slug to list terms for (empty = no terms).
 * @return {{termOptions: Array<{label: string, value: number}>}} Term options for a SelectControl.
 */
export function useTermOptions( taxonomySlug ) {
	return useSelect(
		( select ) => {
			if ( ! taxonomySlug ) {
				return { termOptions: [] };
			}

			const terms =
				select( coreStore ).getEntityRecords(
					'taxonomy',
					taxonomySlug,
					{ per_page: -1 }
				) || [];

			return {
				termOptions: terms.map( ( term ) => ( {
					label: term.name,
					value: term.id,
				} ) ),
			};
		},
		[ taxonomySlug ]
	);
}

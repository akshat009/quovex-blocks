/**
 * Editor-side hooks: taxonomies registered for a given post type, and
 * terms within a given taxonomy -- for the taxonomy-filter control used by
 * both blocks. Only taxonomies that actually apply to the selected CPT are
 * listed, not a hardcoded 'category' dropdown.
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
				} ) || [];

			const taxonomyOptions = taxonomies
				.filter(
					( taxonomy ) =>
						taxonomy.visibility?.publicly_queryable ||
						taxonomy.types?.includes( postType )
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

/**
 * "Facet Filters" Inspector panel -- search bar, sidebar placement, and
 * which taxonomies act as filter-pill facets (with optional per-taxonomy
 * heading text).
 */
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	TextControl,
	CheckboxControl,
	BaseControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function FacetFiltersPanel( {
	attributes,
	setAttributes,
	taxonomyOptions,
	facets,
	toggleFacet,
	facetHeadingsValue,
	setFacetHeading,
} ) {
	const {
		showSearch,
		showCategoryFilter,
		showSidebar,
		sidebarSide,
		searchAlign,
		showFilterHeadings,
	} = attributes;

	return (
		<PanelBody
			title={ __( 'Facet Filters', 'quovex-blocks' ) }
			initialOpen={ false }
		>
			{ ( showSearch || showCategoryFilter ) && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Sidebar', 'quovex-blocks' ) }
					help={ __(
						'Off keeps search/filters at the top instead. Only applies when at least one is on.',
						'quovex-blocks'
					) }
					checked={ !! showSidebar }
					onChange={ ( value ) =>
						setAttributes( { showSidebar: value } )
					}
				/>
			) }
			{ ( showSearch || showCategoryFilter ) && showSidebar && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Sidebar Side', 'quovex-blocks' ) }
					value={ sidebarSide }
					options={ [
						{
							label: __( 'Left', 'quovex-blocks' ),
							value: 'left',
						},
						{
							label: __( 'Right', 'quovex-blocks' ),
							value: 'right',
						},
					] }
					onChange={ ( value ) =>
						setAttributes( { sidebarSide: value } )
					}
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show Search', 'quovex-blocks' ) }
				checked={ !! showSearch }
				onChange={ ( value ) => setAttributes( { showSearch: value } ) }
			/>
			{ showSearch && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Search Alignment', 'quovex-blocks' ) }
					value={ searchAlign }
					options={ [
						{
							label: __( 'Left', 'quovex-blocks' ),
							value: 'left',
						},
						{
							label: __( 'Center', 'quovex-blocks' ),
							value: 'center',
						},
						{
							label: __( 'Right', 'quovex-blocks' ),
							value: 'right',
						},
					] }
					onChange={ ( value ) =>
						setAttributes( { searchAlign: value } )
					}
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show Category Filter', 'quovex-blocks' ) }
				checked={ !! showCategoryFilter }
				onChange={ ( value ) =>
					setAttributes( { showCategoryFilter: value } )
				}
			/>
			{ showCategoryFilter && taxonomyOptions.length > 0 && (
				<BaseControl
					id="qv-query-grid-facet-taxonomies"
					__nextHasNoMarginBottom
					label={ __(
						'Filter by these taxonomies',
						'quovex-blocks'
					) }
				>
					{ taxonomyOptions.map( ( taxonomy ) => (
						<CheckboxControl
							key={ taxonomy.value }
							label={ taxonomy.label }
							checked={ facets.includes( taxonomy.value ) }
							onChange={ ( checked ) =>
								toggleFacet( taxonomy.value, checked )
							}
						/>
					) ) }
				</BaseControl>
			) }
			{ showCategoryFilter && taxonomyOptions.length === 0 && (
				<p>
					{ __(
						'This post type has no public taxonomies to filter by.',
						'quovex-blocks'
					) }
				</p>
			) }
			{ showCategoryFilter && facets.length > 0 && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Filter Headings', 'quovex-blocks' ) }
					help={ __(
						'Labels each selected taxonomy above its pills, e.g. "Categories", "Tags".',
						'quovex-blocks'
					) }
					checked={ !! showFilterHeadings }
					onChange={ ( value ) =>
						setAttributes( { showFilterHeadings: value } )
					}
				/>
			) }
			{ showCategoryFilter &&
				showFilterHeadings &&
				facets.map( ( slug ) => {
					const taxonomyLabel =
						taxonomyOptions.find(
							( option ) => option.value === slug
						)?.label || slug;
					return (
						<TextControl
							key={ slug }
							__nextHasNoMarginBottom
							label={ sprintf(
								/* translators: %s: taxonomy label, e.g. "Categories". */
								__( '%s Heading Text', 'quovex-blocks' ),
								taxonomyLabel
							) }
							placeholder={ taxonomyLabel }
							value={ facetHeadingsValue[ slug ] || '' }
							onChange={ ( value ) =>
								setFacetHeading( slug, value )
							}
						/>
					);
				} ) }
		</PanelBody>
	);
}

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
			title={ __( 'Facet Filters', 'flux-blocks' ) }
			initialOpen={ false }
		>
			{ ( showSearch || showCategoryFilter ) && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Sidebar', 'flux-blocks' ) }
					help={ __(
						'Off keeps search/filters at the top instead. Only applies when at least one is on.',
						'flux-blocks'
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
					label={ __( 'Sidebar Side', 'flux-blocks' ) }
					value={ sidebarSide }
					options={ [
						{
							label: __( 'Left', 'flux-blocks' ),
							value: 'left',
						},
						{
							label: __( 'Right', 'flux-blocks' ),
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
				label={ __( 'Show Search', 'flux-blocks' ) }
				checked={ !! showSearch }
				onChange={ ( value ) => setAttributes( { showSearch: value } ) }
			/>
			{ showSearch && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Search Alignment', 'flux-blocks' ) }
					value={ searchAlign }
					options={ [
						{
							label: __( 'Left', 'flux-blocks' ),
							value: 'left',
						},
						{
							label: __( 'Center', 'flux-blocks' ),
							value: 'center',
						},
						{
							label: __( 'Right', 'flux-blocks' ),
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
				label={ __( 'Show Category Filter', 'flux-blocks' ) }
				checked={ !! showCategoryFilter }
				onChange={ ( value ) =>
					setAttributes( { showCategoryFilter: value } )
				}
			/>
			{ showCategoryFilter && taxonomyOptions.length > 0 && (
				<BaseControl
					id="fb-query-grid-facet-taxonomies"
					__nextHasNoMarginBottom
					label={ __( 'Filter by these taxonomies', 'flux-blocks' ) }
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
						'flux-blocks'
					) }
				</p>
			) }
			{ showCategoryFilter && facets.length > 0 && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Filter Headings', 'flux-blocks' ) }
					help={ __(
						'Labels each selected taxonomy above its pills, e.g. "Categories", "Tags".',
						'flux-blocks'
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
								__( '%s Heading Text', 'flux-blocks' ),
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

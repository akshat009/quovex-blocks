/**
 * Query Grid editor UI.
 *
 * Why: lets the editor pick ANY public post type, ordering, a responsive
 * column count, a two-tone heading, which taxonomies act as filter facets,
 * and a full color palette for the section — via grouped Inspector panels
 * matching the reference design. Layout (Grid/List/Masonry/Carousel) is
 * NOT a control here — it is a native block Style Variation
 * (see block.json's `styles`), selected from the editor's own "Styles" tab,
 * so WordPress already provides that UI for free.
 * The canvas itself is a ServerSideRender preview (see below) so it always
 * shows the exact render.php output, not a separate editor-only summary.
 * Plain-JS equivalent: `attributes`/`setAttributes` is the same idea as
 * reading/writing values into a plain JS object then re-rendering the DOM
 * by hand — React (and `useBlockProps`) just automates "re-render whenever
 * this object changes" instead of you calling `render()` yourself.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState, useRef } from '@wordpress/element';
import {
	useBlockProps,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';
/* eslint-disable @wordpress/no-unsafe-wp-apis -- NumberControl is still
   experimental in @wordpress/components but has no stable alternative for
   this UI pattern yet; this opt-in-by-comment is the standard way the block
   editor ecosystem uses it until it stabilizes. */
import {
	SelectControl,
	RangeControl,
	__experimentalNumberControl as NumberControl,
	PanelBody,
	ToggleControl,
	TextControl,
	CheckboxControl,
	BaseControl,
} from '@wordpress/components';
/* eslint-enable @wordpress/no-unsafe-wp-apis */

import usePostTypeOptions from '../shared/use-post-type-options';
import useTaxonomyOptions from '../shared/use-taxonomy-options';

const DEFAULT_COLUMNS = { mobile: 1, tablet: 2, desktop: 3 };
const DEFAULT_COLORS = {
	titleColor: '',
	accentColor: '',
	activeAccent: '',
	disabledNav: '',
	inactivePillBg: '',
	inactivePillText: '',
	metaText: '',
	authorText: '',
};

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		showHeading,
		heading,
		headingAccent,
		showSearch,
		showCategoryFilter,
		facetTaxonomies,
		showSidebar,
		sidebarSide,
		showFilterHeadings,
		facetHeadings,
		searchAlign,
		paginationStyle,
		showAllPosts,
		carouselItemsPerView,
		postType,
		postCount,
		orderBy,
		order,
		columns,
		colors,
		queryId,
	} = attributes;

	const blockProps = useBlockProps();
	const { options: postTypeOptions } = usePostTypeOptions();
	const { taxonomyOptions } = useTaxonomyOptions( postType );

	// The active Style Variation (Grid/List/Masonry/Carousel) isn't a
	// tracked attribute -- WordPress stores it as `is-style-<name>` inside
	// `attributes.className` (see block.json's `styles` + PHP's
	// layout_from_class_name()). Parsed the same way here just to know
	// whether Carousel is selected (which hides pagination-style-only
	// controls below), not to drive any rendering ourselves.
	const currentStyle =
		attributes.className?.match( /is-style-([a-z]+)/ )?.[ 1 ] ?? 'grid';

	// A stable per-instance id, generated once and persisted into the
	// attribute — NOT useInstanceId(), which counts "the Nth instance of
	// this hook mounted this session" and is NOT deterministic across
	// page loads/block reordering, so it can collide with an id already
	// saved elsewhere in the same post. clientId itself IS stable/unique,
	// so a slice of it (with the block-editor's own `-` separators
	// stripped) makes a safe, permanent id once saved into the attribute.
	// Needed so pagination state stays scoped to the right block instance
	// on the frontend.
	useEffect( () => {
		if ( ! queryId ) {
			setAttributes( {
				queryId: `q${ clientId.replace( /-/g, '' ).slice( 0, 10 ) }`,
			} );
		}
	}, [ queryId, clientId, setAttributes ] );

	// The pagination URL segment (/<slug>/2/, see PaginationEndpoint.php)
	// is a SITE-WIDE setting, not a per-block attribute -- the rewrite rule
	// backing it has to be registered with one fixed name before WordPress
	// parses any specific post's blocks, so it can't vary per instance (see
	// that file's docblock). Read/written through a dedicated REST route
	// (not core's /wp/v2/settings, which requires Administrator access) so
	// any Editor-capable user configuring THIS block can also set it.
	const [ paginationSlug, setPaginationSlug ] = useState( null );
	const paginationSlugSaveTimeout = useRef();

	useEffect( () => {
		apiFetch( { path: '/flux-blocks/v1/pagination-slug' } )
			.then( ( res ) => setPaginationSlug( res.slug ) )
			.catch( () => setPaginationSlug( 'flux-page' ) );
	}, [] );

	const updatePaginationSlug = ( value ) => {
		setPaginationSlug( value );
		// Debounced (not saved on every keystroke) -- each save triggers a
		// flush_rewrite_rules() call server-side (see PaginationEndpoint),
		// which is expensive enough that it should only happen once typing
		// actually pauses, not per character.
		clearTimeout( paginationSlugSaveTimeout.current );
		paginationSlugSaveTimeout.current = setTimeout( () => {
			apiFetch( {
				path: '/flux-blocks/v1/pagination-slug',
				method: 'POST',
				data: { slug: value },
			} );
		}, 600 );
	};

	const columnsValue = columns || DEFAULT_COLUMNS;
	const colorsValue = { ...DEFAULT_COLORS, ...colors };
	const setColor = ( key ) => ( value ) =>
		setAttributes( { colors: { ...colorsValue, [ key ]: value ?? '' } } );

	const facets = facetTaxonomies || [];
	const toggleFacet = ( slug, checked ) =>
		setAttributes( {
			facetTaxonomies: checked
				? [ ...facets, slug ]
				: facets.filter( ( t ) => t !== slug ),
		} );

	const facetHeadingsValue = facetHeadings || {};
	const setFacetHeading = ( slug, value ) =>
		setAttributes( {
			facetHeadings: { ...facetHeadingsValue, [ slug ]: value },
		} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Section Heading Settings', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Section Heading', 'flux-blocks' ) }
						checked={ !! showHeading }
						onChange={ ( value ) =>
							setAttributes( { showHeading: value } )
						}
					/>
					{ showHeading && (
						<>
							<TextControl
								__nextHasNoMarginBottom
								label={ __( 'Section Title', 'flux-blocks' ) }
								value={ heading }
								onChange={ ( value ) =>
									setAttributes( { heading: value } )
								}
							/>
							<TextControl
								__nextHasNoMarginBottom
								label={ __(
									'Highlighted Accent Text',
									'flux-blocks'
								) }
								help={ __(
									'Must exactly match a substring of the title above to be colored.',
									'flux-blocks'
								) }
								value={ headingAccent }
								onChange={ ( value ) =>
									setAttributes( { headingAccent: value } )
								}
							/>
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Grid Layout Settings', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Post type', 'flux-blocks' ) }
						value={ postType }
						options={
							postTypeOptions.length
								? postTypeOptions
								: [
										{
											label: __( 'Post', 'flux-blocks' ),
											value: 'post',
										},
								  ]
						}
						onChange={ ( value ) =>
							setAttributes( { postType: value } )
						}
					/>
					{ ! showAllPosts && (
						<RangeControl
							__nextHasNoMarginBottom
							label={ __( 'Posts per page', 'flux-blocks' ) }
							min={ 1 }
							max={ 50 }
							value={ postCount }
							onChange={ ( value ) =>
								setAttributes( { postCount: value } )
							}
						/>
					) }
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Order by', 'flux-blocks' ) }
						value={ `${ orderBy }-${ order }` }
						options={ [
							{
								label: __( 'Newest first', 'flux-blocks' ),
								value: 'date-desc',
							},
							{
								label: __( 'Oldest first', 'flux-blocks' ),
								value: 'date-asc',
							},
							{
								label: __( 'Title A→Z', 'flux-blocks' ),
								value: 'title-asc',
							},
							{
								label: __( 'Title Z→A', 'flux-blocks' ),
								value: 'title-desc',
							},
						] }
						onChange={ ( value ) => {
							const [ newOrderBy, newOrder ] = value.split( '-' );
							setAttributes( {
								orderBy: newOrderBy,
								order: newOrder,
							} );
						} }
					/>
					{ currentStyle !== 'carousel' && ! showAllPosts && (
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Pagination Style', 'flux-blocks' ) }
							value={ paginationStyle }
							options={ [
								{
									label: __( 'Page numbers', 'flux-blocks' ),
									value: 'numbers',
								},
								{
									label: __(
										'Load more button',
										'flux-blocks'
									),
									value: 'load-more',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { paginationStyle: value } )
							}
						/>
					) }
					{ currentStyle !== 'carousel' &&
						! showAllPosts &&
						paginationStyle === 'numbers' && (
							<TextControl
								__nextHasNoMarginBottom
								label={ __(
									'Pagination URL Segment',
									'flux-blocks'
								) }
								help={ __(
									'Site-wide -- shared by every Query Grid block on this site. Shown in the address bar as e.g. /your-value/2/.',
									'flux-blocks'
								) }
								value={ paginationSlug ?? '' }
								onChange={ updatePaginationSlug }
							/>
						) }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show All Posts (No Pagination)',
							'flux-blocks'
						) }
						help={
							currentStyle === 'carousel'
								? __(
										'Cycles through every matching post (up to 200) instead of capping at Posts per page -- Prev/Next still work as normal.',
										'flux-blocks'
								  )
								: __(
										'Shows every matching post on one page (up to 200) instead of paginating. Turns off Posts per page and Pagination Style above.',
										'flux-blocks'
								  )
						}
						checked={ !! showAllPosts }
						onChange={ ( value ) =>
							setAttributes( { showAllPosts: value } )
						}
					/>
					{ currentStyle === 'carousel' && (
						<RangeControl
							__nextHasNoMarginBottom
							label={ __( 'Cards Per Slide', 'flux-blocks' ) }
							help={ __(
								'How many cards show side by side; Next/Prev move a whole group at a time.',
								'flux-blocks'
							) }
							min={ 1 }
							max={ 6 }
							value={ carouselItemsPerView }
							onChange={ ( value ) =>
								setAttributes( { carouselItemsPerView: value } )
							}
						/>
					) }
					<NumberControl
						label={ __( 'Mobile columns', 'flux-blocks' ) }
						min={ 1 }
						max={ 4 }
						value={ columnsValue.mobile }
						onChange={ ( value ) =>
							setAttributes( {
								columns: {
									...columnsValue,
									mobile: Number( value ),
								},
							} )
						}
					/>
					<NumberControl
						label={ __( 'Tablet columns', 'flux-blocks' ) }
						min={ 1 }
						max={ 6 }
						value={ columnsValue.tablet }
						onChange={ ( value ) =>
							setAttributes( {
								columns: {
									...columnsValue,
									tablet: Number( value ),
								},
							} )
						}
					/>
					<NumberControl
						label={ __( 'Desktop columns', 'flux-blocks' ) }
						min={ 1 }
						max={ 6 }
						value={ columnsValue.desktop }
						onChange={ ( value ) =>
							setAttributes( {
								columns: {
									...columnsValue,
									desktop: Number( value ),
								},
							} )
						}
					/>
				</PanelBody>

				<PanelColorSettings
					title={ __( 'Section Header Colors', 'flux-blocks' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: colorsValue.titleColor,
							onChange: setColor( 'titleColor' ),
							label: __( 'Section Title Color', 'flux-blocks' ),
						},
						{
							value: colorsValue.accentColor,
							onChange: setColor( 'accentColor' ),
							label: __(
								'Title Accent Highlight Color',
								'flux-blocks'
							),
						},
						{
							value: colorsValue.subheadingColor,
							onChange: setColor( 'subheadingColor' ),
							label: __( 'Subheading Text Color', 'flux-blocks' ),
						},
					] }
				/>

				<PanelColorSettings
					title={ __( 'Card Content & Link Colors', 'flux-blocks' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: colorsValue.metaText,
							onChange: setColor( 'metaText' ),
							label: __(
								'Date & Meta Text Color',
								'flux-blocks'
							),
						},
						{
							value: colorsValue.authorText,
							onChange: setColor( 'authorText' ),
							label: __( 'Author Name Color', 'flux-blocks' ),
						},
						{
							value: colorsValue.cardTitleColor,
							onChange: setColor( 'cardTitleColor' ),
							label: __( 'Card Title Color', 'flux-blocks' ),
						},
						{
							value: colorsValue.cardTitleHoverColor,
							onChange: setColor( 'cardTitleHoverColor' ),
							label: __(
								'Card Title Link Hover Color',
								'flux-blocks'
							),
						},
						{
							value: colorsValue.cardExcerptColor,
							onChange: setColor( 'cardExcerptColor' ),
							label: __(
								'Card Excerpt Text Color',
								'flux-blocks'
							),
						},
						{
							value: colorsValue.readMoreColor,
							onChange: setColor( 'readMoreColor' ),
							label: __( 'Read More Link Color', 'flux-blocks' ),
						},
						{
							value: colorsValue.readMoreHoverColor,
							onChange: setColor( 'readMoreHoverColor' ),
							label: __(
								'Read More Link Hover Color',
								'flux-blocks'
							),
						},
					] }
				/>

				<PanelColorSettings
					title={ __( 'Filter & Pagination Colors', 'flux-blocks' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: colorsValue.activeAccent,
							onChange: setColor( 'activeAccent' ),
							label: __(
								'Active Pill & Pagination Accent',
								'flux-blocks'
							),
						},
						{
							value: colorsValue.disabledNav,
							onChange: setColor( 'disabledNav' ),
							label: __(
								'Disabled Prev/Next Button Color',
								'flux-blocks'
							),
						},
						{
							value: colorsValue.inactivePillBg,
							onChange: setColor( 'inactivePillBg' ),
							label: __(
								'Inactive Pill Background Color',
								'flux-blocks'
							),
						},
						{
							value: colorsValue.inactivePillText,
							onChange: setColor( 'inactivePillText' ),
							label: __(
								'Inactive Pill Text Color',
								'flux-blocks'
							),
						},
					] }
				/>

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
						onChange={ ( value ) =>
							setAttributes( { showSearch: value } )
						}
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
							label={ __(
								'Filter by these taxonomies',
								'flux-blocks'
							) }
						>
							{ taxonomyOptions.map( ( taxonomy ) => (
								<CheckboxControl
									key={ taxonomy.value }
									label={ taxonomy.label }
									checked={ facets.includes(
										taxonomy.value
									) }
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
							label={ __(
								'Show Filter Headings',
								'flux-blocks'
							) }
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
			</InspectorControls>

			<div { ...blockProps }>
				{ /*
				 * Why ServerSideRender instead of a plain preview: render.php
				 * is the only place Query Grid's markup exists (see
				 * includes/Blocks/QueryGrid/Renderer.php) -- SSR calls the
				 * same PHP render path via the core `wp/v2/block-renderer`
				 * REST endpoint on every attribute change (including a Style Variation
				 * switch, since that changes `attributes.className`), so the
				 * editor always shows exactly what the frontend will. Load
				 * more/pagination/search/filter/carousel behavior does not
				 * run inside this preview -- it is a frontend-only concern,
				 * same as any other dynamic block.
				 */ }
				<ServerSideRender
					block="flux-blocks/query-grid"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

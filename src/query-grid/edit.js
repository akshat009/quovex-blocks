/**
 * Query Grid editor UI -- Inspector panels for post type, ordering,
 * columns, heading, facet taxonomies, and colors. Layout (Grid/List/
 * Masonry/Carousel) is a native Style Variation, not a control here (see
 * block.json's `styles`). The canvas is a ServerSideRender preview of
 * render.php's real output (see below).
 *
 * Orchestrator only -- resolves shared/derived state and hands each
 * Inspector panel to its own component in ./inspector/, mirroring the
 * split already done for the PHP Renderer.
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState, useRef } from '@wordpress/element';
import {
	useBlockProps,
	useSettings,
	InspectorControls,
} from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';

import usePostTypeOptions from '../shared/use-post-type-options';
import useTaxonomyOptions from '../shared/use-taxonomy-options';
import ContentSettingsPanel from './inspector/ContentSettingsPanel';
import SectionHeaderStylesPanel from './inspector/SectionHeaderStylesPanel';
import CardStylesPanel from './inspector/CardStylesPanel';
import FilterPaginationStylesPanel from './inspector/FilterPaginationStylesPanel';
import FacetFiltersPanel from './inspector/FacetFiltersPanel';

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
const DEFAULT_TYPOGRAPHY = {
	headingTitleFontFamily: '',
	headingTitleFontWeight: '',
	cardTitleFontFamily: '',
	cardTitleFontWeight: '',
	cardExcerptFontFamily: '',
	cardExcerptFontWeight: '',
	metaFontFamily: '',
	metaFontWeight: '',
	filterPaginationFontFamily: '',
	filterPaginationFontWeight: '',
};

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		facetTaxonomies,
		facetHeadings,
		postType,
		columns,
		colors,
		typography,
		queryId,
	} = attributes;

	const blockProps = useBlockProps();
	const { options: postTypeOptions } = usePostTypeOptions();
	const { taxonomyOptions } = useTaxonomyOptions( postType );

	// Theme-provided fonts (theme.json), not a hardcoded list -- guaranteed
	// already loaded by the theme itself.
	const [ rawThemeFontFamilies ] = useSettings( 'typography.fontFamilies' );
	// Guards against non-array shapes some themes/no-theme.json return.
	const themeFontFamilies = Array.isArray( rawThemeFontFamilies )
		? rawThemeFontFamilies
		: [];
	const fontFamilyOptions = [
		{ label: __( 'Theme Default', 'flux-blocks' ), value: '' },
		...themeFontFamilies.map( ( font ) => ( {
			label: font.name,
			value: font.fontFamily,
		} ) ),
	];
	const fontWeightOptions = [
		{ label: __( 'Theme Default', 'flux-blocks' ), value: '' },
		{ label: __( 'Normal (400)', 'flux-blocks' ), value: '400' },
		{ label: __( 'Medium (500)', 'flux-blocks' ), value: '500' },
		{ label: __( 'Semi-Bold (600)', 'flux-blocks' ), value: '600' },
		{ label: __( 'Bold (700)', 'flux-blocks' ), value: '700' },
		{ label: __( 'Extra-Bold (800)', 'flux-blocks' ), value: '800' },
	];

	// Style Variation isn't a tracked attribute -- WordPress stores it as
	// `is-style-<name>` in `className`. Parsed here only to know if
	// Carousel is selected (hides pagination-style-only controls below).
	const currentStyle =
		attributes.className?.match( /is-style-([a-z]+)/ )?.[ 1 ] ?? 'grid';

	// Stable per-instance id, persisted once -- NOT useInstanceId(), which
	// isn't deterministic across page loads and can collide. clientId is
	// stable/unique, so a slice of it is safe once saved.
	useEffect( () => {
		if ( ! queryId ) {
			setAttributes( {
				queryId: `q${ clientId.replace( /-/g, '' ).slice( 0, 10 ) }`,
			} );
		}
	}, [ queryId, clientId, setAttributes ] );

	// Pagination URL segment is a site-wide setting (see
	// PaginationEndpoint.php), read/written through a dedicated REST route
	// (not core's /wp/v2/settings, which needs manage_options to even
	// read) -- writing still requires manage_options server-side.
	const [ paginationSlug, setPaginationSlug ] = useState( null );
	const paginationSlugSaveTimeout = useRef();

	useEffect( () => {
		apiFetch( { path: '/flux-blocks/v1/pagination-slug' } )
			.then( ( res ) => setPaginationSlug( res.slug ) )
			.catch( () => setPaginationSlug( 'flux-page' ) );
	}, [] );

	const updatePaginationSlug = ( value ) => {
		setPaginationSlug( value );
		// Debounced -- each save triggers flush_rewrite_rules() server-side,
		// expensive enough to only run once typing pauses.
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

	const typographyValue = { ...DEFAULT_TYPOGRAPHY, ...typography };
	const setTypography = ( key ) => ( value ) =>
		setAttributes( {
			typography: { ...typographyValue, [ key ]: value ?? '' },
		} );

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
				<ContentSettingsPanel
					attributes={ attributes }
					setAttributes={ setAttributes }
					postTypeOptions={ postTypeOptions }
					currentStyle={ currentStyle }
					columnsValue={ columnsValue }
					paginationSlug={ paginationSlug }
					updatePaginationSlug={ updatePaginationSlug }
				/>

				<FacetFiltersPanel
					attributes={ attributes }
					setAttributes={ setAttributes }
					taxonomyOptions={ taxonomyOptions }
					facets={ facets }
					toggleFacet={ toggleFacet }
					facetHeadingsValue={ facetHeadingsValue }
					setFacetHeading={ setFacetHeading }
				/>
			</InspectorControls>

			{ /* Colors/typography render into the native "Styles" tab, matching core blocks' own convention. */ }
			<InspectorControls group="styles">
				<SectionHeaderStylesPanel
					attributes={ attributes }
					colorsValue={ colorsValue }
					setColor={ setColor }
					typographyValue={ typographyValue }
					setTypography={ setTypography }
					fontFamilyOptions={ fontFamilyOptions }
					fontWeightOptions={ fontWeightOptions }
				/>

				<CardStylesPanel
					colorsValue={ colorsValue }
					setColor={ setColor }
					typographyValue={ typographyValue }
					setTypography={ setTypography }
					fontFamilyOptions={ fontFamilyOptions }
					fontWeightOptions={ fontWeightOptions }
				/>

				<FilterPaginationStylesPanel
					colorsValue={ colorsValue }
					setColor={ setColor }
					typographyValue={ typographyValue }
					setTypography={ setTypography }
					fontFamilyOptions={ fontFamilyOptions }
					fontWeightOptions={ fontWeightOptions }
				/>
			</InspectorControls>

			<div
				{ ...blockProps }
				onClickCapture={ ( e ) => {
					if ( e.target.closest( 'a' ) ) {
						e.preventDefault();
					}
				} }
			>
				{ /* ServerSideRender calls the real render.php path so the editor always matches the frontend; interactive behavior (pagination/search/filter/carousel) doesn't run in this preview. */ }
				<ServerSideRender
					block="flux-blocks/query-grid"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

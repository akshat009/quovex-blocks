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
 *
 * This file is the ORCHESTRATOR only -- it resolves shared/derived state
 * (colors, typography, font option lists, the pagination-slug REST round
 * trip, ...) and hands each Inspector panel group to its own component in
 * ./inspector/ (mirrors the same "extract collaborators out of one god
 * file" split already done for the PHP Renderer -- see
 * includes/Blocks/QueryGrid/Render/Renderer.php's docblock). Splitting it
 * doesn't change any UI -- every panel, label, and control is byte-for-byte
 * the same, just moved into its own file.
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

	// Theme-provided font choices (theme.json's settings.typography.fontFamilies)
	// -- deliberately NOT a hardcoded Google Fonts list: those would need to be
	// enqueued/loaded ourselves to actually work, whereas whatever the theme
	// declares here is guaranteed already loaded by the theme itself.
	const [ rawThemeFontFamilies ] = useSettings( 'typography.fontFamilies' );
	// Some themes return this as an array; others (e.g. no theme.json entry)
	// return `false`/`undefined`/a non-array shape -- guard so `.map()` below
	// never blows up regardless of what the active theme provides.
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
	// that file's docblock). Read through a dedicated REST route (not
	// core's /wp/v2/settings, which requires Administrator access for
	// reads too) so any Editor-capable user configuring THIS block can see
	// the current value -- writing it still requires manage_options
	// server-side (see PaginationSlugController.php), since it's a
	// site-wide setting, not scoped to this one block.
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

			{ /*
			 * Color/typography panels render into the native "Styles" tab
			 * (group="styles") instead of "Settings" -- matches both core
			 * blocks' own convention and Content Showcase's existing split,
			 * so appearance controls live where WordPress users already
			 * expect to find them.
			 */ }
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

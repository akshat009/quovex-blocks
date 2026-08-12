/**
 * Content Showcase editor UI.
 *
 * Why TWO <InspectorControls> groups: WordPress's block editor supports
 * splitting the Inspector into "Settings" and "Styles" tabs -- the default
 * (no `group` prop) renders into "Settings", `group="styles"` renders into
 * "Styles". This is native editor behavior, no custom tab UI needed.
 *
 * This file is the ORCHESTRATOR only -- it resolves shared/derived state
 * (font option lists, manual-selection query, hotspot add/update/remove
 * handlers, ...) and hands each Inspector panel group to its own component
 * in ./inspector/ (mirrors the same "extract collaborators out of one god
 * file" split already done for the PHP Renderer -- see
 * includes/Blocks/ContentShowcase/Render/Renderer.php's docblock).
 * Splitting it doesn't change any UI -- every panel, label, and control is
 * byte-for-byte the same, just moved into its own file.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#inspectorcontrols
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useBlockProps, useSettings } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

import usePostTypeOptions from '../shared/use-post-type-options';
import useTaxonomyOptions, {
	useTermOptions,
} from '../shared/use-taxonomy-options';
import SettingsPanel from './inspector/SettingsPanel';
import TaxonomyFilterPanel from './inspector/TaxonomyFilterPanel';
import DisplayElementsPanel from './inspector/DisplayElementsPanel';
import HotspotsPanel from './inspector/HotspotsPanel';
import ExploreButtonPanel from './inspector/ExploreButtonPanel';
import SectionHeaderStylesPanel from './inspector/SectionHeaderStylesPanel';
import CardStylesPanel from './inspector/CardStylesPanel';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object}   props               Properties passed to the function.
 * @param {Object}   props.attributes    Available block attributes.
 * @param {Function} props.setAttributes Function that updates individual attributes.
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		postType,
		hotspots = [],
		taxonomyFilter = {},
		manualPost1 = 0,
		manualPost2 = 0,
		manualPost3 = 0,
	} = attributes;

	const blockProps = useBlockProps();
	const { options: postTypeOptions } = usePostTypeOptions();

	// Theme-provided font choices (theme.json's settings.typography.fontFamilies)
	// -- never a hardcoded Google Fonts list: those would need to be
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

	// Shared with query-grid/edit.js instead of duplicating the same
	// taxonomy/terms fetching inline a second time. Also fixes a real
	// behavior difference from this block's old inline version: that
	// filtered on `tax.visibility?.show_ui` (has an ADMIN UI); this shared
	// hook filters on `publicly_queryable` (can actually be QUERIED on the
	// frontend), which is what a frontend filter control actually needs.
	const { taxonomyOptions } = useTaxonomyOptions( postType );

	const activeTax =
		taxonomyFilter?.taxonomy || ( taxonomyOptions[ 0 ]?.value ?? '' );

	const { termOptions } = useTermOptions( activeTax );

	const isManualActive =
		manualPost1 > 0 || manualPost2 > 0 || manualPost3 > 0;

	const queryPosts = useSelect(
		( select ) => {
			const { getEntityRecords } = select( 'core' );
			const query = {
				per_page: 50,
				status: 'publish',
			};
			if (
				! isManualActive &&
				taxonomyFilter?.taxonomy &&
				taxonomyFilter?.terms?.length
			) {
				query[ taxonomyFilter.taxonomy ] = taxonomyFilter.terms;
			}
			return getEntityRecords( 'postType', postType, query ) || [];
		},
		[ postType, taxonomyFilter, isManualActive ]
	);

	const addHotspot = () => {
		const newHotspot = {
			id: `hs-${ Date.now() }`,
			postIndex: 0,
			x: 50,
			y: 50,
			title: __( 'Hotspot Title', 'flux-blocks' ),
			content: __(
				'Hotspot details or description text…',
				'flux-blocks'
			),
			linkUrl: '',
		};
		setAttributes( { hotspots: [ ...hotspots, newHotspot ] } );
	};

	const updateHotspot = ( index, key, value ) => {
		const updated = [ ...hotspots ];
		updated[ index ] = { ...updated[ index ], [ key ]: value };
		setAttributes( { hotspots: updated } );
	};

	const removeHotspot = ( index ) => {
		const updated = hotspots.filter( ( _, i ) => i !== index );
		setAttributes( { hotspots: updated } );
	};

	return (
		<>
			<SettingsPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
				postTypeOptions={ postTypeOptions }
				queryPosts={ queryPosts }
			/>

			<TaxonomyFilterPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
				activeTax={ activeTax }
				taxonomyOptions={ taxonomyOptions }
				termOptions={ termOptions }
			/>

			<DisplayElementsPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>

			<HotspotsPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
				addHotspot={ addHotspot }
				updateHotspot={ updateHotspot }
				removeHotspot={ removeHotspot }
			/>

			<ExploreButtonPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
				fontFamilyOptions={ fontFamilyOptions }
				fontWeightOptions={ fontWeightOptions }
			/>

			<SectionHeaderStylesPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
				fontFamilyOptions={ fontFamilyOptions }
				fontWeightOptions={ fontWeightOptions }
			/>

			<CardStylesPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
				fontFamilyOptions={ fontFamilyOptions }
				fontWeightOptions={ fontWeightOptions }
			/>

			<div { ...blockProps }>
				{ /*
				 * Why ServerSideRender instead of a plain preview: render.php
				 * is the only place Content Showcase's markup exists (see
				 * includes/Blocks/ContentShowcase/Renderer.php) -- SSR calls
				 * the same PHP render path via the core
				 * `wp/v2/block-renderer` REST endpoint on every
				 * attribute change (including a Style Variation switch,
				 * since that changes `attributes.className`), so the editor
				 * always shows exactly what the frontend will.
				 */ }
				<ServerSideRender
					block="flux-blocks/content-showcase"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

/**
 * Content Showcase editor UI. Two <InspectorControls> groups split into
 * "Settings" (default) and "Styles" (`group="styles"`) tabs -- native
 * editor behavior, no custom tab UI needed.
 *
 * Orchestrator only -- resolves shared/derived state and hands each
 * Inspector panel to its own component in ./inspector/, mirroring the
 * split already done for the PHP Renderer.
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

	// Theme-provided fonts (theme.json), not a hardcoded list -- guaranteed
	// already loaded by the theme itself.
	const [ rawThemeFontFamilies ] = useSettings( 'typography.fontFamilies' );
	// Guards against non-array shapes some themes/no-theme.json return.
	const themeFontFamilies = Array.isArray( rawThemeFontFamilies )
		? rawThemeFontFamilies
		: [];
	const fontFamilyOptions = [
		{ label: __( 'Theme Default', 'quovex-blocks' ), value: '' },
		...themeFontFamilies.map( ( font ) => ( {
			label: font.name,
			value: font.fontFamily,
		} ) ),
	];
	const fontWeightOptions = [
		{ label: __( 'Theme Default', 'quovex-blocks' ), value: '' },
		{ label: __( 'Normal (400)', 'quovex-blocks' ), value: '400' },
		{ label: __( 'Medium (500)', 'quovex-blocks' ), value: '500' },
		{ label: __( 'Semi-Bold (600)', 'quovex-blocks' ), value: '600' },
		{ label: __( 'Bold (700)', 'quovex-blocks' ), value: '700' },
		{ label: __( 'Extra-Bold (800)', 'quovex-blocks' ), value: '800' },
	];

	// Shared with query-grid/edit.js -- filters on `publicly_queryable`
	// (frontend-queryable), not `show_ui` (has an admin UI).
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
			title: __( 'Hotspot Title', 'quovex-blocks' ),
			content: __(
				'Hotspot details or description text…',
				'quovex-blocks'
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

			<div
				{ ...blockProps }
				onClickCapture={ ( e ) => {
					if ( e.target.closest( 'a' ) ) {
						e.preventDefault();
					}
				} }
			>
				{ /* ServerSideRender calls the real render.php path (see includes/Blocks/ContentShowcase/Render/Renderer.php) so the editor always matches the frontend. */ }
				<ServerSideRender
					block="quovex-blocks/content-showcase"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

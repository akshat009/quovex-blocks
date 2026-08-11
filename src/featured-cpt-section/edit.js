/**
 * Featured CPT Section editor UI.
 *
 * Why: mode toggle switches between an explicit post picker (manual) and
 * the shared order/count/taxonomy controls (automatic) — see PRD §5.3.
 * Heading/description are sidebar TextControls, not in-canvas RichText:
 * the canvas shows a live ServerSideRender preview of the real render.php
 * output (which already includes the heading/description), so editing them
 * in-canvas too would show two copies of the same text at once.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
/* eslint-disable @wordpress/no-unsafe-wp-apis -- ToolsPanel/ToolsPanelItem/
   ToggleGroupControl(Option) are still experimental in @wordpress/components
   but have no stable alternative for this UI pattern yet; this opt-in-by-
   comment is the standard way the block editor ecosystem uses them until
   they stabilize. */
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	SelectControl,
	RangeControl,
	FormTokenField,
	TextControl,
} from '@wordpress/components';
/* eslint-enable @wordpress/no-unsafe-wp-apis */
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import usePostTypeOptions from '../shared/use-post-type-options';
import useTaxonomyOptions from '../shared/use-taxonomy-options';

export default function Edit( { attributes, setAttributes } ) {
	const {
		postType,
		selectionMode,
		manualIds,
		postCount,
		orderBy,
		order,
		taxonomyFilter,
		heading,
		description,
	} = attributes;

	const blockProps = useBlockProps();
	const { options: postTypeOptions } = usePostTypeOptions();
	const { taxonomyOptions } = useTaxonomyOptions( postType );

	// Plain-JS equivalent: this is the same idea as a `fetch`-backed
	// autocomplete's results array kept in `useState` — `useSelect` just
	// keeps it wired to the `core` store so it re-runs when `postType`
	// changes, instead of a manual `useEffect` + `fetch` + `setState`.
	const searchResults = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'postType', postType, {
				per_page: 20,
				orderby: 'title',
				order: 'asc',
			} ) || [],
		[ postType ]
	);

	const idToTitle = new Map(
		searchResults.map( ( post ) => [
			post.id,
			post.title?.rendered || `#${ post.id }`,
		] )
	);
	const manualTokens = manualIds.map(
		( id ) => idToTitle.get( id ) || `#${ id }`
	);

	return (
		<>
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Featured Section Settings', 'flux-blocks' ) }
					resetAll={ () =>
						setAttributes( {
							postCount: 6,
							orderBy: 'date',
							order: 'desc',
							taxonomyFilter: { taxonomy: '', terms: [] },
						} )
					}
				>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Heading', 'flux-blocks' ) }
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Description', 'flux-blocks' ) }
						value={ description }
						onChange={ ( value ) =>
							setAttributes( { description: value } )
						}
					/>

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
							setAttributes( { postType: value, manualIds: [] } )
						}
					/>

					<ToggleGroupControl
						label={ __( 'Selection mode', 'flux-blocks' ) }
						value={ selectionMode }
						onChange={ ( value ) =>
							setAttributes( { selectionMode: value } )
						}
						isBlock
						__nextHasNoMarginBottom
					>
						<ToggleGroupControlOption
							value="automatic"
							label={ __( 'Automatic', 'flux-blocks' ) }
						/>
						<ToggleGroupControlOption
							value="manual"
							label={ __( 'Manual', 'flux-blocks' ) }
						/>
					</ToggleGroupControl>

					{ selectionMode === 'manual' ? (
						<FormTokenField
							label={ __( 'Featured items', 'flux-blocks' ) }
							value={ manualTokens }
							suggestions={ searchResults.map(
								( post ) =>
									post.title?.rendered || `#${ post.id }`
							) }
							onChange={ ( tokens ) => {
								const ids = tokens
									.map( ( token ) => {
										const match = searchResults.find(
											( post ) =>
												( post.title?.rendered ||
													`#${ post.id }` ) === token
										);
										return match ? match.id : null;
									} )
									.filter( ( id ) => id !== null );
								setAttributes( { manualIds: ids } );
							} }
						/>
					) : (
						<>
							<ToolsPanelItem
								label={ __( 'Post count', 'flux-blocks' ) }
								hasValue={ () => postCount !== 6 }
								onDeselect={ () =>
									setAttributes( { postCount: 6 } )
								}
								isShownByDefault
							>
								<RangeControl
									__nextHasNoMarginBottom
									label={ __( 'Post count', 'flux-blocks' ) }
									min={ 1 }
									max={ 12 }
									value={ postCount }
									onChange={ ( value ) =>
										setAttributes( { postCount: value } )
									}
								/>
							</ToolsPanelItem>

							<ToolsPanelItem
								label={ __( 'Order by', 'flux-blocks' ) }
								hasValue={ () =>
									orderBy !== 'date' || order !== 'desc'
								}
								onDeselect={ () =>
									setAttributes( {
										orderBy: 'date',
										order: 'desc',
									} )
								}
								isShownByDefault
							>
								<SelectControl
									__nextHasNoMarginBottom
									label={ __( 'Order by', 'flux-blocks' ) }
									value={ `${ orderBy }-${ order }` }
									options={ [
										{
											label: __(
												'Newest first',
												'flux-blocks'
											),
											value: 'date-desc',
										},
										{
											label: __(
												'Oldest first',
												'flux-blocks'
											),
											value: 'date-asc',
										},
										{
											label: __(
												'Title A→Z',
												'flux-blocks'
											),
											value: 'title-asc',
										},
									] }
									onChange={ ( value ) => {
										const [ newOrderBy, newOrder ] =
											value.split( '-' );
										setAttributes( {
											orderBy: newOrderBy,
											order: newOrder,
										} );
									} }
								/>
							</ToolsPanelItem>

							{ taxonomyOptions.length > 0 && (
								<ToolsPanelItem
									label={ __(
										'Filter by taxonomy',
										'flux-blocks'
									) }
									hasValue={ () =>
										!! taxonomyFilter?.taxonomy
									}
									onDeselect={ () =>
										setAttributes( {
											taxonomyFilter: {
												taxonomy: '',
												terms: [],
											},
										} )
									}
								>
									<SelectControl
										__nextHasNoMarginBottom
										label={ __(
											'Taxonomy',
											'flux-blocks'
										) }
										value={ taxonomyFilter?.taxonomy || '' }
										options={ [
											{
												label: __(
													'None',
													'flux-blocks'
												),
												value: '',
											},
											...taxonomyOptions,
										] }
										onChange={ ( value ) =>
											setAttributes( {
												taxonomyFilter: {
													taxonomy: value,
													terms: [],
												},
											} )
										}
									/>
								</ToolsPanelItem>
							) }
						</>
					) }
				</ToolsPanel>
			</InspectorControls>

			<div { ...blockProps }>
				{ /*
				 * Why ServerSideRender: same reasoning as query-grid/edit.js
				 * -- render.php is the single source of truth for markup,
				 * SSR calls it live via the core `wp/v2/block-renderer` REST
				 * endpoint on every attribute change. Carousel nav/load-more
				 * expand/hotspot hover do not run inside this preview --
				 * those are frontend-only Interactivity API behavior.
				 */ }
				<ServerSideRender
					block="flux-blocks/featured-cpt-section"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

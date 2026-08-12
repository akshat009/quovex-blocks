/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element, plus the
 * Inspector sidebar API.
 *
 * Why TWO <InspectorControls> blocks below: WordPress's block editor
 * supports splitting the Inspector into "Settings" and "Styles" tabs --
 * the default (no `group` prop) renders into "Settings", `group="styles"`
 * renders into "Styles". This is native editor behavior, no custom tab UI
 * needed.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#inspectorcontrols
 */
import { useSelect } from '@wordpress/data';
import {
	useBlockProps,
	InspectorControls,
	ColorPalette,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextControl,
	TextareaControl,
	Button,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import usePostTypeOptions from '../shared/use-post-type-options';
import useTaxonomyOptions, {
	useTermOptions,
} from '../shared/use-taxonomy-options';

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
		postCount,
		orderBy,
		order,
		showExploreButton,
		exploreButtonText,
		exploreButtonUrl,
		enableHotspots = false,
		hotspots = [],
		hotspotPinColor = '#d1372d',
		hotspotTooltipBg = '#141414',
		hotspotTooltipTextColor = '#ffffff',
		hotspotPinSize = 28,
		showHeading = false,
		heading = '',
		headingAccent = '',
		headingTitleColor = '',
		headingAccentColor = '',
		subheading = '',
		subheadingColor = '',
		taxonomyFilter = {},
		post1Image = true,
		post1Date = true,
		post1Excerpt = true,
		post2Image = true,
		post2Date = true,
		post2Excerpt = false,
		post3Image = true,
		post3Date = true,
		post3Excerpt = false,
		manualPost1 = 0,
		manualPost2 = 0,
		manualPost3 = 0,
		enableManualSelection = false,
		exploreButtonTextColor = '',
		exploreButtonBgColor = '',
		exploreButtonHoverBg = '',
		cardTitleColor = '',
		cardTitleHoverColor = '',
		cardDateColor = '',
		cardExcerptColor = '',
	} = attributes;

	const blockProps = useBlockProps();
	const { options: postTypeOptions } = usePostTypeOptions();

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
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'flux-blocks' ) }
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
							// Also resets any manually-picked posts back to
							// "Automatic" -- they belong to the OLD post
							// type, so keeping their IDs around after
							// switching would silently show wrong-type
							// items later (see Renderer.php's matching
							// post_type safety check).
							setAttributes( {
								postType: value,
								manualPost1: 0,
								manualPost2: 0,
								manualPost3: 0,
							} )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Number of items', 'flux-blocks' ) }
						min={ 1 }
						max={ 3 }
						value={ postCount }
						onChange={ ( value ) =>
							setAttributes( { postCount: value } )
						}
					/>
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
				</PanelBody>

				<PanelBody
					title={ __( 'Section Heading Settings', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Section Heading', 'flux-blocks' ) }
						checked={ showHeading }
						onChange={ ( value ) =>
							setAttributes( { showHeading: value } )
						}
					/>
					{ showHeading && (
						<>
							<TextControl
								__nextHasNoMarginBottom
								label={ __( 'SECTION TITLE', 'flux-blocks' ) }
								value={ heading }
								onChange={ ( value ) =>
									setAttributes( { heading: value } )
								}
							/>
							<TextControl
								__nextHasNoMarginBottom
								label={ __(
									'HIGHLIGHTED ACCENT TEXT',
									'flux-blocks'
								) }
								value={ headingAccent }
								help={ __(
									'Must exactly match a substring of the title above to be colored.',
									'flux-blocks'
								) }
								onChange={ ( value ) =>
									setAttributes( { headingAccent: value } )
								}
							/>
							<TextareaControl
								__nextHasNoMarginBottom
								label={ __(
									'SECTION SUBHEADING / DESCRIPTION',
									'flux-blocks'
								) }
								value={ subheading }
								help={ __(
									'Subheading text displayed directly below the main title.',
									'flux-blocks'
								) }
								onChange={ ( value ) =>
									setAttributes( { subheading: value } )
								}
							/>
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __(
						'Manual Post Selection & Order',
						'flux-blocks'
					) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Enable Manual Post Selection',
							'flux-blocks'
						) }
						help={ __(
							'Turn ON to manually pick individual posts for each slot. Overrides Category Filter.',
							'flux-blocks'
						) }
						checked={ !! enableManualSelection }
						onChange={ ( val ) =>
							setAttributes( { enableManualSelection: val } )
						}
					/>

					{ enableManualSelection && (
						<>
							<hr
								style={ {
									margin: '14px 0',
									borderColor: '#e5e5e5',
								} }
							/>
							<SelectControl
								__nextHasNoMarginBottom
								label={ __(
									'Post Slot 1 (Main Featured)',
									'flux-blocks'
								) }
								value={ manualPost1 }
								options={ [
									{
										label: __(
											'-- Automatic (Query Default) --',
											'flux-blocks'
										),
										value: 0,
									},
									...queryPosts
										.filter(
											( p ) =>
												p.id !== manualPost2 &&
												p.id !== manualPost3
										)
										.map( ( p ) => ( {
											label:
												p.title?.rendered ||
												`#${ p.id }`,
											value: p.id,
										} ) ),
								] }
								onChange={ ( val ) =>
									setAttributes( {
										manualPost1: parseInt( val, 10 ) || 0,
									} )
								}
							/>

							<SelectControl
								__nextHasNoMarginBottom
								label={ __(
									'Post Slot 2 (Second Post)',
									'flux-blocks'
								) }
								value={ manualPost2 }
								options={ [
									{
										label: __(
											'-- Automatic (Query Default) --',
											'flux-blocks'
										),
										value: 0,
									},
									...queryPosts
										.filter(
											( p ) =>
												p.id !== manualPost1 &&
												p.id !== manualPost3
										)
										.map( ( p ) => ( {
											label:
												p.title?.rendered ||
												`#${ p.id }`,
											value: p.id,
										} ) ),
								] }
								onChange={ ( val ) =>
									setAttributes( {
										manualPost2: parseInt( val, 10 ) || 0,
									} )
								}
							/>

							<SelectControl
								__nextHasNoMarginBottom
								label={ __(
									'Post Slot 3 (Third Post)',
									'flux-blocks'
								) }
								value={ manualPost3 }
								options={ [
									{
										label: __(
											'-- Automatic (Query Default) --',
											'flux-blocks'
										),
										value: 0,
									},
									...queryPosts
										.filter(
											( p ) =>
												p.id !== manualPost1 &&
												p.id !== manualPost2
										)
										.map( ( p ) => ( {
											label:
												p.title?.rendered ||
												`#${ p.id }`,
											value: p.id,
										} ) ),
								] }
								onChange={ ( val ) =>
									setAttributes( {
										manualPost3: parseInt( val, 10 ) || 0,
									} )
								}
							/>
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Category & Taxonomy Filter', 'flux-blocks' ) }
					initialOpen={ false }
				>
					{ enableManualSelection ? (
						<div
							style={ {
								padding: '10px 12px',
								background: '#fcf9e8',
								borderLeft: '3px solid #dba617',
								borderRadius: '3px',
							} }
						>
							<p
								style={ {
									fontSize: '12px',
									color: '#555',
									margin: 0,
									lineHeight: 1.4,
								} }
							>
								{ __(
									'Category filter is automatically disabled because Manual Post Selection is active.',
									'flux-blocks'
								) }
							</p>
						</div>
					) : (
						<>
							<SelectControl
								__nextHasNoMarginBottom
								label={ __( 'Taxonomy Filter', 'flux-blocks' ) }
								value={ activeTax }
								options={ [
									{
										label: __(
											'-- All Taxonomies (No Filter) --',
											'flux-blocks'
										),
										value: '',
									},
									...taxonomyOptions,
								] }
								onChange={ ( taxSlug ) => {
									if ( ! taxSlug ) {
										setAttributes( { taxonomyFilter: {} } );
									} else {
										setAttributes( {
											taxonomyFilter: {
												taxonomy: taxSlug,
												terms: [],
											},
										} );
									}
								} }
							/>

							{ !! activeTax && termOptions.length > 0 && (
								<SelectControl
									__nextHasNoMarginBottom
									label={ __(
										'Filter by Category / Term',
										'flux-blocks'
									) }
									value={ taxonomyFilter?.terms?.[ 0 ] ?? '' }
									options={ [
										{
											label: __(
												'-- All Categories / Terms --',
												'flux-blocks'
											),
											value: '',
										},
										...termOptions,
									] }
									onChange={ ( termId ) => {
										if ( ! termId ) {
											setAttributes( {
												taxonomyFilter: {
													taxonomy: activeTax,
													terms: [],
												},
											} );
										} else {
											setAttributes( {
												taxonomyFilter: {
													taxonomy: activeTax,
													terms: [
														parseInt( termId, 10 ),
													],
												},
											} );
										}
									} }
								/>
							) }
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Display Elements (Per Post)', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<p
						style={ {
							fontWeight: 700,
							fontSize: '11px',
							textTransform: 'uppercase',
							letterSpacing: '0.5px',
							marginBottom: '8px',
							color: '#d1372d',
						} }
					>
						{ __( 'POST 1 (MAIN ITEM)', 'flux-blocks' ) }
					</p>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Image (Post 1)', 'flux-blocks' ) }
						checked={ post1Image }
						onChange={ ( value ) =>
							setAttributes( { post1Image: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Date (Post 1)', 'flux-blocks' ) }
						checked={ post1Date }
						onChange={ ( value ) =>
							setAttributes( { post1Date: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Excerpt (Post 1)', 'flux-blocks' ) }
						checked={ post1Excerpt }
						onChange={ ( value ) =>
							setAttributes( { post1Excerpt: value } )
						}
					/>

					<hr
						style={ { margin: '14px 0', borderColor: '#e5e5e5' } }
					/>

					<p
						style={ {
							fontWeight: 700,
							fontSize: '11px',
							textTransform: 'uppercase',
							letterSpacing: '0.5px',
							marginBottom: '8px',
							color: '#d1372d',
						} }
					>
						{ __( 'POST 2 (SECOND ITEM)', 'flux-blocks' ) }
					</p>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Image (Post 2)', 'flux-blocks' ) }
						checked={ post2Image }
						onChange={ ( value ) =>
							setAttributes( { post2Image: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Date (Post 2)', 'flux-blocks' ) }
						checked={ post2Date }
						onChange={ ( value ) =>
							setAttributes( { post2Date: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Excerpt (Post 2)', 'flux-blocks' ) }
						checked={ post2Excerpt }
						onChange={ ( value ) =>
							setAttributes( { post2Excerpt: value } )
						}
					/>

					<hr
						style={ { margin: '14px 0', borderColor: '#e5e5e5' } }
					/>

					<p
						style={ {
							fontWeight: 700,
							fontSize: '11px',
							textTransform: 'uppercase',
							letterSpacing: '0.5px',
							marginBottom: '8px',
							color: '#d1372d',
						} }
					>
						{ __( 'POST 3 (THIRD ITEM)', 'flux-blocks' ) }
					</p>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Image (Post 3)', 'flux-blocks' ) }
						checked={ post3Image }
						onChange={ ( value ) =>
							setAttributes( { post3Image: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Date (Post 3)', 'flux-blocks' ) }
						checked={ post3Date }
						onChange={ ( value ) =>
							setAttributes( { post3Date: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Excerpt (Post 3)', 'flux-blocks' ) }
						checked={ post3Excerpt }
						onChange={ ( value ) =>
							setAttributes( { post3Excerpt: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Image Hotspots', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Enable Image Hotspots', 'flux-blocks' ) }
						help={ __(
							'Add interactive pins to post images with popup cards.',
							'flux-blocks'
						) }
						checked={ enableHotspots }
						onChange={ ( value ) =>
							setAttributes( { enableHotspots: value } )
						}
					/>
					{ enableHotspots && (
						<>
							<Button
								variant="secondary"
								onClick={ addHotspot }
								style={ {
									width: '100%',
									justifyContent: 'center',
									marginBottom: '1em',
								} }
							>
								{ __( '+ Add Hotspot Pin', 'flux-blocks' ) }
							</Button>

							{ hotspots.map( ( hs, index ) => (
								<PanelBody
									key={ hs.id || index }
									title={ `${ __(
										'Hotspot #',
										'flux-blocks'
									) }${ index + 1 }: ${
										hs.title ||
										__( 'Untitled', 'flux-blocks' )
									}` }
									initialOpen={ false }
								>
									<SelectControl
										__nextHasNoMarginBottom
										label={ __(
											'Target Post Image',
											'flux-blocks'
										) }
										value={ hs.postIndex ?? 0 }
										options={ Array.from(
											{ length: postCount },
											( _, i ) => ( {
												label: `${ __(
													'Post #',
													'flux-blocks'
												) }${ i + 1 }`,
												value: i,
											} )
										) }
										onChange={ ( val ) =>
											updateHotspot(
												index,
												'postIndex',
												parseInt( val, 10 )
											)
										}
									/>
									<RangeControl
										__nextHasNoMarginBottom
										label={ __(
											'Horizontal Position (X %)',
											'flux-blocks'
										) }
										min={ 0 }
										max={ 100 }
										value={ hs.x ?? 50 }
										onChange={ ( val ) =>
											updateHotspot( index, 'x', val )
										}
									/>
									<RangeControl
										__nextHasNoMarginBottom
										label={ __(
											'Vertical Position (Y %)',
											'flux-blocks'
										) }
										min={ 0 }
										max={ 100 }
										value={ hs.y ?? 50 }
										onChange={ ( val ) =>
											updateHotspot( index, 'y', val )
										}
									/>
									<TextControl
										__nextHasNoMarginBottom
										label={ __(
											'Tooltip Title',
											'flux-blocks'
										) }
										value={ hs.title || '' }
										onChange={ ( val ) =>
											updateHotspot( index, 'title', val )
										}
									/>
									<TextareaControl
										__nextHasNoMarginBottom
										label={ __(
											'Tooltip Content',
											'flux-blocks'
										) }
										value={ hs.content || '' }
										onChange={ ( val ) =>
											updateHotspot(
												index,
												'content',
												val
											)
										}
									/>
									<TextControl
										__nextHasNoMarginBottom
										type="url"
										label={ __(
											'Button / Link URL (Optional)',
											'flux-blocks'
										) }
										placeholder="https://"
										value={ hs.linkUrl || '' }
										onChange={ ( val ) =>
											updateHotspot(
												index,
												'linkUrl',
												val
											)
										}
									/>
									<Button
										isDestructive
										variant="tertiary"
										onClick={ () => removeHotspot( index ) }
										style={ { marginTop: '0.5em' } }
									>
										{ __(
											'Remove Hotspot',
											'flux-blocks'
										) }
									</Button>
								</PanelBody>
							) ) }
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Explore Button', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show button', 'flux-blocks' ) }
						checked={ showExploreButton }
						onChange={ ( value ) =>
							setAttributes( { showExploreButton: value } )
						}
					/>
					{ showExploreButton && (
						<>
							<TextControl
								__nextHasNoMarginBottom
								label={ __( 'Button text', 'flux-blocks' ) }
								value={ exploreButtonText }
								onChange={ ( value ) =>
									setAttributes( {
										exploreButtonText: value,
									} )
								}
							/>
							<TextControl
								__nextHasNoMarginBottom
								type="url"
								label={ __( 'Button link', 'flux-blocks' ) }
								placeholder="https://"
								value={ exploreButtonUrl }
								onChange={ ( value ) =>
									setAttributes( { exploreButtonUrl: value } )
								}
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			<InspectorControls group="styles">
				{ showHeading && (
					<PanelBody
						title={ __( 'Section Header Colors', 'flux-blocks' ) }
						initialOpen={ false }
					>
						<div>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __( 'Section Title Color', 'flux-blocks' ) }
							</p>
							<ColorPalette
								value={ headingTitleColor }
								onChange={ ( val ) =>
									setAttributes( {
										headingTitleColor: val || '',
									} )
								}
							/>
						</div>

						<div style={ { marginTop: '1em' } }>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __(
									'Title Accent Highlight Color',
									'flux-blocks'
								) }
							</p>
							<ColorPalette
								value={ headingAccentColor }
								onChange={ ( val ) =>
									setAttributes( {
										headingAccentColor: val || '',
									} )
								}
							/>
						</div>

						<div style={ { marginTop: '1em' } }>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __( 'Subheading Text Color', 'flux-blocks' ) }
							</p>
							<ColorPalette
								value={ subheadingColor }
								onChange={ ( val ) =>
									setAttributes( {
										subheadingColor: val || '',
									} )
								}
							/>
						</div>
					</PanelBody>
				) }

				{ enableHotspots && (
					<PanelBody
						title={ __( 'Hotspot Styling', 'flux-blocks' ) }
						initialOpen={ false }
					>
						<RangeControl
							__nextHasNoMarginBottom
							label={ __(
								'Hotspot Pin Size (px)',
								'flux-blocks'
							) }
							min={ 20 }
							max={ 45 }
							value={ hotspotPinSize }
							onChange={ ( val ) =>
								setAttributes( { hotspotPinSize: val } )
							}
						/>

						<div style={ { marginTop: '1em' } }>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __( 'Pin Color', 'flux-blocks' ) }
							</p>
							<ColorPalette
								value={ hotspotPinColor }
								onChange={ ( val ) =>
									setAttributes( {
										hotspotPinColor: val || '#d1372d',
									} )
								}
							/>
						</div>

						<div style={ { marginTop: '1em' } }>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __( 'Tooltip Background', 'flux-blocks' ) }
							</p>
							<ColorPalette
								value={ hotspotTooltipBg }
								onChange={ ( val ) =>
									setAttributes( {
										hotspotTooltipBg: val || '#141414',
									} )
								}
							/>
						</div>

						<div style={ { marginTop: '1em' } }>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __( 'Tooltip Text Color', 'flux-blocks' ) }
							</p>
							<ColorPalette
								value={ hotspotTooltipTextColor }
								onChange={ ( val ) =>
									setAttributes( {
										hotspotTooltipTextColor:
											val || '#ffffff',
									} )
								}
							/>
						</div>
					</PanelBody>
				) }

				{ showExploreButton && (
					<PanelBody
						title={ __( 'Explore Button Colors', 'flux-blocks' ) }
						initialOpen={ false }
					>
						<div>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __(
									'Button Text & Border Color',
									'flux-blocks'
								) }
							</p>
							<ColorPalette
								value={ exploreButtonTextColor }
								onChange={ ( val ) =>
									setAttributes( {
										exploreButtonTextColor: val || '',
									} )
								}
							/>
						</div>

						<div style={ { marginTop: '1em' } }>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __(
									'Button Background Color',
									'flux-blocks'
								) }
							</p>
							<ColorPalette
								value={ exploreButtonBgColor }
								onChange={ ( val ) =>
									setAttributes( {
										exploreButtonBgColor: val || '',
									} )
								}
							/>
						</div>

						<div style={ { marginTop: '1em' } }>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __(
									'Button Hover Background',
									'flux-blocks'
								) }
							</p>
							<ColorPalette
								value={ exploreButtonHoverBg }
								onChange={ ( val ) =>
									setAttributes( {
										exploreButtonHoverBg: val || '',
									} )
								}
							/>
						</div>
					</PanelBody>
				) }

				<PanelBody
					title={ __( 'Post Card Content Colors', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<div>
						<p style={ { marginBottom: '6px', fontWeight: '500' } }>
							{ __( 'Post Date Color', 'flux-blocks' ) }
						</p>
						<ColorPalette
							value={ cardDateColor }
							onChange={ ( val ) =>
								setAttributes( { cardDateColor: val || '' } )
							}
						/>
					</div>

					<div style={ { marginTop: '1em' } }>
						<p style={ { marginBottom: '6px', fontWeight: '500' } }>
							{ __( 'Post Title Color', 'flux-blocks' ) }
						</p>
						<ColorPalette
							value={ cardTitleColor }
							onChange={ ( val ) =>
								setAttributes( { cardTitleColor: val || '' } )
							}
						/>
					</div>

					<div style={ { marginTop: '1em' } }>
						<p style={ { marginBottom: '6px', fontWeight: '500' } }>
							{ __( 'Post Title Hover Color', 'flux-blocks' ) }
						</p>
						<ColorPalette
							value={ cardTitleHoverColor }
							onChange={ ( val ) =>
								setAttributes( {
									cardTitleHoverColor: val || '',
								} )
							}
						/>
					</div>

					<div style={ { marginTop: '1em' } }>
						<p style={ { marginBottom: '6px', fontWeight: '500' } }>
							{ __( 'Post Excerpt / Text Color', 'flux-blocks' ) }
						</p>
						<ColorPalette
							value={ cardExcerptColor }
							onChange={ ( val ) =>
								setAttributes( { cardExcerptColor: val || '' } )
							}
						/>
					</div>
				</PanelBody>
			</InspectorControls>

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

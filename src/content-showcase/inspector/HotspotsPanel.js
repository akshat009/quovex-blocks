/**
 * "Image Hotspots" (content, Settings tab) + "Hotspot Styling" (Styles tab)
 * Inspector panels -- both belong to the same feature, just split across
 * the editor's native Settings/Styles tabs, so this component renders TWO
 * <InspectorControls> (one default, one `group="styles"`) instead of one.
 */
import { InspectorControls, ColorPalette } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Button,
	SelectControl,
	RangeControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function HotspotsPanel( {
	attributes,
	setAttributes,
	addHotspot,
	updateHotspot,
	removeHotspot,
} ) {
	const {
		postCount,
		enableHotspots = false,
		hotspots = [],
		hotspotPinColor = '#d1372d',
		hotspotTooltipBg = '#141414',
		hotspotTooltipTextColor = '#ffffff',
		hotspotPinSize = 28,
	} = attributes;

	return (
		<>
			<InspectorControls>
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
			</InspectorControls>

			<InspectorControls group="styles">
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
			</InspectorControls>
		</>
	);
}

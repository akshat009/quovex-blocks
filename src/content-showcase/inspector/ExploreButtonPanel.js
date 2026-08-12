/**
 * "Explore Button" (content, Settings tab) + "Explore Button Colors" +
 * "Explore Button Typography" (Styles tab) Inspector panels -- all belong
 * to the same feature, split across the editor's native Settings/Styles
 * tabs, so this component renders TWO <InspectorControls>.
 */
import { InspectorControls, ColorPalette } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ExploreButtonPanel( {
	attributes,
	setAttributes,
	fontFamilyOptions,
	fontWeightOptions,
} ) {
	const {
		showExploreButton,
		exploreButtonText,
		exploreButtonUrl,
		exploreButtonTextColor = '',
		exploreButtonBgColor = '',
		exploreButtonHoverBg = '',
		exploreButtonFontFamily = '',
		exploreButtonFontWeight = '',
	} = attributes;

	return (
		<>
			<InspectorControls>
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

				{ showExploreButton && (
					<PanelBody
						title={ __(
							'Explore Button Typography',
							'flux-blocks'
						) }
						initialOpen={ false }
					>
						<p>
							{ __(
								'Font choices come from the active theme -- picking one here never needs a separate font to be installed or loaded.',
								'flux-blocks'
							) }
						</p>

						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Button Font', 'flux-blocks' ) }
							value={ exploreButtonFontFamily }
							options={ fontFamilyOptions }
							onChange={ ( val ) =>
								setAttributes( {
									exploreButtonFontFamily: val,
								} )
							}
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Button Weight', 'flux-blocks' ) }
							value={ exploreButtonFontWeight }
							options={ fontWeightOptions }
							onChange={ ( val ) =>
								setAttributes( {
									exploreButtonFontWeight: val,
								} )
							}
						/>
					</PanelBody>
				) }
			</InspectorControls>
		</>
	);
}

/**
 * "Color & Border Customization" Inspector panel -- renders into the native
 * "Styles" tab (group="styles") instead of "Settings", matching Query Grid
 * and Content Showcase's color/typography panels.
 */
import { InspectorControls, ColorPalette } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ColorPanel( {
	attributes,
	setAttributes,
	fontFamilyOptions,
	fontWeightOptions,
} ) {
	const {
		borderRadius,
		bgColor,
		textColor,
		textFontFamily,
		textFontWeight,
		accentColor,
		showButton,
		btnBgColor,
		btnTextColor,
		btnHoverBg,
	} = attributes;

	return (
		<InspectorControls group="styles">
			<PanelBody
				title={ __( 'Color & Border Customization', 'quovex-blocks' ) }
				initialOpen={ false }
			>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Border Radius (px)', 'quovex-blocks' ) }
					min={ 0 }
					max={ 30 }
					value={ borderRadius }
					onChange={ ( val ) =>
						setAttributes( { borderRadius: val } )
					}
				/>

				<div style={ { marginTop: '1em' } }>
					<p style={ { marginBottom: '6px', fontWeight: '500' } }>
						{ __( 'Background Color', 'quovex-blocks' ) }
					</p>
					<ColorPalette
						value={ bgColor }
						onChange={ ( val ) =>
							setAttributes( { bgColor: val || '' } )
						}
					/>
				</div>

				<div style={ { marginTop: '1em' } }>
					<p style={ { marginBottom: '6px', fontWeight: '500' } }>
						{ __( 'Text & Title Color', 'quovex-blocks' ) }
					</p>
					<ColorPalette
						value={ textColor }
						onChange={ ( val ) =>
							setAttributes( { textColor: val || '' } )
						}
					/>
				</div>

				<div style={ { marginTop: '1em' } }>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Text & Title Font', 'quovex-blocks' ) }
						help={ __(
							'Comes from the active theme -- picking one never needs a separate font to be installed or loaded.',
							'quovex-blocks'
						) }
						value={ textFontFamily }
						options={ fontFamilyOptions }
						onChange={ ( val ) =>
							setAttributes( { textFontFamily: val } )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Text & Title Weight', 'quovex-blocks' ) }
						value={ textFontWeight }
						options={ fontWeightOptions }
						onChange={ ( val ) =>
							setAttributes( { textFontWeight: val } )
						}
					/>
				</div>

				<div style={ { marginTop: '1em' } }>
					<p style={ { marginBottom: '6px', fontWeight: '500' } }>
						{ __(
							'Accent Border / Highlight Color',
							'quovex-blocks'
						) }
					</p>
					<ColorPalette
						value={ accentColor }
						onChange={ ( val ) =>
							setAttributes( { accentColor: val || '' } )
						}
					/>
				</div>

				{ showButton && (
					<>
						<div style={ { marginTop: '1em' } }>
							<p
								style={ {
									marginBottom: '6px',
									fontWeight: '500',
								} }
							>
								{ __(
									'CTA Button Background',
									'quovex-blocks'
								) }
							</p>
							<ColorPalette
								value={ btnBgColor }
								onChange={ ( val ) =>
									setAttributes( {
										btnBgColor: val || '',
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
									'CTA Button Text Color',
									'quovex-blocks'
								) }
							</p>
							<ColorPalette
								value={ btnTextColor }
								onChange={ ( val ) =>
									setAttributes( {
										btnTextColor: val || '',
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
									'CTA Button Hover Background',
									'quovex-blocks'
								) }
							</p>
							<ColorPalette
								value={ btnHoverBg }
								onChange={ ( val ) =>
									setAttributes( {
										btnHoverBg: val || '',
									} )
								}
							/>
						</div>
					</>
				) }
			</PanelBody>
		</InspectorControls>
	);
}

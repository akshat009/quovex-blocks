/**
 * "Section Header Colors" + "Section Header Typography" Inspector panels
 * (Styles tab) -- only shown when the section heading itself is on.
 */
import { ColorPalette, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function SectionHeaderStylesPanel( {
	attributes,
	setAttributes,
	fontFamilyOptions,
	fontWeightOptions,
} ) {
	const {
		showHeading = false,
		headingTitleColor = '',
		headingAccentColor = '',
		headingTitleFontFamily = '',
		headingTitleFontWeight = '',
		subheadingColor = '',
		subheadingFontFamily = '',
		subheadingFontWeight = '',
	} = attributes;

	if ( ! showHeading ) {
		return null;
	}

	return (
		<InspectorControls group="styles">
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
						{ __( 'Title Accent Highlight Color', 'flux-blocks' ) }
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

			<PanelBody
				title={ __( 'Section Header Typography', 'flux-blocks' ) }
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
					label={ __( 'Section Title Font', 'flux-blocks' ) }
					value={ headingTitleFontFamily }
					options={ fontFamilyOptions }
					onChange={ ( val ) =>
						setAttributes( {
							headingTitleFontFamily: val,
						} )
					}
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Section Title Weight', 'flux-blocks' ) }
					value={ headingTitleFontWeight }
					options={ fontWeightOptions }
					onChange={ ( val ) =>
						setAttributes( {
							headingTitleFontWeight: val,
						} )
					}
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Subheading Font', 'flux-blocks' ) }
					value={ subheadingFontFamily }
					options={ fontFamilyOptions }
					onChange={ ( val ) =>
						setAttributes( {
							subheadingFontFamily: val,
						} )
					}
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Subheading Weight', 'flux-blocks' ) }
					value={ subheadingFontWeight }
					options={ fontWeightOptions }
					onChange={ ( val ) =>
						setAttributes( {
							subheadingFontWeight: val,
						} )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);
}

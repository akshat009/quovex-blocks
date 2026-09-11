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
				title={ __( 'Section Header Colors', 'quovex-blocks' ) }
				initialOpen={ false }
			>
				<div>
					<p
						style={ {
							marginBottom: '6px',
							fontWeight: '500',
						} }
					>
						{ __( 'Section Title Color', 'quovex-blocks' ) }
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
							'quovex-blocks'
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
						{ __( 'Subheading Text Color', 'quovex-blocks' ) }
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
				title={ __( 'Section Header Typography', 'quovex-blocks' ) }
				initialOpen={ false }
			>
				<p>
					{ __(
						'Font choices come from the active theme -- picking one here never needs a separate font to be installed or loaded.',
						'quovex-blocks'
					) }
				</p>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Section Title Font', 'quovex-blocks' ) }
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
					label={ __( 'Section Title Weight', 'quovex-blocks' ) }
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
					label={ __( 'Subheading Font', 'quovex-blocks' ) }
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
					label={ __( 'Subheading Weight', 'quovex-blocks' ) }
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

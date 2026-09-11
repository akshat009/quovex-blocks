/**
 * "Section Header Colors" + "Section Header Typography" Inspector panels.
 */
import { PanelColorSettings } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function SectionHeaderStylesPanel( {
	attributes,
	colorsValue,
	setColor,
	typographyValue,
	setTypography,
	fontFamilyOptions,
	fontWeightOptions,
} ) {
	// No heading is rendered when this is off (see ContentSettingsPanel's
	// "Show Section Heading" toggle) -- hide controls for text that isn't
	// on the page.
	if ( ! attributes.showHeading ) {
		return null;
	}

	return (
		<>
			<PanelColorSettings
				title={ __( 'Section Header Colors', 'quovex-blocks' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: colorsValue.titleColor,
						onChange: setColor( 'titleColor' ),
						label: __( 'Section Title Color', 'quovex-blocks' ),
					},
					{
						value: colorsValue.accentColor,
						onChange: setColor( 'accentColor' ),
						label: __(
							'Title Accent Highlight Color',
							'quovex-blocks'
						),
					},
					{
						value: colorsValue.subheadingColor,
						onChange: setColor( 'subheadingColor' ),
						label: __( 'Subheading Text Color', 'quovex-blocks' ),
					},
				] }
			/>

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
					value={ typographyValue.headingTitleFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'headingTitleFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Section Title Weight', 'quovex-blocks' ) }
					value={ typographyValue.headingTitleFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'headingTitleFontWeight' ) }
				/>
			</PanelBody>
		</>
	);
}

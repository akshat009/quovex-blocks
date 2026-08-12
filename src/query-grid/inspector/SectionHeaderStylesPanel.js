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
	// No heading is rendered at all when this is off (see
	// ContentSettingsPanel's "Show Section Heading" toggle) -- showing
	// color/font controls for text that isn't even on the page would just
	// be confusing, same as Content Showcase's equivalent panel.
	if ( ! attributes.showHeading ) {
		return null;
	}

	return (
		<>
			<PanelColorSettings
				title={ __( 'Section Header Colors', 'flux-blocks' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: colorsValue.titleColor,
						onChange: setColor( 'titleColor' ),
						label: __( 'Section Title Color', 'flux-blocks' ),
					},
					{
						value: colorsValue.accentColor,
						onChange: setColor( 'accentColor' ),
						label: __(
							'Title Accent Highlight Color',
							'flux-blocks'
						),
					},
					{
						value: colorsValue.subheadingColor,
						onChange: setColor( 'subheadingColor' ),
						label: __( 'Subheading Text Color', 'flux-blocks' ),
					},
				] }
			/>

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
					value={ typographyValue.headingTitleFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'headingTitleFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Section Title Weight', 'flux-blocks' ) }
					value={ typographyValue.headingTitleFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'headingTitleFontWeight' ) }
				/>
			</PanelBody>
		</>
	);
}

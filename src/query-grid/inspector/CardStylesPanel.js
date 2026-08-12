/**
 * "Card Content & Link Colors" + "Card Typography" Inspector panels.
 */
import { PanelColorSettings } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function CardStylesPanel( {
	colorsValue,
	setColor,
	typographyValue,
	setTypography,
	fontFamilyOptions,
	fontWeightOptions,
} ) {
	return (
		<>
			<PanelColorSettings
				title={ __( 'Card Content & Link Colors', 'flux-blocks' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: colorsValue.metaText,
						onChange: setColor( 'metaText' ),
						label: __( 'Date & Meta Text Color', 'flux-blocks' ),
					},
					{
						value: colorsValue.authorText,
						onChange: setColor( 'authorText' ),
						label: __( 'Author Name Color', 'flux-blocks' ),
					},
					{
						value: colorsValue.cardTitleColor,
						onChange: setColor( 'cardTitleColor' ),
						label: __( 'Card Title Color', 'flux-blocks' ),
					},
					{
						value: colorsValue.cardTitleHoverColor,
						onChange: setColor( 'cardTitleHoverColor' ),
						label: __(
							'Card Title Link Hover Color',
							'flux-blocks'
						),
					},
					{
						value: colorsValue.cardExcerptColor,
						onChange: setColor( 'cardExcerptColor' ),
						label: __( 'Card Excerpt Text Color', 'flux-blocks' ),
					},
					{
						value: colorsValue.readMoreColor,
						onChange: setColor( 'readMoreColor' ),
						label: __( 'Read More Link Color', 'flux-blocks' ),
					},
					{
						value: colorsValue.readMoreHoverColor,
						onChange: setColor( 'readMoreHoverColor' ),
						label: __(
							'Read More Link Hover Color',
							'flux-blocks'
						),
					},
				] }
			/>

			<PanelBody
				title={ __( 'Card Typography', 'flux-blocks' ) }
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
					label={ __( 'Card Title Font', 'flux-blocks' ) }
					value={ typographyValue.cardTitleFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'cardTitleFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Card Title Weight', 'flux-blocks' ) }
					value={ typographyValue.cardTitleFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'cardTitleFontWeight' ) }
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Card Excerpt Font', 'flux-blocks' ) }
					value={ typographyValue.cardExcerptFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'cardExcerptFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Card Excerpt Weight', 'flux-blocks' ) }
					value={ typographyValue.cardExcerptFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'cardExcerptFontWeight' ) }
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Date & Author Font', 'flux-blocks' ) }
					value={ typographyValue.metaFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'metaFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Date & Author Weight', 'flux-blocks' ) }
					value={ typographyValue.metaFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'metaFontWeight' ) }
				/>
			</PanelBody>
		</>
	);
}

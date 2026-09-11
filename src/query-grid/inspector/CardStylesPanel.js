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
				title={ __( 'Card Content & Link Colors', 'quovex-blocks' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: colorsValue.metaText,
						onChange: setColor( 'metaText' ),
						label: __( 'Date & Meta Text Color', 'quovex-blocks' ),
					},
					{
						value: colorsValue.authorText,
						onChange: setColor( 'authorText' ),
						label: __( 'Author Name Color', 'quovex-blocks' ),
					},
					{
						value: colorsValue.cardTitleColor,
						onChange: setColor( 'cardTitleColor' ),
						label: __( 'Card Title Color', 'quovex-blocks' ),
					},
					{
						value: colorsValue.cardTitleHoverColor,
						onChange: setColor( 'cardTitleHoverColor' ),
						label: __(
							'Card Title Link Hover Color',
							'quovex-blocks'
						),
					},
					{
						value: colorsValue.cardExcerptColor,
						onChange: setColor( 'cardExcerptColor' ),
						label: __( 'Card Excerpt Text Color', 'quovex-blocks' ),
					},
					{
						value: colorsValue.readMoreColor,
						onChange: setColor( 'readMoreColor' ),
						label: __( 'Read More Link Color', 'quovex-blocks' ),
					},
					{
						value: colorsValue.readMoreHoverColor,
						onChange: setColor( 'readMoreHoverColor' ),
						label: __(
							'Read More Link Hover Color',
							'quovex-blocks'
						),
					},
				] }
			/>

			<PanelBody
				title={ __( 'Card Typography', 'quovex-blocks' ) }
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
					label={ __( 'Card Title Font', 'quovex-blocks' ) }
					value={ typographyValue.cardTitleFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'cardTitleFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Card Title Weight', 'quovex-blocks' ) }
					value={ typographyValue.cardTitleFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'cardTitleFontWeight' ) }
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Card Excerpt Font', 'quovex-blocks' ) }
					value={ typographyValue.cardExcerptFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'cardExcerptFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Card Excerpt Weight', 'quovex-blocks' ) }
					value={ typographyValue.cardExcerptFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'cardExcerptFontWeight' ) }
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Date & Author Font', 'quovex-blocks' ) }
					value={ typographyValue.metaFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'metaFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Date & Author Weight', 'quovex-blocks' ) }
					value={ typographyValue.metaFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'metaFontWeight' ) }
				/>
			</PanelBody>
		</>
	);
}

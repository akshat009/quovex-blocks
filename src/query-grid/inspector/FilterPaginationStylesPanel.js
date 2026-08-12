/**
 * "Filter & Pagination Colors" + "Filter & Pagination Typography" Inspector
 * panels -- covers taxonomy filter pills AND page-number/Prev-Next/Load-more
 * controls, which share one color/font set (see style.scss).
 */
import { PanelColorSettings } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function FilterPaginationStylesPanel( {
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
				title={ __( 'Filter & Pagination Colors', 'flux-blocks' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: colorsValue.activeAccent,
						onChange: setColor( 'activeAccent' ),
						label: __(
							'Active Pill & Pagination Accent',
							'flux-blocks'
						),
					},
					{
						value: colorsValue.disabledNav,
						onChange: setColor( 'disabledNav' ),
						label: __(
							'Disabled Prev/Next Button Color',
							'flux-blocks'
						),
					},
					{
						value: colorsValue.inactivePillBg,
						onChange: setColor( 'inactivePillBg' ),
						label: __(
							'Inactive Pill Background Color',
							'flux-blocks'
						),
					},
					{
						value: colorsValue.inactivePillText,
						onChange: setColor( 'inactivePillText' ),
						label: __( 'Inactive Pill Text Color', 'flux-blocks' ),
					},
				] }
			/>

			<PanelBody
				title={ __( 'Filter & Pagination Typography', 'flux-blocks' ) }
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
					label={ __( 'Filter & Pagination Font', 'flux-blocks' ) }
					value={ typographyValue.filterPaginationFontFamily }
					options={ fontFamilyOptions }
					onChange={ setTypography( 'filterPaginationFontFamily' ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Filter & Pagination Weight', 'flux-blocks' ) }
					value={ typographyValue.filterPaginationFontWeight }
					options={ fontWeightOptions }
					onChange={ setTypography( 'filterPaginationFontWeight' ) }
				/>
			</PanelBody>
		</>
	);
}

/**
 * "Preset Theme & Layout" Inspector panel -- color preset, Style Variation,
 * and content alignment.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function LayoutPanel( { attributes, setAttributes } ) {
	const { preset, styleVariation, alignment } = attributes;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Preset Theme & Layout', 'quovex-blocks' ) }
				initialOpen={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Preset Theme', 'quovex-blocks' ) }
					value={ preset }
					options={ [
						{
							label: __(
								'Announcement (Gradient)',
								'quovex-blocks'
							),
							value: 'announcement',
						},
						{
							label: __( 'Info (Blue Tint)', 'quovex-blocks' ),
							value: 'info',
						},
						{
							label: __(
								'Success (Green Tint)',
								'quovex-blocks'
							),
							value: 'success',
						},
						{
							label: __(
								'Warning (Amber Tint)',
								'quovex-blocks'
							),
							value: 'warning',
						},
						{
							label: __( 'Custom Styling', 'quovex-blocks' ),
							value: 'custom',
						},
					] }
					onChange={ ( val ) => setAttributes( { preset: val } ) }
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Style Variation', 'quovex-blocks' ) }
					value={ styleVariation }
					options={ [
						{
							label: __(
								'Full Background Fill',
								'quovex-blocks'
							),
							value: 'gradient-bg',
						},
						{
							label: __( 'Left Border Accent', 'quovex-blocks' ),
							value: 'left-accent',
						},
						{
							label: __( 'Soft Card Shadow', 'quovex-blocks' ),
							value: 'soft-card',
						},
						{
							label: __( 'Glassmorphism Blur', 'quovex-blocks' ),
							value: 'glassmorphism',
						},
						{
							label: __(
								'Minimal Outline Border',
								'quovex-blocks'
							),
							value: 'outline',
						},
					] }
					onChange={ ( val ) =>
						setAttributes( { styleVariation: val } )
					}
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Content Alignment', 'quovex-blocks' ) }
					value={ alignment }
					options={ [
						{
							label: __( 'Left Aligned', 'quovex-blocks' ),
							value: 'left',
						},
						{
							label: __( 'Center Aligned', 'quovex-blocks' ),
							value: 'center',
						},
					] }
					onChange={ ( val ) => setAttributes( { alignment: val } ) }
				/>
			</PanelBody>
		</InspectorControls>
	);
}

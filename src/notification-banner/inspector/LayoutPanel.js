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
				title={ __( 'Preset Theme & Layout', 'flux-blocks' ) }
				initialOpen={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Preset Theme', 'flux-blocks' ) }
					value={ preset }
					options={ [
						{
							label: __(
								'Announcement (Gradient)',
								'flux-blocks'
							),
							value: 'announcement',
						},
						{
							label: __( 'Info (Blue Tint)', 'flux-blocks' ),
							value: 'info',
						},
						{
							label: __( 'Success (Green Tint)', 'flux-blocks' ),
							value: 'success',
						},
						{
							label: __( 'Warning (Amber Tint)', 'flux-blocks' ),
							value: 'warning',
						},
						{
							label: __( 'Custom Styling', 'flux-blocks' ),
							value: 'custom',
						},
					] }
					onChange={ ( val ) => setAttributes( { preset: val } ) }
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Style Variation', 'flux-blocks' ) }
					value={ styleVariation }
					options={ [
						{
							label: __( 'Full Background Fill', 'flux-blocks' ),
							value: 'gradient-bg',
						},
						{
							label: __( 'Left Border Accent', 'flux-blocks' ),
							value: 'left-accent',
						},
						{
							label: __( 'Soft Card Shadow', 'flux-blocks' ),
							value: 'soft-card',
						},
						{
							label: __( 'Glassmorphism Blur', 'flux-blocks' ),
							value: 'glassmorphism',
						},
						{
							label: __(
								'Minimal Outline Border',
								'flux-blocks'
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
					label={ __( 'Content Alignment', 'flux-blocks' ) }
					value={ alignment }
					options={ [
						{
							label: __( 'Left Aligned', 'flux-blocks' ),
							value: 'left',
						},
						{
							label: __( 'Center Aligned', 'flux-blocks' ),
							value: 'center',
						},
					] }
					onChange={ ( val ) => setAttributes( { alignment: val } ) }
				/>
			</PanelBody>
		</InspectorControls>
	);
}

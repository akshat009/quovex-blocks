/**
 * "Dismiss & Memory Settings" Inspector panel.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function DismissPanel( { attributes, setAttributes } ) {
	const { allowDismiss, rememberDismiss } = attributes;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Dismiss & Memory Settings', 'flux-blocks' ) }
				initialOpen={ false }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Allow Dismiss (✕ Button)', 'flux-blocks' ) }
					help={ __(
						'Displays a close button for users to dismiss the notice.',
						'flux-blocks'
					) }
					checked={ allowDismiss }
					onChange={ ( val ) =>
						setAttributes( { allowDismiss: val } )
					}
				/>

				{ allowDismiss && (
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Remember Dismissal (localStorage)',
							'flux-blocks'
						) }
						help={ __(
							'Prevents the notice from reappearing once dismissed by the visitor.',
							'flux-blocks'
						) }
						checked={ rememberDismiss }
						onChange={ ( val ) =>
							setAttributes( { rememberDismiss: val } )
						}
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
}

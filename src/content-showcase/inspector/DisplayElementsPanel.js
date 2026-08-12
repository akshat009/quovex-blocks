/**
 * "Display Elements (Per Post)" Inspector panel -- per-slot image/date/
 * excerpt visibility toggles for each of the 3 post slots.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SLOT_LABEL_STYLE = {
	fontWeight: 700,
	fontSize: '11px',
	textTransform: 'uppercase',
	letterSpacing: '0.5px',
	marginBottom: '8px',
	color: '#d1372d',
};

export default function DisplayElementsPanel( { attributes, setAttributes } ) {
	const {
		post1Image = true,
		post1Date = true,
		post1Excerpt = true,
		post2Image = true,
		post2Date = true,
		post2Excerpt = false,
		post3Image = true,
		post3Date = true,
		post3Excerpt = false,
	} = attributes;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Display Elements (Per Post)', 'flux-blocks' ) }
				initialOpen={ false }
			>
				<p style={ SLOT_LABEL_STYLE }>
					{ __( 'POST 1 (MAIN ITEM)', 'flux-blocks' ) }
				</p>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Image (Post 1)', 'flux-blocks' ) }
					checked={ post1Image }
					onChange={ ( value ) =>
						setAttributes( { post1Image: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Date (Post 1)', 'flux-blocks' ) }
					checked={ post1Date }
					onChange={ ( value ) =>
						setAttributes( { post1Date: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Excerpt (Post 1)', 'flux-blocks' ) }
					checked={ post1Excerpt }
					onChange={ ( value ) =>
						setAttributes( { post1Excerpt: value } )
					}
				/>

				<hr style={ { margin: '14px 0', borderColor: '#e5e5e5' } } />

				<p style={ SLOT_LABEL_STYLE }>
					{ __( 'POST 2 (SECOND ITEM)', 'flux-blocks' ) }
				</p>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Image (Post 2)', 'flux-blocks' ) }
					checked={ post2Image }
					onChange={ ( value ) =>
						setAttributes( { post2Image: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Date (Post 2)', 'flux-blocks' ) }
					checked={ post2Date }
					onChange={ ( value ) =>
						setAttributes( { post2Date: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Excerpt (Post 2)', 'flux-blocks' ) }
					checked={ post2Excerpt }
					onChange={ ( value ) =>
						setAttributes( { post2Excerpt: value } )
					}
				/>

				<hr style={ { margin: '14px 0', borderColor: '#e5e5e5' } } />

				<p style={ SLOT_LABEL_STYLE }>
					{ __( 'POST 3 (THIRD ITEM)', 'flux-blocks' ) }
				</p>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Image (Post 3)', 'flux-blocks' ) }
					checked={ post3Image }
					onChange={ ( value ) =>
						setAttributes( { post3Image: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Date (Post 3)', 'flux-blocks' ) }
					checked={ post3Date }
					onChange={ ( value ) =>
						setAttributes( { post3Date: value } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Excerpt (Post 3)', 'flux-blocks' ) }
					checked={ post3Excerpt }
					onChange={ ( value ) =>
						setAttributes( { post3Excerpt: value } )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);
}

/**
 * "Post Card Content Colors" + "Post Card Typography" Inspector panels
 * (Styles tab).
 */
import { ColorPalette, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function CardStylesPanel( {
	attributes,
	setAttributes,
	fontFamilyOptions,
	fontWeightOptions,
} ) {
	const {
		cardDateColor = '',
		cardTitleColor = '',
		cardTitleHoverColor = '',
		cardExcerptColor = '',
		cardTitleFontFamily = '',
		cardTitleFontWeight = '',
		cardExcerptFontFamily = '',
		cardExcerptFontWeight = '',
		cardDateFontFamily = '',
		cardDateFontWeight = '',
	} = attributes;

	return (
		<InspectorControls group="styles">
			<PanelBody
				title={ __( 'Post Card Content Colors', 'flux-blocks' ) }
				initialOpen={ false }
			>
				<div>
					<p style={ { marginBottom: '6px', fontWeight: '500' } }>
						{ __( 'Post Date Color', 'flux-blocks' ) }
					</p>
					<ColorPalette
						value={ cardDateColor }
						onChange={ ( val ) =>
							setAttributes( { cardDateColor: val || '' } )
						}
					/>
				</div>

				<div style={ { marginTop: '1em' } }>
					<p style={ { marginBottom: '6px', fontWeight: '500' } }>
						{ __( 'Post Title Color', 'flux-blocks' ) }
					</p>
					<ColorPalette
						value={ cardTitleColor }
						onChange={ ( val ) =>
							setAttributes( { cardTitleColor: val || '' } )
						}
					/>
				</div>

				<div style={ { marginTop: '1em' } }>
					<p style={ { marginBottom: '6px', fontWeight: '500' } }>
						{ __( 'Post Title Hover Color', 'flux-blocks' ) }
					</p>
					<ColorPalette
						value={ cardTitleHoverColor }
						onChange={ ( val ) =>
							setAttributes( {
								cardTitleHoverColor: val || '',
							} )
						}
					/>
				</div>

				<div style={ { marginTop: '1em' } }>
					<p style={ { marginBottom: '6px', fontWeight: '500' } }>
						{ __( 'Post Excerpt / Text Color', 'flux-blocks' ) }
					</p>
					<ColorPalette
						value={ cardExcerptColor }
						onChange={ ( val ) =>
							setAttributes( { cardExcerptColor: val || '' } )
						}
					/>
				</div>
			</PanelBody>

			<PanelBody
				title={ __( 'Post Card Typography', 'flux-blocks' ) }
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
					label={ __( 'Post Title Font', 'flux-blocks' ) }
					value={ cardTitleFontFamily }
					options={ fontFamilyOptions }
					onChange={ ( val ) =>
						setAttributes( { cardTitleFontFamily: val } )
					}
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Post Title Weight', 'flux-blocks' ) }
					value={ cardTitleFontWeight }
					options={ fontWeightOptions }
					onChange={ ( val ) =>
						setAttributes( { cardTitleFontWeight: val } )
					}
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Post Excerpt Font', 'flux-blocks' ) }
					value={ cardExcerptFontFamily }
					options={ fontFamilyOptions }
					onChange={ ( val ) =>
						setAttributes( { cardExcerptFontFamily: val } )
					}
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Post Excerpt Weight', 'flux-blocks' ) }
					value={ cardExcerptFontWeight }
					options={ fontWeightOptions }
					onChange={ ( val ) =>
						setAttributes( { cardExcerptFontWeight: val } )
					}
				/>

				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Post Date Font', 'flux-blocks' ) }
					value={ cardDateFontFamily }
					options={ fontFamilyOptions }
					onChange={ ( val ) =>
						setAttributes( { cardDateFontFamily: val } )
					}
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Post Date Weight', 'flux-blocks' ) }
					value={ cardDateFontWeight }
					options={ fontWeightOptions }
					onChange={ ( val ) =>
						setAttributes( { cardDateFontWeight: val } )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);
}

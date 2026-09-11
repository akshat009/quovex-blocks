/**
 * "Content & Icon Settings" Inspector panel -- title/message text, the
 * icon library picker (emoji/dashicon/SVG, each with its own swatch grid),
 * and the CTA button's text/link/target.
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { svgIcons } from '../hooks/useIconLibraries';

export default function ContentIconPanel( {
	attributes,
	setAttributes,
	libraries,
	emojiList,
	dashiconList,
	svgList,
} ) {
	const {
		title,
		message,
		showIcon,
		iconType,
		icon,
		showButton,
		buttonText,
		buttonUrl,
		buttonTarget,
	} = attributes;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Content & Icon Settings', 'quovex-blocks' ) }
				initialOpen={ false }
			>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Announcement Title', 'quovex-blocks' ) }
					value={ title }
					onChange={ ( val ) => setAttributes( { title: val } ) }
				/>

				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Announcement Message', 'quovex-blocks' ) }
					value={ message }
					onChange={ ( val ) => setAttributes( { message: val } ) }
					rows={ 3 }
				/>

				<hr style={ { margin: '12px 0' } } />

				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Icon / Emoji', 'quovex-blocks' ) }
					checked={ showIcon }
					onChange={ ( val ) => setAttributes( { showIcon: val } ) }
				/>

				{ showIcon && (
					<div style={ { marginBottom: '1em' } }>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __(
								'Icon Library Choice',
								'quovex-blocks'
							) }
							value={ iconType }
							options={ libraries }
							onChange={ ( val ) => {
								let defaultVal = '🎉';
								if ( val === 'dashicon' ) {
									defaultVal = 'dashicons-megaphone';
								} else if ( val === 'svg' ) {
									defaultVal = 'megaphone';
								}
								setAttributes( {
									iconType: val,
									icon: defaultVal,
								} );
							} }
						/>

						{ iconType === 'emoji' && (
							<>
								<TextControl
									__nextHasNoMarginBottom
									label={ __(
										'Emoji Input',
										'quovex-blocks'
									) }
									value={ icon }
									onChange={ ( val ) =>
										setAttributes( { icon: val } )
									}
								/>
								<div
									style={ {
										display: 'flex',
										flexWrap: 'wrap',
										gap: '6px',
										marginTop: '6px',
									} }
								>
									{ emojiList.map( ( em ) => (
										<button
											key={ em }
											type="button"
											style={ {
												background:
													icon === em
														? '#007cba'
														: '#f3f4f6',
												color:
													icon === em
														? '#fff'
														: '#000',
												border: '1px solid #ccc',
												borderRadius: '4px',
												padding: '4px 8px',
												cursor: 'pointer',
												fontSize: '14px',
											} }
											onClick={ () =>
												setAttributes( {
													icon: em,
												} )
											}
										>
											{ em }
										</button>
									) ) }
								</div>
							</>
						) }

						{ iconType === 'dashicon' && (
							<div style={ { marginTop: '8px' } }>
								<p
									style={ {
										marginBottom: '6px',
										fontSize: '12px',
										fontWeight: '500',
									} }
								>
									{ __(
										'Select WordPress Dashicon:',
										'quovex-blocks'
									) }
								</p>
								<div
									style={ {
										display: 'grid',
										gridTemplateColumns: 'repeat(5, 1fr)',
										gap: '6px',
									} }
								>
									{ dashiconList.map( ( item ) => (
										<button
											key={ item.value }
											type="button"
											title={ item.label }
											style={ {
												background:
													icon === item.value
														? '#007cba'
														: '#f3f4f6',
												color:
													icon === item.value
														? '#fff'
														: '#444',
												border: '1px solid #ccc',
												borderRadius: '4px',
												padding: '6px',
												display: 'flex',
												alignItems: 'center',
												justifyContent: 'center',
												cursor: 'pointer',
											} }
											onClick={ () =>
												setAttributes( {
													icon: item.value,
												} )
											}
										>
											<span
												className={ `dashicons ${ item.value }` }
												style={ {
													fontSize: '16px',
													width: 'auto',
													height: 'auto',
												} }
											></span>
										</button>
									) ) }
								</div>
							</div>
						) }

						{ iconType === 'svg' && (
							<div style={ { marginTop: '8px' } }>
								<p
									style={ {
										marginBottom: '6px',
										fontSize: '12px',
										fontWeight: '500',
									} }
								>
									{ __(
										'Select SVG Vector Icon:',
										'quovex-blocks'
									) }
								</p>
								<div
									style={ {
										display: 'grid',
										gridTemplateColumns: 'repeat(5, 1fr)',
										gap: '6px',
									} }
								>
									{ svgList.map( ( item ) => (
										<button
											key={ item.value }
											type="button"
											title={ item.label }
											style={ {
												background:
													icon === item.value
														? '#007cba'
														: '#f3f4f6',
												color:
													icon === item.value
														? '#fff'
														: '#444',
												border: '1px solid #ccc',
												borderRadius: '4px',
												padding: '6px',
												display: 'flex',
												alignItems: 'center',
												justifyContent: 'center',
												cursor: 'pointer',
											} }
											onClick={ () =>
												setAttributes( {
													icon: item.value,
												} )
											}
										>
											<span
												style={ {
													display: 'inline-flex',
													width: '18px',
													height: '18px',
												} }
											>
												{ svgIcons[ item.value ] }
											</span>
										</button>
									) ) }
								</div>
							</div>
						) }
					</div>
				) }

				<hr style={ { margin: '12px 0' } } />

				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show CTA Button', 'quovex-blocks' ) }
					checked={ showButton }
					onChange={ ( val ) => setAttributes( { showButton: val } ) }
				/>

				{ showButton && (
					<>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Button Text', 'quovex-blocks' ) }
							value={ buttonText }
							onChange={ ( val ) =>
								setAttributes( { buttonText: val } )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Button Target URL', 'quovex-blocks' ) }
							value={ buttonUrl }
							onChange={ ( val ) =>
								setAttributes( { buttonUrl: val } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Open in new tab', 'quovex-blocks' ) }
							checked={ buttonTarget }
							onChange={ ( val ) =>
								setAttributes( { buttonTarget: val } )
							}
						/>
					</>
				) }
			</PanelBody>
		</InspectorControls>
	);
}

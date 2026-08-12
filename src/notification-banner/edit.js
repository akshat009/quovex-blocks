import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	RichText,
	ColorPalette,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	TextareaControl,
	RangeControl,
} from '@wordpress/components';
import { useIconLibraries, svgIcons } from './hooks/useIconLibraries';

export default function Edit( { attributes, setAttributes } ) {
	const {
		preset = 'announcement',
		styleVariation = 'gradient-bg',
		icon = '🎉',
		iconType = 'emoji',
		showIcon = true,
		title = 'Special Announcement',
		message = 'Explore our latest 2026 Magazine Edition & featured content.',
		showButton = true,
		buttonText = 'Explore Now →',
		buttonUrl = '#',
		buttonTarget = false,
		allowDismiss = true,
		rememberDismiss = true,
		bgColor = '',
		textColor = '',
		accentColor = '',
		btnBgColor = '',
		btnTextColor = '',
		btnHoverBg = '',
		borderRadius = 12,
		alignment = 'left',
	} = attributes;

	const { libraries, emojiList, dashiconList, svgList } = useIconLibraries();

	const blockProps = useBlockProps( {
		className: `fb-notification-banner fb-notification-banner--preset-${ preset } fb-notification-banner--style-${ styleVariation } fb-notification-banner--align-${ alignment }`,
		style: {
			'--fb-nb-radius': `${ borderRadius }px`,
			'--fb-nb-custom-bg': bgColor || undefined,
			'--fb-nb-custom-text': textColor || undefined,
			'--fb-nb-custom-accent': accentColor || undefined,
			'--fb-nb-custom-btn-bg': btnBgColor || undefined,
			'--fb-nb-custom-btn-text': btnTextColor || undefined,
			'--fb-nb-custom-btn-hover': btnHoverBg || undefined,
		},
	} );

	const renderIcon = () => {
		if ( ! showIcon || ! icon ) {
			return null;
		}

		if ( iconType === 'dashicon' ) {
			return (
				<span
					className={ `dashicons ${ icon }` }
					style={ {
						fontSize: '1.6rem',
						width: 'auto',
						height: 'auto',
					} }
				></span>
			);
		}

		if ( iconType === 'svg' && svgIcons[ icon ] ) {
			return <span className="fb-svg-icon">{ svgIcons[ icon ] }</span>;
		}

		return <span>{ icon }</span>;
	};

	return (
		<>
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
								label: __(
									'Success (Green Tint)',
									'flux-blocks'
								),
								value: 'success',
							},
							{
								label: __(
									'Warning (Amber Tint)',
									'flux-blocks'
								),
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
								label: __(
									'Full Background Fill',
									'flux-blocks'
								),
								value: 'gradient-bg',
							},
							{
								label: __(
									'Left Border Accent',
									'flux-blocks'
								),
								value: 'left-accent',
							},
							{
								label: __( 'Soft Card Shadow', 'flux-blocks' ),
								value: 'soft-card',
							},
							{
								label: __(
									'Glassmorphism Blur',
									'flux-blocks'
								),
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
						onChange={ ( val ) =>
							setAttributes( { alignment: val } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Content & Icon Settings', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Announcement Title', 'flux-blocks' ) }
						value={ title }
						onChange={ ( val ) => setAttributes( { title: val } ) }
					/>

					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Announcement Message', 'flux-blocks' ) }
						value={ message }
						onChange={ ( val ) =>
							setAttributes( { message: val } )
						}
						rows={ 3 }
					/>

					<hr style={ { margin: '12px 0' } } />

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show Icon / Emoji', 'flux-blocks' ) }
						checked={ showIcon }
						onChange={ ( val ) =>
							setAttributes( { showIcon: val } )
						}
					/>

					{ showIcon && (
						<div style={ { marginBottom: '1em' } }>
							<SelectControl
								__nextHasNoMarginBottom
								label={ __(
									'Icon Library Choice',
									'flux-blocks'
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
											'flux-blocks'
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
											'flux-blocks'
										) }
									</p>
									<div
										style={ {
											display: 'grid',
											gridTemplateColumns:
												'repeat(5, 1fr)',
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
											'flux-blocks'
										) }
									</p>
									<div
										style={ {
											display: 'grid',
											gridTemplateColumns:
												'repeat(5, 1fr)',
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
						label={ __( 'Show CTA Button', 'flux-blocks' ) }
						checked={ showButton }
						onChange={ ( val ) =>
							setAttributes( { showButton: val } )
						}
					/>

					{ showButton && (
						<>
							<TextControl
								__nextHasNoMarginBottom
								label={ __( 'Button Text', 'flux-blocks' ) }
								value={ buttonText }
								onChange={ ( val ) =>
									setAttributes( { buttonText: val } )
								}
							/>
							<TextControl
								__nextHasNoMarginBottom
								label={ __(
									'Button Target URL',
									'flux-blocks'
								) }
								value={ buttonUrl }
								onChange={ ( val ) =>
									setAttributes( { buttonUrl: val } )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Open in new tab', 'flux-blocks' ) }
								checked={ buttonTarget }
								onChange={ ( val ) =>
									setAttributes( { buttonTarget: val } )
								}
							/>
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Dismiss & Memory Settings', 'flux-blocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Allow Dismiss (✕ Button)',
							'flux-blocks'
						) }
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

				<PanelBody
					title={ __(
						'Color & Border Customization',
						'flux-blocks'
					) }
					initialOpen={ false }
				>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Border Radius (px)', 'flux-blocks' ) }
						min={ 0 }
						max={ 30 }
						value={ borderRadius }
						onChange={ ( val ) =>
							setAttributes( { borderRadius: val } )
						}
					/>

					<div style={ { marginTop: '1em' } }>
						<p style={ { marginBottom: '6px', fontWeight: '500' } }>
							{ __( 'Background Color', 'flux-blocks' ) }
						</p>
						<ColorPalette
							value={ bgColor }
							onChange={ ( val ) =>
								setAttributes( { bgColor: val || '' } )
							}
						/>
					</div>

					<div style={ { marginTop: '1em' } }>
						<p style={ { marginBottom: '6px', fontWeight: '500' } }>
							{ __( 'Text & Title Color', 'flux-blocks' ) }
						</p>
						<ColorPalette
							value={ textColor }
							onChange={ ( val ) =>
								setAttributes( { textColor: val || '' } )
							}
						/>
					</div>

					<div style={ { marginTop: '1em' } }>
						<p style={ { marginBottom: '6px', fontWeight: '500' } }>
							{ __(
								'Accent Border / Highlight Color',
								'flux-blocks'
							) }
						</p>
						<ColorPalette
							value={ accentColor }
							onChange={ ( val ) =>
								setAttributes( { accentColor: val || '' } )
							}
						/>
					</div>

					{ showButton && (
						<>
							<div style={ { marginTop: '1em' } }>
								<p
									style={ {
										marginBottom: '6px',
										fontWeight: '500',
									} }
								>
									{ __(
										'CTA Button Background',
										'flux-blocks'
									) }
								</p>
								<ColorPalette
									value={ btnBgColor }
									onChange={ ( val ) =>
										setAttributes( {
											btnBgColor: val || '',
										} )
									}
								/>
							</div>

							<div style={ { marginTop: '1em' } }>
								<p
									style={ {
										marginBottom: '6px',
										fontWeight: '500',
									} }
								>
									{ __(
										'CTA Button Text Color',
										'flux-blocks'
									) }
								</p>
								<ColorPalette
									value={ btnTextColor }
									onChange={ ( val ) =>
										setAttributes( {
											btnTextColor: val || '',
										} )
									}
								/>
							</div>

							<div style={ { marginTop: '1em' } }>
								<p
									style={ {
										marginBottom: '6px',
										fontWeight: '500',
									} }
								>
									{ __(
										'CTA Button Hover Background',
										'flux-blocks'
									) }
								</p>
								<ColorPalette
									value={ btnHoverBg }
									onChange={ ( val ) =>
										setAttributes( {
											btnHoverBg: val || '',
										} )
									}
								/>
							</div>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="fb-notification-banner__main">
					{ showIcon && (
						<div className="fb-notification-banner__icon">
							{ renderIcon() }
						</div>
					) }

					<div className="fb-notification-banner__body">
						<RichText
							tagName="h4"
							className="fb-notification-banner__title"
							value={ title }
							onChange={ ( val ) =>
								setAttributes( { title: val } )
							}
							placeholder={ __(
								'Announcement title…',
								'flux-blocks'
							) }
						/>
						<RichText
							tagName="div"
							className="fb-notification-banner__message"
							value={ message }
							onChange={ ( val ) =>
								setAttributes( { message: val } )
							}
							placeholder={ __(
								'Announcement message…',
								'flux-blocks'
							) }
						/>
					</div>
				</div>

				{ ( showButton || allowDismiss ) && (
					<div className="fb-notification-banner__actions">
						{ showButton && (
							<span className="fb-notification-banner__btn">
								{ buttonText ||
									__( 'Explore Now →', 'flux-blocks' ) }
							</span>
						) }

						{ allowDismiss && (
							<button
								type="button"
								className="fb-notification-banner__dismiss"
								aria-label={ __(
									'Dismiss notice',
									'flux-blocks'
								) }
							>
								✕
							</button>
						) }
					</div>
				) }
			</div>
		</>
	);
}

/**
 * Notification Banner editor UI.
 *
 * This file resolves shared/derived state (icon library lists, theme font
 * options, the bannerId auto-generation effect), hands each Inspector
 * panel group to its own component in ./inspector/ (mirrors the same
 * split already done for Query Grid and Content Showcase's edit.js), and
 * renders the actual banner canvas itself.
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useBlockProps, RichText, useSettings } from '@wordpress/block-editor';
import { useIconLibraries, svgIcons } from './hooks/useIconLibraries';
import LayoutPanel from './inspector/LayoutPanel';
import ContentIconPanel from './inspector/ContentIconPanel';
import DismissPanel from './inspector/DismissPanel';
import ColorPanel from './inspector/ColorPanel';

export default function Edit( { attributes, setAttributes, clientId } ) {
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
		allowDismiss = true,
		bannerId = '',
		bgColor = '',
		textColor = '',
		textFontFamily = '',
		textFontWeight = '',
		accentColor = '',
		btnBgColor = '',
		btnTextColor = '',
		btnHoverBg = '',
		borderRadius = 12,
		alignment = 'left',
	} = attributes;

	// Stable bannerId, persisted once (same pattern as query-grid's
	// queryId) -- "Remember Dismissal" needs this to key localStorage on.
	useEffect( () => {
		if ( ! bannerId ) {
			setAttributes( {
				bannerId: `nb${ clientId.replace( /-/g, '' ).slice( 0, 10 ) }`,
			} );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- run once on mount only, matching query-grid/edit.js's identical queryId pattern.

	const { libraries, emojiList, dashiconList, svgList } = useIconLibraries();

	// Theme-provided fonts (theme.json), not a hardcoded list -- guaranteed
	// already loaded by the theme itself.
	const [ rawThemeFontFamilies ] = useSettings( 'typography.fontFamilies' );
	// Guards against non-array shapes some themes/no-theme.json return.
	const themeFontFamilies = Array.isArray( rawThemeFontFamilies )
		? rawThemeFontFamilies
		: [];
	const fontFamilyOptions = [
		{ label: __( 'Theme Default', 'quovex-blocks' ), value: '' },
		...themeFontFamilies.map( ( font ) => ( {
			label: font.name,
			value: font.fontFamily,
		} ) ),
	];
	const fontWeightOptions = [
		{ label: __( 'Theme Default', 'quovex-blocks' ), value: '' },
		{ label: __( 'Normal (400)', 'quovex-blocks' ), value: '400' },
		{ label: __( 'Medium (500)', 'quovex-blocks' ), value: '500' },
		{ label: __( 'Semi-Bold (600)', 'quovex-blocks' ), value: '600' },
		{ label: __( 'Bold (700)', 'quovex-blocks' ), value: '700' },
		{ label: __( 'Extra-Bold (800)', 'quovex-blocks' ), value: '800' },
	];

	const blockProps = useBlockProps( {
		className: `qv-notification-banner qv-notification-banner--preset-${ preset } qv-notification-banner--style-${ styleVariation } qv-notification-banner--align-${ alignment }`,
		style: {
			'--qv-nb-radius': `${ borderRadius }px`,
			'--qv-nb-custom-bg': bgColor || undefined,
			'--qv-nb-custom-text': textColor || undefined,
			'--qv-nb-text-font-family': textFontFamily || undefined,
			'--qv-nb-text-font-weight': textFontWeight || undefined,
			'--qv-nb-custom-accent': accentColor || undefined,
			'--qv-nb-custom-btn-bg': btnBgColor || undefined,
			'--qv-nb-custom-btn-text': btnTextColor || undefined,
			'--qv-nb-custom-btn-hover': btnHoverBg || undefined,
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
			return <span className="qv-svg-icon">{ svgIcons[ icon ] }</span>;
		}

		return <span>{ icon }</span>;
	};

	return (
		<>
			<LayoutPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>

			<ContentIconPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
				libraries={ libraries }
				emojiList={ emojiList }
				dashiconList={ dashiconList }
				svgList={ svgList }
			/>

			<DismissPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>

			<ColorPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
				fontFamilyOptions={ fontFamilyOptions }
				fontWeightOptions={ fontWeightOptions }
			/>

			<div { ...blockProps }>
				<div className="qv-notification-banner__main">
					{ showIcon && (
						<div className="qv-notification-banner__icon">
							{ renderIcon() }
						</div>
					) }

					<div className="qv-notification-banner__body">
						<RichText
							tagName="h4"
							className="qv-notification-banner__title"
							value={ title }
							onChange={ ( val ) =>
								setAttributes( { title: val } )
							}
							placeholder={ __(
								'Announcement title…',
								'quovex-blocks'
							) }
						/>
						<RichText
							tagName="div"
							className="qv-notification-banner__message"
							value={ message }
							onChange={ ( val ) =>
								setAttributes( { message: val } )
							}
							placeholder={ __(
								'Announcement message…',
								'quovex-blocks'
							) }
						/>
					</div>
				</div>

				{ ( showButton || allowDismiss ) && (
					<div className="qv-notification-banner__actions">
						{ showButton && (
							<span className="qv-notification-banner__btn">
								{ buttonText ||
									__( 'Explore Now →', 'quovex-blocks' ) }
							</span>
						) }

						{ allowDismiss && (
							<button
								type="button"
								className="qv-notification-banner__dismiss"
								aria-label={ __(
									'Dismiss notice',
									'quovex-blocks'
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

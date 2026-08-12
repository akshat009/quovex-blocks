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

	// A stable, unique bannerId, generated once and persisted into the
	// attribute -- same pattern query-grid/edit.js uses for its queryId.
	// Without this, "Remember Dismissal" has nothing to key the
	// localStorage entry on (bannerId defaults to '', and view.js's
	// `remember && bannerId` check is always false), so it silently never
	// remembers anything.
	useEffect( () => {
		if ( ! bannerId ) {
			setAttributes( {
				bannerId: `nb${ clientId.replace( /-/g, '' ).slice( 0, 10 ) }`,
			} );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- run once on mount only, matching query-grid/edit.js's identical queryId pattern.

	const { libraries, emojiList, dashiconList, svgList } = useIconLibraries();

	// Theme-provided font choices (theme.json's settings.typography.fontFamilies)
	// -- never a hardcoded Google Fonts list: those would need to be
	// enqueued/loaded ourselves to actually work, whereas whatever the theme
	// declares here is guaranteed already loaded by the theme itself.
	const [ rawThemeFontFamilies ] = useSettings( 'typography.fontFamilies' );
	// Some themes return this as an array; others (e.g. no theme.json entry)
	// return `false`/`undefined`/a non-array shape -- guard so `.map()` below
	// never blows up regardless of what the active theme provides.
	const themeFontFamilies = Array.isArray( rawThemeFontFamilies )
		? rawThemeFontFamilies
		: [];
	const fontFamilyOptions = [
		{ label: __( 'Theme Default', 'flux-blocks' ), value: '' },
		...themeFontFamilies.map( ( font ) => ( {
			label: font.name,
			value: font.fontFamily,
		} ) ),
	];
	const fontWeightOptions = [
		{ label: __( 'Theme Default', 'flux-blocks' ), value: '' },
		{ label: __( 'Normal (400)', 'flux-blocks' ), value: '400' },
		{ label: __( 'Medium (500)', 'flux-blocks' ), value: '500' },
		{ label: __( 'Semi-Bold (600)', 'flux-blocks' ), value: '600' },
		{ label: __( 'Bold (700)', 'flux-blocks' ), value: '700' },
		{ label: __( 'Extra-Bold (800)', 'flux-blocks' ), value: '800' },
	];

	const blockProps = useBlockProps( {
		className: `fb-notification-banner fb-notification-banner--preset-${ preset } fb-notification-banner--style-${ styleVariation } fb-notification-banner--align-${ alignment }`,
		style: {
			'--fb-nb-radius': `${ borderRadius }px`,
			'--fb-nb-custom-bg': bgColor || undefined,
			'--fb-nb-custom-text': textColor || undefined,
			'--fb-nb-text-font-family': textFontFamily || undefined,
			'--fb-nb-text-font-weight': textFontWeight || undefined,
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

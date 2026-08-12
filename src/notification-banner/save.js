import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * Only these URL schemes (plus scheme-less relative/anchor links) are
 * allowed through to the saved `href` -- WordPress's own KSES filtering on
 * post save already strips `javascript:`/similar for anyone without
 * `unfiltered_html`, but that is a server-side safety net for the
 * capability-restricted majority of users, not a reason to skip validating
 * here too (an `unfiltered_html` user, e.g. most single-site admins,
 * doesn't get that net at all).
 */
const ALLOWED_URL_SCHEMES = /^(https?:|mailto:|tel:|#|\/)/i;

function safeButtonUrl( url ) {
	return ALLOWED_URL_SCHEMES.test( url ) ? url : '#';
}

/**
 * SVG icon markup strings (raw HTML, not React components).
 * Must match the set in hooks/useIconLibraries.js.
 */
const svgIconsHTML = {
	megaphone:
		'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
	bell: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
	info: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
	checkCircle:
		'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
	alertTriangle:
		'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
	flame: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3.5z"/></svg>',
	rocket: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg>',
	sparkles:
		'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/></svg>',
	shield: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.8 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
	gift: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
};

export default function save( { attributes } ) {
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
		bannerId = '',
		bgColor = '',
		textColor = '',
		accentColor = '',
		btnBgColor = '',
		btnTextColor = '',
		btnHoverBg = '',
		borderRadius = 12,
		alignment = 'left',
	} = attributes;

	const styleVars = {
		'--fb-nb-radius': `${ borderRadius }px`,
	};
	if ( bgColor ) {
		styleVars[ '--fb-nb-custom-bg' ] = bgColor;
	}
	if ( textColor ) {
		styleVars[ '--fb-nb-custom-text' ] = textColor;
	}
	if ( accentColor ) {
		styleVars[ '--fb-nb-custom-accent' ] = accentColor;
	}
	if ( btnBgColor ) {
		styleVars[ '--fb-nb-custom-btn-bg' ] = btnBgColor;
	}
	if ( btnTextColor ) {
		styleVars[ '--fb-nb-custom-btn-text' ] = btnTextColor;
	}
	if ( btnHoverBg ) {
		styleVars[ '--fb-nb-custom-btn-hover' ] = btnHoverBg;
	}

	const blockProps = useBlockProps.save( {
		className: `fb-notification-banner fb-notification-banner--preset-${ preset } fb-notification-banner--style-${ styleVariation } fb-notification-banner--align-${ alignment }`,
		style: styleVars,
		'data-banner-id': bannerId || undefined,
		'data-remember-dismiss':
			allowDismiss && rememberDismiss ? 'true' : 'false',
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

		if ( iconType === 'svg' && svgIconsHTML[ icon ] ) {
			return (
				<span
					className="fb-svg-icon"
					style={ {
						display: 'inline-flex',
						width: '24px',
						height: '24px',
					} }
					dangerouslySetInnerHTML={ {
						__html: svgIconsHTML[ icon ],
					} }
				/>
			);
		}

		// Emoji fallback
		return <span>{ icon }</span>;
	};

	const target = buttonTarget ? '_blank' : '_self';

	return (
		<div { ...blockProps }>
			<div className="fb-notification-banner__main">
				{ showIcon && icon && (
					<div className="fb-notification-banner__icon">
						{ renderIcon() }
					</div>
				) }

				<div className="fb-notification-banner__body">
					{ title && (
						<RichText.Content
							tagName="h4"
							className="fb-notification-banner__title"
							value={ title }
						/>
					) }
					{ message && (
						<RichText.Content
							tagName="div"
							className="fb-notification-banner__message"
							value={ message }
						/>
					) }
				</div>
			</div>

			{ ( showButton || allowDismiss ) && (
				<div className="fb-notification-banner__actions">
					{ showButton && buttonText && (
						<a
							href={ safeButtonUrl( buttonUrl ) }
							target={ target }
							className="fb-notification-banner__btn"
							{ ...( buttonTarget
								? { rel: 'noopener noreferrer' }
								: {} ) }
						>
							{ buttonText }
						</a>
					) }

					{ allowDismiss && (
						<button
							type="button"
							className="fb-notification-banner__dismiss"
							aria-label={ __( 'Dismiss notice', 'flux-blocks' ) }
						>
							✕
						</button>
					) }
				</div>
			) }
		</div>
	);
}

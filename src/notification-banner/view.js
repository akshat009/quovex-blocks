/**
 * Front-end view script for Notification & Announcement Banner block.
 * Handles client-side dismiss with optional localStorage persistence.
 */
function initNotificationBanners() {
	const banners = document.querySelectorAll(
		'.fb-notification-banner[data-banner-id]'
	);

	banners.forEach( ( banner ) => {
		const bannerId = banner.getAttribute( 'data-banner-id' )?.trim();
		const remember =
			banner.getAttribute( 'data-remember-dismiss' ) === 'true';

		// Only use localStorage if a non-empty bannerId is assigned
		if (
			remember &&
			bannerId &&
			localStorage.getItem( 'fb_dismissed_' + bannerId )
		) {
			banner.style.display = 'none';
			banner.classList.add( 'is-dismissed' );
			return;
		}

		const dismissBtn = banner.querySelector(
			'.fb-notification-banner__dismiss'
		);
		if ( dismissBtn ) {
			dismissBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				banner.classList.add( 'is-dismissed' );

				if ( remember && bannerId ) {
					localStorage.setItem( 'fb_dismissed_' + bannerId, '1' );
				}

				setTimeout( () => {
					banner.style.display = 'none';
				}, 300 );
			} );
		}
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initNotificationBanners );
} else {
	initNotificationBanners();
}

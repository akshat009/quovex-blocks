/**
 * Interactivity API store for Content Showcase block.
 */
import { store, getContext } from '@wordpress/interactivity';

store( 'flux-blocks/content-showcase', {
	actions: {
		toggleHotspot() {
			const context = getContext();
			context.isOpen = ! context.isOpen;
		},
	},
} );

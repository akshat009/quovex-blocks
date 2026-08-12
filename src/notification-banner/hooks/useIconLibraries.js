import { useMemo } from '@wordpress/element';

export const svgIcons = {
	megaphone: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="m3 11 18-5v12L3 13v-2z" />
			<path d="M11.6 16.8a3 3 0 1 1-5.8-1.6" />
		</svg>
	),
	bell: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
			<path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
		</svg>
	),
	info: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<circle cx="12" cy="12" r="10" />
			<path d="M12 16v-4" />
			<path d="M12 8h.01" />
		</svg>
	),
	checkCircle: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
			<path d="m9 11 3 3L22 4" />
		</svg>
	),
	alertTriangle: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
			<path d="M12 9v4" />
			<path d="M12 17h.01" />
		</svg>
	),
	flame: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3.5z" />
		</svg>
	),
	rocket: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z" />
			<path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z" />
		</svg>
	),
	sparkles: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z" />
		</svg>
	),
	shield: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.8 17 5 19 5a1 1 0 0 1 1 1z" />
		</svg>
	),
	gift: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M20 12v10H4V12" />
			<path d="M2 7h20v5H2z" />
			<path d="M12 22V7" />
			<path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z" />
			<path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
		</svg>
	),
};

export function useIconLibraries() {
	const libraries = useMemo(
		() => [
			{ label: 'Emoji Preset', value: 'emoji' },
			{ label: 'WordPress Dashicons', value: 'dashicon' },
			{ label: 'SVG Vector Icons', value: 'svg' },
		],
		[]
	);

	const emojiList = useMemo(
		() => [
			'🎉',
			'🔔',
			'ℹ️',
			'✅',
			'⚠️',
			'🔥',
			'🚀',
			'💡',
			'📌',
			'📢',
			'⭐',
			'⚡',
			'🎁',
			'💬',
			'🚨',
			'👑',
		],
		[]
	);

	const dashiconList = useMemo(
		() => [
			{ label: 'Megaphone', value: 'dashicons-megaphone' },
			{ label: 'Bell', value: 'dashicons-bell' },
			{ label: 'Info', value: 'dashicons-info' },
			{ label: 'Checkmark', value: 'dashicons-yes-alt' },
			{ label: 'Warning', value: 'dashicons-warning' },
			{ label: 'Star', value: 'dashicons-star-filled' },
			{ label: 'Award', value: 'dashicons-award' },
			{ label: 'Lightbulb', value: 'dashicons-lightbulb' },
			{ label: 'Announcement', value: 'dashicons-tickets-alt' },
			{ label: 'Flag', value: 'dashicons-flag' },
		],
		[]
	);

	const svgList = useMemo(
		() => [
			{ label: 'Megaphone', value: 'megaphone' },
			{ label: 'Bell', value: 'bell' },
			{ label: 'Info Circle', value: 'info' },
			{ label: 'Check Circle', value: 'checkCircle' },
			{ label: 'Warning Triangle', value: 'alertTriangle' },
			{ label: 'Flame / Hot', value: 'flame' },
			{ label: 'Rocket', value: 'rocket' },
			{ label: 'Sparkles', value: 'sparkles' },
			{ label: 'Shield', value: 'shield' },
			{ label: 'Gift', value: 'gift' },
		],
		[]
	);

	return {
		libraries,
		emojiList,
		dashiconList,
		svgList,
		svgIcons,
	};
}

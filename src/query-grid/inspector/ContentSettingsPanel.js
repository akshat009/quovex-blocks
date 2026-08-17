/**
 * "Section Heading Settings" + "Grid Layout Settings" Inspector panels --
 * content/behavior settings, grouped apart from colors/typography.
 */
/* eslint-disable @wordpress/no-unsafe-wp-apis -- NumberControl is still
   experimental in @wordpress/components but has no stable alternative for
   this UI pattern yet; this opt-in-by-comment is the standard way the block
   editor ecosystem uses it until it stabilizes. */
import {
	SelectControl,
	RangeControl,
	__experimentalNumberControl as NumberControl,
	PanelBody,
	ToggleControl,
	TextControl,
} from '@wordpress/components';
/* eslint-enable @wordpress/no-unsafe-wp-apis */
import { __ } from '@wordpress/i18n';

export default function ContentSettingsPanel( {
	attributes,
	setAttributes,
	postTypeOptions,
	currentStyle,
	columnsValue,
	paginationSlug,
	updatePaginationSlug,
} ) {
	const {
		showHeading,
		heading,
		headingAccent,
		postType,
		showAllPosts,
		postCount,
		orderBy,
		order,
		paginationStyle,
		carouselItemsPerView,
	} = attributes;

	return (
		<>
			<PanelBody
				title={ __( 'Section Heading Settings', 'flux-blocks' ) }
				initialOpen={ false }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Section Heading', 'flux-blocks' ) }
					checked={ !! showHeading }
					onChange={ ( value ) =>
						setAttributes( { showHeading: value } )
					}
				/>
				{ showHeading && (
					<>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Section Title', 'flux-blocks' ) }
							value={ heading }
							onChange={ ( value ) =>
								setAttributes( { heading: value } )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __(
								'Highlighted Accent Text',
								'flux-blocks'
							) }
							help={ __(
								'Must exactly match a substring of the title above to be colored.',
								'flux-blocks'
							) }
							value={ headingAccent }
							onChange={ ( value ) =>
								setAttributes( { headingAccent: value } )
							}
						/>
					</>
				) }
			</PanelBody>

			<PanelBody
				title={ __( 'Grid Layout Settings', 'flux-blocks' ) }
				initialOpen={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Post type', 'flux-blocks' ) }
					value={ postType }
					options={
						postTypeOptions.length
							? postTypeOptions
							: [
									{
										label: __( 'Post', 'flux-blocks' ),
										value: 'post',
									},
							  ]
					}
					onChange={ ( value ) =>
						setAttributes( { postType: value } )
					}
				/>
				{ ! showAllPosts && (
					<RangeControl
						__nextHasNoMarginBottom
						label={
							currentStyle === 'carousel'
								? __( 'Total Posts (Carousel)', 'flux-blocks' )
								: __( 'Posts per page', 'flux-blocks' )
						}
						help={
							currentStyle === 'carousel'
								? __(
										'Total posts loaded into the carousel (Prev/Next cycle through these). Want every matching post instead? Turn on "Show All Posts (No Pagination)" below.',
										'flux-blocks'
								  )
								: undefined
						}
						min={ 1 }
						max={ 50 }
						value={ postCount }
						onChange={ ( value ) =>
							setAttributes( { postCount: value } )
						}
					/>
				) }
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Order by', 'flux-blocks' ) }
					value={ `${ orderBy }-${ order }` }
					options={ [
						{
							label: __( 'Newest first', 'flux-blocks' ),
							value: 'date-desc',
						},
						{
							label: __( 'Oldest first', 'flux-blocks' ),
							value: 'date-asc',
						},
						{
							label: __( 'Title A→Z', 'flux-blocks' ),
							value: 'title-asc',
						},
						{
							label: __( 'Title Z→A', 'flux-blocks' ),
							value: 'title-desc',
						},
					] }
					onChange={ ( value ) => {
						const [ newOrderBy, newOrder ] = value.split( '-' );
						setAttributes( {
							orderBy: newOrderBy,
							order: newOrder,
						} );
					} }
				/>
				{ currentStyle !== 'carousel' && ! showAllPosts && (
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Pagination Style', 'flux-blocks' ) }
						value={ paginationStyle }
						options={ [
							{
								label: __( 'Page numbers', 'flux-blocks' ),
								value: 'numbers',
							},
							{
								label: __( 'Load more button', 'flux-blocks' ),
								value: 'load-more',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { paginationStyle: value } )
						}
					/>
				) }
				{ currentStyle !== 'carousel' &&
					! showAllPosts &&
					paginationStyle === 'numbers' && (
						<TextControl
							__nextHasNoMarginBottom
							label={ __(
								'Pagination URL Segment',
								'flux-blocks'
							) }
							help={ __(
								'Site-wide -- shared by every Query Grid block on this site. Shown in the address bar as e.g. /your-value/2/.',
								'flux-blocks'
							) }
							value={ paginationSlug ?? '' }
							onChange={ updatePaginationSlug }
						/>
					) }
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Show All Posts (No Pagination)',
						'flux-blocks'
					) }
					help={
						currentStyle === 'carousel'
							? __(
									'Cycles through every matching post (up to 200) instead of stopping at the Total Posts number above -- Prev/Next still work as normal.',
									'flux-blocks'
							  )
							: __(
									'Shows every matching post on one page (up to 200) instead of paginating. Turns off Posts per page and Pagination Style above.',
									'flux-blocks'
							  )
					}
					checked={ !! showAllPosts }
					onChange={ ( value ) =>
						setAttributes( { showAllPosts: value } )
					}
				/>
				{ currentStyle === 'carousel' && (
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Cards Per Slide', 'flux-blocks' ) }
						help={ __(
							'How many cards show side by side; Next/Prev move a whole group at a time.',
							'flux-blocks'
						) }
						min={ 1 }
						max={ 6 }
						value={ carouselItemsPerView }
						onChange={ ( value ) =>
							setAttributes( { carouselItemsPerView: value } )
						}
					/>
				) }
				<NumberControl
					label={ __( 'Mobile columns', 'flux-blocks' ) }
					min={ 1 }
					max={ 4 }
					value={ columnsValue.mobile }
					onChange={ ( value ) =>
						setAttributes( {
							columns: {
								...columnsValue,
								mobile: Number( value ),
							},
						} )
					}
				/>
				<NumberControl
					label={ __( 'Tablet columns', 'flux-blocks' ) }
					min={ 1 }
					max={ 6 }
					value={ columnsValue.tablet }
					onChange={ ( value ) =>
						setAttributes( {
							columns: {
								...columnsValue,
								tablet: Number( value ),
							},
						} )
					}
				/>
				<NumberControl
					label={ __( 'Desktop columns', 'flux-blocks' ) }
					min={ 1 }
					max={ 6 }
					value={ columnsValue.desktop }
					onChange={ ( value ) =>
						setAttributes( {
							columns: {
								...columnsValue,
								desktop: Number( value ),
							},
						} )
					}
				/>
			</PanelBody>
		</>
	);
}

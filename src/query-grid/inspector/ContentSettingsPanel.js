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
				title={ __( 'Section Heading Settings', 'quovex-blocks' ) }
				initialOpen={ false }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Section Heading', 'quovex-blocks' ) }
					checked={ !! showHeading }
					onChange={ ( value ) =>
						setAttributes( { showHeading: value } )
					}
				/>
				{ showHeading && (
					<>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Section Title', 'quovex-blocks' ) }
							value={ heading }
							onChange={ ( value ) =>
								setAttributes( { heading: value } )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __(
								'Highlighted Accent Text',
								'quovex-blocks'
							) }
							help={ __(
								'Must exactly match a substring of the title above to be colored.',
								'quovex-blocks'
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
				title={ __( 'Grid Layout Settings', 'quovex-blocks' ) }
				initialOpen={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Post type', 'quovex-blocks' ) }
					value={ postType }
					options={
						postTypeOptions.length
							? postTypeOptions
							: [
									{
										label: __( 'Post', 'quovex-blocks' ),
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
								? __(
										'Total Posts (Carousel)',
										'quovex-blocks'
								  )
								: __( 'Posts per page', 'quovex-blocks' )
						}
						help={
							currentStyle === 'carousel'
								? __(
										'Total posts loaded into the carousel (Prev/Next cycle through these). Want every matching post instead? Turn on "Show All Posts (No Pagination)" below.',
										'quovex-blocks'
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
					label={ __( 'Order by', 'quovex-blocks' ) }
					value={ `${ orderBy }-${ order }` }
					options={ [
						{
							label: __( 'Newest first', 'quovex-blocks' ),
							value: 'date-desc',
						},
						{
							label: __( 'Oldest first', 'quovex-blocks' ),
							value: 'date-asc',
						},
						{
							label: __( 'Title A→Z', 'quovex-blocks' ),
							value: 'title-asc',
						},
						{
							label: __( 'Title Z→A', 'quovex-blocks' ),
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
						label={ __( 'Pagination Style', 'quovex-blocks' ) }
						value={ paginationStyle }
						options={ [
							{
								label: __( 'Page numbers', 'quovex-blocks' ),
								value: 'numbers',
							},
							{
								label: __(
									'Load more button',
									'quovex-blocks'
								),
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
								'quovex-blocks'
							) }
							help={ __(
								'Site-wide -- shared by every Query Grid block on this site. Shown in the address bar as e.g. /your-value/2/.',
								'quovex-blocks'
							) }
							value={ paginationSlug ?? '' }
							onChange={ updatePaginationSlug }
						/>
					) }
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Show All Posts (No Pagination)',
						'quovex-blocks'
					) }
					help={
						currentStyle === 'carousel'
							? __(
									'Cycles through every matching post (up to 200) instead of stopping at the Total Posts number above -- Prev/Next still work as normal.',
									'quovex-blocks'
							  )
							: __(
									'Shows every matching post on one page (up to 200) instead of paginating. Turns off Posts per page and Pagination Style above.',
									'quovex-blocks'
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
						label={ __( 'Cards Per Slide', 'quovex-blocks' ) }
						help={ __(
							'How many cards show side by side; Next/Prev move a whole group at a time.',
							'quovex-blocks'
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
					label={ __( 'Mobile columns', 'quovex-blocks' ) }
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
					label={ __( 'Tablet columns', 'quovex-blocks' ) }
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
					label={ __( 'Desktop columns', 'quovex-blocks' ) }
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

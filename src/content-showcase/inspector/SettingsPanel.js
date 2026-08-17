/**
 * "Settings" + "Section Heading Settings" + "Manual Post Selection & Order"
 * Inspector panels -- grouped together as the block's core content/structure
 * settings (all render into the default "Settings" tab, unlike the color/
 * typography panels which render into "Styles").
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function SettingsPanel( {
	attributes,
	setAttributes,
	postTypeOptions,
	queryPosts,
} ) {
	const {
		postType,
		postCount,
		orderBy,
		order,
		showHeading = false,
		heading = '',
		headingAccent = '',
		subheading = '',
		enableManualSelection = false,
		manualPost1 = 0,
		manualPost2 = 0,
		manualPost3 = 0,
	} = attributes;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Settings', 'flux-blocks' ) }
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
						// Resets manually-picked posts to "Automatic" -- they
						// belong to the OLD post type (see Renderer.php's
						// post_type safety check).
						setAttributes( {
							postType: value,
							manualPost1: 0,
							manualPost2: 0,
							manualPost3: 0,
						} )
					}
				/>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Number of items', 'flux-blocks' ) }
					min={ 1 }
					max={ 3 }
					value={ postCount }
					onChange={ ( value ) =>
						setAttributes( { postCount: value } )
					}
				/>
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
			</PanelBody>

			<PanelBody
				title={ __( 'Section Heading Settings', 'flux-blocks' ) }
				initialOpen={ false }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Section Heading', 'flux-blocks' ) }
					checked={ showHeading }
					onChange={ ( value ) =>
						setAttributes( { showHeading: value } )
					}
				/>
				{ showHeading && (
					<>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'SECTION TITLE', 'flux-blocks' ) }
							value={ heading }
							onChange={ ( value ) =>
								setAttributes( { heading: value } )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __(
								'HIGHLIGHTED ACCENT TEXT',
								'flux-blocks'
							) }
							value={ headingAccent }
							help={ __(
								'Must exactly match a substring of the title above to be colored.',
								'flux-blocks'
							) }
							onChange={ ( value ) =>
								setAttributes( { headingAccent: value } )
							}
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __(
								'SECTION SUBHEADING / DESCRIPTION',
								'flux-blocks'
							) }
							value={ subheading }
							help={ __(
								'Subheading text displayed directly below the main title.',
								'flux-blocks'
							) }
							onChange={ ( value ) =>
								setAttributes( { subheading: value } )
							}
						/>
					</>
				) }
			</PanelBody>

			<PanelBody
				title={ __( 'Manual Post Selection & Order', 'flux-blocks' ) }
				initialOpen={ false }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Enable Manual Post Selection',
						'flux-blocks'
					) }
					help={ __(
						'Turn ON to manually pick individual posts for each slot. Overrides Category Filter.',
						'flux-blocks'
					) }
					checked={ !! enableManualSelection }
					onChange={ ( val ) =>
						setAttributes( { enableManualSelection: val } )
					}
				/>

				{ enableManualSelection && (
					<>
						<hr
							style={ {
								margin: '14px 0',
								borderColor: '#e5e5e5',
							} }
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __(
								'Post Slot 1 (Main Featured)',
								'flux-blocks'
							) }
							value={ manualPost1 }
							options={ [
								{
									label: __(
										'-- Automatic (Query Default) --',
										'flux-blocks'
									),
									value: 0,
								},
								...queryPosts
									.filter(
										( p ) =>
											p.id !== manualPost2 &&
											p.id !== manualPost3
									)
									.map( ( p ) => ( {
										label:
											p.title?.rendered || `#${ p.id }`,
										value: p.id,
									} ) ),
							] }
							onChange={ ( val ) =>
								setAttributes( {
									manualPost1: parseInt( val, 10 ) || 0,
								} )
							}
						/>

						<SelectControl
							__nextHasNoMarginBottom
							label={ __(
								'Post Slot 2 (Second Post)',
								'flux-blocks'
							) }
							value={ manualPost2 }
							options={ [
								{
									label: __(
										'-- Automatic (Query Default) --',
										'flux-blocks'
									),
									value: 0,
								},
								...queryPosts
									.filter(
										( p ) =>
											p.id !== manualPost1 &&
											p.id !== manualPost3
									)
									.map( ( p ) => ( {
										label:
											p.title?.rendered || `#${ p.id }`,
										value: p.id,
									} ) ),
							] }
							onChange={ ( val ) =>
								setAttributes( {
									manualPost2: parseInt( val, 10 ) || 0,
								} )
							}
						/>

						<SelectControl
							__nextHasNoMarginBottom
							label={ __(
								'Post Slot 3 (Third Post)',
								'flux-blocks'
							) }
							value={ manualPost3 }
							options={ [
								{
									label: __(
										'-- Automatic (Query Default) --',
										'flux-blocks'
									),
									value: 0,
								},
								...queryPosts
									.filter(
										( p ) =>
											p.id !== manualPost1 &&
											p.id !== manualPost2
									)
									.map( ( p ) => ( {
										label:
											p.title?.rendered || `#${ p.id }`,
										value: p.id,
									} ) ),
							] }
							onChange={ ( val ) =>
								setAttributes( {
									manualPost3: parseInt( val, 10 ) || 0,
								} )
							}
						/>
					</>
				) }
			</PanelBody>
		</InspectorControls>
	);
}

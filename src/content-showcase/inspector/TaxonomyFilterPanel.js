/**
 * "Category & Taxonomy Filter" Inspector panel.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function TaxonomyFilterPanel( {
	attributes,
	setAttributes,
	activeTax,
	taxonomyOptions,
	termOptions,
} ) {
	const { enableManualSelection = false, taxonomyFilter = {} } = attributes;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Category & Taxonomy Filter', 'quovex-blocks' ) }
				initialOpen={ false }
			>
				{ enableManualSelection ? (
					<div
						style={ {
							padding: '10px 12px',
							background: '#fcf9e8',
							borderLeft: '3px solid #dba617',
							borderRadius: '3px',
						} }
					>
						<p
							style={ {
								fontSize: '12px',
								color: '#555',
								margin: 0,
								lineHeight: 1.4,
							} }
						>
							{ __(
								'Category filter is automatically disabled because Manual Post Selection is active.',
								'quovex-blocks'
							) }
						</p>
					</div>
				) : (
					<>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Taxonomy Filter', 'quovex-blocks' ) }
							value={ activeTax }
							options={ [
								{
									label: __(
										'-- All Taxonomies (No Filter) --',
										'quovex-blocks'
									),
									value: '',
								},
								...taxonomyOptions,
							] }
							onChange={ ( taxSlug ) => {
								if ( ! taxSlug ) {
									setAttributes( { taxonomyFilter: {} } );
								} else {
									setAttributes( {
										taxonomyFilter: {
											taxonomy: taxSlug,
											terms: [],
										},
									} );
								}
							} }
						/>

						{ !! activeTax && termOptions.length > 0 && (
							<SelectControl
								__nextHasNoMarginBottom
								label={ __(
									'Filter by Category / Term',
									'quovex-blocks'
								) }
								value={ taxonomyFilter?.terms?.[ 0 ] ?? '' }
								options={ [
									{
										label: __(
											'-- All Categories / Terms --',
											'quovex-blocks'
										),
										value: '',
									},
									...termOptions,
								] }
								onChange={ ( termId ) => {
									if ( ! termId ) {
										setAttributes( {
											taxonomyFilter: {
												taxonomy: activeTax,
												terms: [],
											},
										} );
									} else {
										setAttributes( {
											taxonomyFilter: {
												taxonomy: activeTax,
												terms: [
													parseInt( termId, 10 ),
												],
											},
										} );
									}
								} }
							/>
						) }
					</>
				) }
			</PanelBody>
		</InspectorControls>
	);
}

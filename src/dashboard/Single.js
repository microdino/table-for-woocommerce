const { __ } = wp.i18n;
import { useEffect, useState } from 'react';
import {
	Button,
	Card,
	CardBody,
	CheckboxControl,
	FormTokenField,
	RadioControl,
	Snackbar,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
	__experimentalHeading as Heading,
	__experimentalNumberControl as NumberControl,
	__experimentalText as Text,
} from '@wordpress/components';

const API_PATH = '/wp/v2/adpro_option';
const COLUMN_OPTIONS = [
	[ 'id', 'ID' ],
	[ 'sku', 'SKU' ],
	[ 'name', 'Name' ],
	[ 'description', 'Description' ],
	[ 'summary', 'Summary' ],
	[ 'date', 'Published date' ],
	[ 'modified', 'Last modified date' ],
	[ 'image', 'Image' ],
	[ 'stock', 'Stock' ],
	[ 'reviews', 'Reviews' ],
	[ 'weight', 'Weight' ],
	[ 'dimensions', 'Dimensions' ],
	[ 'price', 'Price' ],
	[ 'buy', 'Buy' ],
	[ 'button', 'Button' ],
	[ 'total', 'Total' ],
	[ 'categories', 'Categories' ],
	[ 'tags', 'Tags' ],
	[ 'tax:pa_color', 'Color' ],
	[ 'tax:pa_size', 'Size' ],
	[ 'author', 'Author' ],
].map( ( [ value, label ] ) => ( { value, label } ) );
const FILTER_OPTIONS = [
	[ 'categories', 'Categories' ],
	[ 'tags', 'Tags' ],
	[ 'tax:pa_color', 'Color' ],
	[ 'tax:pa_size', 'Size' ],
	[ 'product_type', 'Product Type' ],
	[ 'product_visibility', 'Product Visibility' ],
	[ 'author', 'Author' ],
].map( ( [ value, label ] ) => ( { value, label } ) );
const makeItem = ( value, options ) => {
	const option = options.find( ( item ) => item.value === value );
	return {
		id: `${ value }-${ Date.now() }-${ Math.random() }`,
		value,
		label: option?.label || value,
	};
};
const normalizeItems = ( value, options ) => {
	const aliases = {
		color: 'tax:pa_color',
		size: 'tax:pa_size',
		'add-to-cart': 'buy',
	};
	const normalized = Array.isArray( value )
		? value
		: typeof value === 'string'
		? value.split( ',' ).filter( Boolean )
		: [];
	return normalized.map( ( item ) => {
		const current =
			typeof item === 'string' ? { value: item.trim() } : item;
		const itemValue = aliases[ current.value ] || current.value;
		const option = options.find( ( choice ) => choice.value === itemValue );
		return {
			...current,
			id: current.id || `${ itemValue }-${ Math.random() }`,
			value: itemValue,
			label: current.label || option?.label || itemValue,
		};
	} );
};
const defaults = {
	tableName: '',
	displayIn: 'shortcode',
	queryType: 'all',
	queryCategories: [],
	queryTags: [],
	queryProducts: [],
	queryBrands: [],
	excludeProducts: [],
	excludeCategories: [],
	shopPage: false,
	searchPage: false,
	categoryPage: false,
	tagPage: false,
	attributesPage: false,
	brandPage: false,
	columns: [ 'sku', 'name', 'stock', 'price', 'total', 'buy' ].map(
		( value ) => makeItem( value, COLUMN_OPTIONS )
	),
	quantitySelector: true,
	variationStyle: 'dropdown',
	productsPerPage: 20,
	searchFilters: [ makeItem( 'tax:pa_color', FILTER_OPTIONS ) ],
	sortBy: 'default',
	sortDirection: 'automatic',
	descriptionLength: 15,
};

const QueryPicker = ( { label, type, value = [], onChange } ) => {
	const [ search, setSearch ] = useState( '' );
	const [ options, setOptions ] = useState( [] );
	const [ labels, setLabels ] = useState( {} );
	useEffect( () => {
		let active = true;
		const timer = setTimeout( async () => {
			try {
				const results = await wp.apiFetch( {
					path: `/prta/v1/query-options?type=${ type }&search=${ encodeURIComponent(
						search
					) }&include=${ value.join( ',' ) }`,
				} );
				if ( active ) {
					setOptions( results );
					setLabels( ( current ) => ( {
						...current,
						...Object.fromEntries(
							results.map( ( item ) => [
								item.value,
								item.label,
							] )
						),
					} ) );
				}
			} catch ( error ) {
				if ( active ) setOptions( [] );
			}
		}, 200 );
		return () => {
			active = false;
			clearTimeout( timer );
		};
	}, [ type, search, value.join( ',' ) ] );
	const tokenFor = ( id ) => `${ labels[ id ] || `#${ id }` } (#${ id })`;
	const tokenId = ( token ) => {
		const text = typeof token === 'string' ? token : token.value;
		return Number( text.match( /\(#(\d+)\)$/ )?.[ 1 ] || 0 );
	};
	return (
		<FormTokenField
			label={ label }
			placeholder={
				type === 'products'
					? __(
							'Search and select products…',
							'table-for-woocommerce'
					  )
					: type === 'categories'
					? __(
							'Search and select categories…',
							'table-for-woocommerce'
					  )
					: type === 'tags'
					? __( 'Search and select tags…', 'table-for-woocommerce' )
					: __( 'Search and select brands…', 'table-for-woocommerce' )
			}
			value={ value.map( tokenFor ) }
			suggestions={ options
				.filter( ( option ) => ! value.includes( option.value ) )
				.map( ( option ) => tokenFor( option.value ) ) }
			onInputChange={ setSearch }
			onChange={ ( tokens ) =>
				onChange( [
					...new Set(
						tokens
							.map( tokenId )
							.filter(
								( id ) =>
									value.includes( id ) ||
									options.some(
										( option ) => option.value === id
									)
							)
					),
				] )
			}
			__experimentalValidateInput={ ( token ) => {
				const id = tokenId( token );
				return (
					value.includes( id ) ||
					options.some( ( option ) => option.value === id )
				);
			} }
			__experimentalShowHowTo={ false }
			__experimentalAutoSelectFirstMatch
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
};

const DynamicFieldList = ( { items, options, placeholder, onChange } ) => {
	const [ selected, setSelected ] = useState( '' );
	const [ dragged, setDragged ] = useState( null );
	const add = () => {
		if ( ! selected || items.some( ( item ) => item.value === selected ) )
			return;
		onChange( [ ...items, makeItem( selected, options ) ] );
		setSelected( '' );
	};
	const move = ( target ) => {
		if ( dragged === null || dragged === target ) return;
		const next = [ ...items ];
		const [ item ] = next.splice( dragged, 1 );
		next.splice( target, 0, item );
		onChange( next );
		setDragged( target );
	};
	return (
		<div className="prta-dynamic-fields">
			{ items.map( ( item, index ) => (
				<div
					className="prta-dynamic-field"
					key={ item.id }
					draggable
					onDragStart={ () => setDragged( index ) }
					onDragOver={ ( event ) => {
						event.preventDefault();
						move( index );
					} }
					onDragEnd={ () => setDragged( null ) }
				>
					<span
						className="prta-drag-handle"
						title={ __(
							'Drag to reorder',
							'table-for-woocommerce'
						) }
					>
						⠿
					</span>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						aria-label={ __(
							'Column or filter title',
							'table-for-woocommerce'
						) }
						value={ item.label }
						onChange={ ( label ) =>
							onChange(
								items.map( ( current ) =>
									current.id === item.id
										? { ...current, label }
										: current
								)
							)
						}
					/>
					<code>{ item.value }</code>
					<Button
						className="prta-remove-field"
						icon="dismiss"
						label={ __( 'Remove', 'table-for-woocommerce' ) }
						onClick={ () =>
							onChange(
								items.filter(
									( current ) => current.id !== item.id
								)
							)
						}
					/>
				</div>
			) ) }
			<div className="prta-field-add">
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					value={ selected }
					onChange={ setSelected }
					options={ [
						{ value: '', label: placeholder },
						...options.filter(
							( option ) =>
								! items.some(
									( item ) => item.value === option.value
								)
						),
					] }
				/>
				<Button
					__next40pxDefaultSize
					variant="secondary"
					icon="plus"
					disabled={ ! selected }
					onClick={ add }
				>
					{ __( 'Add', 'table-for-woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

const Section = ( { title, description, children } ) => (
	<section className="prta-editor-section">
		<div className="prta-editor-section-heading">
			<Heading level={ 4 }>{ title }</Heading>
			{ description && <Text variant="muted">{ description }</Text> }
		</div>
		<div className="prta-editor-fields">{ children }</div>
	</section>
);

const Single = ( { tableId, onTableIdResolved, onCancel } ) => {
	const [ settings, setSettings ] = useState( defaults );
	const [ activeTableId, setActiveTableId ] = useState( tableId );
	const [ postStatus, setPostStatus ] = useState( 'publish' );
	const [ loading, setLoading ] = useState( Boolean( tableId ) );
	const [ saving, setSaving ] = useState( false );
	const [ snackbar, setSnackbar ] = useState( null );
	const [ copied, setCopied ] = useState( false );
	const set = ( key, value ) =>
		setSettings( ( current ) => ( { ...current, [ key ]: value } ) );

	useEffect( () => {
		setActiveTableId( tableId );
		if ( ! tableId ) {
			setSettings( defaults );
			setPostStatus( 'publish' );
			setLoading( false );
			return;
		}
		setLoading( true );
		wp.apiFetch( { path: `${ API_PATH }/${ tableId }?context=edit` } )
			.then( ( post ) => {
				setPostStatus( post.status );
				let saved = {};
				try {
					saved = JSON.parse( post.content?.raw || '{}' );
				} catch ( parseError ) {
					saved = {};
				}
				setSettings( {
					...defaults,
					...saved,
					sortBy:
						saved.sortBy === 'sorting'
							? 'default'
							: saved.sortBy || defaults.sortBy,
					tableName: post.title?.raw || saved.tableName || '',
					columns: normalizeItems(
						saved.columns ?? defaults.columns,
						COLUMN_OPTIONS
					),
					searchFilters: normalizeItems(
						saved.searchFilters ??
							saved.searchFilter ??
							defaults.searchFilters,
						FILTER_OPTIONS
					),
				} );
			} )
			.catch( ( requestError ) =>
				setSnackbar( { type: 'error', message: requestError.message } )
			)
			.finally( () => setLoading( false ) );
	}, [ tableId ] );

	const saveSettings = async () => {
		if ( ! settings.tableName.trim() ) {
			setSnackbar( {
				type: 'error',
				message: __(
					'Please enter a table name.',
					'table-for-woocommerce'
				),
			} );
			return;
		}
		setSaving( true );
		setSnackbar( null );
		try {
			const path = activeTableId
				? `${ API_PATH }/${ activeTableId }`
				: API_PATH;
			const savedPost = await wp.apiFetch( {
				path,
				method: 'POST',
				data: {
					title: settings.tableName.trim(),
					content: JSON.stringify( settings ),
					status: postStatus,
				},
			} );
			setActiveTableId( savedPost.id );
			onTableIdResolved?.( savedPost.id );
			setSnackbar( {
				type: 'success',
				message: activeTableId
					? __(
							'Table updated successfully.',
							'table-for-woocommerce'
					  )
					: __(
							'Table created successfully.',
							'table-for-woocommerce'
					  ),
			} );
		} catch ( requestError ) {
			setSnackbar( {
				type: 'error',
				message:
					requestError.message ||
					__( 'Unable to save table.', 'table-for-woocommerce' ),
			} );
		} finally {
			setSaving( false );
		}
	};
	const shortcode = activeTableId
		? `[product_table id="${ activeTableId }"]`
		: __( 'Available after saving', 'table-for-woocommerce' );
	const copyShortcode = async () => {
		if ( activeTableId ) {
			await navigator.clipboard.writeText( shortcode );
			setCopied( true );
		}
	};

	if ( loading ) {
		return (
			<Card
				className="prta-editor-card"
				style={ { margin: '20px 20px 20px 0' } }
			>
				<CardBody>
					<Spinner />
				</CardBody>
			</Card>
		);
	}

	return (
		<Card
			className="prta-editor-card"
			style={ { margin: '20px 20px 20px 0' } }
		>
			<CardBody>
				{ snackbar && (
					<div
						style={ {
							position: 'fixed',
							right: '32px',
							bottom: '32px',
							zIndex: 100000,
						} }
					>
						<Snackbar
							className={ `adpro-snackbar is-${ snackbar.type }` }
							explicitDismiss={ snackbar.type === 'error' }
							onRemove={ () => setSnackbar( null ) }
						>
							{ snackbar.type === 'success'
								? __( 'Success:', 'table-for-woocommerce' )
								: __( 'Error:', 'table-for-woocommerce' ) }
							{ snackbar.message }
						</Snackbar>
					</div>
				) }
				<Section
					title={
						activeTableId
							? __( 'Edit Table', 'table-for-woocommerce' )
							: __( 'Add New Table', 'table-for-woocommerce' )
					}
				>
					<TextControl
						label={ __( 'Table Name', 'table-for-woocommerce' ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						value={ settings.tableName }
						onChange={ ( value ) => set( 'tableName', value ) }
					/>
				</Section>
				<Section title={ __( 'Display In', 'table-for-woocommerce' ) }>
					<RadioControl
						label={ __( 'Display In', 'table-for-woocommerce' ) }
						hideLabelFromVision
						selected={ settings.displayIn }
						onChange={ ( value ) => set( 'displayIn', value ) }
						options={ [
							{
								label: __(
									'Display Using Shortcode',
									'table-for-woocommerce'
								),
								value: 'shortcode',
							},
							{
								label: __(
									'Display on Shop Specific Page',
									'table-for-woocommerce'
								),
								value: 'shop',
							},
						] }
					/>
					{ settings.displayIn === 'shortcode' ? (
						<>
							<div className="prta-shortcode-control">
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __(
										'Shortcode',
										'table-for-woocommerce'
									) }
									value={ shortcode }
									readOnly
								/>
								<Button
									className="components-clipboard-button prta-copy-shortcode"
									variant="secondary"
									icon={ copied ? 'yes' : 'admin-page' }
									label={
										copied
											? __(
													'Copied',
													'table-for-woocommerce'
											  )
											: __(
													'Copy shortcode',
													'table-for-woocommerce'
											  )
									}
									disabled={ ! activeTableId }
									onClick={ copyShortcode }
								/>
							</div>
							<div className="prta-editor-row">
								<SelectControl
									label={ __(
										'Product Query',
										'table-for-woocommerce'
									) }
									value={ settings.queryType }
									onChange={ ( value ) =>
										set( 'queryType', value )
									}
									options={ [
										{
											value: 'all',
											label: __(
												'All Products',
												'table-for-woocommerce'
											),
										},
										{
											value: 'categories',
											label: __(
												'Specific Categories',
												'table-for-woocommerce'
											),
										},
										{
											value: 'tags',
											label: __(
												'Specific Tags',
												'table-for-woocommerce'
											),
										},
										{
											value: 'products',
											label: __(
												'Specific Products',
												'table-for-woocommerce'
											),
										},
										{
											value: 'brands',
											label: __(
												'Specific Brands',
												'table-for-woocommerce'
											),
										},
									] }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								{ settings.queryType !== 'all' && (
									<QueryPicker
										label={ __(
											'Select items',
											'table-for-woocommerce'
										) }
										type={ settings.queryType }
										value={
											settings[
												`query${
													settings.queryType[ 0 ].toUpperCase() +
													settings.queryType.slice(
														1
													)
												}`
											] || []
										}
										onChange={ ( value ) =>
											set(
												`query${
													settings.queryType[ 0 ].toUpperCase() +
													settings.queryType.slice(
														1
													)
												}`,
												value
											)
										}
									/>
								) }
							</div>
							{ settings.queryType !== 'products' && (
								<div className="prta-editor-row">
									{ settings.queryType !== 'products' && (
										<QueryPicker
											label={ __(
												'Exclude Specific Products',
												'table-for-woocommerce'
											) }
											type="products"
											value={ settings.excludeProducts }
											onChange={ ( value ) =>
												set( 'excludeProducts', value )
											}
										/>
									) }
									{ settings.queryType === 'all' && (
										<QueryPicker
											label={ __(
												'Exclude Categories',
												'table-for-woocommerce'
											) }
											type="categories"
											value={ settings.excludeCategories }
											onChange={ ( value ) =>
												set(
													'excludeCategories',
													value
												)
											}
										/>
									) }
								</div>
							) }
						</>
					) : (
						<div className="prta-page-options">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __(
									'Shop Page',
									'table-for-woocommerce'
								) }
								checked={ settings.shopPage }
								onChange={ ( value ) =>
									set( 'shopPage', value )
								}
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __(
									'Product Search Page',
									'table-for-woocommerce'
								) }
								checked={ settings.searchPage }
								onChange={ ( value ) =>
									set( 'searchPage', value )
								}
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __(
									'Product Category Page',
									'table-for-woocommerce'
								) }
								checked={ settings.categoryPage }
								onChange={ ( value ) =>
									set( 'categoryPage', value )
								}
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __(
									'Product Tag Page',
									'table-for-woocommerce'
								) }
								checked={ settings.tagPage }
								onChange={ ( value ) =>
									set( 'tagPage', value )
								}
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __(
									'Product Attributes Page',
									'table-for-woocommerce'
								) }
								checked={ settings.attributesPage }
								onChange={ ( value ) =>
									set( 'attributesPage', value )
								}
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __(
									'Product Brand Page',
									'table-for-woocommerce'
								) }
								checked={ settings.brandPage }
								onChange={ ( value ) =>
									set( 'brandPage', value )
								}
							/>
						</div>
					) }
				</Section>
				<Section title={ __( 'Columns', 'table-for-woocommerce' ) }>
					<DynamicFieldList
						items={ settings.columns }
						options={ COLUMN_OPTIONS }
						placeholder={ __(
							'Choose a Column',
							'table-for-woocommerce'
						) }
						onChange={ ( value ) => set( 'columns', value ) }
					/>
				</Section>
				<Section title={ __( 'Others', 'table-for-woocommerce' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ settings.quantitySelector }
						onChange={ ( value ) =>
							set( 'quantitySelector', value )
						}
						label={ __(
							'Enable Quantity Selector',
							'table-for-woocommerce'
						) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Variation Display Style',
							'table-for-woocommerce'
						) }
						value={ settings.variationStyle }
						options={ [
							{
								value: 'link',
								label: __(
									'Read more link to product page',
									'table-for-woocommerce'
								),
							},
							{
								value: 'dropdown',
								label: __(
									'Display variations as dropdown',
									'table-for-woocommerce'
								),
							},
							{
								value: 'separate',
								label: __(
									'Display variations as separate rows',
									'table-for-woocommerce'
								),
							},
						] }
						onChange={ ( value ) => set( 'variationStyle', value ) }
					/>
					<NumberControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Products per page',
							'table-for-woocommerce'
						) }
						value={ settings.productsPerPage }
						onChange={ ( value ) =>
							set( 'productsPerPage', Number( value ) )
						}
						min={ 1 }
					/>
				</Section>
				<Section
					title={ __( 'Search Filters', 'table-for-woocommerce' ) }
					description={ __(
						'Choose the filters to show above the table.',
						'table-for-woocommerce'
					) }
				>
					<div className="prta-search-filter-options">
						{ FILTER_OPTIONS.map( ( option ) => (
							<CheckboxControl
								key={ option.value }
								label={ option.label }
								checked={ settings.searchFilters.some(
									( item ) => item.value === option.value
								) }
								onChange={ ( checked ) =>
									set(
										'searchFilters',
										checked
											? [
													...settings.searchFilters,
													makeItem(
														option.value,
														FILTER_OPTIONS
													),
											  ]
											: settings.searchFilters.filter(
													( item ) =>
														item.value !==
														option.value
											  )
									)
								}
								__nextHasNoMarginBottom
							/>
						) ) }
					</div>
					<div className="prta-editor-row">
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Sort By', 'table-for-woocommerce' ) }
							value={ settings.sortBy }
							options={ [
								{
									label: 'Default WooCommerce',
									value: 'default',
								},
								{
									label: 'ID',
									value: 'id',
								},
								{
									label: 'Name',
									value: 'name',
								},
								{
									label: 'Published',
									value: 'published',
								},
								{
									label: 'Modified',
									value: 'modified',
								},
								{
									label: 'Sales',
									value: 'sales',
								},
								{
									label: 'Rating',
									value: 'rating',
								},
								{
									label: 'Random',
									value: 'random',
								},
								{
									label: 'Price',
									value: 'price',
								},
							] }
							onChange={ ( value ) => set( 'sortBy', value ) }
						/>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __(
								'Sort Direction',
								'table-for-woocommerce'
							) }
							value={ settings.sortDirection }
							options={ [
								{
									label: 'Automatic',
									value: 'automatic',
								},
								{
									label: 'Ascending',
									value: 'ascending',
								},
								{
									label: 'Descending',
									value: 'descending',
								},
							] }
							onChange={ ( value ) =>
								set( 'sortDirection', value )
							}
						/>
					</div>
				</Section>
				<Section
					title={ __( 'Content', 'table-for-woocommerce' ) }
					description={ __(
						'Settings for table actions and descriptions.',
						'table-for-woocommerce'
					) }
				>
					<NumberControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Description Length (Words)',
							'table-for-woocommerce'
						) }
						value={ settings.descriptionLength }
						onChange={ ( value ) =>
							set( 'descriptionLength', Number( value ) )
						}
						min={ 0 }
					/>
					<div className="prta-editor-actions">
						<Button
							variant="secondary"
							onClick={ onCancel }
							disabled={ saving }
						>
							{ __( 'Cancel', 'table-for-woocommerce' ) }
						</Button>{ ' ' }
						<Button
							icon="saved"
							variant="primary"
							onClick={ saveSettings }
							isBusy={ saving }
							disabled={ saving }
						>
							{ saving
								? __( 'Saving…', 'table-for-woocommerce' )
								: __( 'Save Table', 'table-for-woocommerce' ) }
						</Button>
					</div>
				</Section>
			</CardBody>
		</Card>
	);
};
export default Single;

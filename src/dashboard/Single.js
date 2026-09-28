const { __ } = wp.i18n;
import { useEffect, useState } from 'react';
import {
	Button,
	Card,
	CardBody,
	CheckboxControl,
	RadioControl,
	Snackbar,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
	__experimentalDivider as Divider,
	__experimentalGrid as Grid,
	__experimentalHeading as Heading,
	__experimentalHStack as HStack,
	__experimentalNumberControl as NumberControl,
	__experimentalText as Text,
	__experimentalVStack as VStack,
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
	sortBy: 'sorting',
	sortDirection: 'automatic',
	cartLocation: 'below',
	selectAll: false,
	descriptionLength: 15,
};

const DynamicFieldList = ( { items, options, placeholder, onChange } ) => {
	const [ selected, setSelected ] = useState( '' );
	const [ editing, setEditing ] = useState( null );
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
					<Button
						className="prta-edit-label"
						icon="edit"
						label={ __( 'Edit title', 'table-for-woocommerce' ) }
						onClick={ () =>
							setEditing( editing === item.id ? null : item.id )
						}
					/>
					{ editing === item.id ? (
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
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
							onBlur={ () => setEditing( null ) }
						/>
					) : (
						<strong className="prta-field-title">
							{ item.label }
						</strong>
					) }
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
			<HStack alignment="left" className="prta-field-add">
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
					disabled={ ! selected }
					onClick={ add }
				>
					{ __( 'Add', 'table-for-woocommerce' ) }
				</Button>
			</HStack>
		</div>
	);
};

const Section = ( { title, description, children } ) => (
	<>
		<Grid templateColumns="1fr 4fr" gap={ 10 }>
			<VStack alignment="topLeft" spacing={ 2 }>
				<Heading level={ 4 }>{ title }</Heading>
				{ description && <Text variant="muted">{ description }</Text> }
			</VStack>
			<VStack spacing={ 5 } style={ { maxWidth: '500px' } }>
				{ children }
			</VStack>
		</Grid>
		<Divider margin={ 6 } style={ { borderColor: 'rgba(0, 0, 0, 0.1)' } } />
	</>
);

const Single = ( { tableId, onTableIdResolved, onCancel } ) => {
	const [ settings, setSettings ] = useState( defaults );
	const [ activeTableId, setActiveTableId ] = useState( tableId );
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
			setLoading( false );
			return;
		}
		setLoading( true );
		wp.apiFetch( { path: `${ API_PATH }/${ tableId }?context=edit` } )
			.then( ( post ) => {
				let saved = {};
				try {
					saved = JSON.parse( post.content?.raw || '{}' );
				} catch ( parseError ) {
					saved = {};
				}
				setSettings( {
					...defaults,
					...saved,
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
					status: 'publish',
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

	if ( loading )
		return (
			<Card style={ { margin: '20px 20px 20px 0' } }>
				<CardBody>
					<Spinner />
				</CardBody>
			</Card>
		);
	return (
		<Card style={ { margin: '20px 20px 20px 0' } }>
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
								? __( 'Success: ', 'table-for-woocommerce' )
								: __( 'Error: ', 'table-for-woocommerce' ) }
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
						<div>
							<TextControl
								label={ __(
									'Shortcode',
									'table-for-woocommerce'
								) }
								value={ shortcode }
								readOnly
							/>
							<Button
								variant="secondary"
								disabled={ ! activeTableId }
								onClick={ copyShortcode }
							>
								{ copied
									? __( 'Copied', 'table-for-woocommerce' )
									: __( 'Copy', 'table-for-woocommerce' ) }
							</Button>
						</div>
					) : (
						<>
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
						</>
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
						'Add, rename, remove, and reorder filters.',
						'table-for-woocommerce'
					) }
				>
					<DynamicFieldList
						items={ settings.searchFilters }
						options={ FILTER_OPTIONS }
						placeholder={ __(
							'Choose a Search Filter',
							'table-for-woocommerce'
						) }
						onChange={ ( value ) => set( 'searchFilters', value ) }
					/>
					<SelectControl
						label={ __( 'Sort By', 'table-for-woocommerce' ) }
						value={ settings.sortBy }
						options={ [
							'sorting',
							'id',
							'name',
							'published',
							'modified',
							'sales',
							'rating',
							'random',
							'price',
						].map( ( value ) => ( { value, label: value } ) ) }
						onChange={ ( value ) => set( 'sortBy', value ) }
					/>
					<SelectControl
						label={ __(
							'Sort Direction',
							'table-for-woocommerce'
						) }
						value={ settings.sortDirection }
						options={ [
							'automatic',
							'ascending',
							'descending',
						].map( ( value ) => ( { value, label: value } ) ) }
						onChange={ ( value ) => set( 'sortDirection', value ) }
					/>
				</Section>
				<Section
					title={ __( 'Content', 'table-for-woocommerce' ) }
					description={ __(
						'Settings for table actions and descriptions.',
						'table-for-woocommerce'
					) }
				>
					<SelectControl
						label={ __(
							'Add to Cart Location',
							'table-for-woocommerce'
						) }
						value={ settings.cartLocation }
						options={ [
							{
								value: 'above',
								label: __(
									'Above Table',
									'table-for-woocommerce'
								),
							},
							{
								value: 'below',
								label: __(
									'Below Table',
									'table-for-woocommerce'
								),
							},
							{
								value: 'all',
								label: __(
									'Above & Below Table',
									'table-for-woocommerce'
								),
							},
						] }
						onChange={ ( value ) => set( 'cartLocation', value ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ settings.selectAll }
						onChange={ ( value ) => set( 'selectAll', value ) }
						label={ __(
							'Select all products in the table header',
							'table-for-woocommerce'
						) }
					/>
					<NumberControl
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
					<div>
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

const { __ } = wp.i18n;
import { useEffect, useState } from 'react';
import Pagination from './Pagination';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	CheckboxControl,
	ClipboardButton,
	DropdownMenu,
	Notice,
	SearchControl,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
	__experimentalDivider as Divider,
	__experimentalHeading as Heading,
	__experimentalHStack as HStack,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';

const API_PATH = '/wp/v2/adpro_option';
const getSettings = ( post ) => {
	try {
		return JSON.parse(
			post.content?.raw || post.content?.rendered || '{}'
		);
	} catch ( error ) {
		return {};
	}
};

const Dashboard = ( { onEdit } ) => {
	const perPage = 5;
	const [ tables, setTables ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ search, setSearch ] = useState( '' );
	const [ selected, setSelected ] = useState( [] );
	const [ bulkAction, setBulkAction ] = useState( '' );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ hasCopied, setHasCopied ] = useState( null );

	const loadTables = async () => {
		setLoading( true );
		setError( '' );
		try {
			const response = await wp.apiFetch( {
				path: `${ API_PATH }?context=edit&status=publish,draft&per_page=${ perPage }&page=${ page }&search=${ encodeURIComponent(
					search
				) }`,
				parse: false,
			} );
			setTables( await response.json() );
			setTotalPages(
				Number( response.headers.get( 'X-WP-TotalPages' ) ) || 1
			);
		} catch ( requestError ) {
			setError(
				requestError.message ||
					__( 'Unable to load tables.', 'table-for-woocommerce' )
			);
		} finally {
			setLoading( false );
		}
	};
	useEffect( () => {
		loadTables();
	}, [ page, search ] );

	const updateStatus = async ( table ) => {
		try {
			await wp.apiFetch( {
				path: `${ API_PATH }/${ table.id }`,
				method: 'POST',
				data: {
					status: table.status === 'publish' ? 'draft' : 'publish',
				},
			} );
			loadTables();
		} catch ( requestError ) {
			setError( requestError.message );
		}
	};
	const deleteTables = async ( ids ) => {
		if (
			! ids.length ||
			! window.confirm(
				__( 'Delete the selected table(s)?', 'table-for-woocommerce' )
			)
		)
			return;
		try {
			await Promise.all(
				ids.map( ( id ) =>
					wp.apiFetch( {
						path: `${ API_PATH }/${ id }?force=true`,
						method: 'DELETE',
					} )
				)
			);
			setSelected( [] );
			loadTables();
		} catch ( requestError ) {
			setError( requestError.message );
		}
	};
	const applyBulk = async () => {
		if ( bulkAction === 'delete' ) return deleteTables( selected );
		if (
			! selected.length ||
			! [ 'enable', 'disable' ].includes( bulkAction )
		)
			return;
		try {
			await Promise.all(
				selected.map( ( id ) =>
					wp.apiFetch( {
						path: `${ API_PATH }/${ id }`,
						method: 'POST',
						data: {
							status:
								bulkAction === 'enable' ? 'publish' : 'draft',
						},
					} )
				)
			);
			setSelected( [] );
			loadTables();
		} catch ( requestError ) {
			setError( requestError.message );
		}
	};

	return (
		<Card style={ { marginTop: '20px' } }>
			<CardHeader>
				<VStack style={ { width: '100%' } } spacing={ 3 }>
					<HStack alignment="edge">
						<Heading level={ 3 }>
							{ __( 'Table List', 'table-for-woocommerce' ) }
						</Heading>
						<Button
							icon="plus-alt2"
							variant="primary"
							onClick={ () => onEdit( null ) }
						>
							{ __( 'Add New Table', 'table-for-woocommerce' ) }
						</Button>
					</HStack>
					<Divider />
					<HStack alignment="edge" spacing={ 3 }>
						<HStack alignment="left" spacing={ 2 }>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								value={ bulkAction }
								options={ [
									{
										label: __(
											'Bulk Actions',
											'table-for-woocommerce'
										),
										value: '',
									},
									{
										label: __(
											'Delete',
											'table-for-woocommerce'
										),
										value: 'delete',
									},
									{
										label: __(
											'Enable',
											'table-for-woocommerce'
										),
										value: 'enable',
									},
									{
										label: __(
											'Disable',
											'table-for-woocommerce'
										),
										value: 'disable',
									},
								] }
								onChange={ setBulkAction }
							/>
							<Button
								__next40pxDefaultSize
								variant="primary"
								disabled={ ! bulkAction || ! selected.length }
								onClick={ applyBulk }
							>
								{ __( 'Apply', 'table-for-woocommerce' ) }
							</Button>
						</HStack>
						<SearchControl
							__nextHasNoMarginBottom
							value={ search }
							placeholder={ __(
								'Search…',
								'table-for-woocommerce'
							) }
							onChange={ ( value ) => {
								setPage( 1 );
								setSearch( value );
							} }
						/>
					</HStack>
				</VStack>
			</CardHeader>
			<CardBody>
				{ error && (
					<Notice
						status="error"
						isDismissible
						onRemove={ () => setError( '' ) }
					>
						{ error }
					</Notice>
				) }
				{ loading ? (
					<Spinner />
				) : (
					<>
						<HStack justify="space-between" gap={ 4 } wrap>
							<CheckboxControl
								__nextHasNoMarginBottom
								checked={
									tables.length > 0 &&
									selected.length === tables.length
								}
								onChange={ ( checked ) =>
									setSelected(
										checked
											? tables.map(
													( table ) => table.id
											  )
											: []
									)
								}
							/>
							<Text
								weight={ 500 }
								style={ { flex: 1, maxWidth: '80px' } }
							>
								#{ __( 'ID', 'table-for-woocommerce' ) }
							</Text>
							<Text weight={ 500 } style={ { flex: 1 } }>
								{ __( 'Table Name', 'table-for-woocommerce' ) }
							</Text>
							<Text
								weight={ 500 }
								style={ { flex: 1, maxWidth: '100px' } }
							>
								{ __( 'Status', 'table-for-woocommerce' ) }
							</Text>
							<Text weight={ 500 } style={ { flex: 1 } }>
								{ __( 'Display In', 'table-for-woocommerce' ) }
							</Text>
							<Text
								weight={ 500 }
								style={ { flex: 1, maxWidth: '200px' } }
							>
								{ __( 'Shortcode', 'table-for-woocommerce' ) }
							</Text>
							<Text
								weight={ 500 }
								style={ {
									flex: 1,
									textAlign: 'right',
									maxWidth: '80px',
								} }
							>
								{ __( 'Action', 'table-for-woocommerce' ) }
							</Text>
						</HStack>
						{ ! tables.length && (
							<>
								<Divider margin="5" />
								<Text>
									{ __(
										'No tables found.',
										'table-for-woocommerce'
									) }
								</Text>
							</>
						) }
						{ tables.map( ( table ) => {
							const settings = getSettings( table );
							const shortcode = `[product_table id="${ table.id }"]`;
							return (
								<div key={ table.id }>
									<Divider margin="5" />
									<HStack
										justify="space-between"
										gap={ 4 }
										wrap
									>
										<CheckboxControl
											__nextHasNoMarginBottom
											checked={ selected.includes(
												table.id
											) }
											onChange={ ( checked ) =>
												setSelected(
													checked
														? [
																...selected,
																table.id,
														  ]
														: selected.filter(
																( id ) =>
																	id !==
																	table.id
														  )
												)
											}
										/>
										<Button
											style={ {
												flex: 1,
												maxWidth: '80px',
											} }
											variant="link"
											onClick={ () => onEdit( table.id ) }
										>
											#{ table.id }
										</Button>
										<Button
											style={ { flex: 1 } }
											variant="link"
											onClick={ () => onEdit( table.id ) }
										>
											{ table.title?.rendered ||
												__(
													'(no title)',
													'table-for-woocommerce'
												) }
										</Button>
										<span
											style={ {
												flex: 1,
												maxWidth: '100px',
											} }
										>
											<ToggleControl
												__nextHasNoMarginBottom
												checked={
													table.status === 'publish'
												}
												onChange={ () =>
													updateStatus( table )
												}
											/>
										</span>
										<Text style={ { flex: 1 } }>
											{ settings.displayIn === 'shop'
												? __(
														'Shop pages',
														'table-for-woocommerce'
												  )
												: __(
														'Shortcode',
														'table-for-woocommerce'
												  ) }
										</Text>
										<span
											style={ {
												flex: 1,
												maxWidth: '200px',
											} }
										>
											<HStack>
												<TextControl
													className="adpro-w-full"
													__nextHasNoMarginBottom
													__next40pxDefaultSize
													value={ shortcode }
													readOnly
													label="shortcode"
													hideLabelFromVision
												/>
												<ClipboardButton
													text={ shortcode }
													variant={
														hasCopied === table.id
															? 'primary'
															: 'secondary'
													}
													onCopy={ () =>
														setHasCopied( table.id )
													}
													onFinishCopy={ () =>
														setHasCopied( null )
													}
												>
													{ hasCopied === table.id
														? __(
																'Copied',
																'table-for-woocommerce'
														  )
														: __(
																'Copy',
																'table-for-woocommerce'
														  ) }
												</ClipboardButton>
											</HStack>
										</span>
										<DropdownMenu
											icon="ellipsis"
											label={ __(
												'Table actions',
												'table-for-woocommerce'
											) }
											controls={ [
												{
													title: __(
														'Edit',
														'table-for-woocommerce'
													),
													icon: 'edit',
													onClick: () =>
														onEdit( table.id ),
												},
												{
													title: __(
														'Delete',
														'table-for-woocommerce'
													),
													icon: 'trash',
													onClick: () =>
														deleteTables( [
															table.id,
														] ),
												},
											] }
										/>
									</HStack>
								</div>
							);
						} ) }
						{ totalPages > 1 && (
							<>
								<Divider margin="5" />
								<Pagination
									currentPage={ page }
									totalPages={ totalPages }
									onPageChange={ setPage }
								/>
							</>
						) }
					</>
				) }
			</CardBody>
		</Card>
	);
};
export default Dashboard;

const { __, sprintf } = wp.i18n;
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
	Modal,
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
const SHOP_PAGE_OPTIONS = [
	[ 'shopPage', __( 'Shop Page', 'table-for-woocommerce' ) ],
	[ 'searchPage', __( 'Product Search Page', 'table-for-woocommerce' ) ],
	[ 'categoryPage', __( 'Product Category Page', 'table-for-woocommerce' ) ],
	[ 'tagPage', __( 'Product Tag Page', 'table-for-woocommerce' ) ],
	[
		'attributesPage',
		__( 'Product Attributes Page', 'table-for-woocommerce' ),
	],
	[ 'brandPage', __( 'Product Brand Page', 'table-for-woocommerce' ) ],
];
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
	const [ copyingId, setCopyingId ] = useState( null );
	const [ pendingDelete, setPendingDelete ] = useState( [] );
	const [ deleting, setDeleting ] = useState( false );
	const pendingTable =
		pendingDelete.length === 1
			? tables.find( ( table ) => table.id === pendingDelete[ 0 ] )
			: null;

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
	const copyTable = async ( table ) => {
		setCopyingId( table.id );
		setError( '' );
		try {
			const title = `${
				table.title?.raw || getSettings( table ).tableName || ''
			} Copy`;
			const settings = { ...getSettings( table ), tableName: title };
			await wp.apiFetch( {
				path: API_PATH,
				method: 'POST',
				data: {
					title,
					content: JSON.stringify( settings ),
					status: 'draft',
				},
			} );
			setPage( 1 );
			if ( page === 1 ) {
				loadTables();
			}
		} catch ( requestError ) {
			setError( requestError.message );
		} finally {
			setCopyingId( null );
		}
	};
	const deleteTables = async () => {
		if ( ! pendingDelete.length ) {
			return;
		}
		setDeleting( true );
		try {
			await Promise.all(
				pendingDelete.map( ( id ) =>
					wp.apiFetch( {
						path: `${ API_PATH }/${ id }?force=true`,
						method: 'DELETE',
					} )
				)
			);
			setSelected( [] );
			setPendingDelete( [] );
			loadTables();
		} catch ( requestError ) {
			setError( requestError.message );
		} finally {
			setDeleting( false );
		}
	};
	const applyBulk = async () => {
		if ( bulkAction === 'delete' ) {
			return setPendingDelete( selected );
		}
		if (
			! selected.length ||
			! [ 'enable', 'disable' ].includes( bulkAction )
		) {
			return;
		}
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
		<>
			<Card style={ { margin: '20px 20px 0 0' } }>
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
								{ __(
									'Add New Table',
									'table-for-woocommerce'
								) }
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
												'- Actions -',
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
									disabled={
										! bulkAction || ! selected.length
									}
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
							<div
								className="prta-dashboard-table-scroll"
								role="region"
								tabIndex={ 0 }
								aria-label={ __(
									'Product tables',
									'table-for-woocommerce'
								) }
							>
								<div className="prta-dashboard-table">
									<div className="prta-dashboard-row prta-dashboard-head">
										<CheckboxControl
											__nextHasNoMarginBottom
											label={ __(
												'Select all tables on this page',
												'table-for-woocommerce'
											) }
											className="prta-selection-checkbox"
											checked={
												tables.length > 0 &&
												selected.length ===
													tables.length
											}
											onChange={ ( checked ) =>
												setSelected(
													checked
														? tables.map(
																( table ) =>
																	table.id
														  )
														: []
												)
											}
										/>
										<Text weight={ 500 }>
											#
											{ __(
												'ID',
												'table-for-woocommerce'
											) }
										</Text>
										<Text weight={ 500 }>
											{ __(
												'Table Name',
												'table-for-woocommerce'
											) }
										</Text>
										<Text weight={ 500 }>
											{ __(
												'Status',
												'table-for-woocommerce'
											) }
										</Text>
										<Text weight={ 500 }>
											{ __(
												'Display In',
												'table-for-woocommerce'
											) }
										</Text>
										<Text weight={ 500 }>
											{ __(
												'Shortcode',
												'table-for-woocommerce'
											) }
										</Text>
										<Text
											className="prta-dashboard-action"
											weight={ 500 }
										>
											{ __(
												'Action',
												'table-for-woocommerce'
											) }
										</Text>
									</div>
									{ ! tables.length && (
										<>
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
											<div
												className="prta-dashboard-row"
												key={ table.id }
											>
												<CheckboxControl
													__nextHasNoMarginBottom
													label={ sprintf(
														/* translators: %s: table name. */
														__(
															'Select %s',
															'table-for-woocommerce'
														),
														table.title?.raw ||
															settings.tableName ||
															String( table.id )
													) }
													className="prta-selection-checkbox"
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
																		(
																			id
																		) =>
																			id !==
																			table.id
																  )
														)
													}
												/>
												<Button
													variant="link"
													onClick={ () =>
														onEdit( table.id )
													}
												>
													#{ table.id }
												</Button>
												<Button
													variant="link"
													onClick={ () =>
														onEdit( table.id )
													}
												>
													{ table.title?.rendered ||
														__(
															'(no title)',
															'table-for-woocommerce'
														) }
												</Button>
												<span>
													<ToggleControl
														__nextHasNoMarginBottom
														checked={
															table.status ===
															'publish'
														}
														onChange={ () =>
															updateStatus(
																table
															)
														}
													/>
												</span>
												<div className="prta-dashboard-display">
													{ settings.displayIn ===
													'shop'
														? SHOP_PAGE_OPTIONS.some(
																( [ key ] ) =>
																	settings[
																		key
																	]
														  )
															? SHOP_PAGE_OPTIONS.filter(
																	( [
																		key,
																	] ) =>
																		settings[
																			key
																		]
															  ).map(
																	( [
																		key,
																		label,
																	] ) => (
																		<span
																			key={
																				key
																			}
																		>
																			{
																				label
																			}
																		</span>
																	)
															  )
															: __(
																	'No pages selected',
																	'table-for-woocommerce'
															  )
														: __(
																'Shortcode',
																'table-for-woocommerce'
														  ) }
												</div>
												<span className="prta-dashboard-shortcode">
													{ settings.displayIn !==
														'shop' && (
														<div className="prta-dashboard-copy">
															<TextControl
																className="adpro-w-full"
																__nextHasNoMarginBottom
																__next40pxDefaultSize
																value={
																	shortcode
																}
																readOnly
																label="shortcode"
																hideLabelFromVision
															/>
															<ClipboardButton
																text={
																	shortcode
																}
																variant="secondary"
																icon={
																	hasCopied ===
																	table.id
																		? 'yes'
																		: 'admin-page'
																}
																label={
																	hasCopied ===
																	table.id
																		? __(
																				'Copied',
																				'table-for-woocommerce'
																		  )
																		: __(
																				'Copy shortcode',
																				'table-for-woocommerce'
																		  )
																}
																onCopy={ () =>
																	setHasCopied(
																		table.id
																	)
																}
																onFinishCopy={ () =>
																	setHasCopied(
																		null
																	)
																}
															/>
														</div>
													) }
												</span>
												<div className="prta-dashboard-action">
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
																	onEdit(
																		table.id
																	),
															},
															{
																title: __(
																	'Copy',
																	'table-for-woocommerce'
																),
																icon: 'admin-page',
																isDisabled:
																	copyingId ===
																	table.id,
																onClick: () =>
																	copyTable(
																		table
																	),
															},
															{
																title: __(
																	'Delete',
																	'table-for-woocommerce'
																),
																icon: 'trash',
																onClick: () =>
																	setPendingDelete(
																		[
																			table.id,
																		]
																	),
															},
														] }
													/>
												</div>
											</div>
										);
									} ) }
								</div>
							</div>
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
			{ pendingDelete.length > 0 && (
				<Modal
					role="alertdialog"
					title={
						pendingDelete.length === 1
							? __( 'Delete table?', 'table-for-woocommerce' )
							: __( 'Delete tables?', 'table-for-woocommerce' )
					}
					size="small"
					onRequestClose={ () =>
						! deleting && setPendingDelete( [] )
					}
					isDismissible={ ! deleting }
				>
					<p>
						{ pendingTable
							? sprintf(
									/* translators: %s: Product table name. */
									__(
										'Delete “%s”? This cannot be undone.',
										'table-for-woocommerce'
									),
									pendingTable.title?.raw ||
										pendingTable.title?.rendered ||
										__(
											'(no title)',
											'table-for-woocommerce'
										)
							  )
							: __(
									'Are you sure you want to delete the selected tables? This cannot be undone.',
									'table-for-woocommerce'
							  ) }
					</p>
					<HStack justify="flex-end" spacing={ 2 }>
						<Button
							disabled={ deleting }
							onClick={ () => setPendingDelete( [] ) }
						>
							{ __( 'Cancel', 'table-for-woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							isBusy={ deleting }
							disabled={ deleting }
							onClick={ deleteTables }
						>
							{ __( 'Delete', 'table-for-woocommerce' ) }
						</Button>
					</HStack>
				</Modal>
			) }
		</>
	);
};
export default Dashboard;

const { __ } = wp.i18n;
import { useEffect, useState } from 'react';
import Settings from './Settings';
import Dashboard from './Dashboard';
import Single from './Single';
import {
	Button,
	__experimentalHStack as HStack,
	__experimentalText as Text,
} from '@wordpress/components';

const getRoute = () => {
	const match = window.location.hash.match( /^#\/table\/(new|\d+)$/ );
	if ( match )
		return {
			nav: 'single',
			tableId: match[ 1 ] === 'new' ? null : Number( match[ 1 ] ),
		};
	return {
		nav: window.location.hash === '#/settings' ? 'settings' : 'dashboard',
		tableId: null,
	};
};

const MenuBar = () => {
	const initialRoute = getRoute();
	const [ currentNav, setCurrentNav ] = useState( initialRoute.nav );
	const [ tableId, setTableId ] = useState( initialRoute.tableId );
	const applyRoute = () => {
		const route = getRoute();
		setCurrentNav( route.nav );
		setTableId( route.tableId );
	};
	useEffect( () => {
		window.addEventListener( 'popstate', applyRoute );
		window.addEventListener( 'hashchange', applyRoute );
		return () => {
			window.removeEventListener( 'popstate', applyRoute );
			window.removeEventListener( 'hashchange', applyRoute );
		};
	}, [] );
	const setUrl = ( hash, replace = false ) => {
		const url = `${ window.location.pathname }${ window.location.search }${ hash }`;
		window.history[ replace ? 'replaceState' : 'pushState' ]( {}, '', url );
	};
	const editTable = ( id = null ) => {
		setTableId( id );
		setCurrentNav( 'single' );
		setUrl( `#/table/${ id || 'new' }` );
	};
	const navigate = ( nav ) => {
		setCurrentNav( nav );
		setTableId( null );
		setUrl( nav === 'settings' ? '#/settings' : '' );
	};
	const menuItems = [
		{ to: 'dashboard', label: __( 'Dashboard', 'table-for-woocommerce' ) },
		{ to: 'settings', label: __( 'Settings', 'table-for-woocommerce' ) },
	];

	return (
		<>
			<div
				style={ {
					margin: '0 auto',
					backgroundColor: '#fff',
					borderBottom: '1px solid #ddd',
					marginLeft: '-20px',
				} }
			>
				<HStack
					style={ { marginRight: '20px' } }
					spacing={ 2 }
					alignment="left"
				>
					<span
						style={ {
							padding: '10px 20px',
							marginRight: '20px',
							borderRight: '1px solid #ccc',
						} }
					>
						<HStack spacing={ 2 } alignment="left">
							<img
								width="55"
								src={ localize.url + 'assets/img/logo.svg' }
								alt="Product Tables for WooCommerce Logo"
							/>
							<Text
								style={ {
									fontSize: '12px',
									borderRadius: '40px',
									color: 'var(--adpro-color-primary)',
									border: '1px solid currentColor',
									padding: '0 10px',
									width: '35px',
								} }
							>
								{ localize.version }
							</Text>
						</HStack>
					</span>
					<HStack spacing={ 5 } alignment="edge">
						<HStack alignment="left" spacing={ 5 }>
							{ menuItems.map( ( item ) => (
								<Button
									key={ item.to }
									variant="tertiary"
									isPressed={ currentNav === item.to }
									onClick={ () => navigate( item.to ) }
								>
									{ item.label }
								</Button>
							) ) }
						</HStack>
						<HStack alignment="right" spacing={ 2 }>
							<Button
								icon="book"
								variant="tertiary"
								onClick={ () =>
									window.open(
										'https://www.producttables.com/docs/',
										'_blank'
									)
								}
							>
								{ __(
									'Documentation',
									'table-for-woocommerce'
								) }
							</Button>
							<Button
								icon="sos"
								variant="tertiary"
								onClick={ () =>
									window.open(
										'https://www.producttables.com/support/',
										'_blank'
									)
								}
							>
								{ __( 'Get Support', 'table-for-woocommerce' ) }
							</Button>
						</HStack>
					</HStack>
				</HStack>
			</div>
			{ currentNav === 'settings' && <Settings /> }
			{ currentNav === 'dashboard' && <Dashboard onEdit={ editTable } /> }
			{ currentNav === 'single' && (
				<Single
					tableId={ tableId }
					onTableIdResolved={ ( id ) =>
						setUrl( `#/table/${ id }`, true )
					}
					onCancel={ () => navigate( 'dashboard' ) }
				/>
			) }
		</>
	);
};
export default MenuBar;

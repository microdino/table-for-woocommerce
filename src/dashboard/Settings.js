const { __ } = wp.i18n;
import { useEffect, useState } from 'react';
import {
	Guide,
	Card,
	CardHeader,
	CardBody,
	Button,
	ExternalLink,
	__experimentalGrid as Grid,
	__experimentalText as Text,
	__experimentalHeading as Heading,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
	__experimentalNumberControl as NumberControl,
	BaseControl,
	__experimentalDivider as Divider,
	ToggleControl,
	TextControl,
	__experimentalSpacer as Spacer,
	SelectControl,
	__experimentalNavigation as Navigation,
	__experimentalNavigationGroup as NavigationGroup,
	__experimentalNavigationItem as NavigationItem,
	__experimentalNavigationMenu as NavigationMenu,
} from '@wordpress/components';

const Settings = () => {
	const [ settings, setSettings ] = useState( {} );
	const [ loading, setLoading ] = useState( false );
	const [ saving, setSaving ] = useState( false );

	const [ isOpen, setIsOpen ] = useState( true );
	if ( ! isOpen ) {
		return null;
	}

	useEffect( () => {
		// fetchSettings();
	}, [] );

	const menuItems = [
		{ to: 'dashboard', label: 'Dashboard' },
		{ to: 'settings', label: 'Settings' },
	];

	const [ hasCopied, setHasCopied ] = useState( false );

	return (
		<div style={ { maxWidth: '1200px', margin: '0 auto' } }>
			<Card style={ { margin: '20px 20px 20px 0' } }>
				<CardBody>
					<Grid templateColumns="1fr 3fr" gap={ 10 }>
						<VStack alignment="topLeft" spacing={ 2 }>
							<Heading level={ 3 }>
								{ __(
									'Add to Cart Settings',
									'table-for-woocommerce'
								) }
							</Heading>
							<Text variant="muted">
								{ __(
									'Clean up all files uploaded through this field to free storage and remove unused data.',
									'table-for-woocommerce'
								) }
							</Text>
						</VStack>
						<VStack spacing={ 5 } style={ { maxWidth: '500px' } }>
							<TextControl
								label={ __(
									'Add to Cart Text',
									'table-for-woocommerce'
								) }
								value={
									settings.addonsPriceText ?? 'Add to cart'
								}
								onChange={ ( v ) =>
									handleChange( 'addonsPriceText', v )
								}
							/>
							<TextControl
								label={ __(
									'Multiple Add to Cart Text (Singular)',
									'table-for-woocommerce'
								) }
								value={
									settings.addonsPriceText ??
									'Add 1 item for {total}'
								}
								onChange={ ( v ) =>
									handleChange( 'addonsPriceText', v )
								}
							/>
							<TextControl
								label={ __(
									'Multiple Add to Cart Text (Plural)',
									'table-for-woocommerce'
								) }
								value={
									settings.addonsPriceText ??
									'Add {items} items for {total}'
								}
								onChange={ ( v ) =>
									handleChange( 'addonsPriceText', v )
								}
							/>
							<SelectControl
								__next40pxDefaultSize
								label={ __(
									'Add to Cart Location',
									'table-for-woocommerce'
								) }
								value={ settings?.condition?.match }
								options={ [
									{ value: 'above', label: 'Above Table' },
									{ value: 'below', label: 'Below Table' },
									{
										value: 'all',
										label: 'Above & Below Table',
									},
								] }
								onChange={ ( v ) =>
									handleChange( 'condition', 'match', v )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={
									settings.enableAddonsPriceText ?? true
								}
								onChange={ ( v ) =>
									handleChange( 'enableAddonsPriceText', v )
								}
								label={ __(
									'Select all products in the table header',
									'table-for-woocommerce'
								) }
							/>
						</VStack>
					</Grid>
					<Divider
						margin={ 6 }
						style={ { borderColor: 'rgba(0, 0, 0, 0.1)' } }
					/>
					<Grid templateColumns="1fr 3fr" gap={ 10 }>
						<VStack alignment="topLeft" spacing={ 2 }>
							<Heading level={ 3 }>
								{ __(
									'Table Content Settings',
									'table-for-woocommerce'
								) }
							</Heading>
							<Text variant="muted">
								{ __(
									'Settings for Managing and Customizing Table Content.',
									'table-for-woocommerce'
								) }
							</Text>
						</VStack>
						<VStack spacing={ 5 } style={ { maxWidth: '500px' } }>
							<NumberControl
								__next40pxDefaultSize
								label={ __(
									'Description Length (Words)',
									'advanced-product-options'
								) }
								value={ settings.uploadTempRemove ?? '' }
								onChange={ ( v ) =>
									handleChange( 'uploadTempRemove', v )
								}
								suffix={
									<div
										style={ {
											marginRight: '8px',
										} }
									>
										Words
									</div>
								}
								min={ 0 }
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={
									settings.enableAddonsPriceText ?? true
								}
								onChange={ ( v ) =>
									handleChange( 'enableAddonsPriceText', v )
								}
								label={ __(
									'Show sticky header',
									'table-for-woocommerce'
								) }
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={
									settings.enableAddonsPriceText ?? true
								}
								onChange={ ( v ) =>
									handleChange( 'enableAddonsPriceText', v )
								}
								label={ __(
									'Hide table heading',
									'table-for-woocommerce'
								) }
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={
									settings.enableAddonsPriceText ?? true
								}
								onChange={ ( v ) =>
									handleChange( 'enableAddonsPriceText', v )
								}
								label={ __(
									'Show table footer',
									'table-for-woocommerce'
								) }
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={
									settings.enableAddonsPriceText ?? true
								}
								onChange={ ( v ) =>
									handleChange( 'enableAddonsPriceText', v )
								}
								label={ __(
									'Show hidden products',
									'table-for-woocommerce'
								) }
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={
									settings.enableAddonsPriceText ?? true
								}
								onChange={ ( v ) =>
									handleChange( 'enableAddonsPriceText', v )
								}
								label={ __(
									'Select all	products in the table header',
									'table-for-woocommerce'
								) }
							/>
						</VStack>
					</Grid>
					<Divider
						margin={ 6 }
						style={ { borderColor: 'rgba(0, 0, 0, 0.1)' } }
					/>
					<Grid templateColumns="1fr 3fr" gap={ 10 }>
						<VStack alignment="topLeft" spacing={ 2 }>
							<Heading level={ 3 }>
								{ __(
									'Other Settings',
									'table-for-woocommerce'
								) }
							</Heading>
							<Text variant="muted">
								{ __(
									'Settings for Managing and Customizing Other Settings.',
									'table-for-woocommerce'
								) }
							</Text>
						</VStack>
						<VStack spacing={ 5 } style={ { maxWidth: '500px' } }>
							<NumberControl
								__next40pxDefaultSize
								label={ __(
									'Products per page',
									'table-for-woocommerce'
								) }
								value={ settings.productsPerPage ?? 10 }
								onChange={ ( v ) =>
									handleChange( 'productsPerPage', v )
								}
								suffix={
									<div
										style={ {
											marginRight: '8px',
										} }
									>
										Words
									</div>
								}
								min={ 1 }
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={
									settings.enableAddonsPriceText ?? true
								}
								onChange={ ( v ) =>
									handleChange( 'enableAddonsPriceText', v )
								}
								label={ __(
									'Search box in table header',
									'table-for-woocommerce'
								) }
							/>
							<div>
								<Button
									isDestructive
									variant="secondary"
									onClick={ () => saveSettings() }
								>
									{ __(
										'Delete All Data & Reset Plugin',
										'table-for-woocommerce'
									) }
								</Button>
							</div>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={
									settings.enableAddonsPriceText ?? true
								}
								onChange={ ( v ) =>
									handleChange( 'enableAddonsPriceText', v )
								}
								label={ __(
									'Enable Caching',
									'table-for-woocommerce'
								) }
							/>
							<NumberControl
								__next40pxDefaultSize
								label={ __(
									'Cache Duration (Hours)',
									'table-for-woocommerce'
								) }
								value={ settings.cacheDuration ?? 10 }
								onChange={ ( v ) =>
									handleChange( 'cacheDuration', v )
								}
								suffix={
									<div
										style={ {
											marginRight: '8px',
										} }
									>
										Hours
									</div>
								}
								min={ 1 }
							/>
							<div>
								<Button
									// <Spinner/>
									icon={ 'saved' }
									variant="primary"
									onClick={ () => saveSettings() }
								>
									{ __(
										'Save Settings',
										'table-for-woocommerce'
									) }
								</Button>
							</div>
						</VStack>
					</Grid>
				</CardBody>
			</Card>
		</div>
	);
};

export default Settings;

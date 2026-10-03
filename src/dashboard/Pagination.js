const { __ } = wp.i18n;
import { Button, __experimentalHStack as HStack } from '@wordpress/components';

const Pagination = ( { currentPage, totalPages, onPageChange } ) => {
	const goToPage = ( page ) => {
		if ( page >= 1 && page <= totalPages ) {
			onPageChange( page );
		}
	};

	return (
		<HStack alignment="left" spacing={ 4 }>
			<Button
				__next40pxDefaultSize
				variant="secondary"
				icon={ 'arrow-left-alt2' }
				label={ __( 'Previous page', 'table-for-woocommerce' ) }
				onClick={ () => goToPage( currentPage - 1 ) }
				disabled={ currentPage === 1 }
			/>
			{ Array( totalPages )
				.fill( 'a' )
				.map( ( _, i ) => {
					return (
						<Button
							key={ i + 1 }
							__next40pxDefaultSize
							variant={
								currentPage === i + 1 ? 'primary' : 'secondary'
							}
							onClick={ () => goToPage( i + 1 ) }
						>
							{ i + 1 }
						</Button>
					);
				} ) }
			<Button
				__next40pxDefaultSize
				variant="secondary"
				icon={ 'arrow-right-alt2' }
				label={ __( 'Next page', 'table-for-woocommerce' ) }
				onClick={ () => goToPage( currentPage + 1 ) }
				disabled={ currentPage === totalPages }
			/>
		</HStack>
	);
};

export default Pagination;

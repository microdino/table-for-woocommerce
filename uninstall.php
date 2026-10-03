<?php
/** Remove this plugin's data only when the saved uninstall preference is enabled. */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

function prta_uninstall_site_data() {
	$settings = get_option( 'prta_cart_settings', array() );
	if ( ! is_array( $settings ) || empty( $settings['deleteDataOnUninstall'] ) ) return;
	$ids = get_posts( array( 'post_type' => 'adpro_option', 'post_status' => array_keys( get_post_stati() ), 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( array_unique( $ids ) as $id ) wp_delete_post( $id, true );
	$keys = get_option( 'prta_query_cache_keys', array() );
	if ( is_array( $keys ) ) foreach ( array_keys( $keys ) as $key ) if ( 0 === strpos( $key, 'prta_query_' ) ) delete_transient( $key );
	global $wpdb;
	$expired_keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_prta_query_' ) . '%', $wpdb->esc_like( '_transient_timeout_prta_query_' ) . '%' ) );
	foreach ( $expired_keys as $key ) delete_option( $key );
	delete_option( 'prta_query_cache_keys' );
	delete_option( 'prta_cart_settings' );
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
		switch_to_blog( $site_id );
		prta_uninstall_site_data();
		restore_current_blog();
	}
} else {
	prta_uninstall_site_data();
}

<?php //phpcs:ignore
/**
 * Options Action.
 *
 * @package PRTA
 * @since v.1.0.0
 */

namespace PRTA\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Options class.
 */
class Options {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu_page_callback' ) );
		add_action( 'in_admin_header', array( $this, 'remove_all_notices' ) );

		add_filter( 'plugin_action_links_' . PRTA_BASE, array( $this, 'plugin_action_links_callback' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_settings_meta' ), 10, 2 );
	}

	/**
	 * Adds quick action links below the plugin name.
	 * @return array Modified plugin action links.
	 */
	public function plugin_action_links_callback( $links ) {
		$links['prta_options'] = '<a href="' . esc_url( admin_url( 'admin.php?page=prta-dashboard' ) ) . '">' . esc_html__( 'Options', 'table-for-woocommerce' ) . '</a>';

		return $links;
	}

	/**
	 * Adds extra links to the plugin row meta on the plugins page.
	 *
	 * @param array  $links Existing plugin meta links.
	 * @param string $file  Plugin file path.
	 * @return array Modified plugin meta links.
	 */
	public function plugin_settings_meta( $links, $file ) {
		if ( strpos( $file, 'table-for-woocommerce.php' ) !== false ) {
			$new_links = array(
				'prta_docs'    => '<a href="#" target="_blank">' . esc_html__( 'Docs', 'table-for-woocommerce' ) . '</a>',
				'prta_support' => '<a href="#" target="_blank">' . esc_html__( 'Support', 'table-for-woocommerce' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}

	/**
	 * Admin Menu Option Page
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function menu_page_callback() {
        add_submenu_page(
            'edit.php?post_type=product',
            __( 'Product Table', 'table-for-woocommerce' ),
            __( 'Product Table', 'table-for-woocommerce' ),
            'manage_options',
            'prta-dashboard',
            array( self::class, 'tab_page_content' ),
        );
	}


	/**
	 * Initial Plugin Setting
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function tab_page_content() {
		echo '<div id="prta-dashboard"></div>'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Remove All Notification From Menu Page
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function remove_all_notices() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash($_GET['page']) ) : ''; // phpcs:ignore
		if ( 'prta-dashboard' === $page ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'in_admin_header' );
		}
	}
}

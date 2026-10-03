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

		add_filter( 'plugin_action_links_' . PRTA_BASE, array( $this, 'plugin_action_links_callback' ) );
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
		if ( ! current_user_can( 'manage_options' ) ) return;
		echo '<div id="prta-dashboard"></div>'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

}

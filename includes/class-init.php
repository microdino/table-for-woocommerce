<?php // phpcs:ignore
/**
 * Initialization Action.
 *
 * @package PRTA
 * @since 1.0.0
 */
namespace PRTA\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Init class.
 */
class Init {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
        $this->required();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_callback' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'forntend_scripts_callback' ) );
		add_action( 'activated_plugin', array( $this, 'activation_redirect' ) );
	}

	/**
	 * Required Class
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function required() {
        require_once PRTA_PATH . 'includes/class-post-type.php';
        require_once PRTA_PATH . 'includes/class-options.php';
        require_once PRTA_PATH . 'includes/class-shortcode.php';
		new PostType();
		// new Analytics();
		new Options();
		new Shortcode();
		// new Hooks();
		// new RequestAPI();


	}

	/**
	 * Only Frontend CSS and JS Scripts
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function forntend_scripts_callback() {
		wp_enqueue_style( 'prta-frontend-style', PRTA_URL . 'assets/css/frontend.css', array(), PRTA_VER );
		wp_enqueue_script( 'prta-frontend-script', PRTA_URL . 'assets/js/frontend.js', array(), PRTA_VER, true );
	}

	/**
	 * Only Backend CSS and JS Scripts
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function admin_scripts_callback() {
		global $pagenow;
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore
		// wp_enqueue_style( 'prta-admin-style', PRTA_URL . 'assets/css/prta-admin.css', array(), PRTA_VER );
		// wp_enqueue_script( 'prta-admin-script', PRTA_URL . 'assets/js/prta-admin.js', array( 'jquery' ), PRTA_VER, true );

		if ( 'edit.php' === $pagenow ) {
			if ( 'prta-dashboard' === $page ) {
				$user_info = get_userdata( get_current_user_id() );
				wp_enqueue_style( 'prta-editor-css', PRTA_URL . 'assets/css/backend.css', array(), PRTA_VER );
				wp_enqueue_style( 'prta-blocks-css', PRTA_URL . 'assets/css/frontend.css', array(), PRTA_VER );
				wp_enqueue_script( 'prta-editor-script', PRTA_URL . 'assets/js/backend.js', array( 'wp-api-fetch', 'wp-components', 'wp-element' ), PRTA_VER, true );
				wp_localize_script(
					'prta-editor-script',
					'localize',
					array_merge(
						array(
                            'url'            => PRTA_URL,
							'version'        => PRTA_VER,
							'db_url'         => admin_url( 'admin.php?page=prta-dashboard#' ),
							'ajax'           => admin_url( 'admin-ajax.php' ),
							'nonce'          => wp_create_nonce( 'prta-nonce' ),
							'decimal_sep'    => get_option( 'woocommerce_price_decimal_sep', '.' ),
							'num_decimals'   => get_option( 'woocommerce_price_num_decimals', '2' ),
							'currency_pos'   => get_option( 'woocommerce_currency_pos', 'left' ),
							'currencySymbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
						),
					)
				);
				wp_enqueue_style( 'wp-components' );
				wp_set_script_translations( 'prta-editor-script', 'table-for-woocommerce', PRTA_PATH . 'languages/' );
			}
		}
	}

	/**
	 * Redirect After Active Plugin
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function activation_redirect( $plugin ) {
		if ( 'table-for-woocommerce/table-for-woocommerce.php' === $plugin ) {
			if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || isset( $_POST['action'] ) && 'activate-selected' == $_POST['action'] ) { // phpcs:ignore
				return;
			}
			exit( wp_safe_redirect( admin_url( 'admin.php?page=adpro-dashboard#dashboard' ) ) ); // phpcs:ignore
		}
	}
}

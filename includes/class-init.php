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
		add_action( 'rest_api_init', array( $this, 'register_query_options_route' ) );
	}

	/** Register searchable product and taxonomy choices for the table editor. */
	public function register_query_options_route() {
		register_rest_route( 'prta/v1', '/query-options', array(
			'methods' => 'GET',
			'args' => array(
				'type' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'products', 'categories', 'tags', 'brands' ) ),
				'search' => array( 'type' => 'string', 'default' => '', 'maxLength' => 200, 'validate_callback' => function ( $value ) { return is_string( $value ) && strlen( $value ) <= 200; } ),
				'include' => array( 'type' => 'string', 'default' => '', 'maxLength' => 12000, 'pattern' => '^(?:[1-9][0-9]*(?:,[1-9][0-9]*)*)?$' ),
			),
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
			'callback' => function ( $request ) {
				$type = sanitize_key( $request->get_param( 'type' ) );
				$search = sanitize_text_field( $request->get_param( 'search' ) );
				$include = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $request->get_param( 'include' ) ) ) ) ) );
				if ( count( $include ) > 1000 ) return new \WP_Error( 'prta_too_many_ids', __( 'Select at most 1000 items.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
				if ( 'products' === $type ) {
					$posts = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 50, 's' => $search ) );
					if ( $include ) $posts = array_merge( $posts, get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => count( $include ), 'post__in' => $include ) ) );
					return array_values( array_unique( array_map( function ( $post ) { return array( 'value' => $post->ID, 'label' => $post->post_title ); }, $posts ), SORT_REGULAR ) );
				}
				$taxonomies = array( 'categories' => 'product_cat', 'tags' => 'product_tag', 'brands' => 'product_brand' );
				if ( ! isset( $taxonomies[ $type ] ) || ! taxonomy_exists( $taxonomies[ $type ] ) ) return array();
				$args = array( 'taxonomy' => $taxonomies[ $type ], 'hide_empty' => false, 'number' => 50, 'search' => $search );
				$terms = get_terms( $args );
				if ( $include ) {
					$selected_terms = get_terms( array( 'taxonomy' => $taxonomies[ $type ], 'hide_empty' => false, 'include' => $include ) );
					$terms = array_merge( is_wp_error( $terms ) ? array() : $terms, is_wp_error( $selected_terms ) ? array() : $selected_terms );
				}
				if ( is_wp_error( $terms ) ) return array();
				$options = array();
				foreach ( $terms as $term ) $options[ $term->term_id ] = array( 'value' => $term->term_id, 'label' => $term->name );
				return array_values( $options );
			},
		) );
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
		require_once PRTA_PATH . 'includes/class-cart-settings.php';
		new PostType();
		new Options();
		new Shortcode();
		new CartSettings();


	}

	/**
	 * Only Frontend CSS and JS Scripts
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function forntend_scripts_callback() {
		wp_enqueue_style( 'prta-frontend-style', PRTA_URL . 'assets/css/frontend.css', array(), filemtime( PRTA_PATH . 'assets/css/frontend.css' ) );
		wp_enqueue_script( 'prta-frontend-script', PRTA_URL . 'assets/js/frontend.js', array(), filemtime( PRTA_PATH . 'assets/js/frontend.js' ), true );
		if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
			wp_localize_script( 'prta-frontend-script', 'prtaPriceFormat', array(
				'symbol' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'decimals' => wc_get_price_decimals(),
				'decimalSeparator' => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'position' => get_option( 'woocommerce_currency_pos', 'left' ),
			) );
		}
	}

	/**
	 * Only Backend CSS and JS Scripts
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function admin_scripts_callback() {
		global $pagenow;
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore

		if ( 'edit.php' === $pagenow ) {
			if ( 'prta-dashboard' === $page && current_user_can( 'manage_options' ) ) {
				wp_enqueue_style( 'prta-editor-css', PRTA_URL . 'assets/css/backend.css', array(), md5_file( PRTA_PATH . 'assets/css/backend.css' ) );
				wp_enqueue_style( 'prta-blocks-css', PRTA_URL . 'assets/css/frontend.css', array(), PRTA_VER );
				wp_enqueue_script( 'prta-editor-script', PRTA_URL . 'assets/js/backend.js', array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ), md5_file( PRTA_PATH . 'assets/js/backend.js' ), true );
				wp_localize_script( 'prta-editor-script', 'localize', array( 'url' => PRTA_URL, 'version' => PRTA_VER ) );
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
		if ( 'table-for-woocommerce/table-for-woocommerce.php' === $plugin && current_user_can( 'activate_plugins' ) ) {
			if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || isset( $_POST['action'] ) && 'activate-selected' == $_POST['action'] ) { // phpcs:ignore
				return;
			}
			exit( wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=prta-dashboard#dashboard' ) ) ); // phpcs:ignore
		}
	}
}

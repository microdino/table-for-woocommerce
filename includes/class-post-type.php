<?php // phpcs:ignore
/**
 * PostType Action.
 *
 * @package PRTA
 * @since v.1.0.0
 */
namespace PRTA\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * PostType class.
 */
class PostType {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'post_type_callback' ) );
	}


	/**
	 * Option PostType
	 *
	 * @since v.1.0.0
	 */
	public function post_type_callback() {
		$labels = array(
			'name'               => _x( 'Table', 'Table', 'table-for-woocommerce' ),
			'singular_name'      => _x( 'Table', 'Table', 'table-for-woocommerce' ),
			'menu_name'          => __( 'Table', 'table-for-woocommerce' ),
			'parent_item_colon'  => __( 'Parent Table', 'table-for-woocommerce' ),
			'all_items'          => __( 'Table', 'table-for-woocommerce' ),
			'view_item'          => __( 'View Table', 'table-for-woocommerce' ),
			'add_new_item'       => __( 'Add New Table', 'table-for-woocommerce' ),
			'add_new'            => __( 'Add New Table', 'table-for-woocommerce' ),
			'edit_item'          => __( 'Edit Table', 'table-for-woocommerce' ),
			'update_item'        => __( 'Update Table', 'table-for-woocommerce' ),
			'search_items'       => __( 'Search Table', 'table-for-woocommerce' ),
			'not_found'          => __( 'No Table Found', 'table-for-woocommerce' ),
			'not_found_in_trash' => __( 'Not Table found in Trash', 'table-for-woocommerce' ),
		);
		$args   = array(
			'labels'              => $labels,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'adpro_option', $args );
	}
}

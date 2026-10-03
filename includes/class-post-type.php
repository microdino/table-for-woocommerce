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
		add_filter( 'rest_pre_insert_adpro_option', array( $this, 'sanitize_table' ), 10, 2 );
		add_filter( 'rest_pre_dispatch', array( $this, 'restrict_table_rest_access' ), 10, 3 );
	}

	/** Private table configuration must not be exposed by public REST reads. */
	public function restrict_table_rest_access( $result, $server, $request ) {
		if ( preg_match( '#^/wp/v2/adpro_option(?:/|$)#', $request->get_route() ) && ! current_user_can( 'manage_options' ) ) return new \WP_Error( 'prta_forbidden', __( 'You cannot manage product tables.', 'table-for-woocommerce' ), array( 'status' => 403 ) );
		return $result;
	}

	/** Validate table JSON before the standard WordPress REST controller saves it. */
	public function sanitize_table( $post, $request ) {
		if ( ! current_user_can( 'manage_options' ) ) return new \WP_Error( 'prta_forbidden', __( 'You cannot manage product tables.', 'table-for-woocommerce' ), array( 'status' => 403 ) );
		if ( ! isset( $post->post_content ) ) return $post;
		$settings = json_decode( $post->post_content, true );
		if ( ! is_array( $settings ) || ( ! empty( $settings ) && array_keys( $settings ) === range( 0, count( $settings ) - 1 ) ) ) return new \WP_Error( 'prta_invalid_table', __( 'Table settings must be a JSON object.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
		$clean = array();
		$enums = array( 'displayIn' => array( 'shortcode', 'shop' ), 'queryType' => array( 'all', 'categories', 'tags', 'products', 'brands' ), 'variationStyle' => array( 'link', 'dropdown', 'separate' ), 'sortBy' => array( 'default', 'sorting', 'id', 'name', 'published', 'modified', 'sales', 'rating', 'random', 'price' ), 'sortDirection' => array( 'automatic', 'ascending', 'descending' ) );
		foreach ( $enums as $key => $choices ) if ( isset( $settings[ $key ] ) ) {
			if ( ! in_array( $settings[ $key ], $choices, true ) ) return new \WP_Error( 'prta_invalid_setting', __( 'Invalid table setting.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
			$clean[ $key ] = $settings[ $key ];
		}
		foreach ( array( 'shopPage', 'searchPage', 'categoryPage', 'tagPage', 'attributesPage', 'brandPage', 'quantitySelector' ) as $key ) if ( array_key_exists( $key, $settings ) ) {
			if ( ! is_bool( $settings[ $key ] ) ) return new \WP_Error( 'prta_invalid_boolean', __( 'Checkbox settings must be true or false.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
			$clean[ $key ] = $settings[ $key ];
		}
		foreach ( array( 'productsPerPage' => array( 1, 1000 ), 'descriptionLength' => array( 0, 10000 ) ) as $key => $bounds ) if ( isset( $settings[ $key ] ) ) {
			$value = $settings[ $key ];
			if ( ! is_numeric( $value ) || (float) $value != (int) $value || $value < $bounds[0] || $value > $bounds[1] ) return new \WP_Error( 'prta_invalid_number', __( 'Invalid table numeric setting.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
			$clean[ $key ] = (int) $value;
		}
		foreach ( array( 'queryCategories', 'queryTags', 'queryBrands', 'queryProducts', 'excludeProducts', 'excludeCategories' ) as $key ) if ( isset( $settings[ $key ] ) ) {
			if ( ! is_array( $settings[ $key ] ) || count( $settings[ $key ] ) > 1000 ) return new \WP_Error( 'prta_invalid_ids', __( 'Invalid product or term selection.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
			foreach ( $settings[ $key ] as $id ) if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id < 1 ) return new \WP_Error( 'prta_invalid_ids', __( 'Selections must contain positive IDs.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
			$clean[ $key ] = array_values( array_unique( array_map( 'absint', $settings[ $key ] ) ) );
		}
		foreach ( array( 'columns', 'searchFilters', 'searchFilter' ) as $key ) if ( isset( $settings[ $key ] ) ) {
			$items = is_string( $settings[ $key ] ) ? explode( ',', $settings[ $key ] ) : $settings[ $key ];
			if ( ! is_array( $items ) || count( $items ) > 100 ) return new \WP_Error( 'prta_invalid_fields', __( 'Invalid table columns or filters.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
			if ( 'columns' === $key && ! $items ) return new \WP_Error( 'prta_invalid_fields', __( 'Select at least one table column.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
			$clean[ $key ] = array();
			foreach ( $items as $item ) {
				if ( is_string( $item ) ) $item = array( 'value' => trim( $item ) );
				if ( ! is_array( $item ) || ! isset( $item['value'] ) || ! is_string( $item['value'] ) || ! preg_match( '/^[a-z0-9_:-]+$/', $item['value'] ) ) return new \WP_Error( 'prta_invalid_fields', __( 'Invalid table field.', 'table-for-woocommerce' ), array( 'status' => 400 ) );
				$row = array( 'value' => $item['value'] );
				foreach ( array( 'id', 'label' ) as $field ) if ( isset( $item[ $field ] ) && is_scalar( $item[ $field ] ) ) $row[ $field ] = sanitize_text_field( $item[ $field ] );
				$clean[ $key ][] = $row;
			}
		}
		if ( isset( $settings['tableName'] ) && is_scalar( $settings['tableName'] ) ) $clean['tableName'] = sanitize_text_field( $settings['tableName'] );
		$post->post_content = wp_json_encode( (object) $clean );
		if ( isset( $post->post_title ) ) $post->post_title = sanitize_text_field( $post->post_title );
		return $post;
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
			'map_meta_cap'        => false,
			'capabilities'        => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ), 'manage_options' ),
		);
		register_post_type( 'adpro_option', $args );
	}
}

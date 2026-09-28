<?php
/**
 * Product table shortcode.
 *
 * @package PRTA
 */

namespace PRTA\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a saved product table.
 */
class Shortcode {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_shortcode( 'product_table', array( $this, 'register_shortcode_callback' ) );
		add_action( 'template_redirect', array( $this, 'handle_add_to_cart' ) );
	}

	/**
	 * Process single and bulk add-to-cart submissions.
	 */
	public function handle_add_to_cart() {
		if ( empty( $_POST['prta_cart_action'] ) || empty( $_POST['prta_cart_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['prta_cart_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'prta_add_to_cart' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		$quantities = isset( $_POST['prta_quantity'] ) && is_array( $_POST['prta_quantity'] ) ? wp_unslash( $_POST['prta_quantity'] ) : array();
		$product_ids = array();
		if ( isset( $_POST['prta_add_product'] ) ) {
			$product_ids[] = absint( $_POST['prta_add_product'] );
		} elseif ( isset( $_POST['prta_products'] ) && is_array( $_POST['prta_products'] ) ) {
			$product_ids = array_map( 'absint', wp_unslash( $_POST['prta_products'] ) );
		}
		foreach ( array_unique( array_filter( $product_ids ) ) as $product_id ) {
			$product  = wc_get_product( $product_id );
			$quantity = isset( $quantities[ $product_id ] ) ? max( 1, absint( $quantities[ $product_id ] ) ) : 1;
			if ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
				WC()->cart->add_to_cart( $product_id, $quantity );
			}
		}
		$redirect = isset( $_POST['prta_return_url'] ) ? esc_url_raw( wp_unslash( $_POST['prta_return_url'] ) ) : wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : wc_get_cart_url() );
		exit;
	}

	/**
	 * Load and normalize a saved table.
	 *
	 * @param int $table_id Table post ID.
	 * @return array|\WP_Error
	 */
	private function get_settings( $table_id ) {
		$post = get_post( $table_id );
		if ( ! $post || 'adpro_option' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new \WP_Error( 'prta_invalid_table', __( 'Product table not found.', 'table-for-woocommerce' ) );
		}
		$settings = json_decode( $post->post_content, true );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$saved_filters = isset( $settings['searchFilters'] ) ? $settings['searchFilters'] : ( isset( $settings['searchFilter'] ) ? $settings['searchFilter'] : array() );
		$settings = wp_parse_args(
			$settings,
			array(
				'columns'           => array( 'sku', 'name', 'stock', 'price', 'total', 'buy' ),
				'searchFilters'     => array(),
				'productsPerPage'   => 20,
				'sortBy'            => 'sorting',
				'sortDirection'     => 'automatic',
				'quantitySelector'  => true,
				'selectAll'         => false,
				'descriptionLength' => 15,
				'cartLocation'      => 'below',
			)
		);
		$settings['columns']       = $this->normalize_items( $settings['columns'] );
		$settings['searchFilters'] = $this->normalize_items( $saved_filters );
		return $settings;
	}

	/**
	 * Normalize legacy strings and structured editor rows.
	 *
	 * @param mixed $items Saved items.
	 * @return array
	 */
	private function normalize_items( $items ) {
		if ( is_string( $items ) ) {
			$items = array_filter( array_map( 'trim', explode( ',', $items ) ) );
		}
		$output = array();
		foreach ( (array) $items as $item ) {
			$value = is_array( $item ) && isset( $item['value'] ) ? $item['value'] : $item;
			$label = is_array( $item ) && isset( $item['label'] ) ? $item['label'] : $this->get_default_label( $value );
			$value = array_search( $value, array( 'tax:pa_color' => 'color', 'tax:pa_size' => 'size', 'buy' => 'add-to-cart' ), true ) ?: $value;
			if ( $value ) {
				$output[] = array( 'value' => sanitize_text_field( $value ), 'label' => sanitize_text_field( $label ) );
			}
		}
		return $output;
	}

	/**
	 * Default field label.
	 *
	 * @param string $value Field key.
	 * @return string
	 */
	private function get_default_label( $value ) {
		$labels = array(
			'id' => 'ID', 'sku' => 'SKU', 'name' => 'Name', 'description' => 'Description',
			'summary' => 'Summary', 'date' => 'Published date', 'modified' => 'Last modified date',
			'image' => 'Image', 'stock' => 'Stock', 'reviews' => 'Reviews', 'weight' => 'Weight',
			'dimensions' => 'Dimensions', 'price' => 'Price', 'buy' => 'Buy', 'button' => 'Button',
			'total' => 'Total', 'categories' => 'Categories', 'tags' => 'Tags',
			'tax:pa_color' => 'Color', 'tax:pa_size' => 'Size', 'author' => 'Author',
		);
		return isset( $labels[ $value ] ) ? $labels[ $value ] : ucwords( str_replace( array( '_', '-' ), ' ', $value ) );
	}

	/**
	 * Taxonomy dropdown options.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $selected Selected slug.
	 * @return string
	 */
	private function get_term_options( $taxonomy, $selected = '' ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
		if ( is_wp_error( $terms ) ) {
			return '';
		}
		$output = '';
		foreach ( $terms as $term ) {
			$output .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $term->slug ), selected( $selected, $term->slug, false ), esc_html( $term->name ) );
		}
		return $output;
	}

	/**
	 * Render a configured search filter.
	 *
	 * @param array  $filter Filter settings.
	 * @param string $prefix Query prefix.
	 * @param array  $get Current query.
	 */
	private function render_filter( $filter, $prefix, $get ) {
		$value    = $filter['value'];
		$taxonomy = 0 === strpos( $value, 'tax:' ) ? substr( $value, 4 ) : array_search( $value, array( 'product_cat' => 'categories', 'product_tag' => 'tags' ), true );
		$taxonomy = $taxonomy ? $taxonomy : ( in_array( $value, array( 'product_type', 'product_visibility' ), true ) ? $value : '' );
		$key      = $prefix . 'filter_' . sanitize_key( str_replace( ':', '_', $value ) );
		$selected = isset( $get[ $key ] ) ? sanitize_title( $get[ $key ] ) : '';
		if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
			printf( '<label><span class="screen-reader-text">%1$s</span><select name="%2$s" aria-label="%1$s" onchange="this.form.submit()"><option value="">%1$s</option>%3$s</select></label>', esc_html( $filter['label'] ), esc_attr( $key ), $this->get_term_options( $taxonomy, $selected ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'author' === $value ) {
			wp_dropdown_users( array( 'name' => $key, 'selected' => absint( $selected ), 'show_option_all' => __( 'All Authors', 'table-for-woocommerce' ), 'echo' => true ) );
		}
	}

	/**
	 * Render a product cell.
	 *
	 * @param string      $column Column key.
	 * @param \WC_Product $product Product.
	 * @param array       $settings Table settings.
	 * @return string
	 */
	private function get_cell( $column, $product, $settings ) {
		$id = $product->get_id();
		switch ( $column ) {
			case 'id': return esc_html( $id );
			case 'sku': return esc_html( $product->get_sku() );
			case 'name': return '<a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
			case 'description': return esc_html( wp_trim_words( wp_strip_all_tags( $product->get_description() ), absint( $settings['descriptionLength'] ) ) );
			case 'summary': return esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), absint( $settings['descriptionLength'] ) ) );
			case 'date': return esc_html( $product->get_date_created() ? wc_format_datetime( $product->get_date_created() ) : '' );
			case 'modified': return esc_html( $product->get_date_modified() ? wc_format_datetime( $product->get_date_modified() ) : '' );
			case 'image': return '<a href="' . esc_url( $product->get_permalink() ) . '">' . $product->get_image( 'woocommerce_thumbnail' ) . '</a>';
			case 'stock': return wp_kses_post( wc_get_stock_html( $product ) );
			case 'reviews': return wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() );
			case 'weight': return esc_html( $product->has_weight() ? wc_format_weight( $product->get_weight() ) : '' );
			case 'dimensions': return esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) );
			case 'price': return wp_kses_post( $product->get_price_html() );
			case 'total': return '<span class="prta-line-total" data-price="' . esc_attr( wc_get_price_to_display( $product ) ) . '">' . wp_kses_post( wc_price( wc_get_price_to_display( $product ) ) ) . '</span>';
			case 'categories': return wp_kses_post( wc_get_product_category_list( $id ) );
			case 'tags': return wp_kses_post( wc_get_product_tag_list( $id ) );
			case 'tax:pa_color': return esc_html( $product->get_attribute( 'pa_color' ) );
			case 'tax:pa_size': return esc_html( $product->get_attribute( 'pa_size' ) );
			case 'author': return esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $id ) ) );
			case 'button': return '<a class="button" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->add_to_cart_text() ) . '</a>';
			case 'buy': return $this->get_buy_control( $product, $settings );
		}
		return '';
	}

	/**
	 * Product purchase controls.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $settings Settings.
	 * @return string
	 */
	private function get_buy_control( $product, $settings ) {
		if ( ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return '<a class="button" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->add_to_cart_text() ) . '</a>';
		}
		$id       = $product->get_id();
		$quantity = ! empty( $settings['quantitySelector'] ) ? '<input class="prta-qty" aria-label="' . esc_attr__( 'Quantity', 'table-for-woocommerce' ) . '" type="number" name="prta_quantity[' . esc_attr( $id ) . ']" value="1" min="1" step="1">' : '';
		return $quantity . '<button type="submit" class="prta-cart" name="prta_add_product" value="' . esc_attr( $id ) . '">' . esc_html__( 'Add to cart', 'table-for-woocommerce' ) . '</button><input class="prta-checkbox" aria-label="' . esc_attr( sprintf( __( 'Select %s', 'table-for-woocommerce' ), $product->get_name() ) ) . '" name="prta_products[]" value="' . esc_attr( $id ) . '" type="checkbox">';
	}

	/**
	 * Render the product table.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function register_shortcode_callback( $atts ) {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
			return '<p class="woocommerce-info">' . esc_html__( 'WooCommerce must be active to display the product table.', 'table-for-woocommerce' ) . '</p>';
		}
		$attributes = shortcode_atts( array( 'id' => '' ), $atts, 'product_table' );
		$table_id   = absint( $attributes['id'] );
		if ( ! $table_id ) {
			return '<p class="woocommerce-info">' . esc_html__( 'A product table ID is required.', 'table-for-woocommerce' ) . '</p>';
		}
		$settings = $this->get_settings( $table_id );
		if ( is_wp_error( $settings ) ) {
			return '<p class="woocommerce-info">' . esc_html( $settings->get_error_message() ) . '</p>';
		}
		$prefix = 'prta_' . $table_id . '_';
		$get    = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page   = isset( $get[ $prefix . 'page' ] ) ? max( 1, absint( $get[ $prefix . 'page' ] ) ) : 1;
		$limit  = isset( $get[ $prefix . 'limit' ] ) ? max( 1, absint( $get[ $prefix . 'limit' ] ) ) : max( 1, absint( $settings['productsPerPage'] ) );
		$search = isset( $get[ $prefix . 'search' ] ) ? sanitize_text_field( $get[ $prefix . 'search' ] ) : '';
		$sort_map = array( 'sorting' => 'menu_order', 'id' => 'ID', 'name' => 'title', 'published' => 'date', 'modified' => 'modified', 'sales' => 'popularity', 'rating' => 'rating', 'random' => 'rand', 'price' => 'price' );
		$orderby = isset( $sort_map[ $settings['sortBy'] ] ) ? $sort_map[ $settings['sortBy'] ] : 'menu_order';
		$order   = 'ascending' === $settings['sortDirection'] ? 'ASC' : ( 'descending' === $settings['sortDirection'] ? 'DESC' : ( in_array( $orderby, array( 'title', 'menu_order' ), true ) ? 'ASC' : 'DESC' ) );
		$tax_query = array();
		$author = 0;
		$active_filter_keys = array();
		foreach ( $settings['searchFilters'] as $filter ) {
			$value = $filter['value'];
			$key = $prefix . 'filter_' . sanitize_key( str_replace( ':', '_', $value ) );
			$selected = isset( $get[ $key ] ) ? sanitize_title( $get[ $key ] ) : '';
			if ( ! $selected ) continue;
			if ( 'author' === $value ) { $author = absint( $selected ); continue; }
			$taxonomy = 0 === strpos( $value, 'tax:' ) ? substr( $value, 4 ) : array_search( $value, array( 'product_cat' => 'categories', 'product_tag' => 'tags' ), true );
			$taxonomy = $taxonomy ? $taxonomy : $value;
			if ( taxonomy_exists( $taxonomy ) ) {
				$active_filter_keys[] = $key;
				$tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => array( $selected ) );
			}
		}
		$query_filter = function ( $query ) use ( $tax_query, $author ) {
			if ( $tax_query ) $query['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			if ( $author ) $query['author'] = $author;
			return $query;
		};
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', $query_filter );
		$results = wc_get_products( array( 'status' => 'publish', 'limit' => $limit, 'page' => $page, 'paginate' => true, 'orderby' => $orderby, 'order' => $order, 's' => $search, 'return' => 'objects' ) );
		remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', $query_filter );
		$products = $results->products;
		$current_url = remove_query_arg( $prefix . 'page' );
		$reset_url  = remove_query_arg( array_merge( $active_filter_keys, array( $prefix . 'page' ) ) );
		$cart_button = '<button type="submit" class="button" name="prta_add_selected" value="1">' . esc_html__( 'Add selected to cart', 'table-for-woocommerce' ) . '</button>';
		ob_start();
		?>
		<div class="prta-wrapper" id="<?php echo esc_attr( 'prta-' . $table_id ); ?>">
			<form class="prta-filters" method="get">
				<?php foreach ( $get as $key => $value ) if ( 0 !== strpos( (string) $key, $prefix ) && is_scalar( $value ) ) printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $key ), esc_attr( $value ) ); ?>
				<?php foreach ( $settings['searchFilters'] as $filter ) $this->render_filter( $filter, $prefix, $get ); ?>
				<?php if ( $active_filter_keys ) : ?><a class="button prta-reset-filters" href="<?php echo esc_url( $reset_url ); ?>"><?php esc_html_e( 'Reset', 'table-for-woocommerce' ); ?></a><?php endif; ?>
				<label class="prta-search"><span class="screen-reader-text"><?php esc_html_e( 'Search products', 'table-for-woocommerce' ); ?></span><input type="search" name="<?php echo esc_attr( $prefix . 'search' ); ?>" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search products…', 'table-for-woocommerce' ); ?>"></label>
				<button type="submit"><?php esc_html_e( 'Filter', 'table-for-woocommerce' ); ?></button>
			</form>
			<form method="post" class="prta-cart-form">
				<?php wp_nonce_field( 'prta_add_to_cart', 'prta_cart_nonce' ); ?>
				<input type="hidden" name="prta_cart_action" value="add"><input type="hidden" name="prta_return_url" value="<?php echo esc_url( $current_url ); ?>">
				<?php if ( in_array( $settings['cartLocation'], array( 'above', 'all' ), true ) ) : ?><div class="prta-bulk-actions"><?php echo $cart_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
				<div class="prta-table-scroll"><table class="prta-table"><thead><tr>
				<?php foreach ( $settings['columns'] as $column ) : ?><th><?php if ( 'buy' === $column['value'] && ! empty( $settings['selectAll'] ) ) : ?><input type="checkbox" onclick="this.closest('table').querySelectorAll('.prta-checkbox').forEach((item)=>item.checked=this.checked)" aria-label="<?php esc_attr_e( 'Select all products', 'table-for-woocommerce' ); ?>"> <?php endif; ?><?php echo esc_html( $column['label'] ); ?></th><?php endforeach; ?>
				</tr></thead><tbody>
				<?php if ( $products ) : foreach ( $products as $product ) : ?><tr>
					<?php foreach ( $settings['columns'] as $column ) : ?><td class="<?php echo esc_attr( 'prta-column-' . sanitize_html_class( $column['value'] ) ); ?>"><?php echo $this->get_cell( $column['value'], $product, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td><?php endforeach; ?>
				</tr><?php endforeach; else : ?><tr><td colspan="<?php echo esc_attr( count( $settings['columns'] ) ); ?>"><?php esc_html_e( 'No products found.', 'table-for-woocommerce' ); ?></td></tr><?php endif; ?>
				</tbody></table></div>
				<?php if ( in_array( $settings['cartLocation'], array( 'below', 'all' ), true ) ) : ?><div class="prta-bulk-actions"><?php echo $cart_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			</form>
			<div class="prta-pagination"><form method="get">
				<?php foreach ( $get as $key => $value ) if ( $key !== $prefix . 'limit' && $key !== $prefix . 'page' && is_scalar( $value ) ) printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $key ), esc_attr( $value ) ); ?>
				<label><?php esc_html_e( 'Products per page', 'table-for-woocommerce' ); ?> <select name="<?php echo esc_attr( $prefix . 'limit' ); ?>" onchange="this.form.submit()"><?php foreach ( array_unique( array( $settings['productsPerPage'], 10, 25, 50, 100 ) ) as $size ) : ?><option value="<?php echo esc_attr( $size ); ?>" <?php selected( $limit, $size ); ?>><?php echo esc_html( $size ); ?></option><?php endforeach; ?></select></label>
			</form><div class="prta-pages-info"><?php echo esc_html( sprintf( _n( '%s item', '%s items', $results->total, 'table-for-woocommerce' ), number_format_i18n( $results->total ) ) ); ?> <?php if ( $results->max_num_pages > 1 ) echo wp_kses_post( paginate_links( array( 'base' => esc_url_raw( add_query_arg( $prefix . 'page', '%#%', $current_url ) ), 'current' => $page, 'total' => $results->max_num_pages, 'type' => 'list' ) ) ); ?></div></div>
		</div>
		<?php
		return ob_get_clean();
	}
}

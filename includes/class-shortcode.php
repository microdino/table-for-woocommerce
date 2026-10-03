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
		add_filter( 'template_include', array( $this, 'archive_table_template' ), 9999 );
	}

	/** Use a table for the selected WooCommerce archive pages. */
	public function archive_table_template( $template ) {
		if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_category() || is_product_tag() || is_tax() || is_search() ) ) return $template;
		$setting = '';
		if ( is_shop() ) $setting = 'shopPage';
		elseif ( is_product_category() ) $setting = 'categoryPage';
		elseif ( is_product_tag() ) $setting = 'tagPage';
		elseif ( is_tax( 'product_brand' ) ) $setting = 'brandPage';
		elseif ( is_tax() && 0 === strpos( (string) get_queried_object()->taxonomy, 'pa_' ) ) $setting = 'attributesPage';
		elseif ( is_search() && in_array( 'product', (array) get_query_var( 'post_type' ), true ) ) $setting = 'searchPage';
		if ( ! $setting ) return $template;
		$tables = get_posts( array( 'post_type' => 'adpro_option', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
		foreach ( $tables as $table ) {
			$options = json_decode( $table->post_content, true );
			if ( is_array( $options ) && 'shop' === ( $options['displayIn'] ?? '' ) && ! empty( $options[ $setting ] ) ) {
				set_query_var( 'prta_table_id', $table->ID );
				return PRTA_PATH . 'templates/archive-product-table.php';
			}
		}
		return $template;
	}

	/**
	 * Process single and bulk add-to-cart submissions.
	 */
	public function handle_add_to_cart() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['prta_cart_action'], $_POST['prta_cart_nonce'] ) || 'add' !== $_POST['prta_cart_action'] || ! is_string( $_POST['prta_cart_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['prta_cart_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'prta_add_to_cart' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		$quantities = isset( $_POST['prta_quantity'] ) && is_array( $_POST['prta_quantity'] ) ? wp_unslash( $_POST['prta_quantity'] ) : array();
		$variations = isset( $_POST['prta_variation'] ) && is_array( $_POST['prta_variation'] ) ? array_map( 'absint', array_filter( wp_unslash( $_POST['prta_variation'] ), 'is_scalar' ) ) : array();
		$posted_attributes = isset( $_POST['prta_attribute'] ) && is_array( $_POST['prta_attribute'] ) ? wp_unslash( $_POST['prta_attribute'] ) : array();
		$product_ids = array();
		if ( isset( $_POST['prta_add_product'] ) && is_scalar( $_POST['prta_add_product'] ) ) {
			$product_ids[] = absint( $_POST['prta_add_product'] );
		} elseif ( isset( $_POST['prta_products'] ) && is_array( $_POST['prta_products'] ) ) {
			$product_ids = array_slice( array_map( 'absint', array_filter( wp_unslash( $_POST['prta_products'] ), 'is_scalar' ) ), 0, 1000 );
		}
		foreach ( array_unique( array_filter( $product_ids ) ) as $product_id ) {
			$product  = wc_get_product( $product_id );
			$quantity = isset( $quantities[ $product_id ] ) && is_scalar( $quantities[ $product_id ] ) ? max( 1, absint( $quantities[ $product_id ] ) ) : 1;
			if ( $product && $product->is_type( 'variation' ) && $product->is_purchasable() && $product->is_in_stock() ) {
				$attributes = $product->get_variation_attributes();
				if ( apply_filters( 'woocommerce_add_to_cart_validation', true, $product->get_parent_id(), $quantity, $product_id, $attributes ) ) WC()->cart->add_to_cart( $product->get_parent_id(), $quantity, $product_id, $attributes );
			} elseif ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
				if ( apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity ) ) WC()->cart->add_to_cart( $product_id, $quantity );
			} elseif ( $product && $product->is_type( 'variable' ) && ! empty( $variations[ $product_id ] ) ) {
				$variation = wc_get_product( $variations[ $product_id ] );
				if ( $variation && $variation->is_type( 'variation' ) && $variation->get_parent_id() === $product_id && $variation->is_purchasable() && $variation->is_in_stock() ) {
					$choices = $product->get_variation_attributes();
					$values  = isset( $posted_attributes[ $product_id ] ) && is_array( $posted_attributes[ $product_id ] ) ? $posted_attributes[ $product_id ] : array();
					$cart_attributes = array();
					$valid = true;
					foreach ( $choices as $name => $options ) {
						$key = 'attribute_' . $name;
						$value = isset( $values[ $name ] ) && is_scalar( $values[ $name ] ) ? sanitize_text_field( $values[ $name ] ) : '';
						$variation_value = $variation->get_variation_attributes()[ $key ] ?? '';
						if ( '' === $value || ! in_array( $value, $options, true ) || ( '' !== $variation_value && $variation_value !== $value ) ) {
							$valid = false;
							break;
						}
						$cart_attributes[ $key ] = $value;
					}
					if ( $valid && apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation->get_id(), $cart_attributes ) ) {
						WC()->cart->add_to_cart( $product_id, $quantity, $variation->get_id(), $cart_attributes );
					}
				}
			}
		}
		$redirect = isset( $_POST['prta_return_url'] ) && is_string( $_POST['prta_return_url'] ) ? esc_url_raw( wp_unslash( $_POST['prta_return_url'] ) ) : wp_get_referer();
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
		$global_settings = CartSettings::get();
		$settings = wp_parse_args(
			$settings,
			array(
				'columns'           => array( 'sku', 'name', 'stock', 'price', 'total', 'buy' ),
				'searchFilters'     => array(),
				'productsPerPage'   => $global_settings['productsPerPage'],
				'sortBy'            => 'default',
				'sortDirection'     => 'automatic',
				'quantitySelector'  => true,
				'descriptionLength' => $global_settings['descriptionLength'],
			)
		);
		$settings['columns']       = $this->normalize_items( $settings['columns'] );
		if ( ! $settings['columns'] ) $settings['columns'] = $this->normalize_items( array( 'name', 'price', 'buy' ) );
		$settings['searchFilters'] = $this->normalize_items( $saved_filters );
		$settings['cartSettings'] = $global_settings;
		return $settings;
	}

	/** Bulk purchase button shared by the positions above and below a table. */
	private function render_bulk_button( $settings ) {
		$cart = $settings['cartSettings'];
		$label = strtr( $cart['multipleAddToCartPlural'], array( '{items}' => '0', '{total}' => html_entity_decode( wp_strip_all_tags( wc_price( 0 ) ), ENT_QUOTES, 'UTF-8' ) ) );
		printf( '<div class="prta-bulk-actions"><button type="submit" class="prta-button prta-bulk-submit" name="prta_add_selected" value="1" data-singular-template="%s" data-plural-template="%s" disabled>%s</button></div>', esc_attr( $cart['multipleAddToCartSingular'] ), esc_attr( $cart['multipleAddToCartPlural'] ), esc_html( $label ) );
	}

	private function get_row_price( $product ) {
		return $product->is_type( 'variable' ) ? (float) $product->get_variation_price( 'min', true ) : (float) wc_get_price_to_display( $product );
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
			if ( ! is_string( $item ) && ! is_array( $item ) ) continue;
			$value = is_array( $item ) && isset( $item['value'] ) ? $item['value'] : $item;
			if ( ! is_string( $value ) ) continue;
			$label = is_array( $item ) && isset( $item['label'] ) ? $item['label'] : $this->get_default_label( $value );
			if ( ! is_scalar( $label ) ) $label = $this->get_default_label( $value );
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
			'id' => __( 'ID', 'table-for-woocommerce' ), 'sku' => __( 'SKU', 'table-for-woocommerce' ), 'name' => __( 'Name', 'table-for-woocommerce' ), 'description' => __( 'Description', 'table-for-woocommerce' ),
			'summary' => __( 'Summary', 'table-for-woocommerce' ), 'date' => __( 'Published date', 'table-for-woocommerce' ), 'modified' => __( 'Last modified date', 'table-for-woocommerce' ),
			'image' => __( 'Image', 'table-for-woocommerce' ), 'stock' => __( 'Stock', 'table-for-woocommerce' ), 'reviews' => __( 'Reviews', 'table-for-woocommerce' ), 'weight' => __( 'Weight', 'table-for-woocommerce' ),
			'dimensions' => __( 'Dimensions', 'table-for-woocommerce' ), 'price' => __( 'Price', 'table-for-woocommerce' ), 'buy' => __( 'Buy', 'table-for-woocommerce' ), 'button' => __( 'Button', 'table-for-woocommerce' ),
			'total' => __( 'Total', 'table-for-woocommerce' ), 'categories' => __( 'Categories', 'table-for-woocommerce' ), 'tags' => __( 'Tags', 'table-for-woocommerce' ),
			'tax:pa_color' => __( 'Color', 'table-for-woocommerce' ), 'tax:pa_size' => __( 'Size', 'table-for-woocommerce' ), 'author' => __( 'Author', 'table-for-woocommerce' ),
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
		if ( 'product_cat' === $taxonomy ) {
			$by_parent = array();
			$term_ids  = array();
			foreach ( $terms as $term ) {
				$term_ids[ $term->term_id ] = true;
			}
			foreach ( $terms as $term ) {
				$parent = isset( $term_ids[ $term->parent ] ) ? $term->parent : 0;
				$by_parent[ $parent ][] = $term;
			}
			$render_children = function ( $parent, $depth ) use ( &$render_children, $by_parent, $selected ) {
				$output = '';
				foreach ( $by_parent[ $parent ] ?? array() as $term ) {
					$label = str_repeat( '— ', $depth ) . $term->name;
					$output .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $term->slug ), selected( $selected, $term->slug, false ), esc_html( $label ) );
					$output .= $render_children( $term->term_id, $depth + 1 );
				}
				return $output;
			};
			return $render_children( 0, 0 );
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
			$options = $this->get_term_options( $taxonomy, $selected );
			if ( '' === $options && in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
				return;
			}
			printf( '<label class="prta-field"><span class="prta-field-label">%1$s</span><select name="%2$s"><option value="">%1$s</option>%3$s</select></label>', esc_html( $filter['label'] ), esc_attr( $key ), $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'author' === $value ) {
			echo '<label class="prta-field"><span class="prta-field-label">' . esc_html( $filter['label'] ) . '</span>';
			wp_dropdown_users( array( 'name' => $key, 'id' => wp_unique_id( $key . '-' ), 'selected' => absint( $selected ), 'show_option_all' => __( 'All Authors', 'table-for-woocommerce' ), 'echo' => true ) );
			echo '</label>';
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
		$taxonomy_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $id;
		switch ( $column ) {
			case 'id': return esc_html( $id );
			case 'sku': return esc_html( $product->get_sku() );
			case 'name': return '<a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
			case 'description': return $settings['descriptionLength'] ? esc_html( wp_trim_words( wp_strip_all_tags( $product->get_description() ), absint( $settings['descriptionLength'] ) ) ) : '';
			case 'summary': return $settings['descriptionLength'] ? esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), absint( $settings['descriptionLength'] ) ) ) : '';
			case 'date': return esc_html( $product->get_date_created() ? wc_format_datetime( $product->get_date_created() ) : '' );
			case 'modified': return esc_html( $product->get_date_modified() ? wc_format_datetime( $product->get_date_modified() ) : '' );
			case 'image': return '<a href="' . esc_url( $product->get_permalink() ) . '">' . wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ) . '</a>';
			case 'stock': return wp_kses_post( wc_get_stock_html( $product ) );
			case 'reviews': return wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() );
			case 'weight': return esc_html( $product->has_weight() ? wc_format_weight( $product->get_weight() ) : '' );
			case 'dimensions': return esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) );
			case 'price': return wp_kses_post( $product->get_price_html() );
			case 'total':
				$price = $product->is_type( 'variable' ) ? $product->get_variation_price( 'min', true ) : wc_get_price_to_display( $product );
				return '<span class="prta-line-total" data-price="' . esc_attr( $price ) . '">' . wp_kses_post( wc_price( $price ) ) . '</span>';
			case 'categories': return wp_kses_post( wc_get_product_category_list( $taxonomy_id ) );
			case 'tags': return wp_kses_post( wc_get_product_tag_list( $taxonomy_id ) );
			case 'tax:pa_color': return esc_html( $product->get_attribute( 'pa_color' ) );
			case 'tax:pa_size': return esc_html( $product->get_attribute( 'pa_size' ) );
			case 'author': return esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $taxonomy_id ) ) );
			case 'button': return $this->get_product_link( $product );
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
		if ( $product->is_type( 'variation' ) ) {
			if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) return $this->get_product_link( $product );
			$id = $product->get_id();
			$quantity = ! empty( $settings['quantitySelector'] ) && ! $product->is_sold_individually() ? '<input class="prta-qty" aria-label="' . esc_attr( sprintf( __( 'Quantity for %s', 'table-for-woocommerce' ), $product->get_name() ) ) . '" type="number" name="prta_quantity[' . esc_attr( $id ) . ']" value="1" min="1" step="1">' : '';
			return '<div class="prta-buy-controls">' . $quantity . '<button type="submit" class="prta-button prta-cart" name="prta_add_product" value="' . esc_attr( $id ) . '">' . esc_html( $settings['cartSettings']['addToCartText'] ) . '</button><input class="prta-checkbox" aria-label="' . esc_attr( sprintf( __( 'Select %s', 'table-for-woocommerce' ), $product->get_name() ) ) . '" name="prta_products[]" value="' . esc_attr( $id ) . '" type="checkbox"></div>';
		}
		if ( $product->is_type( 'external' ) ) {
			return $this->get_product_link( $product );
		}
		if ( $product->is_type( 'variable' ) && 'dropdown' === ( $settings['variationStyle'] ?? 'dropdown' ) ) {
			return $this->get_variation_control( $product, $settings );
		}
		if ( ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return $this->get_product_link( $product );
		}
		$id       = $product->get_id();
		$quantity = ! empty( $settings['quantitySelector'] ) && ! $product->is_sold_individually() ? '<input class="prta-qty" aria-label="' . esc_attr( sprintf( __( 'Quantity for %s', 'table-for-woocommerce' ), $product->get_name() ) ) . '" type="number" name="prta_quantity[' . esc_attr( $id ) . ']" value="1" min="1" step="1">' : '';
		return '<div class="prta-buy-controls">' . $quantity . '<button type="submit" class="prta-button prta-cart" aria-label="' . esc_attr( sprintf( __( 'Add %s to cart', 'table-for-woocommerce' ), $product->get_name() ) ) . '" name="prta_add_product" value="' . esc_attr( $id ) . '">' . esc_html( $settings['cartSettings']['addToCartText'] ) . '</button><input class="prta-checkbox" aria-label="' . esc_attr( sprintf( __( 'Select %s', 'table-for-woocommerce' ), $product->get_name() ) ) . '" name="prta_products[]" value="' . esc_attr( $id ) . '" type="checkbox"></div>';
	}

	/**
	 * Link to a product or its external purchase URL.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	private function get_product_link( $product ) {
		$external = $product->is_type( 'external' );
		$url      = $external ? $product->add_to_cart_url() : $product->get_permalink();
		return '<a class="prta-button prta-button-secondary" href="' . esc_url( $url ) . '"' . ( $external ? ' target="_blank" rel="noopener noreferrer"' : '' ) . '>' . esc_html( $product->add_to_cart_text() ) . '</a>';
	}

	/**
	 * Render available variations above the purchase controls.
	 *
	 * @param \WC_Product $product Variable product.
	 * @param array       $settings Table settings.
	 * @return string
	 */
	private function get_variation_control( $product, $settings ) {
		$id       = $product->get_id();
		$defaults = $product->get_default_attributes();
		$available = array();
		foreach ( $product->get_available_variations() as $data ) {
			if ( empty( $data['is_purchasable'] ) || empty( $data['is_in_stock'] ) || empty( $data['variation_is_active'] ) ) {
				continue;
			}
			$available[] = array( 'id' => absint( $data['variation_id'] ), 'attributes' => $data['attributes'], 'price' => (float) $data['display_price'] );
		}
		if ( ! $available ) {
			return $this->get_product_link( $product );
		}
		$selectors = '';
		foreach ( $product->get_variation_attributes() as $name => $options ) {
			$attribute_label = wc_attribute_label( $name );
			$selectors .= '<select class="prta-attribute" aria-label="' . esc_attr( $attribute_label ) . '" data-attribute="' . esc_attr( 'attribute_' . $name ) . '" name="prta_attribute[' . esc_attr( $id ) . '][' . esc_attr( $name ) . ']"><option value="">' . esc_html( sprintf( __( 'Select %s', 'table-for-woocommerce' ), $attribute_label ) ) . '</option>';
			foreach ( $options as $value ) {
				$term = taxonomy_exists( $name ) ? get_term_by( 'slug', $value, $name ) : false;
				$label = $term ? $term->name : $value;
				$selectors .= '<option value="' . esc_attr( $value ) . '"' . selected( $defaults[ $name ] ?? '', $value, false ) . '>' . esc_html( $label ) . '</option>';
			}
			$selectors .= '</select>';
		}
		$quantity = ! empty( $settings['quantitySelector'] ) && ! $product->is_sold_individually() ? '<input class="prta-qty" aria-label="' . esc_attr( sprintf( __( 'Quantity for %s', 'table-for-woocommerce' ), $product->get_name() ) ) . '" type="number" name="prta_quantity[' . esc_attr( $id ) . ']" value="1" min="1" step="1">' : '';
		return '<div class="prta-variable-controls" data-variations="' . esc_attr( wp_json_encode( $available ) ) . '">' . $selectors . '<input type="hidden" class="prta-variation" name="prta_variation[' . esc_attr( $id ) . ']" value=""><div class="prta-buy-controls">' . $quantity . '<button type="submit" class="prta-button prta-cart" name="prta_add_product" value="' . esc_attr( $id ) . '" disabled>' . esc_html( $settings['cartSettings']['addToCartText'] ) . '</button><input class="prta-checkbox" aria-label="' . esc_attr( sprintf( __( 'Select %s', 'table-for-woocommerce' ), $product->get_name() ) ) . '" name="prta_products[]" value="' . esc_attr( $id ) . '" type="checkbox" disabled></div></div>';
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
		$attributes = shortcode_atts( array( 'id' => '', 'archive' => '' ), $atts, 'product_table' );
		$table_id   = absint( $attributes['id'] );
		if ( ! $table_id ) {
			return '<p class="woocommerce-info">' . esc_html__( 'A product table ID is required.', 'table-for-woocommerce' ) . '</p>';
		}
		$settings = $this->get_settings( $table_id );
		if ( is_wp_error( $settings ) ) {
			return '<p class="woocommerce-info">' . esc_html( $settings->get_error_message() ) . '</p>';
		}
		$prefix = 'prta_' . $table_id . '_';
		$get    = array_map( 'sanitize_text_field', wp_unslash( array_filter( $_GET, 'is_scalar' ) ) ); // Read-only product filters require no nonce.
		$page   = isset( $get[ $prefix . 'page' ] ) ? max( 1, absint( $get[ $prefix . 'page' ] ) ) : ( $attributes['archive'] ? max( 1, (int) get_query_var( 'paged' ) ) : 1 );
		$limit  = min( 1000, isset( $get[ $prefix . 'limit' ] ) ? max( 1, absint( $get[ $prefix . 'limit' ] ) ) : max( 1, absint( $settings['productsPerPage'] ) ) );
		$search = $settings['cartSettings']['showSearchBox'] && isset( $get[ $prefix . 'search' ] ) ? sanitize_text_field( $get[ $prefix . 'search' ] ) : ( $attributes['archive'] && is_search() ? get_search_query() : '' );
		$sort_map = array( 'sorting' => 'menu_order', 'id' => 'ID', 'name' => 'title', 'published' => 'date', 'modified' => 'modified', 'sales' => 'popularity', 'rating' => 'rating', 'random' => 'rand', 'price' => 'price' );
		$orderby = isset( $sort_map[ $settings['sortBy'] ] ) ? $sort_map[ $settings['sortBy'] ] : 'menu_order';
		$order   = 'ascending' === $settings['sortDirection'] ? 'ASC' : ( 'descending' === $settings['sortDirection'] ? 'DESC' : ( in_array( $orderby, array( 'title', 'menu_order' ), true ) ? 'ASC' : 'DESC' ) );
		$tax_query = array();
		if ( $attributes['archive'] && is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) $tax_query[] = array( 'taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => array( $term->term_id ) );
		}
		$query_type = $attributes['archive'] ? 'all' : ( isset( $settings['queryType'] ) ? sanitize_key( $settings['queryType'] ) : 'all' );
		$query_taxonomies = array( 'categories' => array( 'product_cat', 'queryCategories' ), 'tags' => array( 'product_tag', 'queryTags' ), 'brands' => array( 'product_brand', 'queryBrands' ) );
		if ( isset( $query_taxonomies[ $query_type ] ) ) {
			list( $taxonomy, $setting_key ) = $query_taxonomies[ $query_type ];
			$terms = array_filter( array_map( 'absint', (array) ( $settings[ $setting_key ] ?? array() ) ) );
			$tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $terms ? $terms : array( 0 ), 'operator' => 'IN' );
		}
		if ( 'all' === $query_type && ! $attributes['archive'] ) {
			$excluded_categories = array_filter( array_map( 'absint', (array) ( $settings['excludeCategories'] ?? array() ) ) );
			if ( $excluded_categories ) $tax_query[] = array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $excluded_categories, 'operator' => 'NOT IN' );
		}
		$product_query = array( 'status' => 'publish', 'limit' => $limit, 'page' => $page, 'paginate' => true, 'orderby' => $orderby, 'order' => $order, 's' => $search, 'return' => 'objects' );
		if ( ! $settings['cartSettings']['showHiddenProducts'] ) $product_query['visibility'] = is_search() ? 'search' : 'catalog';
		if ( 'default' === $settings['sortBy'] ) {
			$catalog_order = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
			$catalog_map = array( 'menu_order' => 'menu_order', 'popularity' => 'popularity', 'rating' => 'rating', 'date' => 'date', 'price' => 'price', 'price-desc' => 'price' );
			$product_query['orderby'] = $catalog_map[ $catalog_order ] ?? 'menu_order';
			$product_query['order'] = 'automatic' === $settings['sortDirection'] ? ( in_array( $catalog_order, array( 'popularity', 'rating', 'date', 'price-desc' ), true ) ? 'DESC' : 'ASC' ) : $order;
		}
		if ( 'products' === $query_type ) {
			$ids = array_filter( array_map( 'absint', (array) ( $settings['queryProducts'] ?? array() ) ) );
			$product_query['include'] = $ids ? $ids : array( 0 );
		} elseif ( ! $attributes['archive'] ) {
			$product_query['exclude'] = array_filter( array_map( 'absint', (array) ( $settings['excludeProducts'] ?? array() ) ) );
		}
		$author = 0;
		$active_filter_keys = array();
		foreach ( $settings['searchFilters'] as $filter ) {
			$value = $filter['value'];
			$key = $prefix . 'filter_' . sanitize_key( str_replace( ':', '_', $value ) );
			$selected = isset( $get[ $key ] ) ? sanitize_title( $get[ $key ] ) : '';
			if ( ! $selected ) continue;
			if ( 'author' === $value ) { $author = absint( $selected ); $active_filter_keys[] = $key; continue; }
			$taxonomy = 0 === strpos( $value, 'tax:' ) ? substr( $value, 4 ) : array_search( $value, array( 'product_cat' => 'categories', 'product_tag' => 'tags' ), true );
			$taxonomy = $taxonomy ? $taxonomy : $value;
			if ( taxonomy_exists( $taxonomy ) ) {
				$active_filter_keys[] = $key;
				$tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => array( $selected ) );
			}
		}
		$query_filter = function ( $query ) use ( $tax_query, $author ) {
			if ( $tax_query ) $query['tax_query'] = array_merge( $query['tax_query'] ?? array(), $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			if ( $author ) $query['author'] = $author;
			return $query;
		};
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', $query_filter );
		try {
			$results = CartSettings::query_products( $product_query, array( $tax_query, $author ) );
		} finally {
			remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', $query_filter );
		}
		$products = $results->products;
		if ( 'separate' === ( $settings['variationStyle'] ?? '' ) ) {
			$rows = array();
			foreach ( $products as $product ) {
				if ( ! $product->is_type( 'variable' ) ) {
					$rows[] = $product;
					continue;
				}
				foreach ( $product->get_visible_children() as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation && $variation->exists() && $variation->variation_is_visible() ) $rows[] = $variation;
				}
			}
			$products = $rows;
		}
		$current_url = remove_query_arg( $prefix . 'page' );
		$reset_url  = remove_query_arg( array_merge( $active_filter_keys, array( $prefix . 'page', $prefix . 'search' ) ) );
		$instance_id = wp_unique_id( 'prta-' . $table_id . '-' );
		ob_start();
		?>
		<div class="prta-wrapper<?php echo $settings['cartSettings']['stickyHeader'] && ! $settings['cartSettings']['hideTableHeading'] ? ' prta-has-sticky-header' : ''; ?>" id="<?php echo esc_attr( $instance_id ); ?>">
			<form class="prta-filters" method="get">
				<?php foreach ( $get as $key => $value ) if ( 0 !== strpos( (string) $key, $prefix ) && is_scalar( $value ) ) printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $key ), esc_attr( $value ) ); ?>
				<?php foreach ( $settings['searchFilters'] as $filter ) $this->render_filter( $filter, $prefix, $get ); ?>
				<?php if ( $settings['cartSettings']['showSearchBox'] ) : ?>
				<div class="prta-field prta-search"><label class="prta-field-label" for="<?php echo esc_attr( $instance_id . '-search' ); ?>"><?php esc_html_e( 'Search products', 'table-for-woocommerce' ); ?></label><div class="prta-search-control"><input type="search" id="<?php echo esc_attr( $instance_id . '-search' ); ?>" name="<?php echo esc_attr( $prefix . 'search' ); ?>" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search products…', 'table-for-woocommerce' ); ?>"><button class="prta-button prta-button-secondary prta-icon-button" type="submit" aria-label="<?php esc_attr_e( 'Search products', 'table-for-woocommerce' ); ?>" title="<?php esc_attr_e( 'Search products', 'table-for-woocommerce' ); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/></svg></button></div></div>
				<?php endif; ?>
				<input type="hidden" name="<?php echo esc_attr( $prefix . 'limit' ); ?>" value="<?php echo esc_attr( $limit ); ?>">
				<?php if ( $active_filter_keys || '' !== $search ) : ?><a class="prta-button prta-button-secondary prta-icon-button prta-reset-filters" href="<?php echo esc_url( $reset_url ); ?>" aria-label="<?php esc_attr_e( 'Clear filters and search', 'table-for-woocommerce' ); ?>" title="<?php esc_attr_e( 'Clear filters and search', 'table-for-woocommerce' ); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18"/></svg></a><?php endif; ?>
			</form>
			<form method="post" class="prta-cart-form">
				<?php wp_nonce_field( 'prta_add_to_cart', 'prta_cart_nonce' ); ?>
				<input type="hidden" name="prta_cart_action" value="add"><input type="hidden" name="prta_return_url" value="<?php echo esc_url( $current_url ); ?>">
				<?php if ( in_array( $settings['cartSettings']['addToCartLocation'], array( 'above', 'all' ), true ) && in_array( 'buy', array_column( $settings['columns'], 'value' ), true ) ) $this->render_bulk_button( $settings ); ?>
				<div class="prta-table-scroll" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Product table', 'table-for-woocommerce' ); ?>"><table class="prta-table"><caption class="prta-sr-only"><?php esc_html_e( 'Products and purchase options', 'table-for-woocommerce' ); ?></caption><thead<?php echo $settings['cartSettings']['hideTableHeading'] ? ' class="prta-sr-only"' : ''; ?>><tr>
				<?php foreach ( $settings['columns'] as $column ) : ?><th scope="col"><span<?php if ( 'buy' === $column['value'] ) : ?> class="prta-select-all-heading"<?php endif; ?>><?php echo esc_html( $column['label'] ); ?><?php if ( 'buy' === $column['value'] && $settings['cartSettings']['selectAllProducts'] ) : ?><input type="checkbox" class="prta-select-all" aria-label="<?php esc_attr_e( 'Select all products', 'table-for-woocommerce' ); ?>"><?php endif; ?></span></th><?php endforeach; ?>
				</tr></thead><tbody>
				<?php if ( $products ) : foreach ( $products as $product ) : ?><tr data-price="<?php echo esc_attr( $this->get_row_price( $product ) ); ?>">
					<?php foreach ( $settings['columns'] as $column ) : ?><td class="<?php echo esc_attr( 'prta-column-' . sanitize_html_class( $column['value'] ) ); ?>"><?php echo $this->get_cell( $column['value'], $product, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td><?php endforeach; ?>
				</tr><?php endforeach; else : ?><tr><td class="prta-empty" colspan="<?php echo esc_attr( count( $settings['columns'] ) ); ?>"><strong><?php esc_html_e( 'No products found', 'table-for-woocommerce' ); ?></strong><span><?php esc_html_e( 'Try another search or reset your filters.', 'table-for-woocommerce' ); ?></span></td></tr><?php endif; ?>
				</tbody>
                <?php if ( $settings['cartSettings']['showTableFooter'] ) : ?><tfoot><tr><?php foreach ( $settings['columns'] as $column ) : ?><th scope="col"><?php echo esc_html( $column['label'] ); ?></th><?php endforeach; ?></tr></tfoot><?php endif; ?>
                </table></div>
				<?php if ( in_array( $settings['cartSettings']['addToCartLocation'], array( 'below', 'all' ), true ) && in_array( 'buy', array_column( $settings['columns'], 'value' ), true ) ) $this->render_bulk_button( $settings ); ?>
			</form>
			<div class="prta-pagination"><form method="get">
				<?php foreach ( $get as $key => $value ) if ( $key !== $prefix . 'limit' && $key !== $prefix . 'page' && is_scalar( $value ) ) printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $key ), esc_attr( $value ) ); ?>
				<label><span class="prta-sr-only"><?php esc_html_e( 'Products per page', 'table-for-woocommerce' ); ?></span><select name="<?php echo esc_attr( $prefix . 'limit' ); ?>"><?php foreach ( array_unique( array( $limit, $settings['productsPerPage'], 10, 25, 50, 100 ) ) as $size ) : ?><option value="<?php echo esc_attr( $size ); ?>" <?php selected( $limit, $size ); ?>><?php echo esc_html( $size ); ?></option><?php endforeach; ?></select></label>
			</form><div class="prta-pages-info"><?php echo esc_html( sprintf( _n( '%s item', '%s items', $results->total, 'table-for-woocommerce' ), number_format_i18n( $results->total ) ) ); ?> <nav aria-label="<?php esc_attr_e( 'Product table pages', 'table-for-woocommerce' ); ?>"><?php if ( $results->max_num_pages > 1 ) echo wp_kses_post( paginate_links( array( 'base' => esc_url_raw( add_query_arg( $prefix . 'page', '%#%', $current_url ) ), 'current' => $page, 'total' => $results->max_num_pages, 'type' => 'list', 'prev_text' => '&larr;<span class="prta-sr-only">' . esc_html__( 'Previous', 'table-for-woocommerce' ) . '</span>', 'next_text' => '&rarr;<span class="prta-sr-only">' . esc_html__( 'Next', 'table-for-woocommerce' ) . '</span>' ) ) ); ?></nav></div></div>
		</div>
		<?php
		return ob_get_clean();
	}
}

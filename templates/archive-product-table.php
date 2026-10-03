<?php
/** Product table replacement for selected WooCommerce archive pages. */
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );
do_action( 'woocommerce_shop_loop_header' );
echo do_shortcode( '[product_table id="' . absint( get_query_var( 'prta_table_id' ) ) . '" archive="1"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
do_action( 'woocommerce_after_main_content' );
do_action( 'woocommerce_sidebar' );
get_footer( 'shop' );

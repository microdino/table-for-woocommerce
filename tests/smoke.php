<?php
/** Run with `php tests/smoke.php`; WordPress and WooCommerce are stubbed. */
define( 'ABSPATH', __DIR__ );
$routes = array();
$options = array();
$posts = array();
$last_query = array();
$transients = array();
$query_count = 0;
function add_action() {}
function add_filter() {}
function remove_filter() {}
function add_shortcode() {}
function register_rest_route( $namespace, $path, $route ) { $GLOBALS['routes'][ $namespace . $path ] = $route; }
function current_user_can() { return true; }
function __( $text ) { return $text; }
function esc_html__( $text ) { return $text; }
function esc_html_e( $text ) { echo $text; }
function esc_attr_e( $text ) { echo $text; }
function sanitize_text_field( $value ) { return strip_tags( (string) $value ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_parse_args( $value, $defaults ) { return array_merge( $defaults, $value ); }
function get_option( $name, $default = false ) { return $GLOBALS['options'][ $name ] ?? $default; }
function update_option( $name, $value ) { $GLOBALS['options'][ $name ] = $value; }
function get_transient( $name ) { return $GLOBALS['transients'][ $name ] ?? false; }
function set_transient( $name, $value ) { $GLOBALS['transients'][ $name ] = $value; }
function get_current_user_id() { return 1; }
function get_locale() { return 'en_US'; }
function get_woocommerce_currency() { return 'USD'; }
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function shortcode_atts( $defaults, $attributes ) { return array_merge( $defaults, (array) $attributes ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_attr( $value ); }
function esc_url_raw( $value ) { return $value; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function sanitize_html_class( $value ) { return sanitize_key( $value ); }
function sanitize_title( $value ) { return sanitize_key( $value ); }
function wp_unslash( $value ) { return $value; }
function wp_unique_id( $prefix ) { return $prefix . '1'; }
function wp_nonce_field() { echo '<input type="hidden" name="prta_cart_nonce" value="test">'; }
function remove_query_arg() { return '/shop/'; }
function add_query_arg() { return '/shop/?page=1'; }
function selected( $a, $b, $echo = true ) { $result = $a == $b ? ' selected' : ''; if ( $echo ) echo $result; return $result; }
function number_format_i18n( $value ) { return (string) $value; }
function _n( $one, $many, $count ) { return 1 === $count ? $one : $many; }
function paginate_links() { return ''; }
function wp_kses_post( $value ) { return $value; }
function is_search() { return false; }
function is_tax() { return false; }
function wc_price( $value ) { return '$' . number_format( $value, 2 ); }
function wc_get_products( $query ) { $GLOBALS['last_query'] = $query; ++$GLOBALS['query_count']; return (object) array( 'products' => array(), 'total' => 0, 'max_num_pages' => 0 ); }
function wc_get_product() { return null; }
class WooCommerce {}
class WC_Cache_Helper { public static function get_transient_version() { return 'v1'; } }
define( 'HOUR_IN_SECONDS', 3600 );
class WP_Error {
	public $code;
	public $data;
	private $message;
	public function __construct( $code, $message, $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_message() { return $this->message; }
}
class TestRequest {
	private $method;
	private $params;
	public function __construct( $method, $params = array() ) { $this->method = $method; $this->params = $params; }
	public function get_method() { return $this->method; }
	public function get_json_params() { return $this->params; }
}
function check( $condition, $message ) { if ( ! $condition ) throw new RuntimeException( $message ); }
require __DIR__ . '/../includes/class-cart-settings.php';
require __DIR__ . '/../includes/class-post-type.php';
require __DIR__ . '/../includes/class-shortcode.php';

$cart = new PRTA\Includes\CartSettings();
$cart->register_routes();
$settings_route = $routes['prta/v1/settings'];
check( is_callable( $settings_route['callback'] ), 'Settings route missing' );
check( $settings_route['callback']( new TestRequest( 'GET' ) )['productsPerPage'] === 20, 'Settings defaults' );
$saved = $settings_route['callback']( new TestRequest( 'POST', array( 'showSearchBox' => false, 'hideTableHeading' => true, 'showTableFooter' => true, 'productsPerPage' => 25 ) ) );
check( $saved['productsPerPage'] === 25 && ! $saved['showSearchBox'], 'Settings save' );
foreach ( array( array( 'productsPerPage' => 0 ), array( 'showSearchBox' => 'false' ), array( 'addToCartLocation' => 'side' ) ) as $invalid ) {
	check( is_wp_error( $settings_route['callback']( new TestRequest( 'POST', $invalid ) ) ), 'Invalid settings accepted' );
}
check( get_option( 'prta_cart_settings' )['productsPerPage'] === 25, 'Invalid settings modified saved settings' );
$settings_route['callback']( new TestRequest( 'POST', array( 'enableCaching' => true ) ) );
PRTA\Includes\CartSettings::query_products( array( 'orderby' => 'date', 'limit' => 10 ), array() );
PRTA\Includes\CartSettings::query_products( array( 'orderby' => 'date', 'limit' => 10 ), array() );
check( $query_count === 1, 'Repeated query did not use cache' );
PRTA\Includes\CartSettings::query_products( array( 'orderby' => 'date', 'limit' => 10 ), array( 'different-filter' ) );
check( $query_count === 2, 'Filter context reused another cache entry' );
$settings_route['callback']( new TestRequest( 'POST', array( 'enableCaching' => false ) ) );

$post_type = new PRTA\Includes\PostType();
$post = (object) array( 'post_content' => json_encode( array( 'columns' => array( array( 'value' => 'name', 'label' => '<b>Name</b>' ) ), 'productsPerPage' => 10, 'queryProducts' => array( 2, 2 ) ) ), 'post_title' => '<b>Table</b>' );
$clean = $post_type->sanitize_table( $post, null );
check( ! is_wp_error( $clean ) && json_decode( $clean->post_content, true )['queryProducts'] === array( 2 ), 'Table save normalization' );
check( $clean->post_title === 'Table', 'Table title sanitation' );
foreach ( array( '{', '{"columns":[]}', '{"productsPerPage":0}', '{"queryProducts":["bad"]}', '{"sortBy":"unsafe"}' ) as $invalid ) {
	check( is_wp_error( $post_type->sanitize_table( (object) array( 'post_content' => $invalid ), null ) ), 'Invalid table accepted: ' . $invalid );
}

$shortcode = new PRTA\Includes\Shortcode();
check( strpos( $shortcode->register_shortcode_callback( array() ), 'ID is required' ) !== false, 'Missing table ID' );
check( strpos( $shortcode->register_shortcode_callback( array( 'id' => 99 ) ), 'not found' ) !== false, 'Missing table post' );
$posts[7] = (object) array( 'post_type' => 'adpro_option', 'post_status' => 'publish', 'post_content' => '{"columns":[],"productsPerPage":10}' );
$_GET = array( 'prta_7_limit' => '99999', 'prta_7_search' => 'ignored' );
$html = $shortcode->register_shortcode_callback( array( 'id' => 7 ) );
check( strpos( $html, 'No products found' ) !== false, 'Empty results message' );
check( strpos( $html, '<thead class="prta-sr-only">' ) !== false && strpos( $html, '<tfoot>' ) !== false, 'Heading and footer interaction' );
check( strpos( $html, 'prta-search-control' ) === false, 'Hidden search field' );
check( strpos( $html, 'colspan="3"' ) !== false, 'Legacy empty columns fallback' );
check( $last_query['limit'] === 1000 && $last_query['s'] === '', 'Query input bounds and hidden search' );
$document = new DOMDocument();
@$document->loadHTML( $html );
$xpath = new DOMXPath( $document );
foreach ( $xpath->query( '//input[not(@type="hidden")] | //select | //button' ) as $control ) {
	$has_name = trim( $control->getAttribute( 'aria-label' ) ) !== '' || trim( $control->getAttribute( 'title' ) ) !== '';
	if ( ! $has_name && $control->nodeName === 'button' ) $has_name = trim( $control->textContent ) !== '';
	if ( ! $has_name ) {
		$id = $control->getAttribute( 'id' );
		$has_name = $xpath->query( 'ancestor::label', $control )->length > 0 || ( $id && $xpath->query( '//label[@for="' . $id . '"]' )->length > 0 );
	}
	check( $has_name, 'Unnamed rendered control: ' . $control->nodeName );
}
$css = file_get_contents( __DIR__ . '/../assets/css/frontend.css' );
check( strpos( $css, '@container (max-width: 600px)' ) !== false && strpos( $css, 'overflow-x: auto' ) !== false, 'Mobile table overflow rules' );
check( strpos( $css, ':focus-visible { outline: 2px' ) !== false, 'Visible keyboard focus rule' );
$settings_route['callback']( new TestRequest( 'POST', array( 'showSearchBox' => true, 'hideTableHeading' => false, 'showTableFooter' => false ) ) );
$_GET = array();
$html = $shortcode->register_shortcode_callback( array( 'id' => 7 ) );
check( strpos( $html, 'prta-search-control' ) !== false && strpos( $html, '<thead><tr>' ) !== false && strpos( $html, 'class="prta-select-all"' ) !== false, 'Default search and heading controls' );
$document = new DOMDocument();
@$document->loadHTML( $html );
$xpath = new DOMXPath( $document );
check( $xpath->query( '//input[@type="search"][@id]' )->length === 1 && $xpath->query( '//label[@for="prta-7-1-search"]' )->length === 1, 'Search input label' );
echo "Smoke tests passed: settings, invalid input, table save, missing table, empty results, labels, responsive and focus rules, feature interactions.\n";

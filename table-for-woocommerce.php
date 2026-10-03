<?php
/**
 * Plugin Name: Product Table
 * Description: Product Table
 * Version:     1.0.0
 * Author:      JOJO
 * Author URI:  https://www.jojo.com/about
 * Text Domain: table-for-woocommerce
 * Requires at least: 6.2
 * Tested up to: 6.8
 * WC requires at least: 8.2
 * WC tested up to: 10.3
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * License:     GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WowAddons
 */

defined( 'ABSPATH' ) || exit;

// Define Variables
define( 'PRTA_VER', '1.0.0' );
define( 'PRTA_URL', plugin_dir_url( __FILE__ ) );
define( 'PRTA_BASE', plugin_basename( __FILE__ ) );
define( 'PRTA_PATH', plugin_dir_path( __FILE__ ) );

require_once PRTA_PATH . 'includes/class-init.php';
new PRTA\Includes\Init();

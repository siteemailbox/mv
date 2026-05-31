<?php
/**
 * Plugin Name: Multi Vendor Marketplace
 * Plugin URI: https://example.com/multi-vendor
 * Description: A complete multi-vendor solution for WooCommerce.
 * Version: 6.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mv-marketplace
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MV_VERSION', '6.0' );
define( 'MV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// بارگذاری کلاس‌ها
require_once MV_PLUGIN_DIR . 'includes/class-mv-vendor.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-product.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-commission.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-admin.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-frontend.php';

function mv_init() {
    new MV_Vendor();
    new MV_Product();
    new MV_Commission();
    new MV_Admin();
    new MV_Frontend();
}
add_action( 'plugins_loaded', 'mv_init' );

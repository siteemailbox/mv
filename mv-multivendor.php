<?php
/**
 * Plugin Name: چندفروشندگی MV
 * Plugin URI: https://github.com/siteemailbox/mv-multivendor
 * Description: افزونه چندفروشندگی سبک و سریع برای ووکامرس - مدیریت محصولات، داشبورد فروشنده، سیستم تیکت و پنل مدیریت.
 * Version: 5.0.0
 * Author: siteemailbox
 * Author URI: https://github.com/siteemailbox
 * Text Domain: mv-multivendor
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

define('MV_VERSION', '5.0.0');
define('MV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MV_PLUGIN_URL', plugin_dir_url(__FILE__));

// لود کردن کلاس‌ها قبل از هر کاری
require_once MV_PLUGIN_DIR . 'includes/class-mv-roles.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-products.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-vendor-dashboard.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-vendor-store.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-messages.php';
require_once MV_PLUGIN_DIR . 'includes/class-mv-admin.php';

class MV_Plugin {
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function activate() {
        MV_Roles::add_roles();
        MV_Roles::add_capabilities();
        MV_Messages::create_table();
        flush_rewrite_rules();
    }

    public function init() {
        load_plugin_textdomain('mv-multivendor', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        if (class_exists('WooCommerce')) {
            new MV_Products();
            new MV_Vendor_Dashboard();
            new MV_Vendor_Store();
            new MV_Messages();
            new MV_Admin();
        } else {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>افزونه چندفروشندگی MV نیاز به نصب و فعال‌سازی ووکامرس دارد.</p></div>';
            });
        }
    }
}

new MV_Plugin();
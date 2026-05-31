<?php
/**
 * MV_Admin Class
 * مدیریت بخش مدیریت
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MV_Admin {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }
    
    /**
     * اضافه کردن منو به بخش مدیریت
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Multi Vendor Marketplace', 'mv-marketplace' ),
            __( 'MV Marketplace', 'mv-marketplace' ),
            'manage_options',
            'mv-marketplace',
            array( $this, 'render_dashboard' ),
            'dashicons-store',
            56
        );
        
        add_submenu_page(
            'mv-marketplace',
            __( 'Dashboard', 'mv-marketplace' ),
            __( 'Dashboard', 'mv-marketplace' ),
            'manage_options',
            'mv-marketplace',
            array( $this, 'render_dashboard' )
        );
        
        add_submenu_page(
            'mv-marketplace',
            __( 'Settings', 'mv-marketplace' ),
            __( 'Settings', 'mv-marketplace' ),
            'manage_options',
            'mv-marketplace-settings',
            array( $this, 'render_settings' )
        );
    }
    
    /**
     * نمایش داشبورد
     */
    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Multi Vendor Marketplace Dashboard', 'mv-marketplace' ); ?></h1>
            
            <div class="mv-dashboard-stats">
                <div class="stat-box">
                    <h3><?php _e( 'Total Vendors', 'mv-marketplace' ); ?></h3>
                    <p><?php echo count( MV_Vendor::get_all_vendors() ); ?></p>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e( 'Total Products', 'mv-marketplace' ); ?></h3>
                    <p><?php echo wp_count_posts( 'product' )->publish; ?></p>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e( 'Total Orders', 'mv-marketplace' ); ?></h3>
                    <p><?php echo wp_count_posts( 'shop_order' )->publish; ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * نمایش تنظیمات
     */
    public function render_settings() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Marketplace Settings', 'mv-marketplace' ); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'mv_marketplace_settings' ); ?>
                <?php do_settings_sections( 'mv_marketplace_settings' ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * بارگذاری CSS و JavaScript مدیریت
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_style( 'mv-admin', MV_PLUGIN_URL . 'assets/css/admin.css' );
        wp_enqueue_script( 'mv-admin', MV_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), MV_VERSION );
    }
}

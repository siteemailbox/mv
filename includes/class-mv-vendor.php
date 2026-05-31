<?php
/**
 * MV_Vendor Class
 * مدیریت فروشندگان
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MV_Vendor {
    
    public function __construct() {
        add_action( 'init', array( $this, 'register_vendor_post_type' ) );
        add_action( 'init', array( $this, 'register_vendor_taxonomy' ) );
    }
    
    /**
     * ثبت نوع نوشته فروشنده
     */
    public function register_vendor_post_type() {
        $args = array(
            'label'               => __( 'Vendors', 'mv-marketplace' ),
            'description'         => __( 'Multi Vendor Marketplace', 'mv-marketplace' ),
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
            'public'              => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'rewrite'             => array( 'slug' => 'vendor' ),
        );
        
        register_post_type( 'mv_vendor', $args );
    }
    
    /**
     * ثبت تاکسونومی فروشنده
     */
    public function register_vendor_taxonomy() {
        $args = array(
            'label'       => __( 'Vendor Categories', 'mv-marketplace' ),
            'public'      => true,
            'show_in_rest' => true,
            'rewrite'     => array( 'slug' => 'vendor-category' ),
        );
        
        register_taxonomy( 'mv_vendor_category', array( 'mv_vendor' ), $args );
    }
    
    /**
     * دریافت تمام فروشندگان
     */
    public static function get_all_vendors() {
        $args = array(
            'post_type'      => 'mv_vendor',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );
        
        return get_posts( $args );
    }
    
    /**
     * دریافت فروشنده بر اساس ID
     */
    public static function get_vendor( $vendor_id ) {
        return get_post( $vendor_id );
    }
}

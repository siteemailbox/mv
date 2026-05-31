<?php
/**
 * MV_Product Class
 * مدیریت محصولات فروشندگان
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MV_Product {
    
    public function __construct() {
        add_action( 'init', array( $this, 'add_vendor_meta_box' ) );
        add_action( 'save_post_product', array( $this, 'save_vendor_meta' ) );
    }
    
    /**
     * اضافه کردن فیلد فروشنده به محصول
     */
    public function add_vendor_meta_box() {
        if ( function_exists( 'wc_get_product' ) ) {
            add_meta_box(
                'mv_product_vendor',
                __( 'Marketplace Vendor', 'mv-marketplace' ),
                array( $this, 'render_vendor_meta_box' ),
                'product',
                'normal',
                'high'
            );
        }
    }
    
    /**
     * نمایش meta box فروشنده
     */
    public function render_vendor_meta_box( $post ) {
        wp_nonce_field( 'mv_product_vendor_nonce', 'mv_product_vendor_nonce' );
        
        $vendor_id = get_post_meta( $post->ID, '_mv_vendor_id', true );
        $vendors = get_posts( array(
            'post_type' => 'mv_vendor',
            'posts_per_page' => -1,
        ) );
        
        echo '<select name="mv_vendor_id" style="width: 100%; padding: 8px;">';
        echo '<option value="">' . __( 'Select Vendor', 'mv-marketplace' ) . '</option>';
        
        foreach ( $vendors as $vendor ) {
            $selected = selected( $vendor_id, $vendor->ID, false );
            echo '<option value="' . esc_attr( $vendor->ID ) . '" ' . $selected . '>' . esc_html( $vendor->post_title ) . '</option>';
        }
        
        echo '</select>';
    }
    
    /**
     * ذخیره meta فروشنده
     */
    public function save_vendor_meta( $post_id ) {
        if ( ! isset( $_POST['mv_product_vendor_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['mv_product_vendor_nonce'], 'mv_product_vendor_nonce' ) ) {
            return;
        }
        
        if ( isset( $_POST['mv_vendor_id'] ) && ! empty( $_POST['mv_vendor_id'] ) ) {
            update_post_meta( $post_id, '_mv_vendor_id', sanitize_text_field( $_POST['mv_vendor_id'] ) );
        }
    }
    
    /**
     * دریافت فروشنده محصول
     */
    public static function get_product_vendor( $product_id ) {
        return get_post_meta( $product_id, '_mv_vendor_id', true );
    }
    
    /**
     * دریافت محصولات فروشنده
     */
    public static function get_vendor_products( $vendor_id ) {
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'meta_key'       => '_mv_vendor_id',
            'meta_value'     => $vendor_id,
        );
        
        return get_posts( $args );
    }
}

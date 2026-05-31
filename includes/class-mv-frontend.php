<?php
/**
 * MV_Frontend Class
 * مدیریت بخش فرانت‌اند
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MV_Frontend {
    
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_shortcode( 'mv_vendors', array( $this, 'vendors_shortcode' ) );
        add_shortcode( 'mv_vendor_products', array( $this, 'vendor_products_shortcode' ) );
    }
    
    /**
     * بارگذاری CSS و JavaScript فرانت‌اند
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style( 'mv-frontend', MV_PLUGIN_URL . 'assets/css/frontend.css' );
        wp_enqueue_script( 'mv-frontend', MV_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), MV_VERSION );
        
        wp_localize_script( 'mv-frontend', 'mvFrontend', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'mv_frontend_nonce' ),
        ) );
    }
    
    /**
     * Shortcode نمایش فروشندگان
     */
    public function vendors_shortcode( $atts ) {
        $vendors = MV_Vendor::get_all_vendors();
        
        if ( empty( $vendors ) ) {
            return '<p>' . __( 'No vendors found', 'mv-marketplace' ) . '</p>';
        }
        
        $output = '<div class="mv-vendors-container">';
        
        foreach ( $vendors as $vendor ) {
            $output .= '<div class="mv-vendor-card">';
            $output .= '<h3>' . esc_html( $vendor->post_title ) . '</h3>';
            $output .= '<p>' . wp_trim_words( $vendor->post_content, 20 ) . '</p>';
            $output .= '<a href="' . get_permalink( $vendor ) . '" class="mv-vendor-link">' . __( 'View Store', 'mv-marketplace' ) . '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode نمایش محصولات فروشنده
     */
    public function vendor_products_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'vendor_id' => 0,
            'posts_per_page' => 12,
        ), $atts );
        
        if ( empty( $atts['vendor_id'] ) ) {
            return '<p>' . __( 'Please specify vendor ID', 'mv-marketplace' ) . '</p>';
        }
        
        $products = MV_Product::get_vendor_products( $atts['vendor_id'] );
        
        if ( empty( $products ) ) {
            return '<p>' . __( 'No products found', 'mv-marketplace' ) . '</p>';
        }
        
        $output = '<div class="mv-products-container">';
        $count = 0;
        
        foreach ( $products as $product ) {
            if ( $count >= $atts['posts_per_page'] ) {
                break;
            }
            
            $product_obj = wc_get_product( $product->ID );
            $output .= '<div class="mv-product-card">';
            $output .= '<h4>' . esc_html( $product->post_title ) . '</h4>';
            $output .= '<p class="price">' . wp_kses_post( $product_obj->get_price_html() ) . '</p>';
            $output .= '<a href="' . get_permalink( $product ) . '" class="mv-product-link">' . __( 'View Product', 'mv-marketplace' ) . '</a>';
            $output .= '</div>';
            
            $count++;
        }
        
        $output .= '</div>';
        
        return $output;
    }
}

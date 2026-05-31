<?php
/**
 * MV_Commission Class
 * مدیریت کمیسیون فروشندگان
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MV_Commission {
    
    private $commission_rate = 10; // درصد کمیسیون پیش‌فرض
    
    public function __construct() {
        add_action( 'woocommerce_order_status_completed', array( $this, 'process_vendor_commission' ) );
        add_action( 'init', array( $this, 'register_commission_post_type' ) );
    }
    
    /**
     * ثبت نوع نوشته کمیسیون
     */
    public function register_commission_post_type() {
        $args = array(
            'label'           => __( 'Commissions', 'mv-marketplace' ),
            'description'     => __( 'Vendor Commissions', 'mv-marketplace' ),
            'public'          => false,
            'show_in_menu'    => 'woocommerce',
            'supports'        => array( 'title' ),
            'show_in_rest'    => true,
        );
        
        register_post_type( 'mv_commission', $args );
    }
    
    /**
     * محاسبه و ثبت کمیسیون
     */
    public function process_vendor_commission( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $vendor_id = MV_Product::get_product_vendor( $product_id );
            
            if ( ! $vendor_id ) {
                continue;
            }
            
            $commission = $this->calculate_commission( $item->get_total(), $vendor_id );
            $this->create_commission_record( $order_id, $vendor_id, $product_id, $commission );
        }
    }
    
    /**
     * محاسبه کمیسیون
     */
    private function calculate_commission( $amount, $vendor_id ) {
        $rate = apply_filters( 'mv_commission_rate', $this->commission_rate, $vendor_id );
        return ( $amount * $rate ) / 100;
    }
    
    /**
     * ایجاد ریکارد کمیسیون
     */
    private function create_commission_record( $order_id, $vendor_id, $product_id, $commission ) {
        $commission_post = array(
            'post_type'   => 'mv_commission',
            'post_title'  => sprintf( __( 'Commission for Order #%d', 'mv-marketplace' ), $order_id ),
            'post_status' => 'publish',
        );
        
        $commission_id = wp_insert_post( $commission_post );
        
        if ( $commission_id ) {
            update_post_meta( $commission_id, '_order_id', $order_id );
            update_post_meta( $commission_id, '_vendor_id', $vendor_id );
            update_post_meta( $commission_id, '_product_id', $product_id );
            update_post_meta( $commission_id, '_commission_amount', $commission );
            update_post_meta( $commission_id, '_commission_date', current_time( 'mysql' ) );
        }
    }
    
    /**
     * دریافت کمیسیون‌های فروشنده
     */
    public static function get_vendor_commissions( $vendor_id ) {
        $args = array(
            'post_type'      => 'mv_commission',
            'posts_per_page' => -1,
            'meta_key'       => '_vendor_id',
            'meta_value'     => $vendor_id,
        );
        
        return get_posts( $args );
    }
    
    /**
     * محاسبه کل درآمد فروشنده
     */
    public static function get_vendor_total_commission( $vendor_id ) {
        $commissions = self::get_vendor_commissions( $vendor_id );
        $total = 0;
        
        foreach ( $commissions as $commission ) {
            $amount = get_post_meta( $commission->ID, '_commission_amount', true );
            $total += floatval( $amount );
        }
        
        return $total;
    }
}

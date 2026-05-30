<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MV_Roles {

	public static function init() {
		add_filter( 'woocommerce_registration_errors', array( __CLASS__, 'validate_registration' ), 10, 3 );
		add_action( 'woocommerce_created_customer', array( __CLASS__, 'handle_registration_role' ), 10, 3 );
		add_action( 'admin_init', array( __CLASS__, 'add_vendor_caps' ) );
	}

	public static function create_vendor_role() {
		add_role(
			'mv_vendor',
			'فروشنده',
			array(
				'read'                    => true,
				'upload_files'            => true,
				'edit_products'           => true,
				'publish_products'        => true,
				'delete_products'         => true,
				'delete_published_products' => true,
				'edit_published_products' => true,
				'read_product'            => true,
				'assign_product_terms'    => true,
			)
		);

		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'manage_mv_multivendor' );
		}
	}

	public static function add_vendor_caps() {
		$role = get_role( 'mv_vendor' );
		if ( $role ) {
			$role->add_cap( 'edit_products' );
			$role->add_cap( 'publish_products' );
			$role->add_cap( 'delete_products' );
			$role->add_cap( 'delete_published_products' );
			$role->add_cap( 'edit_published_products' );
			$role->add_cap( 'assign_product_terms' );
		}
	}

	public static function validate_registration( $errors, $username, $email ) {
		if ( empty( $_POST['mv_account_type'] ) ) {
			return $errors;
		}

		return $errors;
	}

	public static function handle_registration_role( $customer_id, $new_customer_data, $password_generated ) {
		if ( empty( $_POST['mv_account_type'] ) ) {
			return;
		}

		$type = sanitize_text_field( wp_unslash( $_POST['mv_account_type'] ) );

		if ( 'vendor' === $type ) {
			$user = new WP_User( $customer_id );
			$user->set_role( 'mv_vendor' );
			update_user_meta( $customer_id, '_mv_account_type', 'vendor' );
			update_user_meta( $customer_id, '_mv_vendor_status', 'active' );
			update_user_meta( $customer_id, '_mv_store_slug', 'vendor-' . $customer_id );
		} else {
			update_user_meta( $customer_id, '_mv_account_type', 'customer' );
		}
	}

	public static function is_vendor( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		return $user && in_array( 'mv_vendor', $user->roles, true );
	}

	public static function get_vendor_id( $product_id ) {
		return (int) get_post_meta( $product_id, '_mv_vendor_id', true );
	}

	public static function can_manage_product( $product_id, $user_id ) {
		$vendor_id = self::get_vendor_id( $product_id );
		
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		return $vendor_id === $user_id && self::is_vendor( $user_id );
	}
}

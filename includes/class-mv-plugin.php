<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MV_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->hooks();
	}

	private function define_constants() {
		if ( ! defined( 'MV_PLUGIN_FILE' ) ) {
			define( 'MV_PLUGIN_FILE', MV_PATH . 'mv-multivendor.php' );
		}
	}

	private function includes() {
		require_once MV_PATH . 'includes/class-mv-roles.php';
		require_once MV_PATH . 'includes/class-mv-products.php';
		require_once MV_PATH . 'includes/class-mv-vendor-dashboard.php';
		require_once MV_PATH . 'includes/class-mv-vendor-store.php';
	}

	private function hooks() {
		add_action( 'init', array( 'MV_Roles', 'init' ) );
		add_action( 'init', array( 'MV_Products', 'init' ) );
		add_action( 'init', array( 'MV_Vendor_Dashboard', 'init' ) );
		add_action( 'init', array( 'MV_Vendor_Store', 'init' ) );
	}

	public static function activate() {
		MV_Roles::create_vendor_role();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}

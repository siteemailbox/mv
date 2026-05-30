<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس مدیریت داشبورد فروشنده در پنل کاربری ووکامرس
 */
final class MV_Vendor_Dashboard {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_items' ), 20 );
		add_filter( 'woocommerce_account_endpoint_title', array( __CLASS__, 'change_dashboard_title' ), 10, 2 );
		add_action( 'woocommerce_account_dashboard_endpoint', array( __CLASS__, 'render_dashboard_content' ) );
	}

	public static function enqueue_styles() {
		if ( MV_Roles::is_vendor( get_current_user_id() ) ) {
			wp_register_style( 'mv-dashboard', false );
			wp_enqueue_style( 'mv-dashboard' );
			wp_add_inline_style( 'mv-dashboard', self::get_dashboard_css() );
		}
	}

	private static function get_dashboard_css() {
		return '
			.mv-vendor-dashboard { max-width: 1200px; margin: 30px auto; }
			.mv-vendor-dashboard h2 { margin-bottom: 30px; color: #333; }
			.mv-dashboard-stats { display: flex; gap: 20px; margin-bottom: 40px; flex-wrap: wrap; }
			.mv-stat-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-width: 200px; flex: 1; }
			.mv-stat-card h3 { font-size: 36px; margin: 0 0 10px 0; color: #7f54b3; }
			.mv-stat-card p { margin: 0; color: #666; font-size: 14px; }
			.mv-add-product-form { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 40px; }
			.mv-add-product-form h3 { margin-top: 0; margin-bottom: 20px; color: #333; }
			.mv-add-product-form p { margin-bottom: 20px; }
			.mv-add-product-form label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
			.mv-add-product-form input[type="text"],
			.mv-add-product-form input[type="number"],
			.mv-add-product-form textarea,
			.mv-add-product-form select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
			.mv-add-product-form input[type="file"] { padding: 10px 0; }
			.mv-products-list { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
			.mv-products-list h3 { margin-top: 0; margin-bottom: 20px; color: #333; }
			.mv-products-list table { width: 100%; border-collapse: collapse; }
			.mv-products-list th { background: #f5f5f5; padding: 12px; text-align: right; font-weight: 600; color: #333; }
			.mv-products-list td { padding: 12px; border-bottom: 1px solid #eee; }
			.mv-products-list tr:hover { background: #f9f9f9; }
			.mv-products-list .button { margin-right: 5px; }
			.woocommerce-message { padding: 15px 20px; background: #7f54b3; color: #fff; border-radius: 4px; margin-bottom: 20px; }
			@media (max-width: 768px) {
				.mv-dashboard-stats { flex-direction: column; }
				.mv-products-list table { font-size: 13px; }
				.mv-products-list th, .mv-products-list td { padding: 8px; }
			}
		';
	}

	public static function add_menu_items( $items ) {
		if ( ! MV_Roles::is_vendor( get_current_user_id() ) ) {
			return $items;
		}

		$new_items = array();
		$inserted = false;

		foreach ( $items as $key => $label ) {
			if ( 'dashboard' === $key && ! $inserted ) {
				$new_items['dashboard'] = __('پیشخوان', 'mv');
				$new_items['my-products'] = __('محصولات من', 'mv');
				$new_items['store-page'] = __('صفحه فروشگاه', 'mv');
				$inserted = true;
			} else {
				$new_items[ $key ] = $label;
			}
		}

		if ( ! $inserted ) {
			$new_items['my-products'] = __('محصولات من', 'mv');
			$new_items['store-page'] = __('صفحه فروشگاه', 'mv');
		}

		return $new_items;
	}

	public static function change_dashboard_title( $title, $endpoint ) {
		if ( 'dashboard' === $endpoint && MV_Roles::is_vendor( get_current_user_id() ) ) {
			return __('داشبند فروشنده', 'mv');
		}
		return $title;
	}

	public static function render_dashboard_content() {
		$current_user = wp_get_current_user();
		$products = MV_Products::get_vendor_products( $current_user->ID );
		
		$total_sales = 0;
		$pending_products = count( MV_Products::get_vendor_products( $current_user->ID, 'pending' ) );
		$published_products = count( MV_Products::get_vendor_products( $current_user->ID, 'publish' ) );
		?>
		<div class="mv-vendor-dashboard">
			<h2><?php esc_html_e( 'داشبند فروشنده', 'mv' ); ?></h2>
			
			<?php if ( isset( $_GET['mv_message'] ) ): ?>
				<div class="woocommerce-message">
					<?php
					switch ( $_GET['mv_message'] ) {
						case 'created': esc_html_e( 'محصول با موفقیت ایجاد شد.', 'mv' ); break;
						case 'updated': esc_html_e( 'محصول با موفقیت به‌روزرسانی شد.', 'mv' ); break;
						case 'deleted': esc_html_e( 'محصول با موفقیت حذف شد.', 'mv' ); break;
					}
					?>
				</div>
			<?php endif; ?>

			<div class="mv-dashboard-stats">
				<div class="mv-stat-card">
					<h3><?php echo count( $products ); ?></h3>
					<p><?php esc_html_e( 'کل محصولات', 'mv' ); ?></p>
				</div>
				<div class="mv-stat-card">
					<h3><?php echo $published_products; ?></h3>
					<p><?php esc_html_e( 'محصولات منتشر شده', 'mv' ); ?></p>
				</div>
				<div class="mv-stat-card">
					<h3><?php echo $pending_products; ?></h3>
					<p><?php esc_html_e( 'در انتظار بررسی', 'mv' ); ?></p>
				</div>
			</div>

			<div class="mv-quick-actions">
				<h3><?php esc_html_e( 'اقدامات سریع', 'mv' ); ?></h3>
				<p>
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'my-products' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'مدیریت محصولات', 'mv' ); ?>
					</a>
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'store-page' ) ); ?>" class="button">
						<?php esc_html_e( 'مشاهده فروشگاه', 'mv' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}

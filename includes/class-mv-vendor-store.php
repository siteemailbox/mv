<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس مدیریت صفحه فروشگاه اختصاصی هر فروشنده
 * این کلاس یک صفحه عمومی برای نمایش محصولات هر فروشنده ایجاد می‌کند
 */
final class MV_Vendor_Store {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_store_endpoint' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_store_page' ) );
		add_shortcode( 'mv_vendor_store', array( __CLASS__, 'render_store_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	public static function register_store_endpoint() {
		add_rewrite_rule( '^vendor/([^/]+)/?$', 'index.php?vendor_slug=$matches[1]', 'top' );
	}

	public static function add_query_vars( $vars ) {
		$vars[] = 'vendor_slug';
		return $vars;
	}

	public static function handle_store_page() {
		$vendor_slug = get_query_var( 'vendor_slug' );
		
		if ( ! empty( $vendor_slug ) ) {
			add_filter( 'template_include', array( __CLASS__, 'load_store_template' ) );
		}
	}

	public static function load_store_template( $template ) {
		$vendor_slug = get_query_var( 'vendor_slug' );
		
		if ( empty( $vendor_slug ) ) {
			return $template;
		}

		$vendor_id = self::get_vendor_id_by_slug( $vendor_slug );
		
		if ( ! $vendor_id ) {
			return $template;
		}

		$custom_template = get_stylesheet_directory() . '/mv-vendor-store.php';
		if ( file_exists( $custom_template ) ) {
			return $custom_template;
		}

		return MV_PATH . 'templates/vendor-store.php';
	}

	public static function get_vendor_id_by_slug( $slug ) {
		$users = get_users( array(
			'meta_key'     => '_mv_store_slug',
			'meta_value'   => $slug,
			'meta_compare' => '=',
			'fields'       => 'ID',
			'number'       => 1,
		) );

		if ( ! empty( $users ) ) {
			return $users[0];
		}

		preg_match( '/vendor-(\d+)/', $slug, $matches );
		if ( isset( $matches[1] ) ) {
			$user_id = (int) $matches[1];
			$user = get_user_by( 'ID', $user_id );
			if ( $user && MV_Roles::is_vendor( $user_id ) ) {
				return $user_id;
			}
		}

		return false;
	}

	public static function get_vendor_info( $vendor_id ) {
		$user = get_user_by( 'ID', $vendor_id );
		
		if ( ! $user ) {
			return false;
		}

		return array(
			'id'           => $vendor_id,
			'name'         => $user->display_name,
			'email'        => $user->user_email,
			'registered'   => $user->user_registered,
			'store_slug'   => get_user_meta( $vendor_id, '_mv_store_slug', true ),
			'total_products' => count( MV_Products::get_vendor_products( $vendor_id, 'publish' ) ),
		);
	}

	public static function get_vendor_products_public( $vendor_id ) {
		return MV_Products::get_vendor_products( $vendor_id, 'publish' );
	}

	public static function render_store_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'vendor_id' => 0,
			'limit'     => -1,
		), $atts );

		$vendor_id = $atts['vendor_id'] > 0 ? $atts['vendor_id'] : get_current_user_id();
		
		if ( ! MV_Roles::is_vendor( $vendor_id ) ) {
			return '<p>' . esc_html__( 'فروشنده معتبر یافت نشد.', 'mv' ) . '</p>';
		}

		ob_start();
		self::render_store_template( $vendor_id, $atts['limit'] );
		return ob_get_clean();
	}

	public static function render_store_template( $vendor_id, $limit = -1 ) {
		$vendor_info = self::get_vendor_info( $vendor_id );
		$products = self::get_vendor_products_public( $vendor_id );
		
		if ( $limit > 0 ) {
			$products = array_slice( $products, 0, $limit );
		}
		?>
		<div class="mv-vendor-store">
			<div class="mv-store-header">
				<h2><?php echo esc_html( $vendor_info['name'] ); ?></h2>
				<p class="mv-store-description">
					<?php echo esc_html( sprintf( _n( '%d محصول', '%d محصول', $vendor_info['total_products'], 'mv' ), $vendor_info['total_products'] ) ); ?>
				</p>
			</div>
			
			<?php if ( ! empty( $products ) ): ?>
				<div class="mv-store-products">
					<?php foreach ( $products as $product_item ): 
						$wc_product = wc_get_product( $product_item->ID );
						if ( ! $wc_product ) continue;
					?>
						<div class="mv-product-card">
							<?php if ( has_post_thumbnail( $product_item->ID ) ): ?>
								<div class="mv-product-image">
									<a href="<?php echo get_permalink( $product_item->ID ); ?>">
										<?php echo get_the_post_thumbnail( $product_item->ID, 'woocommerce_thumbnail' ); ?>
									</a>
								</div>
							<?php endif; ?>
							
							<div class="mv-product-info">
								<h3 class="mv-product-title">
									<a href="<?php echo get_permalink( $product_item->ID ); ?>">
										<?php echo esc_html( $product_item->post_title ); ?>
									</a>
								</h3>
								
								<div class="mv-product-price">
									<?php echo $wc_product->get_price_html(); ?>
								</div>
								
								<?php if ( $wc_product->is_purchasable() && $wc_product->is_in_stock() ): ?>
									<?php woocommerce_template_loop_add_to_cart(); ?>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<p class="mv-no-products"><?php esc_html_e( 'هیچ محصولی در این فروشگاه یافت نشد.', 'mv' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function enqueue_styles() {
		wp_register_style( 'mv-store', false );
		wp_enqueue_style( 'mv-store' );
		wp_add_inline_style( 'mv-store', self::get_store_css() );
	}

	private static function get_store_css() {
		return '
			.mv-vendor-store { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
			.mv-store-header { text-align: center; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 2px solid #eee; }
			.mv-store-header h2 { font-size: 32px; color: #333; margin-bottom: 10px; }
			.mv-store-description { color: #666; font-size: 16px; }
			.mv-store-products { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
			.mv-product-card { background: #fff; border: 1px solid #eee; border-radius: 8px; overflow: hidden; transition: box-shadow 0.3s ease; }
			.mv-product-card:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
			.mv-product-image { height: 250px; overflow: hidden; background: #f9f9f9; display: flex; align-items: center; justify-content: center; }
			.mv-product-image img { max-width: 100%; height: auto; transition: transform 0.3s ease; }
			.mv-product-card:hover .mv-product-image img { transform: scale(1.05); }
			.mv-product-info { padding: 20px; }
			.mv-product-title { font-size: 16px; margin: 0 0 10px 0; }
			.mv-product-title a { color: #333; text-decoration: none; }
			.mv-product-title a:hover { color: #7f54b3; }
			.mv-product-price { margin-bottom: 15px; color: #7f54b3; font-weight: 600; }
			.mv-no-products { text-align: center; padding: 40px; color: #666; font-size: 18px; }
			@media (max-width: 768px) {
				.mv-store-products { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
				.mv-store-header h2 { font-size: 24px; }
			}
		';
	}

	public static function get_store_url( $vendor_id ) {
		$store_slug = get_user_meta( $vendor_id, '_mv_store_slug', true );
		
		if ( empty( $store_slug ) ) {
			$store_slug = 'vendor-' . $vendor_id;
		}

		return home_url( '/vendor/' . $store_slug . '/' );
	}
}

<?php
/**
 * قالب صفحه فروشگاه فروشنده
 * این فایل به صورت پیش‌فرض استفاده می‌شود مگر اینکه قالب سفارشی در پوشه تم وجود داشته باشد
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vendor_slug = get_query_var( 'vendor_slug' );
$vendor_id = MV_Vendor_Store::get_vendor_id_by_slug( $vendor_slug );

if ( ! $vendor_id ) {
	get_header();
	echo '<div style="max-width:800px;margin:100px auto;text-align:center;">';
	echo '<h1>' . esc_html__( 'فروشنده یافت نشد', 'mv' ) . '</h1>';
	echo '<p>' . esc_html__( 'فروشگاهی با این مشخصات پیدا نشد.', 'mv' ) . '</p>';
	echo '<a href="' . home_url() . '" class="button">' . esc_html__( 'بازگشت به صفحه اصلی', 'mv' ) . '</a>';
	echo '</div>';
	get_footer();
	exit;
}

$vendor_info = MV_Vendor_Store::get_vendor_info( $vendor_id );
$products = MV_Vendor_Store::get_vendor_products_public( $vendor_id );

get_header();
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
get_footer();

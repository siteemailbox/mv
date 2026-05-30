<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MV_Products {

	public static function init() {
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_vendor_field' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_vendor_field' ), 10, 1 );
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( __CLASS__, 'filter_products_by_vendor' ), 10, 2 );
		add_filter( 'wp_count_posts', array( __CLASS__, 'filter_product_counts' ), 10, 3 );
		add_action( 'init', array( __CLASS__, 'handle_product_submission' ) );
		add_action( 'wp', array( __CLASS__, 'check_product_permissions' ) );
		add_action( 'woocommerce_register_form_start', array( __CLASS__, 'render_registration_fields' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_dashboard_menu_item' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_dashboard_endpoint' ) );
	}

	public static function add_vendor_field() {
		$current_user = wp_get_current_user();

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="options_group">';
		woocommerce_wp_text_input(
			array(
				'id'          => '_mv_vendor_id',
				'label'       => 'شناسه فروشنده',
				'description' => 'شناسه کاربری فروشنده این محصول را وارد کنید.',
				'type'        => 'number',
				'desc_tip'    => true,
			)
		);
		echo '</div>';
	}

	public static function save_vendor_field( $product_id ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) && isset( $_POST['_mv_vendor_id'] ) ) {
			$vendor_id = absint( $_POST['_mv_vendor_id'] );
			update_post_meta( $product_id, '_mv_vendor_id', $vendor_id );
		} elseif ( MV_Roles::is_vendor( get_current_user_id() ) ) {
			$current_user_id = get_current_user_id();
			$existing_vendor_id = (int) get_post_meta( $product_id, '_mv_vendor_id', true );
			
			if ( $existing_vendor_id === $current_user_id || $existing_vendor_id === 0 ) {
				update_post_meta( $product_id, '_mv_vendor_id', $current_user_id );
			}
		}
	}

	public static function filter_products_by_vendor( $query_args, $query_vars ) {
		if ( is_admin() && ! current_user_can( 'manage_options' ) && MV_Roles::is_vendor( get_current_user_id() ) ) {
			$query_args['meta_query'] = isset( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();
			$query_args['meta_query'][] = array(
				'key'   => '_mv_vendor_id',
				'value' => get_current_user_id(),
			);
		}

		return $query_args;
	}

	public static function filter_product_counts( $counts, $type, $perm ) {
		if ( 'product' !== $type || ! is_admin() || current_user_can( 'manage_options' ) || ! MV_Roles::is_vendor( get_current_user_id() ) ) {
			return $counts;
		}

		global $wpdb;
		$user_id = get_current_user_id();

		$cache_key = 'mv_product_counts_' . $user_id;
		$cached = wp_cache_get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_status, COUNT(*) as num 
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'product'
				AND pm.meta_key = '_mv_vendor_id'
				AND pm.meta_value = %d
				GROUP BY post_status",
				$user_id
			),
			ARRAY_A
		);

		$filtered_counts = (object) array(
			'publish'     => 0,
			'draft'       => 0,
			'pending'     => 0,
			'private'     => 0,
			'trash'       => 0,
			'future'      => 0,
			'inherit'     => 0,
			'auto-draft'  => 0,
		);

		foreach ( $results as $row ) {
			if ( isset( $filtered_counts->{$row['post_status']} ) ) {
				$filtered_counts->{$row['post_status']} = (int) $row['num'];
			}
		}

		wp_cache_set( $cache_key, $filtered_counts, 3600 );

		return $filtered_counts;
	}

	public static function handle_product_submission() {
		if ( ! isset( $_POST['mv_submit_product_nonce'] ) || ! wp_verify_nonce( $_POST['mv_submit_product_nonce'], 'mv_submit_product' ) ) {
			return;
		}

		if ( ! MV_Roles::is_vendor( get_current_user_id() ) ) {
			return;
		}

		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		
		if ( 'mv_create_product' === $action || 'mv_edit_product' === $action ) {
			self::process_product_form();
		} elseif ( 'mv_delete_product' === $action ) {
			self::process_delete_product();
		}
	}

	private static function process_product_form() {
		check_admin_referer( 'mv_submit_product', 'mv_submit_product_nonce' );

		$is_edit = isset( $_POST['action'] ) && 'mv_edit_product' === $_POST['action'];
		$product_id = $is_edit ? absint( $_POST['product_id'] ) : 0;

		if ( $is_edit && ! MV_Roles::can_manage_product( $product_id, get_current_user_id() ) ) {
			wp_die( 'شما اجازه ویرایش این محصول را ندارید.' );
		}

		$title   = sanitize_text_field( wp_unslash( $_POST['product_title'] ) );
		$desc    = wp_kses_post( wp_unslash( $_POST['product_description'] ) );
		$price   = isset( $_POST['product_price'] ) ? wc_format_decimal( $_POST['product_price'] ) : '';
		$type    = isset( $_POST['product_type'] ) ? sanitize_text_field( wp_unslash( $_POST['product_type'] ) ) : 'simple';
		$status  = isset( $_POST['product_status'] ) ? sanitize_text_field( wp_unslash( $_POST['product_status'] ) ) : 'pending';

		if ( empty( $title ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="error"><p>عنوان محصول الزامی است.</p></div>';
			});
			return;
		}

		$post_data = array(
			'post_title'   => $title,
			'post_content' => $desc,
			'post_status'  => current_user_can( 'publish_products' ) ? $status : 'pending',
			'post_type'    => 'product',
		);

		if ( $is_edit ) {
			$post_data['ID'] = $product_id;
			$product_id = wp_update_post( $post_data );
		} else {
			$product_id = wp_insert_post( $post_data );
		}

		if ( is_wp_error( $product_id ) || ! $product_id ) {
			add_action( 'admin_notices', function() {
				echo '<div class="error"><p>خطا در ذخیره محصول.</p></div>';
			});
			return;
		}

		update_post_meta( $product_id, '_mv_vendor_id', get_current_user_id() );
		update_post_meta( $product_id, '_regular_price', $price );
		update_post_meta( $product_id, '_price', $price );
		update_post_meta( $product_id, '_sold_individually', 'yes' );

		if ( isset( $_FILES['product_image'] ) && ! empty( $_FILES['product_image']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			
			$attachment_id = media_handle_upload( 'product_image', 0 );
			if ( ! is_wp_error( $attachment_id ) ) {
				set_post_thumbnail( $product_id, $attachment_id );
			}
		}

		do_action( 'mv_product_saved', $product_id, $is_edit );

		wp_safe_redirect( add_query_arg( 'mv_message', $is_edit ? 'updated' : 'created', wp_get_referer() ) );
		exit;
	}

	private static function process_delete_product() {
		check_admin_referer( 'mv_submit_product', 'mv_submit_product_nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

		if ( ! $product_id || ! MV_Roles::can_manage_product( $product_id, get_current_user_id() ) ) {
			wp_die( 'شما اجازه حذف این محصول را ندارید.' );
		}

		wp_trash_post( $product_id );

		wp_safe_redirect( add_query_arg( 'mv_message', 'deleted', wp_get_referer() ) );
		exit;
	}

	public static function check_product_permissions() {
		if ( is_admin() && isset( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			$product_id = absint( $_GET['post'] );
			if ( 'product' === get_post_type( $product_id ) && ! MV_Roles::can_manage_product( $product_id, get_current_user_id() ) ) {
				wp_die( 'شما اجازه ویرایش این محصول را ندارید.', 'دسترسی غیرمجاز', array( 'response' => 403 ) );
			}
		}
	}

	public static function render_registration_fields() {
		?>
		<p class="form-row form-row-wide">
			<label for="mv_account_type"><?php esc_html_e( 'نوع حساب', 'mv' ); ?> <span class="required">*</span></label>
			<select name="mv_account_type" id="mv_account_type" required>
				<option value="customer"><?php esc_html_e( 'مشتری', 'mv' ); ?></option>
				<option value="vendor"><?php esc_html_e( 'فروشنده', 'mv' ); ?></option>
			</select>
		</p>
		<?php
	}

	public static function add_dashboard_menu_item( $items ) {
		if ( MV_Roles::is_vendor( get_current_user_id() ) ) {
			$new_items = array();
			$new_items['dashboard'] = 'داشبند فروشنده';
			foreach ( $items as $key => $label ) {
				$new_items[ $key ] = $label;
			}
			return $new_items;
		}
		return $items;
	}

	public static function add_query_vars( $vars ) {
		$vars[] = 'dashboard';
		return $vars;
	}

	public static function handle_dashboard_endpoint() {
		if ( ! MV_Roles::is_vendor( get_current_user_id() ) ) {
			return;
		}

		$endpoint = get_query_var( 'dashboard' );
		
		if ( '' !== $endpoint ) {
			add_action( 'woocommerce_account_dashboard_endpoint', array( __CLASS__, 'render_dashboard_page' ) );
		}
	}

	public static function render_dashboard_page() {
		$current_user = wp_get_current_user();
		$products = self::get_vendor_products( $current_user->ID );
		?>
		<div class="mv-vendor-dashboard">
			<h2>داشبند فروشنده</h2>
			
			<?php if ( isset( $_GET['mv_message'] ) ): ?>
				<div class="woocommerce-message">
					<?php
					switch ( $_GET['mv_message'] ) {
						case 'created': echo 'محصول با موفقیت ایجاد شد.'; break;
						case 'updated': echo 'محصول با موفقیت به‌روزرسانی شد.'; break;
						case 'deleted': echo 'محصول با موفقیت حذف شد.'; break;
					}
					?>
				</div>
			<?php endif; ?>

			<div class="mv-dashboard-stats">
				<div class="mv-stat-card">
					<h3><?php echo count( $products ); ?></h3>
					<p>تعداد محصولات</p>
				</div>
			</div>

			<div class="mv-add-product-form">
				<h3>افزودن محصول جدید</h3>
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'mv_submit_product', 'mv_submit_product_nonce' ); ?>
					<input type="hidden" name="action" value="mv_create_product">
					
					<p>
						<label>عنوان محصول *</label><br>
						<input type="text" name="product_title" required style="width:100%">
					</p>
					
					<p>
						<label>توضیحات</label><br>
						<textarea name="product_description" rows="5" style="width:100%"></textarea>
					</p>
					
					<p>
						<label>قیمت</label><br>
						<input type="number" step="0.01" name="product_price" style="width:200px">
					</p>
					
					<p>
						<label>تصویر محصول</label><br>
						<input type="file" name="product_image" accept="image/*">
					</p>
					
					<p>
						<label>وضعیت</label><br>
						<select name="product_status">
							<option value="pending">در انتظار بررسی</option>
							<option value="publish" <?php echo current_user_can( 'publish_products' ) ? '' : 'disabled'; ?>>منتشر شده</option>
						</select>
					</p>
					
					<p>
						<button type="submit" class="button button-primary">ذخیره محصول</button>
					</p>
				</form>
			</div>

			<div class="mv-products-list">
				<h3>محصولات من</h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>عنوان</th>
							<th>قیمت</th>
							<th>وضعیت</th>
							<th>عملیات</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $products ) ): ?>
							<tr>
								<td colspan="4">هیچ محصولی یافت نشد.</td>
							</tr>
						<?php else: ?>
							<?php foreach ( $products as $product ): 
								$wc_product = wc_get_product( $product->ID );
								$price = $wc_product ? $wc_product->get_price() : '0';
							?>
								<tr>
									<td><?php echo esc_html( $product->post_title ); ?></td>
									<td><?php echo wc_price( $price ); ?></td>
									<td><?php echo esc_html( get_post_status_object( $product->post_status )->label ); ?></td>
									<td>
										<a href="<?php echo admin_url( 'post.php?post=' . $product->ID . '&action=edit' ); ?>" class="button button-small">ویرایش</a>
										<form method="post" style="display:inline;" onsubmit="return confirm('آیا از حذف این محصول مطمئن هستید؟');">
											<?php wp_nonce_field( 'mv_submit_product', 'mv_submit_product_nonce' ); ?>
											<input type="hidden" name="action" value="mv_delete_product">
											<input type="hidden" name="product_id" value="<?php echo $product->ID; ?>">
											<button type="submit" class="button button-small button-link-delete">حذف</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	public static function get_vendor_products( $vendor_id, $status = 'any' ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => $status,
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_mv_vendor_id',
					'value' => $vendor_id,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return get_posts( $args );
	}
}

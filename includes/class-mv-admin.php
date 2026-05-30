<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MV_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_vendor_status' ) );
        // اضافه کردن ستون‌های اختصاصی به لیست کاربران
        add_filter( 'manage_users_columns', array( $this, 'add_vendor_columns' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'render_vendor_columns' ), 10, 3 );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'فروشگاه‌ها', 'mv-marketplace' ),
            __( 'فروشگاه‌ها', 'mv-marketplace' ),
            'manage_options',
            'mv-vendors',
            array( $this, 'render_vendors_list' ),
            'dashicons-store',
            56
        );
    }

    public function render_vendors_list() {
        // بررسی دسترسی
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // هندل کردن اکشن‌های فعال/غیرفعال
        if ( isset( $_GET['mv_action'] ) && isset( $_GET['vendor_id'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'mv_vendor_action' ) ) {
            $vendor_id = intval( $_GET['vendor_id'] );
            $action = sanitize_text_field( $_GET['mv_action'] );
            
            if ( $action === 'activate' ) {
                update_user_meta( $vendor_id, '_mv_vendor_status', 'active' );
            } elseif ( $action === 'deactivate' ) {
                update_user_meta( $vendor_id, '_mv_vendor_status', 'inactive' );
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>وضعیت فروشنده با موفقیت تغییر کرد.</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'مدیریت فروشگاه‌ها', 'mv-marketplace' ); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'نام فروشنده', 'mv-marketplace' ); ?></th>
                        <th><?php echo esc_html__( 'نام فروشگاه', 'mv-marketplace' ); ?></th>
                        <th><?php echo esc_html__( 'ایمیل', 'mv-marketplace' ); ?></th>
                        <th><?php echo esc_html__( 'وضعیت', 'mv-marketplace' ); ?></th>
                        <th><?php echo esc_html__( 'تاریخ ثبت‌نام', 'mv-marketplace' ); ?></th>
                        <th><?php echo esc_html__( 'عملیات', 'mv-marketplace' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // اصلاح مهم: تغییر نقش از 'vendor' به 'mv_vendor'
                    $args = array(
                        'role' => 'mv_vendor', 
                        'orderby' => 'registered',
                        'order' => 'DESC'
                    );
                    $vendors = get_users( $args );

                    if ( empty( $vendors ) ) {
                        echo '<tr><td colspan="6">' . esc_html__( 'هیچ فروشنده‌ای یافت نشد.', 'mv-marketplace' ) . '</td></tr>';
                    } else {
                        foreach ( $vendors as $vendor ) {
                            $shop_name = get_user_meta( $vendor->ID, '_mv_shop_name', true );
                            $status = get_user_meta( $vendor->ID, '_mv_vendor_status', true );
                            $status_label = ( $status === 'active' ) ? '<span style="color:green;">فعال</span>' : '<span style="color:red;">غیرفعال</span>';
                            
                            $nonce = wp_create_nonce( 'mv_vendor_action' );
                            $action_link = ( $status === 'active' ) 
                                ? "admin.php?page=mv-vendors&mv_action=deactivate&vendor_id={$vendor->ID}&_wpnonce={$nonce}"
                                : "admin.php?page=mv-vendors&mv_action=activate&vendor_id={$vendor->ID}&_wpnonce={$nonce}";
                            
                            $action_text = ( $status === 'active' ) ? 'غیرفعال کردن' : 'فعال کردن';

                            echo '<tr>';
                            echo '<td>' . esc_html( $vendor->display_name ) . '</td>';
                            echo '<td>' . esc_html( $shop_name ? $shop_name : '-' ) . '</td>';
                            echo '<td>' . esc_html( $vendor->user_email ) . '</td>';
                            echo '<td>' . $status_label . '</td>';
                            echo '<td>' . date_i18n( 'Y/m/d', strtotime( $vendor->user_registered ) ) . '</td>';
                            echo '<td><a href="' . esc_url( $action_link ) . '" class="button button-small">' . esc_html( $action_text ) . '</a></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function add_vendor_columns( $columns ) {
        $columns['mv_shop_name'] = __( 'نام فروشگاه', 'mv-marketplace' );
        $columns['mv_vendor_status'] = __( 'وضعیت فروشنده', 'mv-marketplace' );
        return $columns;
    }

    public function render_vendor_columns( $value, $column_name, $user_id ) {
        // فقط برای نقش mv_vendor نمایش داده شود
        $user = get_userdata( $user_id );
        if ( ! in_array( 'mv_vendor', (array) $user->roles ) ) {
            return $value;
        }

        if ( 'mv_shop_name' === $column_name ) {
            return get_user_meta( $user_id, '_mv_shop_name', true );
        }
        if ( 'mv_vendor_status' === $column_name ) {
            $status = get_user_meta( $user_id, '_mv_vendor_status', true );
            return ( $status === 'active' ) ? 'فعال' : 'غیرفعال';
        }
        return $value;
    }
    
    public function handle_vendor_status() {
        // منطق اضافی اگر نیاز باشد
    }
}

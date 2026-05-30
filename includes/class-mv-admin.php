<?php
if (!defined('ABSPATH')) exit;

class MV_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_mv_toggle_vendor_status', array($this, 'toggle_vendor_status'));
        add_action('admin_post_mv_reply_ticket', array($this, 'reply_ticket'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'چند فروشندگی',
            'چند فروشندگی',
            'manage_woocommerce',
            'mv-multivendor',
            array($this, 'dashboard_page'),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'mv-multivendor',
            'مدیریت فروشندگان',
            'فروشندگان',
            'manage_woocommerce',
            'mv-vendors',
            array($this, 'vendors_page')
        );

        add_submenu_page(
            'mv-multivendor',
            'تیکت‌های پشتیبانی',
            'تیکت‌ها',
            'manage_woocommerce',
            'mv-tickets',
            array($this, 'tickets_page')
        );
    }

    public function dashboard_page() {
        echo '<div class="wrap"><h1>داشبورد چندفروشندگی</h1>';
        echo '<p>به پنل مدیریت افزونه چندفروشندگی خوش آمدید.</p>';
        
        global $wpdb;
        $vendors_count = count_users()['total_users']; // ساده‌سازی شده
        $tickets_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mv_messages WHERE type = 'ticket'");
        
        echo '<div style="display:flex; gap:20px; margin-top:20px;">';
        echo '<div style="background:#fff; padding:20px; border-left:4px solid #0073aa; flex:1;"><h3>فروشندگان</h3><p>مدیریت وضعیت فروشندگان</p><a href="?page=mv-vendors" class="button">مشاهده</a></div>';
        echo '<div style="background:#fff; padding:20px; border-left:4px solid #d63638; flex:1;"><h3>تیکت‌ها</h3><p>' . $tickets_count . ' پیام جدید</p><a href="?page=mv-tickets" class="button">مشاهده</a></div>';
        echo '</div></div>';
    }

    public function vendors_page() {
        echo '<div class="wrap"><h1>مدیریت فروشندگان</h1>';
        
        $users = get_users(array('role' => 'vendor'));
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>نام</th><th>نام کاربری</th><th>محصولات</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>';
        
        foreach ($users as $user) {
            $status = get_user_meta($user->ID, '_mv_account_status', true) ?: 'active';
            $products_count = count_user_posts($user->ID, 'product');
            $status_label = $status == 'active' ? '<span style="color:green">فعال</span>' : '<span style="color:red">غیرفعال</span>';
            $action_label = $status == 'active' ? 'غیرفعال‌سازی' : 'فعال‌سازی';
            $next_status = $status == 'active' ? 'inactive' : 'active';
            
            echo "<tr>";
            echo "<td>{$user->display_name}</td>";
            echo "<td>{$user->user_login}</td>";
            echo "<td>{$products_count}</td>";
            echo "<td>{$status_label}</td>";
            echo "<td><a href='" . wp_nonce_url(admin_url('admin-post.php?action=mv_toggle_vendor_status&user_id=' . $user->ID . '&status=' . $next_status), 'mv_toggle_vendor') . "' class='button button-small'>{$action_label}</a></td>";
            echo "</tr>";
        }
        
        echo '</tbody></table></div>';
    }

    public function toggle_vendor_status() {
        if (!current_user_can('manage_woocommerce')) wp_die('دسترسی غیرمجاز');
        
        $user_id = intval($_GET['user_id']);
        $status = sanitize_text_field($_GET['status']);
        
        check_admin_referer('mv_toggle_vendor');
        
        update_user_meta($user_id, '_mv_account_status', $status);
        wp_redirect(admin_url('admin.php?page=mv-vendors&updated=1'));
        exit;
    }

    public function tickets_page() {
        echo '<div class="wrap"><h1>تیکت‌های پشتیبانی</h1>';
        global $wpdb;
        $table_name = $wpdb->prefix . 'mv_messages';
        $tickets = $wpdb->get_results("SELECT * FROM $table_name WHERE type = 'ticket' ORDER BY created_at DESC");
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>فرستنده</th><th>موضوع (محصول)</th><th>پیام</th><th>تاریخ</th><th>پاسخ</th></tr></thead><tbody>';
        
        foreach ($tickets as $ticket) {
            $user = get_userdata($ticket->sender_id);
            $product = get_post($ticket->product_id);
            $name = $user ? $user->display_name : 'ناشناس';
            $prod_name = $product ? $product->post_title : 'حذف شده';
            
            echo "<tr>";
            echo "<td>{$name}</td>";
            echo "<td>{$prod_name}</td>";
            echo "<td>" . esc_html($ticket->message) . "</td>";
            echo "<td>" . date_i18n('Y/m/d H:i', strtotime($ticket->created_at)) . "</td>";
            echo "<td>";
            if (!$ticket->is_read) {
                echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
                echo '<input type="hidden" name="action" value="mv_reply_ticket">';
                echo '<input type="hidden" name="ticket_id" value="' . $ticket->id . '">';
                echo '<textarea name="reply" placeholder="پاسخ شما..." style="width:100%"></textarea>';
                echo '<button type="submit" class="button button-small" style="margin-top:5px">ارسال پاسخ</button>';
                echo '</form>';
            } else {
                echo '<span style="color:#aaa">پاسخ داده شده</span>';
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo '</tbody></table></div>';
    }

    public function reply_ticket() {
        if (!current_user_can('manage_woocommerce')) wp_die('دسترسی غیرمجاز');
        
        $ticket_id = intval($_POST['ticket_id']);
        $reply = sanitize_textarea_field($_POST['reply']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mv_messages';
        
        $wpdb->update($table_name, 
            ['message' => $reply, 'is_read' => 1], 
            ['id' => $ticket_id]
        );
        
        wp_redirect(admin_url('admin.php?page=mv-tickets&replied=1'));
        exit;
    }
}

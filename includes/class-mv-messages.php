<?php
if (!defined('ABSPATH')) exit;

class MV_Messages {
    public function __construct() {
        self::create_table();
        add_action('wp_ajax_mv_send_message', array($this, 'handle_message'));
        add_action('wp_ajax_nopriv_mv_send_message', array($this, 'handle_message'));
        add_shortcode('mv_contact_form', array($this, 'contact_form_shortcode'));
    }

    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mv_messages';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            product_id bigint(20) DEFAULT 0,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            type varchar(20) DEFAULT 'message',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function handle_message() {
        check_ajax_referer('mv_message_nonce', 'nonce');
        
        $receiver_id = intval($_POST['receiver_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $sender_id = get_current_user_id();
        
        if (!$sender_id || !$message) {
            wp_send_json_error('اطلاعات ناقص است');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mv_messages';
        
        $wpdb->insert($table_name, array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'product_id' => $product_id,
            'message' => $message,
            'type' => $product_id > 0 ? 'ticket' : 'message'
        ));
        
        wp_send_json_success('پیام ارسال شد');
    }

    public function contact_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'vendor_id' => get_current_user_id(),
            'product_id' => 0
        ), $atts);
        
        ob_start();
        ?>
        <div class="mv-contact-form" style="background:#f9f9f9; padding:15px; border:1px solid #ddd; margin-top:20px;">
            <h4>ارسال پیام به فروشنده</h4>
            <input type="hidden" id="mv_receiver_id" value="<?php echo esc_attr($atts['vendor_id']); ?>">
            <input type="hidden" id="mv_product_id" value="<?php echo esc_attr($atts['product_id']); ?>">
            <textarea id="mv_message_content" rows="4" style="width:100%; margin-bottom:10px;" placeholder="پیام شما..."></textarea>
            <button id="mv_send_btn" class="button">ارسال پیام</button>
            <div id="mv_msg_result"></div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#mv_send_btn').click(function() {
                    var data = {
                        action: 'mv_send_message',
                        nonce: '<?php echo wp_create_nonce('mv_message_nonce'); ?>',
                        receiver_id: $('#mv_receiver_id').val(),
                        message: $('#mv_message_content').val(),
                        product_id: $('#mv_product_id').val()
                    };
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(res) {
                        if(res.success) {
                            $('#mv_msg_result').html('<span style="color:green">پیام ارسال شد.</span>');
                            $('#mv_message_content').val('');
                        } else {
                            $('#mv_msg_result').html('<span style="color:red">خطا: ' + res.data + '</span>');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}

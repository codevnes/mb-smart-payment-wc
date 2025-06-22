<?php
/**
 * Hiển thị giao dịch trong admin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_Transactions_Admin {

    public static function init() {}

    // Việc thêm submenu được thực hiện trong MBSPWC_Admin

    public static function render() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Lịch sử giao dịch MBBank', 'mb-smart-payment-wc' ) . '</h1>';

        // Hiển thị giao dịch vừa lấy trực tiếp từ API
        $opts   = get_option( 'mbspwc_settings', [] );
        $token  = $opts['token'] ?? '';
        $expires= $opts['expires'] ?? 0;
        if ( empty( $token ) || $expires < time() ) {
            $creds  = $opts['creds'] ?? $opts; // Thống nhất key
            $login  = MBSPWC_API::login( $creds['user'] ?? '', $creds['pass'] ?? '' );
            if ( $login ) {
                $token = $login['token'];
                $opts['token']   = $token;
                $opts['expires'] = $login['expires_at'];
                update_option( 'mbspwc_settings', $opts );
            }
        }
        $transactions = MBSPWC_API::get_transactions( $token );

        echo '<h2>' . esc_html__( 'Giao dịch mới nhất (API)', 'mb-smart-payment-wc' ) . '</h2>';
        self::table_api( $transactions );

        // Hiển thị lịch sử đã match
        global $wpdb;
        $table_name = $wpdb->prefix . MBSPWC_DB::TABLE;
        $logs = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created DESC LIMIT 100", ARRAY_A );

        echo '<h2>' . esc_html__( 'Giao dịch đã khớp đơn', 'mb-smart-payment-wc' ) . '</h2>';
        self::table_db( $logs );

        echo '</div>';
    }

    protected static function table_api( $rows ) {
        echo '<table class="widefat"><thead><tr>';
        echo '<th>' . __( 'Mã GD', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Số tiền', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Mô tả', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Thời gian', 'mb-smart-payment-wc' ) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ( $rows as $r ) {
            printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', esc_html( $r['trans_id'] ), wc_price( $r['amount'] ), esc_html( $r['description'] ), esc_html( $r['time'] ) );
        }
        echo '</tbody></table>';
    }

    protected static function table_db( $rows ) {
        echo '<table class="widefat"><thead><tr>';
        echo '<th>ID</th><th>' . __( 'Đơn hàng', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Mã GD', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Số tiền', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Trạng thái', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Thời gian', 'mb-smart-payment-wc' ) . '</th></tr></thead><tbody>';
        foreach ( $rows as $r ) {
            printf( '<tr><td>%d</td><td><a href="%s">#%d</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $r['id'],
                esc_url( admin_url( 'post.php?post=' . $r['order_id'] . '&action=edit' ) ),
                $r['order_id'],
                esc_html( $r['trans_id'] ),
                wc_price( $r['amount'] ),
                esc_html( $r['status'] ),
                esc_html( $r['created'] )
            );
        }
        echo '</tbody></table>';
    }
}

<?php
/**
 * Hiển thị giao dịch trong admin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_Transactions_Admin {

    public static function init() {}

    // Việc thêm submenu được thực hiện trong MBSPWC_Admin

    public static function render() {
        echo '<div class="wrap mbsp-trans"><h1>' . esc_html__( 'Lịch sử giao dịch MBBank', 'mb-smart-payment-wc' ) . '</h1>';

        echo '<div class="mbsp-filters"><input type="date" id="mbsp-from"> <input type="date" id="mbsp-to"> <input type="text" id="mbsp-acc" placeholder="' . esc_attr__( 'Số TK', 'mb-smart-payment-wc' ) . '" size="12"> <button class="button" id="mbsp-load-trans">' . esc_html__( 'Tải giao dịch', 'mb-smart-payment-wc' ) . '</button></div>';

        echo '<table class="widefat" id="mbsp-trans-table"><thead><tr><th>' . __( 'Mã GD', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Số tiền', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Mô tả', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Thời gian', 'mb-smart-payment-wc' ) . '</th></tr></thead><tbody><tr><td colspan="4">' . __( 'Chưa có dữ liệu', 'mb-smart-payment-wc' ) . '</td></tr></tbody></table>';

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

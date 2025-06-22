<?php
/**
 * Hiển thị giao dịch trong admin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_Transactions_Admin {

    public static function init() {}

    // Việc thêm submenu được thực hiện trong MBSPWC_Admin

    public static function render() {
        // Always use Vue.js interface
        echo '<div id="mbsp-vue-admin"></div>';
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

    protected static function table_orders( $orders ) {
        echo '<table class="mbsp-table"><thead><tr>';
        echo '<th>ID</th>';
        echo '<th>' . __( 'Đơn hàng', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Khách hàng', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Số tiền', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Trạng thái', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Mã GD', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Thời gian tạo', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Cập nhật', 'mb-smart-payment-wc' ) . '</th>';
        echo '</tr></thead><tbody>';
        
        if ( empty( $orders ) ) {
            echo '<tr><td colspan="8" style="text-align: center; padding: 40px;">';
            echo '<div class="mbsp-empty-state">';
            echo '<div class="icon">🛒</div>';
            echo '<h3>' . __( 'Chưa có đơn hàng nào', 'mb-smart-payment-wc' ) . '</h3>';
            echo '<p>' . __( 'Các đơn hàng sử dụng MB Smart Payment sẽ hiển thị ở đây', 'mb-smart-payment-wc' ) . '</p>';
            echo '</div>';
            echo '</td></tr>';
        } else {
            foreach ( $orders as $order ) {
                $status_class = '';
                $status_text = '';
                
                switch ( $order->status ) {
                    case 'pending':
                        $status_class = 'pending';
                        $status_text = 'Chờ thanh toán';
                        break;
                    case 'completed':
                        $status_class = 'completed';
                        $status_text = 'Đã thanh toán';
                        break;
                    case 'failed':
                        $status_class = 'failed';
                        $status_text = 'Thất bại';
                        break;
                    default:
                        $status_class = 'pending';
                        $status_text = ucfirst( $order->status );
                }
                
                printf( '<tr><td>%d</td><td><a href="%s" style="color: #667eea; text-decoration: none; font-weight: 600;">#%d</a></td><td>%s</td><td class="amount">%s</td><td><span class="status %s">%s</span></td><td style="font-family: monospace; font-size: 13px;">%s</td><td style="font-size: 13px;">%s</td><td style="font-size: 13px;">%s</td></tr>',
                    $order->id,
                    esc_url( admin_url( 'post.php?post=' . $order->order_id . '&action=edit' ) ),
                    $order->order_id,
                    esc_html( $order->customer_name ?: $order->customer_email ),
                    wc_price( $order->amount ),
                    esc_attr( $status_class ),
                    esc_html( $status_text ),
                    esc_html( $order->trans_id ?: '-' ),
                    esc_html( date( 'Y-m-d H:i', strtotime( $order->created ) ) ),
                    esc_html( date( 'Y-m-d H:i', strtotime( $order->updated ) ) )
                );
            }
        }
        echo '</tbody></table>';
    }

    protected static function table_db( $rows ) {
        echo '<table class="widefat"><thead><tr>';
        echo '<th>ID</th><th>' . __( 'Đơn hàng', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Mã GD', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Số tiền', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Trạng thái', 'mb-smart-payment-wc' ) . '</th><th>' . __( 'Thời gian', 'mb-smart-payment-wc' ) . '</th></tr></thead><tbody>';
        
        if ( empty( $rows ) ) {
            echo '<tr><td colspan="6">' . __( 'Chưa có giao dịch nào được khớp', 'mb-smart-payment-wc' ) . '</td></tr>';
        } else {
            foreach ( $rows as $r ) {
                printf( '<tr><td>%d</td><td><a href="%s">#%d</a></td><td>%s</td><td class="amount">%s</td><td><span class="status %s">%s</span></td><td>%s</td></tr>',
                    $r['id'],
                    esc_url( admin_url( 'post.php?post=' . $r['order_id'] . '&action=edit' ) ),
                    $r['order_id'],
                    esc_html( $r['trans_id'] ),
                    wc_price( $r['amount'] ),
                    esc_attr( $r['status'] ),
                    esc_html( $r['status'] ),
                    esc_html( $r['created'] )
                );
            }
        }
        echo '</tbody></table>';
    }
}

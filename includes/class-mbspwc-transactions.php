<?php
/**
 * Hiển thị giao dịch trong admin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_Transactions_Admin {

    public static function init() {}

    // Việc thêm submenu được thực hiện trong MBSPWC_Admin

    public static function render() {
        echo '<div class="mbsp-admin-wrap">';
        
        // Header
        echo '<div class="mbsp-admin-header">';
        echo '<h1>' . esc_html__( 'Lịch sử giao dịch MBBank', 'mb-smart-payment-wc' ) . '</h1>';
        echo '<p class="subtitle">' . esc_html__( 'Theo dõi và quản lý các giao dịch thanh toán qua MBBank', 'mb-smart-payment-wc' ) . '</p>';
        echo '</div>';

        echo '<div class="mbsp-admin-content">';
        
        // Statistics Cards
        $orders = MBSPWC_DB::get_orders( 1000 );
        $total_orders = count( $orders );
        $completed_orders = count( array_filter( $orders, function( $order ) { return $order->status === 'completed'; } ) );
        $pending_orders = count( array_filter( $orders, function( $order ) { return $order->status === 'pending'; } ) );
        $total_amount = array_sum( array_map( function( $order ) { return $order->status === 'completed' ? $order->amount : 0; }, $orders ) );
        
        echo '<div class="mbsp-stats-grid">';
        echo '<div class="mbsp-stat-card">';
        echo '<div class="mbsp-stat-value">' . number_format( $total_orders ) . '</div>';
        echo '<div class="mbsp-stat-label">' . __( 'Tổng đơn hàng', 'mb-smart-payment-wc' ) . '</div>';
        echo '</div>';
        echo '<div class="mbsp-stat-card">';
        echo '<div class="mbsp-stat-value">' . number_format( $completed_orders ) . '</div>';
        echo '<div class="mbsp-stat-label">' . __( 'Đã thanh toán', 'mb-smart-payment-wc' ) . '</div>';
        echo '</div>';
        echo '<div class="mbsp-stat-card">';
        echo '<div class="mbsp-stat-value">' . number_format( $pending_orders ) . '</div>';
        echo '<div class="mbsp-stat-label">' . __( 'Chờ thanh toán', 'mb-smart-payment-wc' ) . '</div>';
        echo '</div>';
        echo '<div class="mbsp-stat-card">';
        echo '<div class="mbsp-stat-value">' . number_format( $total_amount ) . '</div>';
        echo '<div class="mbsp-stat-label">' . __( 'Tổng doanh thu (VND)', 'mb-smart-payment-wc' ) . '</div>';
        echo '</div>';
        echo '</div>';
        
        // Filters
        echo '<div class="mbsp-filters">';
        echo '<div class="mbsp-filters-grid">';
        echo '<div class="mbsp-filter-group">';
        echo '<label class="mbsp-filter-label">' . __( 'Từ ngày', 'mb-smart-payment-wc' ) . '</label>';
        echo '<input type="date" id="mbsp-from" class="mbsp-filter-input">';
        echo '</div>';
        echo '<div class="mbsp-filter-group">';
        echo '<label class="mbsp-filter-label">' . __( 'Đến ngày', 'mb-smart-payment-wc' ) . '</label>';
        echo '<input type="date" id="mbsp-to" class="mbsp-filter-input">';
        echo '</div>';
        echo '<div class="mbsp-filter-group">';
        echo '<label class="mbsp-filter-label">' . __( 'Số tài khoản', 'mb-smart-payment-wc' ) . '</label>';
        $settings = get_option( 'mbspwc_settings', [] );
        $default_account = $settings['acc_no'] ?? '';
        echo '<input type="text" id="mbsp-acc" class="mbsp-filter-input" placeholder="' . esc_attr__( 'Nhập số tài khoản', 'mb-smart-payment-wc' ) . '" value="' . esc_attr( $default_account ) . '">';
        echo '</div>';
        echo '<div class="mbsp-filter-group">';
        echo '<label class="mbsp-filter-label">&nbsp;</label>';
        echo '<button class="mbsp-button" id="mbsp-load-trans">' . esc_html__( 'Tải giao dịch', 'mb-smart-payment-wc' ) . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="mbsp-grid">';
        
        // API Transactions Card
        echo '<div class="mbsp-card mbsp-card-full">';
        echo '<div class="mbsp-card-header">';
        echo '<h2>' . __( 'Giao dịch từ MBBank API', 'mb-smart-payment-wc' ) . '</h2>';
        echo '</div>';
        echo '<div class="mbsp-card-body" style="padding: 0;">';
        echo '<div class="mbsp-table-container">';
        echo '<table class="mbsp-table" id="mbsp-trans-table">';
        echo '<thead><tr>';
        echo '<th>' . __( 'Mã giao dịch', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Số tiền', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Mô tả', 'mb-smart-payment-wc' ) . '</th>';
        echo '<th>' . __( 'Thời gian', 'mb-smart-payment-wc' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        echo '<tr><td colspan="4" style="text-align: center; padding: 40px;">';
        echo '<div class="mbsp-empty-state">';
        echo '<div class="icon">📊</div>';
        echo '<h3>' . __( 'Chưa có dữ liệu', 'mb-smart-payment-wc' ) . '</h3>';
        echo '<p>' . __( 'Nhấn "Tải giao dịch" để xem dữ liệu từ MBBank', 'mb-smart-payment-wc' ) . '</p>';
        echo '</div>';
        echo '</td></tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Orders Card
        echo '<div class="mbsp-card mbsp-card-full">';
        echo '<div class="mbsp-card-header">';
        echo '<h2>' . esc_html__( 'Đơn hàng MB Smart Payment', 'mb-smart-payment-wc' ) . '</h2>';
        echo '</div>';
        echo '<div class="mbsp-card-body" style="padding: 0;">';
        echo '<div class="mbsp-table-container">';
        self::table_orders( MBSPWC_DB::get_orders( 100 ) );
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // grid
        echo '</div>'; // content
        echo '</div>'; // wrap
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

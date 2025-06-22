<?php
/**
 * Lưu trữ dữ liệu ra bảng riêng.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_DB {

    const TABLE = 'mbspwc_transactions';

    /**
     * Tạo bảng khi kích hoạt plugin.
     */
    public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            trans_id VARCHAR(100) DEFAULT '',
            amount DECIMAL(18,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_method VARCHAR(50) DEFAULT 'mbspwc',
            customer_name VARCHAR(255) DEFAULT '',
            customer_email VARCHAR(255) DEFAULT '',
            order_note TEXT DEFAULT '',
            qr_url TEXT DEFAULT '',
            raw LONGTEXT NULL,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY trans_id (trans_id),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Tạo record đơn hàng mới
     */
    public static function create_order_record( $order_id, $amount, $customer_name = '', $customer_email = '', $order_note = '', $qr_url = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE;

        return $wpdb->insert( $table_name, [
            'order_id' => $order_id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'mbspwc',
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'order_note' => $order_note,
            'qr_url' => $qr_url,
        ], [ '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s' ] );
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public static function update_order_status( $order_id, $status, $trans_id = '', $raw_data = null ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE;

        $data = [
            'status' => $status,
            'updated' => current_time( 'mysql' )
        ];
        $format = [ '%s', '%s' ];

        if ( $trans_id ) {
            $data['trans_id'] = $trans_id;
            $format[] = '%s';
        }

        if ( $raw_data ) {
            $data['raw'] = maybe_serialize( $raw_data );
            $format[] = '%s';
        }

        return $wpdb->update( 
            $table_name, 
            $data,
            [ 'order_id' => $order_id ],
            $format,
            [ '%d' ]
        );
    }

    /**
     * Ghi log giao dịch (backward compatibility)
     */
    public static function log( $order_id, $trans, $status = 'matched' ) {
        return self::update_order_status( 
            $order_id, 
            $status, 
            $trans['trans_id'] ?? '', 
            $trans 
        );
    }

    /**
     * Lấy thông tin đơn hàng
     */
    public static function get_order_record( $order_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE order_id = %d ORDER BY created DESC LIMIT 1",
            $order_id
        ) );
    }

    /**
     * Lấy danh sách đơn hàng
     */
    public static function get_orders( $limit = 50, $offset = 0, $status = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE;

        $where = '';
        $params = [];

        if ( $status ) {
            $where = 'WHERE status = %s';
            $params[] = $status;
        }

        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT * FROM {$table_name} {$where} ORDER BY created DESC LIMIT %d OFFSET %d";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    }
}

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
            trans_id VARCHAR(100) NOT NULL,
            amount DECIMAL(18,2) NOT NULL,
            status VARCHAR(20) NOT NULL,
            raw LONGTEXT NULL,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY trans_id (trans_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Ghi log giao dịch.
     */
    public static function log( $order_id, $trans, $status = 'matched' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE;

        $wpdb->insert( $table_name, [
            'order_id' => $order_id,
            'trans_id' => $trans['trans_id'] ?? '',
            'amount'   => $trans['amount'] ?? 0,
            'status'   => $status,
            'raw'      => maybe_serialize( $trans ),
        ], [ '%d', '%s', '%f', '%s', '%s' ] );
    }
}

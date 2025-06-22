<?php
/**
 * Tạo QR theo chuẩn VietQR (mock).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_VietQR {
    public static function generate( $account_no, $account_name, $amount, $order_id ) {
        // URL tĩnh mẫu
        $query = http_build_query([
            'acc'   => $account_no,
            'name'  => $account_name,
            'amount'=> $amount,
            'addInfo' => 'ORDER-' . $order_id,
        ]);
        $url = 'https://img.vietqr.io/image/MB-'. $account_no . '-compact.png?' . $query;
        return $url; // Trong thực tế gọi API VietQR & trả về URL/ data.
    }
}

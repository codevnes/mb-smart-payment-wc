<?php
/**
 * Class MBSPWC_API
 *
 * Giao tiếp mock với API MBBank.
 * Thay thế các hàm bên dưới khi tích hợp thật.
 *
 * @package MBSPWC
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBSPWC_API {

    /**
     * Login & lấy token.
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    public static function login( $username, $password ) {
        /**
         * TODO: Gọi API thật tại đây.
         */
        return [
            'token'      => wp_generate_password( 40, false, false ),
            'expires_at' => time() + 3600,
        ];
    }

    /**
     * Lấy lịch sử giao dịch.
     *
     * @param string $token
     * @param array  $args
     *
     * @return array
     */
    public static function get_transactions( $token, $args = [] ) {
        // Demo data
        return [
            [
                'trans_id'    => 'TX' . rand( 10000, 99999 ),
                'amount'      => 100000,
                'description' => 'ORDER-1234',
                'time'        => current_time( 'mysql' ),
            ],
        ];
    }
}

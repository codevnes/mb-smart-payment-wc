<?php
/**
 * Xử lý Ajax cho MBSP admin.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBSPWC_Ajax {
    public static function init() {
        $actions = [ 'login', 'status', 'logout', 'transactions', 'test_connection', 'check_payment' ];
        foreach ( $actions as $act ) {
            add_action( 'wp_ajax_mbsp_' . $act, [ __CLASS__, $act ] );
            add_action( 'wp_ajax_nopriv_mbsp_' . $act, [ __CLASS__, $act ] );
        }
    }

    private static function json( $data ) {
        wp_send_json( $data );
    }

    public static function login() {
        check_ajax_referer( 'mbsp_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Không có quyền truy cập', 'mb-smart-payment-wc' ), 403 );
        }
        
        $user = sanitize_text_field( $_POST['user'] ?? '' );
        $pass = sanitize_text_field( $_POST['pass'] ?? '' );
        
        if ( ! $user || ! $pass ) {
            wp_send_json_error( __( 'Vui lòng nhập đầy đủ thông tin đăng nhập', 'mb-smart-payment-wc' ) );
        }
        
        $res = MBSPWC_Backend::login( $user, $pass );
        
        // Debug log
        error_log( 'MBSPWC Login Response: ' . print_r( $res, true ) );
        
        if ( is_wp_error( $res ) ) {
            wp_send_json_error( $res->get_error_message() );
        }
        
        // Check if response has success field
        if ( isset( $res['success'] ) && $res['success'] === false ) {
            $error_msg = $res['message'] ?? $res['error'] ?? __( 'Đăng nhập thất bại', 'mb-smart-payment-wc' );
            wp_send_json_error( $error_msg );
        }
        
        // Check for token
        if ( empty( $res['token'] ) && empty( $res['accessToken'] ) ) {
            $error_msg = $res['message'] ?? $res['error'] ?? __( 'Không nhận được token từ server', 'mb-smart-payment-wc' );
            wp_send_json_error( $error_msg );
        }
        
        $token = $res['token'] ?? $res['accessToken'] ?? '';
        $refresh_token = $res['refreshToken'] ?? $res['refresh_token'] ?? '';
        
        $opts = [
            'token'         => $token,
            'refresh_token' => $refresh_token,
            'expires'       => time() + 3600,
        ];
        
        update_option( 'mbspwc_backend', $opts );
        
        wp_send_json_success( [
            'message' => __( 'Đăng nhập thành công', 'mb-smart-payment-wc' ),
            'token_expires' => $opts['expires']
        ] );
    }

    public static function logout() {
        check_ajax_referer( 'mbsp_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'denied', 403 );
        }
        $opts = get_option( 'mbspwc_backend', [] );
        if ( ! empty( $opts['token'] ) ) {
            MBSPWC_Backend::logout( $opts['token'] );
        }
        delete_option( 'mbspwc_backend' );
        self::json( [ 'success' => true ] );
    }

    public static function status() {
        check_ajax_referer( 'mbsp_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'denied', 403 );
        }
        $opts = get_option( 'mbspwc_backend', [] );
        $logged = ! empty( $opts['token'] ) && ( $opts['expires'] ?? 0 ) > time();
        self::json( [ 'logged_in' => $logged, 'expires' => $opts['expires'] ?? 0 ] );
    }

    public static function transactions() {
        check_ajax_referer( 'mbsp_admin', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'denied', 403 );
        }
        $opts = get_option( 'mbspwc_backend', [] );
        if ( empty( $opts['token'] ) ) {
            wp_send_json_error( __( 'Not logged in', 'mb-smart-payment-wc' ), 401 );
        }
        $params = [
            'accountNumber' => sanitize_text_field( $_POST['account'] ?? '' ),
            'fromDate'      => sanitize_text_field( $_POST['from'] ?? '' ),
            'toDate'        => sanitize_text_field( $_POST['to'] ?? '' ),
        ];
        $data = MBSPWC_Backend::transactions( $opts['token'], $params );
        if ( is_wp_error( $data ) ) {
            wp_send_json_error( $data->get_error_message() );
        }
        self::json( [ 'items' => $data ] );
    }

    public static function test_connection() {
        check_ajax_referer( 'mbsp_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Không có quyền truy cập', 403 );
        }

        // Test basic connection to backend
        $response = wp_remote_get( MBSPWC_Backend::API_URL . '/api/health', [
            'timeout' => 10,
            'headers' => [ 'Content-Type' => 'application/json' ]
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'Không thể kết nối đến backend: ' . $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code === 200 ) {
            wp_send_json_success( [
                'message' => 'Kết nối backend thành công',
                'backend_url' => MBSPWC_Backend::API_URL,
                'response' => $body
            ] );
        } else {
            wp_send_json_error( "Backend trả về lỗi: HTTP {$code}" );
        }
    }

    public static function check_payment() {
        // Verify nonce for frontend requests
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'mbsp_frontend' ) ) {
            wp_send_json_error( 'Nonce verification failed' );
        }
        
        // Allow both logged in and non-logged in users to check payment
        $order_id = intval( $_POST['order_id'] ?? 0 );
        
        if ( ! $order_id ) {
            wp_send_json_error( 'ID đơn hàng không hợp lệ' );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( 'Không tìm thấy đơn hàng' );
        }

        // Check if order belongs to current user (if logged in)
        if ( is_user_logged_in() ) {
            $current_user_id = get_current_user_id();
            $order_user_id = $order->get_user_id();
            
            if ( $order_user_id && $order_user_id !== $current_user_id ) {
                wp_send_json_error( 'Bạn không có quyền xem đơn hàng này' );
            }
        }

        $status = $order->get_status();
        $status_text = '';
        $status_class = '';

        switch ( $status ) {
            case 'completed':
            case 'processing':
                $status_text = '✅ Đã thanh toán thành công';
                $status_class = 'mbsp-status-completed';
                break;
            case 'on-hold':
            case 'pending':
                $status_text = '⏳ Đang chờ thanh toán';
                $status_class = 'mbsp-status-pending';
                break;
            case 'failed':
            case 'cancelled':
                $status_text = '❌ Thanh toán thất bại';
                $status_class = 'mbsp-status-failed';
                break;
            default:
                $status_text = '📋 ' . wc_get_order_status_name( $status );
                $status_class = 'mbsp-status-pending';
        }

        wp_send_json_success( [
            'status' => $status,
            'status_text' => $status_text,
            'status_class' => $status_class,
            'order_total' => $order->get_total(),
            'order_date' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
            'payment_method' => $order->get_payment_method_title(),
            'is_paid' => $order->is_paid()
        ] );
    }
}

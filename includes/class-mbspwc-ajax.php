<?php
/**
 * Xử lý Ajax cho MBSP admin.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBSPWC_Ajax {
    public static function init() {
        $actions = [ 'login', 'status', 'logout', 'transactions' ];
        foreach ( $actions as $act ) {
            add_action( 'wp_ajax_mbsp_' . $act, [ __CLASS__, $act ] );
        }
    }

    private static function json( $data ) {
        wp_send_json( $data );
    }

    public static function login() {
        check_ajax_referer( 'mbsp_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'mb-smart-payment-wc' ), 403 );
        }
        $user = sanitize_text_field( $_POST['user'] ?? '' );
        $pass = sanitize_text_field( $_POST['pass'] ?? '' );
        if ( ! $user || ! $pass ) {
            wp_send_json_error( __( 'Missing credentials', 'mb-smart-payment-wc' ) );
        }
        $res = MBSPWC_Backend::login( $user, $pass );
        if ( is_wp_error( $res ) || empty( $res['token'] ) ) {
            wp_send_json_error( $res instanceof WP_Error ? $res->get_error_message() : __( 'Login failed', 'mb-smart-payment-wc' ) );
        }
        $opts = [
            'token'         => $res['token'],
            'refresh_token' => $res['refreshToken'] ?? '',
            'expires'       => time() + 3600,
        ];
        update_option( 'mbspwc_backend', $opts );
        self::json( [ 'success' => true ] );
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
}

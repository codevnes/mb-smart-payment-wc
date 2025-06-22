<?php
/**
 * Top-level Admin menu 'MBSB'
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_Admin {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
    }

    public static function menu() {
        add_menu_page( 'MBSB', 'MBSB', 'manage_options', 'mbsb', [ __CLASS__, 'status_page' ], 'dashicons-bank', 56 );

        add_submenu_page( 'mbsb', __( 'Trạng thái', 'mb-smart-payment-wc' ), __( 'Trạng thái', 'mb-smart-payment-wc' ), 'manage_options', 'mbsb', [ __CLASS__, 'status_page' ] );
        add_submenu_page( 'mbsb', __( 'Giao dịch', 'mb-smart-payment-wc' ), __( 'Giao dịch', 'mb-smart-payment-wc' ), 'manage_woocommerce', 'mbsb-transactions', [ 'MBSPWC_Transactions_Admin', 'render' ] );
        add_submenu_page( 'mbsb', __( 'Cài đặt', 'mb-smart-payment-wc' ), __( 'Cài đặt', 'mb-smart-payment-wc' ), 'manage_options', 'mbspwc-settings', [ 'MBSPWC_Settings', 'render' ] );
    }

    public static function status_page() {
        $opts = get_option( 'mbspwc_backend', [] );
        $logged_in = ! empty( $opts['token'] ) && ( $opts['expires'] ?? 0 ) > time();

        if ( isset( $_POST['mbsb_login_nonce'] ) && wp_verify_nonce( $_POST['mbsb_login_nonce'], 'mbsb_login' ) ) {
            $user = sanitize_text_field( $_POST['mb_user'] );
            $pass = sanitize_text_field( $_POST['mb_pass'] );
            $res  = MBSPWC_Backend::login( $user, $pass );
            if ( is_wp_error( $res ) || empty( $res['success'] ) ) {
                add_settings_error( 'mbsb', 'login_fail', __( 'Đăng nhập thất bại', 'mb-smart-payment-wc' ), 'error' );
            } else {
                $opts = [
                    'token'         => $res['token'],
                    'refresh_token' => $res['refreshToken'],
                    'expires'       => time() + 3600, // giả sử 1h
                ];
                update_option( 'mbspwc_backend', $opts );
                $logged_in = true;
            }
        } elseif ( isset( $_POST['mbsb_logout'] ) && wp_verify_nonce( $_POST['mbsb_logout'], 'mbsb_logout' ) ) {
            if ( ! empty( $opts['token'] ) ) {
                MBSPWC_Backend::logout( $opts['token'] );
            }
            delete_option( 'mbspwc_backend' );
            $logged_in = false;
        }

        echo '<div class="wrap"><h1>MBSB - ' . esc_html__( 'Trạng thái', 'mb-smart-payment-wc' ) . '</h1>';

        if ( $logged_in ) {
            echo '<p>' . esc_html__( 'Đã đăng nhập.', 'mb-smart-payment-wc' ) . '</p>';
            echo '<form method="post"><p>';
            wp_nonce_field( 'mbsb_logout', 'mbsb_logout' );
            submit_button( __( 'Đăng xuất', 'mb-smart-payment-wc' ) );
            echo '</p></form>';
        } else {
            echo '<p>' . esc_html__( 'Chưa đăng nhập.', 'mb-smart-payment-wc' ) . '</p>';
            echo '<form method="post">';
            wp_nonce_field( 'mbsb_login', 'mbsb_login_nonce' );
            echo '<table class="form-table"><tr><th>' . __( 'User', 'mb-smart-payment-wc' ) . '</th><td><input type="text" name="mb_user" class="regular-text"/></td></tr>';
            echo '<tr><th>' . __( 'Password', 'mb-smart-payment-wc' ) . '</th><td><input type="password" name="mb_pass" class="regular-text"/></td></tr></table>';
            submit_button( __( 'Đăng nhập', 'mb-smart-payment-wc' ) );
            echo '</form>';
        }
        echo '</div>';
    }
}

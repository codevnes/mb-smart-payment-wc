<?php
/**
 * Top-level Admin menu 'MBSP'
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_Admin {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
    }

    public static function menu() {
        add_menu_page( 'MBSP', 'MBSP', 'manage_options', 'mbsp', [ __CLASS__, 'status_page' ], 'dashicons-bank', 56 );

        add_submenu_page( 'mbsp', __( 'Trạng thái', 'mb-smart-payment-wc' ), __( 'Trạng thái', 'mb-smart-payment-wc' ), 'manage_options', 'mbsp', [ __CLASS__, 'status_page' ] );
        add_submenu_page( 'mbsp', __( 'Giao dịch', 'mb-smart-payment-wc' ), __( 'Giao dịch', 'mb-smart-payment-wc' ), 'manage_woocommerce', 'mbsp-transactions', [ 'MBSPWC_Transactions_Admin', 'render' ] );
        add_submenu_page( 'mbsp', __( 'Cài đặt', 'mb-smart-payment-wc' ), __( 'Cài đặt', 'mb-smart-payment-wc' ), 'manage_options', 'mbspwc-settings', [ 'MBSPWC_Settings', 'render' ] );
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
            echo '<p id="mbsp-status">' . esc_html__( 'Đã đăng nhập', 'mb-smart-payment-wc' ) . '</p>';
            echo '<a href="#" id="mbsp-logout" class="button button-secondary">' . esc_html__( 'Đăng xuất', 'mb-smart-payment-wc' ) . '</a>';
        } else {
            echo '<p id="mbsp-status">' . esc_html__( 'Chưa đăng nhập', 'mb-smart-payment-wc' ) . '</p>';
            echo '<form id="mbsp-login-form">';
            wp_nonce_field( 'mbsp_admin', 'nonce' );
            echo '<p><label>' . __( 'User', 'mb-smart-payment-wc' ) . ' <input type="text" id="mb_user" name="mb_user" class="regular-text"></label></p>';
            echo '<p><label>' . __( 'Password', 'mb-smart-payment-wc' ) . ' <input type="password" id="mb_pass" name="mb_pass" class="regular-text"></label></p>';
            echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Đăng nhập', 'mb-smart-payment-wc' ) . '</button></p>';
            echo '</form>';
        }
        echo '</div>';
    }
    public static function assets( $hook ) {
        if ( strpos( $hook, 'mbsp' ) === false ) return;
        wp_enqueue_script( 'mbsp-admin', MBSPWC_URL . 'assets/admin.js', [ 'jquery' ], MBSPWC_VERSION, true );
        wp_localize_script( 'mbsp-admin', 'mbsp_admin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'mbsp_admin' ),
            'i18n'     => [
                'logged'     => __( 'Đã đăng nhập', 'mb-smart-payment-wc' ),
                'not_logged' => __( 'Chưa đăng nhập', 'mb-smart-payment-wc' ),
            ],
        ] );
        wp_enqueue_style( 'mbsp-admin', MBSPWC_URL . 'assets/admin.css', [], MBSPWC_VERSION );
    }

}

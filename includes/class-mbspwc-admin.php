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

        echo '<div class="wrap mbsp-admin-wrap">';
        echo '<h1>' . esc_html__( 'MB Smart Payment - Trạng thái', 'mb-smart-payment-wc' ) . '</h1>';

        // Status Card
        echo '<div class="mbsp-status-card">';
        echo '<div class="mbsp-status-indicator ' . ( $logged_in ? 'logged-in' : 'logged-out' ) . '" id="mbsp-status-indicator">';
        echo '<span class="mbsp-status-dot"></span>';
        echo '<span id="mbsp-status-text">' . ( $logged_in ? __( 'Đã đăng nhập MBBank', 'mb-smart-payment-wc' ) : __( 'Chưa đăng nhập MBBank', 'mb-smart-payment-wc' ) ) . '</span>';
        echo '</div>';

        if ( $logged_in ) {
            $expires_time = $opts['expires'] ?? 0;
            $remaining = $expires_time - time();
            echo '<p><strong>' . __( 'Token hết hạn:', 'mb-smart-payment-wc' ) . '</strong> ' . date( 'Y-m-d H:i:s', $expires_time ) . ' (' . sprintf( __( 'còn %d phút', 'mb-smart-payment-wc' ), max( 0, floor( $remaining / 60 ) ) ) . ')</p>';
            
            echo '<div class="mbsp-button-group">';
            echo '<button type="button" id="mbsp-check-status" class="button button-secondary">' . esc_html__( 'Kiểm tra trạng thái', 'mb-smart-payment-wc' ) . '</button>';
            echo '<button type="button" id="mbsp-logout" class="button button-secondary">' . esc_html__( 'Đăng xuất', 'mb-smart-payment-wc' ) . '</button>';
            echo '</div>';
        } else {
            echo '<div class="mbsp-login-form">';
            echo '<form id="mbsp-login-form">';
            wp_nonce_field( 'mbsp_admin', 'nonce' );
            echo '<table class="form-table">';
            echo '<tr><th><label for="mb_user">' . __( 'Tên đăng nhập', 'mb-smart-payment-wc' ) . '</label></th>';
            echo '<td><input type="text" id="mb_user" name="mb_user" class="regular-text" required></td></tr>';
            echo '<tr><th><label for="mb_pass">' . __( 'Mật khẩu', 'mb-smart-payment-wc' ) . '</label></th>';
            echo '<td><input type="password" id="mb_pass" name="mb_pass" class="regular-text" required></td></tr>';
            echo '</table>';
            echo '<div class="mbsp-button-group">';
            echo '<button type="submit" class="button button-primary">' . esc_html__( 'Đăng nhập', 'mb-smart-payment-wc' ) . '</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
        }
        echo '</div>';

        // Connection Info
        echo '<div class="mbsp-status-card">';
        echo '<h3>' . __( 'Thông tin kết nối', 'mb-smart-payment-wc' ) . '</h3>';
        echo '<p><strong>' . __( 'Backend API:', 'mb-smart-payment-wc' ) . '</strong> ' . MBSPWC_Backend::API_URL . '</p>';
        
        $settings = get_option( 'mbspwc_settings', [] );
        $account_no = $settings['acc_no'] ?? '';
        if ( $account_no ) {
            echo '<p><strong>' . __( 'Số tài khoản:', 'mb-smart-payment-wc' ) . '</strong> ' . esc_html( $account_no ) . '</p>';
        }
        echo '</div>';

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

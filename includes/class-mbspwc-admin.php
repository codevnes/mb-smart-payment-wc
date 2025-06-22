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
        $settings = get_option( 'mbspwc_settings', [] );
        $use_vue = isset( $settings['use_vue'] ) && $settings['use_vue'];
        
        if ( $use_vue ) {
            echo '<div id="mbsp-vue-admin"></div>';
            return;
        }
        
        // Fallback to traditional PHP rendering
        $opts = get_option( 'mbspwc_backend', [] );
        $logged_in = ! empty( $opts['token'] ) && ( $opts['expires'] ?? 0 ) > time();

        echo '<div class="mbsp-admin-wrap">';
        
        // Header
        echo '<div class="mbsp-admin-header">';
        echo '<h1>' . esc_html__( 'Trạng thái kết nối MBBank', 'mb-smart-payment-wc' ) . '</h1>';
        echo '<p class="subtitle">' . esc_html__( 'Quản lý kết nối và xác thực với hệ thống MBBank', 'mb-smart-payment-wc' ) . '</p>';
        echo '</div>';

        echo '<div class="mbsp-admin-content">';
        
        // Status indicator
        echo '<div id="mbsp-status-indicator" class="mbsp-status-indicator ' . ( $logged_in ? 'logged-in' : 'logged-out' ) . '">';
        echo '<span id="mbsp-status-text">' . ( $logged_in ? __( 'Đã đăng nhập MBBank', 'mb-smart-payment-wc' ) : __( 'Chưa đăng nhập MBBank', 'mb-smart-payment-wc' ) ) . '</span>';
        echo '</div>';

        echo '<div class="mbsp-grid">';
        
        if ( $logged_in ) {
            // Session Info Card
            echo '<div class="mbsp-card">';
            echo '<div class="mbsp-card-header">';
            echo '<h2>' . esc_html__( 'Thông tin phiên đăng nhập', 'mb-smart-payment-wc' ) . '</h2>';
            echo '</div>';
            echo '<div class="mbsp-card-body">';
            $expires_time = $opts['expires'] ?? 0;
            $remaining = $expires_time - time();
            echo '<div class="mbsp-form-group">';
            echo '<label class="mbsp-form-label">' . __( 'Token hết hạn', 'mb-smart-payment-wc' ) . '</label>';
            echo '<div style="font-family: monospace; font-size: 14px; color: #374151;">' . date( 'Y-m-d H:i:s', $expires_time ) . '</div>';
            echo '<div class="mbsp-form-description">' . sprintf( __( 'Còn %d phút', 'mb-smart-payment-wc' ), max( 0, floor( $remaining / 60 ) ) ) . '</div>';
            echo '</div>';
            echo '<div style="display: flex; gap: 12px; flex-wrap: wrap;">';
            echo '<button type="button" id="mbsp-check-status" class="mbsp-button secondary">' . esc_html__( 'Kiểm tra trạng thái', 'mb-smart-payment-wc' ) . '</button>';
            echo '<button type="button" id="mbsp-logout" class="mbsp-button danger">' . esc_html__( 'Đăng xuất', 'mb-smart-payment-wc' ) . '</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        } else {
            // Login Card
            echo '<div class="mbsp-card">';
            echo '<div class="mbsp-card-header">';
            echo '<h2>' . esc_html__( 'Đăng nhập MBBank', 'mb-smart-payment-wc' ) . '</h2>';
            echo '</div>';
            echo '<div class="mbsp-card-body">';
            echo '<form id="mbsp-login-form">';
            wp_nonce_field( 'mbsp_admin', 'nonce' );
            echo '<div class="mbsp-form-group">';
            echo '<label class="mbsp-form-label" for="mb_user">' . __( 'Tên đăng nhập', 'mb-smart-payment-wc' ) . '</label>';
            echo '<input type="text" id="mb_user" name="mb_user" class="mbsp-form-input" placeholder="' . esc_attr__( 'Nhập tên đăng nhập MBBank', 'mb-smart-payment-wc' ) . '" required>';
            echo '</div>';
            echo '<div class="mbsp-form-group">';
            echo '<label class="mbsp-form-label" for="mb_pass">' . __( 'Mật khẩu', 'mb-smart-payment-wc' ) . '</label>';
            echo '<input type="password" id="mb_pass" name="mb_pass" class="mbsp-form-input" placeholder="' . esc_attr__( 'Nhập mật khẩu', 'mb-smart-payment-wc' ) . '" required>';
            echo '</div>';
            echo '<button type="submit" class="mbsp-button">' . esc_html__( 'Đăng nhập', 'mb-smart-payment-wc' ) . '</button>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
        }

        // Connection Info Card
        echo '<div class="mbsp-card">';
        echo '<div class="mbsp-card-header">';
        echo '<h2>' . __( 'Thông tin kết nối', 'mb-smart-payment-wc' ) . '</h2>';
        echo '</div>';
        echo '<div class="mbsp-card-body">';
        echo '<div class="mbsp-form-group">';
        echo '<label class="mbsp-form-label">' . __( 'Backend API', 'mb-smart-payment-wc' ) . '</label>';
        echo '<div style="font-family: monospace; font-size: 14px; color: #374151; word-break: break-all;">' . esc_html( MBSPWC_Backend::API_URL ) . '</div>';
        echo '</div>';
        
        $settings = get_option( 'mbspwc_settings', [] );
        $account_no = $settings['acc_no'] ?? '';
        if ( $account_no ) {
            echo '<div class="mbsp-form-group">';
            echo '<label class="mbsp-form-label">' . __( 'Số tài khoản', 'mb-smart-payment-wc' ) . '</label>';
            echo '<div style="font-family: monospace; font-size: 14px; color: #374151;">' . esc_html( $account_no ) . '</div>';
            echo '</div>';
        }
        
        echo '<button type="button" id="mbsp-test-connection" class="mbsp-button secondary">' . esc_html__( 'Kiểm tra kết nối Backend', 'mb-smart-payment-wc' ) . '</button>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // grid
        echo '</div>'; // content
        echo '</div>'; // wrap
    }
    public static function assets( $hook ) {
        if ( strpos( $hook, 'mbsp' ) === false ) return;
        
        // Check if Vue mode is enabled
        $settings = get_option( 'mbspwc_settings', [] );
        $use_vue = isset( $settings['use_vue'] ) && $settings['use_vue'];
        
        if ( $use_vue ) {
            // Load Vue.js and Element Plus from CDN
            wp_enqueue_script( 'vue-js', 'https://unpkg.com/vue@3/dist/vue.global.js', [], '3.3.4', false );
            wp_enqueue_script( 'element-plus', 'https://unpkg.com/element-plus/dist/index.full.js', [ 'vue-js' ], '2.4.4', false );
            wp_enqueue_style( 'element-plus-css', 'https://unpkg.com/element-plus/dist/index.css', [], '2.4.4' );
            
            // Load custom components and styles
            wp_enqueue_script( 'mbsp-vue-modern', MBSPWC_URL . 'assets/vue-modern.js', [ 'vue-js', 'element-plus' ], MBSPWC_VERSION, true );
            wp_enqueue_style( 'mbsp-element-theme', MBSPWC_URL . 'assets/element-theme.css', [ 'element-plus-css' ], MBSPWC_VERSION );
            
            wp_localize_script( 'mbsp-vue-modern', 'mbsp_admin', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'mbsp_admin' ),
                'api_url' => MBSPWC_Backend::API_URL,
                'use_vue' => true,
                'i18n' => [
                    'logged' => __( 'Đã đăng nhập MBBank', 'mb-smart-payment-wc' ),
                    'not_logged' => __( 'Chưa đăng nhập MBBank', 'mb-smart-payment-wc' ),
                ]
            ] );
        } else {
            // Fallback to jQuery version
            wp_enqueue_script( 'mbsp-admin', MBSPWC_URL . 'assets/admin.js', [ 'jquery' ], MBSPWC_VERSION, true );
            wp_enqueue_style( 'mbsp-admin', MBSPWC_URL . 'assets/admin.css', [], MBSPWC_VERSION );
            
            wp_localize_script( 'mbsp-admin', 'mbsp_admin', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'mbsp_admin' ),
                'api_url' => MBSPWC_Backend::API_URL,
                'use_vue' => false,
                'i18n'     => [
                    'logged'     => __( 'Đã đăng nhập', 'mb-smart-payment-wc' ),
                    'not_logged' => __( 'Chưa đăng nhập', 'mb-smart-payment-wc' ),
                ],
            ] );
        }
    }

}

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
        // Always use Vue.js interface
        echo '<div id="mbsp-vue-admin"></div>';
    }
    public static function assets( $hook ) {
        if ( strpos( $hook, 'mbsp' ) === false ) return;
        
        // Always use Vue.js and Element Plus
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
    }

}

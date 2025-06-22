<?php
/**
 * Plugin Name: MB Smart Payment WC
 * Plugin URI:  https://danhtrong.com/mb-smart-payment-wc
 * Description: Tích hợp thanh toán tự động WooCommerce qua API ngân hàng MB (MBBank).
 * Version:     1.0.0
 * Author:      Trần Danh Trọng
 * Author URI:  https://danhtrong.com
 * Text Domain: mb-smart-payment-wc
 * Domain Path: /languages
 * License:     GPLv2 or later
 * Requires at least: 6.0
 * Tested up to: 6.5
 */
register_activation_hook( __FILE__, [ 'MBSPWC_DB', 'install' ] );


if ( ! defined( 'ABSPATH' ) ) exit; // Ngăn truy cập trực tiếp

// Định nghĩa hằng số đường dẫn
define( 'MBSPWC_PATH', plugin_dir_path( __FILE__ ) );
define( 'MBSPWC_URL', plugin_dir_url( __FILE__ ) );

define( 'MBSPWC_VERSION', '1.0.0' );
// Nạp sớm file DB để dùng trong activation hook
require_once MBSPWC_PATH . 'includes/class-mbspwc-db.php';

// Nạp text domain
add_action( 'init', function () {
    load_plugin_textdomain( 'mb-smart-payment-wc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Khởi tạo plugin khi WooCommerce sẵn sàng
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'MB Smart Payment WC yêu cầu WooCommerce. Vui lòng cài đặt và kích hoạt WooCommerce.', 'mb-smart-payment-wc' ) . '</p></div>';
        } );
        return;
    }

    // Tự động nạp file class
    require_once MBSPWC_PATH . 'includes/class-mbspwc-gateway.php';
    require_once MBSPWC_PATH . 'includes/class-mbspwc-cron.php';
    require_once MBSPWC_PATH . 'includes/class-mbspwc-backend.php';
    require_once MBSPWC_PATH . 'includes/class-mbspwc-admin.php';
    require_once MBSPWC_PATH . 'includes/class-mbspwc-ajax.php';
    require_once MBSPWC_PATH . 'includes/class-mbspwc-settings.php';
    require_once MBSPWC_PATH . 'includes/class-mbspwc-vietqr.php';
    require_once MBSPWC_PATH . 'includes/class-mbspwc-transactions.php';

    MBSPWC_Admin::init();
    // Đăng ký gateway với WooCommerce
    MBSPWC_Ajax::init();
    add_filter( 'woocommerce_payment_gateways', function ( $methods ) {
        $methods[] = 'WC_Gateway_MBSPWC';
        return $methods;
    } );

    MBSPWC_Cron::init();
    MBSPWC_Settings::init();
}, 20 );

// Thêm lịch phút cho WP-Cron
add_filter( 'cron_schedules', function ( $schedules ) {
    if ( ! isset( $schedules['minute'] ) ) {
        $schedules['minute'] = [
            'interval' => 60,
            'display'  => __( 'Every Minute', 'mb-smart-payment-wc' ),
        ];
    }
    return $schedules;
} );

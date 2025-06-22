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

if ( ! defined( 'ABSPATH' ) ) exit; // Ngăn truy cập trực tiếp

// Định nghĩa đường dẫn plugin
define('MBSPWC_PATH', plugin_dir_path(__FILE__));
define('MBSPWC_URL', plugin_dir_url(__FILE__));

// Kiểm tra WooCommerce đã active chưa
add_action( 'plugins_loaded', 'mbspwc_init_gateway', 11 );
function mbspwc_init_gateway() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>MB Smart Payment WC yêu cầu WooCommerce. Vui lòng cài đặt và kích hoạt WooCommerce.</p></div>';
        });
        return;
    }
}

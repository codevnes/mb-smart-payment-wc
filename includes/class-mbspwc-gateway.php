<?php
/**
 * Class WC_Gateway_MBSPWC
 *
 * Gateway chính.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_MBSPWC extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'mbspwc';
        $this->method_title       = __( 'MBBank Smart Payment', 'mb-smart-payment-wc' );
        $this->method_description = __( 'Thanh toán tự động qua MBBank.', 'mb-smart-payment-wc' );
        $this->has_fields         = false;
        $this->icon               = MBSPWC_URL . 'assets/mbbank.svg';

        // Load settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled'     => [
                'title'       => __( 'Kích hoạt', 'mb-smart-payment-wc' ),
                'type'        => 'checkbox',
                'label'       => __( 'Bật cổng thanh toán', 'mb-smart-payment-wc' ),
                'default'     => 'no',
            ],
            'title'       => [
                'title'       => __( 'Tiêu đề', 'mb-smart-payment-wc' ),
                'type'        => 'text',
                'description' => __( 'Tiêu đề hiển thị cho khách.', 'mb-smart-payment-wc' ),
                'default'     => __( 'Chuyển khoản MBBank', 'mb-smart-payment-wc' ),
            ],
            'description' => [
                'title'       => __( 'Mô tả', 'mb-smart-payment-wc' ),
                'type'        => 'textarea',
                'description' => __( 'Mô tả hiển thị trên trang thanh toán.', 'mb-smart-payment-wc' ),
                'default'     => __( 'Vui lòng chuyển khoản theo hướng dẫn.', 'mb-smart-payment-wc' ),
            ],
        ];
    }

    /**
     * Khi customer chọn gateway & đặt hàng.
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        // Đặt trạng thái chờ thanh toán.
        $order->update_status( 'on-hold', __( 'Chờ thanh toán qua MBBank', 'mb-smart-payment-wc' ) );

        // Sinh QR VietQR
        if ( class_exists( 'MBSPWC_VietQR' ) ) {
            $qr_url = MBSPWC_VietQR::generate( '0123456789', 'Shop', $order->get_total(), $order_id );
            $order->add_order_note( sprintf( __( 'QR thanh toán: %s', 'mb-smart-payment-wc' ), $qr_url ) );
        }

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_order_received_url(),
        ];    }
}

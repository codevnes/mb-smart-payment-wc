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
        add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ] );
        add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );
        add_action( 'wp_enqueue_scripts', [ $this, 'frontend_styles' ] );
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

        // Lấy thông tin tài khoản từ settings
        $settings = get_option( 'mbspwc_settings', [] );
        $account_no = $settings['acc_no'] ?? '0123456789';
        $account_name = $settings['acc_name'] ?? 'Shop';

        // Sinh QR VietQR
        if ( class_exists( 'MBSPWC_VietQR' ) ) {
            $qr_url = MBSPWC_VietQR::generate( $account_no, $account_name, $order->get_total(), $order_id );
            $order->update_meta_data( '_mbspwc_qr_url', $qr_url );
            $order->save();
            $order->add_order_note( sprintf( __( 'QR thanh toán: %s', 'mb-smart-payment-wc' ), $qr_url ) );
        }

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_order_received_url(),
        ];
    }

    /**
     * Hiển thị thông tin thanh toán trên trang thank you
     */
    public function thankyou_page( $order_id ) {
        if ( ! $order_id ) return;

        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_payment_method() !== $this->id ) return;

        $settings = get_option( 'mbspwc_settings', [] );
        $account_no = $settings['acc_no'] ?? '0123456789';
        $account_name = $settings['acc_name'] ?? 'Shop';
        $qr_url = $order->get_meta( '_mbspwc_qr_url' );

        echo '<div class="mbspwc-payment-info">';
        echo '<h2>' . __( 'Thông tin thanh toán', 'mb-smart-payment-wc' ) . '</h2>';
        echo '<div class="mbspwc-payment-details">';
        echo '<p><strong>' . __( 'Số tài khoản:', 'mb-smart-payment-wc' ) . '</strong> ' . esc_html( $account_no ) . '</p>';
        echo '<p><strong>' . __( 'Chủ tài khoản:', 'mb-smart-payment-wc' ) . '</strong> ' . esc_html( $account_name ) . '</p>';
        echo '<p><strong>' . __( 'Số tiền:', 'mb-smart-payment-wc' ) . '</strong> ' . wc_price( $order->get_total() ) . '</p>';
        echo '<p><strong>' . __( 'Nội dung:', 'mb-smart-payment-wc' ) . '</strong> ORDER-' . $order_id . '</p>';
        
        if ( $qr_url ) {
            echo '<div class="mbspwc-qr-code">';
            echo '<h3>' . __( 'Quét mã QR để thanh toán', 'mb-smart-payment-wc' ) . '</h3>';
            echo '<img src="' . esc_url( $qr_url ) . '" alt="QR Code" style="max-width: 300px; height: auto;" />';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Thêm thông tin thanh toán vào email
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        if ( ! $order || $order->get_payment_method() !== $this->id || $sent_to_admin ) return;

        $settings = get_option( 'mbspwc_settings', [] );
        $account_no = $settings['acc_no'] ?? '0123456789';
        $account_name = $settings['acc_name'] ?? 'Shop';

        if ( $plain_text ) {
            echo "\n" . __( 'Thông tin thanh toán:', 'mb-smart-payment-wc' ) . "\n";
            echo __( 'Số tài khoản:', 'mb-smart-payment-wc' ) . ' ' . $account_no . "\n";
            echo __( 'Chủ tài khoản:', 'mb-smart-payment-wc' ) . ' ' . $account_name . "\n";
            echo __( 'Số tiền:', 'mb-smart-payment-wc' ) . ' ' . wc_price( $order->get_total() ) . "\n";
            echo __( 'Nội dung:', 'mb-smart-payment-wc' ) . ' ORDER-' . $order->get_id() . "\n\n";
        } else {
            echo '<h2>' . __( 'Thông tin thanh toán', 'mb-smart-payment-wc' ) . '</h2>';
            echo '<p><strong>' . __( 'Số tài khoản:', 'mb-smart-payment-wc' ) . '</strong> ' . esc_html( $account_no ) . '</p>';
            echo '<p><strong>' . __( 'Chủ tài khoản:', 'mb-smart-payment-wc' ) . '</strong> ' . esc_html( $account_name ) . '</p>';
            echo '<p><strong>' . __( 'Số tiền:', 'mb-smart-payment-wc' ) . '</strong> ' . wc_price( $order->get_total() ) . '</p>';
            echo '<p><strong>' . __( 'Nội dung:', 'mb-smart-payment-wc' ) . '</strong> ORDER-' . $order->get_id() . '</p>';
        }
    }

    /**
     * Enqueue frontend styles
     */
    public function frontend_styles() {
        if ( is_wc_endpoint_url( 'order-received' ) ) {
            wp_enqueue_style( 'mbspwc-frontend', MBSPWC_URL . 'assets/admin.css', [], MBSPWC_VERSION );
        }
    }
}

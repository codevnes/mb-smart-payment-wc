<?php
/**
 * Lên lịch cron kiểm tra giao dịch.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBSPWC_Cron {
    const HOOK = 'mbspwc_check_transactions';

    public static function init() {
        add_action( 'wp', [ __CLASS__, 'schedule_event' ] );
        add_action( self::HOOK, [ __CLASS__, 'run' ] );
    }

    public static function schedule_event() {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time(), 'minute', self::HOOK );
        }
    }

    public static function run() {
        $backend_opts = get_option( 'mbspwc_backend', [] );
        $settings = get_option( 'mbspwc_settings', [] );

        $token = $backend_opts['token'] ?? '';
        $expires = $backend_opts['expires'] ?? 0;

        // Check if token is expired
        if ( empty( $token ) || $expires < time() ) {
            // Try to refresh token
            $refresh_token = $backend_opts['refresh_token'] ?? '';
            if ( $refresh_token ) {
                $refresh_result = MBSPWC_Backend::refresh( $refresh_token );
                if ( ! is_wp_error( $refresh_result ) && ! empty( $refresh_result['token'] ) ) {
                    $backend_opts['token'] = $refresh_result['token'];
                    $backend_opts['expires'] = time() + 3600; // 1 hour
                    update_option( 'mbspwc_backend', $backend_opts );
                    $token = $refresh_result['token'];
                } else {
                    // Refresh failed, clear backend options
                    delete_option( 'mbspwc_backend' );
                    return;
                }
            } else {
                return; // No token and no refresh token
            }
        }

        if ( empty( $token ) ) {
            return;
        }

        // Get account number from settings
        $account_no = $settings['acc_no'] ?? '';
        if ( empty( $account_no ) ) {
            return; // No account number configured
        }

        // Get transactions from last 24 hours
        $args = [
            'accountNumber' => $account_no,
            'fromDate' => date( 'Y-m-d', strtotime( '-1 day' ) ),
            'toDate' => date( 'Y-m-d' ),
        ];

        $result = MBSPWC_Backend::transactions( $token, $args );
        
        if ( is_wp_error( $result ) ) {
            return;
        }

        $transactions = $result['items'] ?? $result;
        if ( ! is_array( $transactions ) ) {
            return;
        }

        // Match orders
        foreach ( $transactions as $trans ) {
            self::match_order( $trans );
        }
    }

    protected static function match_order( $trans ) {
        global $wpdb;

        $order_id = null;
        $description = $trans['description'] ?? $trans['transactionContent'] ?? '';
        $trans_id = $trans['trans_id'] ?? $trans['transactionId'] ?? '';
        $amount = $trans['amount'] ?? $trans['creditAmount'] ?? 0;

        // Extract order ID from description
        if ( preg_match( '/ORDER-(\d+)/', $description, $m ) ) {
            $order_id = (int) $m[1];
        }

        if ( ! $order_id ) {
            return;
        }

        // Check if this transaction was already processed
        $table_name = $wpdb->prefix . MBSPWC_DB::TABLE;
        $existing = $wpdb->get_var( $wpdb->prepare( 
            "SELECT id FROM {$table_name} WHERE trans_id = %s", 
            $trans_id 
        ) );
        
        if ( $existing ) {
            return; // Already processed
        }

        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_status() !== 'on-hold' ) {
            return;
        }

        // Check if payment method is our gateway
        if ( $order->get_payment_method() !== 'mbspwc' ) {
            return;
        }

        $order_amount = $order->get_total();
        if ( abs( $order_amount - $amount ) > 100 ) {
            return; // Amount mismatch (tolerance: 100 VND)
        }

        // Mark as paid
        $order->payment_complete( $trans_id );
        $order->add_order_note( sprintf( 
            __( 'Thanh toán đã được xác nhận tự động. Mã giao dịch: %s, Số tiền: %s', 'mb-smart-payment-wc' ), 
            $trans_id, 
            wc_price( $amount ) 
        ) );

        // Log to database
        if ( class_exists( 'MBSPWC_DB' ) ) {
            MBSPWC_DB::log( $order_id, $trans, 'matched' );
        }
    }
}

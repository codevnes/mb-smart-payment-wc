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
        $options = get_option( 'mbspwc_settings', [] );

        $token = $options['token'] ?? '';
        $expires = $options['expires'] ?? 0;

        if ( empty( $token ) || $expires < time() ) {
            // Refresh token
            $creds   = $options['creds'] ?? [];
            $login   = MBSPWC_API::login( $creds['user'] ?? '', $creds['pass'] ?? '' );
            $token   = $login['token'] ?? '';
            $expires = $login['expires_at'] ?? 0;
            $options['token']   = $token;
            $options['expires'] = $expires;
            update_option( 'mbspwc_settings', $options );
        }

        if ( empty( $token ) ) {
            return;
        }

        $args     = [];
        $transactions = MBSPWC_API::get_transactions( $token, $args );

        // So khop
        foreach ( $transactions as $trans ) {
            self::match_order( $trans );
        }
    }

    protected static function match_order( $trans ) {
        global $wpdb;

        $order_id = null;

        if ( preg_match( '/ORDER-(\d+)/', $trans['description'], $m ) ) {
            $order_id = (int) $m[1];
        }

        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_status() !== 'on-hold' ) {
            return;
        }

        $amount = $order->get_total();
        if ( abs( $amount - $trans['amount'] ) > 100 ) {
            return;
        }

        // Mark paid
        $order->payment_complete( $trans['trans_id'] );

        // Log vào bảng riêng
        if ( class_exists( 'MBSPWC_DB' ) ) {
            MBSPWC_DB::log( $order_id, $trans, 'matched' );
        }
    }
}

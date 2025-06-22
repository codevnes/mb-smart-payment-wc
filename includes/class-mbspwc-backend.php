<?php
/**
 * Kết nối Backend Node (API_URL localhost:3005)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MBSPWC_Backend {
    const API_URL = 'http://localhost:3005';

    protected static function request( $method, $path, $args = [] ) {
        $url  = trailingslashit( self::API_URL ) . ltrim( $path, '/' );
        $body = isset( $args['body'] ) ? wp_json_encode( $args['body'] ) : null;
        $headers = [ 'Content-Type' => 'application/json' ];
        if ( ! empty( $args['token'] ) ) {
            $headers['Authorization'] = 'Bearer ' . $args['token'];
        }
        $response = wp_remote_request( $url, [
            'method'  => $method,
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 15,
        ] );
        if ( is_wp_error( $response ) ) return $response;
        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $code >= 200 && $code < 300 && ! empty( $data ) ) {
            return $data;
        }
        return new WP_Error( 'mbsb_api_error', __( 'Lỗi gọi API', 'mb-smart-payment-wc' ), $data );
    }

    public static function login( $user, $pass ) {
        return self::request( 'POST', '/api/auth/login', [ 'body' => [ 'mbUsername' => $user, 'mbPassword' => $pass ] ] );
    }

    public static function refresh( $refresh_token ) {
        return self::request( 'POST', '/api/auth/refresh', [ 'body' => [ 'refreshToken' => $refresh_token ] ] );
    }

    public static function logout( $token ) {
        return self::request( 'POST', '/api/auth/logout', [ 'token' => $token ] );
    }

    public static function status( $token ) {
        return self::request( 'GET', '/api/auth/status', [ 'token' => $token ] );
    }

    public static function transactions( $token, $args ) {
        $query = http_build_query( $args );
        return self::request( 'GET', '/api/mbbank/transactions?' . $query, [ 'token' => $token ] );
    }
}
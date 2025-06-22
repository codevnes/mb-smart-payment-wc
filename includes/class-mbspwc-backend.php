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
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        $body_content = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_content, true );
        
        // Log for debugging
        error_log( "MBSPWC API Request: {$method} {$url}" );
        error_log( "MBSPWC API Response Code: {$code}" );
        error_log( "MBSPWC API Response Body: {$body_content}" );
        
        // Handle successful responses
        if ( $code >= 200 && $code < 300 ) {
            return $data ?: [];
        }
        
        // Handle error responses
        $error_message = __( 'Lỗi kết nối API', 'mb-smart-payment-wc' );
        
        if ( $data ) {
            if ( isset( $data['message'] ) ) {
                $error_message = $data['message'];
            } elseif ( isset( $data['error'] ) ) {
                $error_message = $data['error'];
            } elseif ( isset( $data['msg'] ) ) {
                $error_message = $data['msg'];
            }
        }
        
        return new WP_Error( 'mbsb_api_error', $error_message, [
            'status_code' => $code,
            'response_data' => $data
        ] );
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
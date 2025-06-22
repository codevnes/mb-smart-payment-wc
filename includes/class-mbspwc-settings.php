<?php
/**
 * Class MBSPWC_Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBSPWC_Settings {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register' ] );
    }

    public static function menu() {
        add_submenu_page(
            'woocommerce',
            __( 'MB Smart Payment', 'mb-smart-payment-wc' ),
            __( 'MB Smart Payment', 'mb-smart-payment-wc' ),
            'manage_woocommerce',
            'mbspwc-settings',
            [ __CLASS__, 'render' ]
        );
    }

    public static function register() {
        register_setting( 'mbspwc_group', 'mbspwc_settings' );

        add_settings_section( 'mbspwc_api', __( 'Thông tin API', 'mb-smart-payment-wc' ), null, 'mbspwc_group' );

        add_settings_field( 'user', __( 'User', 'mb-smart-payment-wc' ), [ __CLASS__, 'field_text' ], 'mbspwc_group', 'mbspwc_api', [ 'id' => 'user' ] );
        add_settings_field( 'pass', __( 'Password', 'mb-smart-payment-wc' ), [ __CLASS__, 'field_password' ], 'mbspwc_group', 'mbspwc_api', [ 'id' => 'pass' ] );
        // Account info
        add_settings_field( 'acc_no', __( 'Số tài khoản', 'mb-smart-payment-wc' ), [ __CLASS__, 'field_text' ], 'mbspwc_group', 'mbspwc_api', [ 'id' => 'acc_no' ] );
        add_settings_field( 'acc_name', __( 'Chủ tài khoản', 'mb-smart-payment-wc' ), [ __CLASS__, 'field_text' ], 'mbspwc_group', 'mbspwc_api', [ 'id' => 'acc_name' ] );
    }

    public static function field_text( $args ) {
        $opts = get_option( 'mbspwc_settings', [] );
        printf( '<input type="text" name="mbspwc_settings[%s]" value="%s" class="regular-text"/>', esc_attr( $args['id'] ), esc_attr( $opts[ $args['id'] ] ?? '' ) );
    }

    public static function field_password( $args ) {
        $opts = get_option( 'mbspwc_settings', [] );
        printf( '<input type="password" name="mbspwc_settings[%s]" value="%s" class="regular-text"/>', esc_attr( $args['id'] ), esc_attr( $opts[ $args['id'] ] ?? '' ) );
    }

    public static function render() {
        echo '<div class="wrap"><h1>' . esc_html__( 'MB Smart Payment Settings', 'mb-smart-payment-wc' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'mbspwc_group' );
        do_settings_sections( 'mbspwc_group' );
        submit_button();
        echo '</form></div>';
    }
}

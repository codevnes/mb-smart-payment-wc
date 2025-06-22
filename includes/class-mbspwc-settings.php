<?php
/**
 * Class MBSPWC_Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBSPWC_Settings {
    public static function init() {
        // add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
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
        
        // UI Settings
        add_settings_section( 'mbspwc_ui', __( 'Giao diện', 'mb-smart-payment-wc' ), null, 'mbspwc_group' );
        add_settings_field( 'use_vue', __( 'Sử dụng giao diện hiện đại', 'mb-smart-payment-wc' ), [ __CLASS__, 'field_checkbox' ], 'mbspwc_group', 'mbspwc_ui', [ 'id' => 'use_vue' ] );
    }

    public static function field_text( $args ) {
        $opts = get_option( 'mbspwc_settings', [] );
        printf( '<input type="text" name="mbspwc_settings[%s]" value="%s" class="regular-text"/>', esc_attr( $args['id'] ), esc_attr( $opts[ $args['id'] ] ?? '' ) );
    }

    public static function field_password( $args ) {
        $opts = get_option( 'mbspwc_settings', [] );
        printf( '<input type="password" name="mbspwc_settings[%s]" value="%s" class="regular-text"/>', esc_attr( $args['id'] ), esc_attr( $opts[ $args['id'] ] ?? '' ) );
    }
    
    public static function field_checkbox( $args ) {
        $opts = get_option( 'mbspwc_settings', [] );
        $checked = isset( $opts[ $args['id'] ] ) && $opts[ $args['id'] ] ? 'checked' : '';
        printf( '<input type="checkbox" name="mbspwc_settings[%s]" value="1" %s/> <label for="mbspwc_settings[%s]">%s</label>', 
            esc_attr( $args['id'] ), 
            $checked, 
            esc_attr( $args['id'] ),
            __( 'Sử dụng Vue.js và Element Plus cho giao diện admin hiện đại', 'mb-smart-payment-wc' )
        );
    }

    public static function render() {
        $settings = get_option( 'mbspwc_settings', [] );
        $use_vue = isset( $settings['use_vue'] ) && $settings['use_vue'];
        
        if ( $use_vue ) {
            echo '<div id="mbsp-vue-admin"></div>';
            return;
        }
        
        // Fallback to traditional PHP rendering
        echo '<div class="mbsp-admin-wrap">';
        
        // Header
        echo '<div class="mbsp-admin-header">';
        echo '<h1>' . esc_html__( 'Cài đặt MB Smart Payment', 'mb-smart-payment-wc' ) . '</h1>';
        echo '<p class="subtitle">' . esc_html__( 'Cấu hình thông tin tài khoản và API MBBank', 'mb-smart-payment-wc' ) . '</p>';
        echo '</div>';

        echo '<div class="mbsp-admin-content">';
        echo '<form method="post" action="options.php">';
        settings_fields( 'mbspwc_group' );
        
        echo '<div class="mbsp-grid">';
        
        // API Settings Card
        echo '<div class="mbsp-card mbsp-card-full">';
        echo '<div class="mbsp-card-header">';
        echo '<h2>' . esc_html__( 'Thông tin tài khoản MBBank', 'mb-smart-payment-wc' ) . '</h2>';
        echo '</div>';
        echo '<div class="mbsp-card-body">';
        
        $opts = get_option( 'mbspwc_settings', [] );
        
        echo '<div class="mbsp-form-group">';
        echo '<label class="mbsp-form-label" for="mbsp_user">' . __( 'Tên đăng nhập', 'mb-smart-payment-wc' ) . '</label>';
        echo '<input type="text" id="mbsp_user" name="mbspwc_settings[user]" value="' . esc_attr( $opts['user'] ?? '' ) . '" class="mbsp-form-input" placeholder="' . esc_attr__( 'Nhập tên đăng nhập MBBank', 'mb-smart-payment-wc' ) . '">';
        echo '<div class="mbsp-form-description">' . __( 'Tên đăng nhập internet banking MBBank của bạn', 'mb-smart-payment-wc' ) . '</div>';
        echo '</div>';
        
        echo '<div class="mbsp-form-group">';
        echo '<label class="mbsp-form-label" for="mbsp_pass">' . __( 'Mật khẩu', 'mb-smart-payment-wc' ) . '</label>';
        echo '<input type="password" id="mbsp_pass" name="mbspwc_settings[pass]" value="' . esc_attr( $opts['pass'] ?? '' ) . '" class="mbsp-form-input" placeholder="' . esc_attr__( 'Nhập mật khẩu', 'mb-smart-payment-wc' ) . '">';
        echo '<div class="mbsp-form-description">' . __( 'Mật khẩu internet banking MBBank của bạn', 'mb-smart-payment-wc' ) . '</div>';
        echo '</div>';
        
        echo '<div class="mbsp-form-group">';
        echo '<label class="mbsp-form-label" for="mbsp_acc_no">' . __( 'Số tài khoản', 'mb-smart-payment-wc' ) . '</label>';
        echo '<input type="text" id="mbsp_acc_no" name="mbspwc_settings[acc_no]" value="' . esc_attr( $opts['acc_no'] ?? '' ) . '" class="mbsp-form-input" placeholder="' . esc_attr__( 'Nhập số tài khoản nhận tiền', 'mb-smart-payment-wc' ) . '">';
        echo '<div class="mbsp-form-description">' . __( 'Số tài khoản MBBank để nhận thanh toán từ khách hàng', 'mb-smart-payment-wc' ) . '</div>';
        echo '</div>';
        
        echo '<div class="mbsp-form-group">';
        echo '<label class="mbsp-form-label" for="mbsp_acc_name">' . __( 'Tên chủ tài khoản', 'mb-smart-payment-wc' ) . '</label>';
        echo '<input type="text" id="mbsp_acc_name" name="mbspwc_settings[acc_name]" value="' . esc_attr( $opts['acc_name'] ?? '' ) . '" class="mbsp-form-input" placeholder="' . esc_attr__( 'Nhập tên chủ tài khoản', 'mb-smart-payment-wc' ) . '">';
        echo '<div class="mbsp-form-description">' . __( 'Tên chủ tài khoản hiển thị trên QR code và thông tin thanh toán', 'mb-smart-payment-wc' ) . '</div>';
        echo '</div>';
        
        echo '<button type="submit" class="mbsp-button">' . __( 'Lưu cài đặt', 'mb-smart-payment-wc' ) . '</button>';
        
        echo '</div>';
        echo '</div>';
        
        // Security Notice Card
        echo '<div class="mbsp-card mbsp-card-full">';
        echo '<div class="mbsp-card-header">';
        echo '<h2>' . esc_html__( 'Lưu ý bảo mật', 'mb-smart-payment-wc' ) . '</h2>';
        echo '</div>';
        echo '<div class="mbsp-card-body">';
        echo '<div class="mbsp-notice warning">';
        echo '<div class="mbsp-notice-content">';
        echo '<p><strong>' . __( 'Quan trọng:', 'mb-smart-payment-wc' ) . '</strong></p>';
        echo '<ul style="margin: 8px 0 0 20px;">';
        echo '<li>' . __( 'Thông tin đăng nhập được mã hóa và lưu trữ an toàn', 'mb-smart-payment-wc' ) . '</li>';
        echo '<li>' . __( 'Chỉ sử dụng tài khoản có quyền truy cập hạn chế', 'mb-smart-payment-wc' ) . '</li>';
        echo '<li>' . __( 'Thường xuyên kiểm tra và thay đổi mật khẩu', 'mb-smart-payment-wc' ) . '</li>';
        echo '<li>' . __( 'Không chia sẻ thông tin đăng nhập với bên thứ ba', 'mb-smart-payment-wc' ) . '</li>';
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // grid
        echo '</form>';
        echo '</div>'; // content
        echo '</div>'; // wrap
    }
}

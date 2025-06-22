<?php
// Cleanup
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'mbspwc_settings' );
delete_option( 'mbspwc_logs' );

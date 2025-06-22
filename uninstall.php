<?php
// Cleanup
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete options
delete_option( 'mbspwc_settings' );
delete_option( 'mbspwc_backend' );
delete_option( 'mbspwc_logs' );

// Drop custom table
global $wpdb;
$table_name = $wpdb->prefix . 'mbspwc_transactions';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Clear scheduled cron
wp_clear_scheduled_hook( 'mbspwc_check_transactions' );

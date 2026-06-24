<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
delete_option( 'postwave_settings' );
delete_option( 'postwave_mail_log' );
delete_option( 'postwave_retry_queue' );
delete_option( 'postwave_accounts' );
delete_option( 'postwave_routing_rules' );
wp_clear_scheduled_hook( 'postwave_process_retry_queue' );

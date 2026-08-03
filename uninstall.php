<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'postwave_settings' );           // POSTWAVE_OPTION_KEY
delete_option( 'postwave_mail_log' );           // POSTWAVE_LOG_OPTION
delete_option( 'postwave_retry_queue' );        // POSTWAVE_RETRY_OPTION
delete_option( 'postwave_accounts' );           // POSTWAVE_ACCOUNTS_OPTION
delete_option( 'postwave_routing_rules' );      // POSTWAVE_ROUTING_OPTION
wp_clear_scheduled_hook( 'postwave_process_retry_queue' ); // Postwave_Retry_Queue::CRON_HOOK

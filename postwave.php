<?php
/**
 * Plugin Name: Postwave JMAP
 * Plugin URI:  https://github.com/Tahhan-nl/postwave
 * Description: Sends WordPress emails via the modern JMAP protocol (RFC 8620/8621). No SMTP ports needed — works with Stalwart, Fastmail, Cyrus and more. Includes live connection testing and full mail logging.
 * Version:     1.0.0
 * Author:      Tahhan
 * Author URI:  https://tahhan.nl
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: postwave
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.7
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'POSTWAVE_VERSION',       '1.0.0' );
define( 'POSTWAVE_PLUGIN_DIR',    plugin_dir_path( __FILE__ ) );
define( 'POSTWAVE_PLUGIN_URL',    plugin_dir_url( __FILE__ ) );
define( 'POSTWAVE_PLUGIN_BASE',   plugin_basename( __FILE__ ) );
define( 'POSTWAVE_OPTION_KEY',    'postwave_settings' );
define( 'POSTWAVE_LOG_OPTION',    'postwave_mail_log' );

require_once POSTWAVE_PLUGIN_DIR . 'includes/class-jmap-client.php';
require_once POSTWAVE_PLUGIN_DIR . 'includes/class-mail-log.php';
require_once POSTWAVE_PLUGIN_DIR . 'includes/class-mailer.php';
require_once POSTWAVE_PLUGIN_DIR . 'includes/class-admin.php';

function postwave_init() {
	if ( is_admin() ) {
		new Postwave_Admin();
	}

	$options = get_option( POSTWAVE_OPTION_KEY, array() );
	if ( ! empty( $options['enabled'] ) && ! empty( $options['server_url'] ) ) {
		new Postwave_Mailer( $options );
	}
}
add_action( 'plugins_loaded', 'postwave_init' );

function postwave_action_links( $links ) {
	$url  = esc_url( admin_url( 'admin.php?page=postwave' ) );
	array_unshift( $links, '<a href="' . $url . '">' . esc_html__( 'Settings', 'postwave' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . POSTWAVE_PLUGIN_BASE, 'postwave_action_links' );

function postwave_activate() {
	if ( false === get_option( POSTWAVE_OPTION_KEY ) ) {
		add_option( POSTWAVE_OPTION_KEY, array(
			'enabled'        => 0,
			'server_url'     => '',
			'username'       => '',
			'password'       => '',
			'from_name'      => get_bloginfo( 'name' ),
			'from_email'     => get_bloginfo( 'admin_email' ),
			'test_recipient' => get_bloginfo( 'admin_email' ),
		) );
	}
}
register_activation_hook( __FILE__, 'postwave_activate' );

function postwave_deactivate() {}
register_deactivation_hook( __FILE__, 'postwave_deactivate' );

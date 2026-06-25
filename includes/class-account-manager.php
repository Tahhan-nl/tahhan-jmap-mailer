<?php
/**
 * Tahhan JMAP Mailer Account Manager — stores and retrieves multiple JMAP account configs.
 *
 * @package Tahhan_JMAP_Mailer
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postwave_Account_Manager {

	/**
	 * Get all accounts as an associative array keyed by ID.
	 * @return array
	 */
	public static function get_all() {
		$accounts = get_option( POSTWAVE_ACCOUNTS_OPTION, array() );
		return is_array( $accounts ) ? $accounts : array();
	}

	/**
	 * Get a single account by ID.
	 * @param string $id
	 * @return array|null
	 */
	public static function get( $id ) {
		$accounts = self::get_all();
		return isset( $accounts[ $id ] ) ? $accounts[ $id ] : null;
	}

	/**
	 * Save (insert or update) an account.
	 * Generates an ID if not present.
	 * @param array $data Account data (all fields optional except those passed).
	 * @return string The account ID.
	 */
	public static function save( array $data ) {
		$accounts = self::get_all();

		$id = sanitize_key( isset( $data['id'] ) ? $data['id'] : '' );
		if ( empty( $id ) ) {
			$id = 'acc_' . substr( md5( microtime( true ) . wp_rand() ), 0, 10 );
		}

		$existing = isset( $accounts[ $id ] ) ? $accounts[ $id ] : array();

		// Preserve password if not sent (empty string means "keep existing").
		$password = isset( $data['password'] ) ? $data['password'] : '';
		if ( '' === $password && ! empty( $existing['password'] ) ) {
			$password = $existing['password'];
		}

		$accounts[ $id ] = array(
			'id'             => $id,
			'name'           => sanitize_text_field( isset( $data['name'] ) ? $data['name'] : __( 'Account', 'tahhan-jmap-mailer' ) ),
			'server_url'     => esc_url_raw( trim( isset( $data['server_url'] ) ? $data['server_url'] : '' ) ),
			'username'       => sanitize_text_field( isset( $data['username'] ) ? $data['username'] : '' ),
			'password'       => $password,
			'identity_id'    => sanitize_text_field( isset( $data['identity_id'] )    ? $data['identity_id']    : '' ),
			'identity_name'  => sanitize_text_field( isset( $data['identity_name'] )  ? $data['identity_name']  : '' ),
			'identity_email' => sanitize_email(      isset( $data['identity_email'] ) ? $data['identity_email'] : '' ),
			'is_primary'     => ! empty( $data['is_primary'] ),
			'last_test_ok'   => isset( $data['last_test_ok'] )  ? $data['last_test_ok']  : ( isset( $existing['last_test_ok'] )  ? $existing['last_test_ok']  : null ),
			'last_tested'    => isset( $data['last_tested'] )   ? $data['last_tested']   : ( isset( $existing['last_tested'] )   ? $existing['last_tested']   : null ),
		);

		update_option( POSTWAVE_ACCOUNTS_OPTION, $accounts, false );
		return $id;
	}

	/**
	 * Delete an account by ID. Cannot delete the primary account.
	 * @param string $id
	 * @return bool
	 */
	public static function delete( $id ) {
		if ( 'acc_primary' === $id ) {
			return false;
		}
		$accounts = self::get_all();
		if ( ! isset( $accounts[ $id ] ) ) {
			return false;
		}
		unset( $accounts[ $id ] );
		update_option( POSTWAVE_ACCOUNTS_OPTION, $accounts, false );
		return true;
	}

	/**
	 * Get the primary account.
	 * @return array|null
	 */
	public static function get_primary() {
		return self::get( 'acc_primary' );
	}

	/**
	 * Migrate existing postwave_settings credentials into the primary account.
	 * Safe to call multiple times — skips if acc_primary already exists.
	 */
	public static function maybe_migrate() {
		$accounts = self::get_all();
		if ( isset( $accounts['acc_primary'] ) ) {
			return; // already migrated
		}

		$settings = get_option( POSTWAVE_OPTION_KEY, array() );

		self::save( array(
			'id'             => 'acc_primary',
			'name'           => __( 'Primary', 'tahhan-jmap-mailer' ),
			'server_url'     => isset( $settings['server_url'] )     ? $settings['server_url']     : '',
			'username'       => isset( $settings['username'] )       ? $settings['username']       : '',
			'password'       => isset( $settings['password'] )       ? $settings['password']       : '',
			'identity_id'    => isset( $settings['identity_id'] )    ? $settings['identity_id']    : '',
			'identity_name'  => isset( $settings['identity_name'] )  ? $settings['identity_name']  : '',
			'identity_email' => isset( $settings['identity_email'] ) ? $settings['identity_email'] : '',
			'is_primary'     => true,
		) );
	}

	/**
	 * Sync primary account credentials back into postwave_settings for backward compat.
	 * Call after saving acc_primary.
	 */
	public static function sync_primary_to_settings() {
		$primary = self::get( 'acc_primary' );
		if ( ! $primary ) {
			return;
		}
		$settings = get_option( POSTWAVE_OPTION_KEY, array() );
		$settings['server_url']     = $primary['server_url'];
		$settings['username']       = $primary['username'];
		$settings['password']       = $primary['password'];
		$settings['identity_id']    = $primary['identity_id'];
		$settings['identity_name']  = $primary['identity_name'];
		$settings['identity_email'] = $primary['identity_email'];
		update_option( POSTWAVE_OPTION_KEY, $settings );
	}

	/**
	 * Update test result for an account.
	 * @param string $id
	 * @param bool   $ok
	 */
	public static function set_test_result( $id, $ok ) {
		$accounts = self::get_all();
		if ( ! isset( $accounts[ $id ] ) ) {
			return;
		}
		$accounts[ $id ]['last_test_ok'] = (bool) $ok;
		$accounts[ $id ]['last_tested']  = time();
		update_option( POSTWAVE_ACCOUNTS_OPTION, $accounts, false );
	}
}

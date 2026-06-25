<?php
/**
 * Tahhan JMAP Mailer Open Tracker — privacy-first, opt-in email open pixel tracking.
 *
 * @package Tahhan_JMAP_Mailer
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postwave_Open_Tracker {

	const REST_NAMESPACE = 'postwave/v1';
	const REST_ROUTE     = '/pixel/(?P<token>[a-f0-9]{64})';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	public static function register_route() {
		register_rest_route( self::REST_NAMESPACE, self::REST_ROUTE, array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'handle_pixel' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'token' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function( $v ) {
						return (bool) preg_match( '/^[a-f0-9]{64}$/', $v );
					},
				),
			),
		) );
	}

	/**
	 * Generate a secure HMAC tracking token for a log entry.
	 *
	 * @param string $entry_id
	 * @return string 64-char hex token
	 */
	public static function generate_token( $entry_id ) {
		return hash_hmac( 'sha256', $entry_id, wp_salt( 'auth' ) );
	}

	/**
	 * Build the pixel URL for a log entry.
	 *
	 * @param string $entry_id
	 * @return string
	 */
	public static function pixel_url( $entry_id ) {
		$token = self::generate_token( $entry_id );
		return rest_url( self::REST_NAMESPACE . '/pixel/' . $token );
	}

	/**
	 * Inject a 1×1 tracking pixel into an HTML email body.
	 *
	 * @param string $html     Email HTML body.
	 * @param string $entry_id Log entry ID.
	 * @return string Modified HTML.
	 */
	public static function inject_pixel( $html, $entry_id ) {
		$url = esc_url( self::pixel_url( $entry_id ) );
		$img = '<img src="' . $url . '" width="1" height="1" alt="" style="display:none;border:0;outline:0;">';

		if ( false !== stripos( $html, '</body>' ) ) {
			$html = str_ireplace( '</body>', $img . '</body>', $html );
		} else {
			$html .= $img;
		}

		return $html;
	}

	/**
	 * REST callback: return a 1×1 transparent GIF and mark the email as opened.
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_pixel( $request ) {
		$token = $request->get_param( 'token' );

		// Find the matching log entry.
		$entries = Postwave_Mail_Log::get_entries();
		foreach ( $entries as $entry ) {
			if ( ! empty( $entry['tracking_token'] ) && hash_equals( $entry['tracking_token'], $token ) ) {
				if ( empty( $entry['opened_at'] ) ) {
					Postwave_Mail_Log::update_by_id( $entry['id'], array( 'opened_at' => time() ) );
				}
				break;
			}
		}

		// Return a 1×1 transparent GIF — no caching.
		header( 'Content-Type: image/gif' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: Thu, 01 Jan 1970 00:00:00 GMT' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
		exit;
	}
}

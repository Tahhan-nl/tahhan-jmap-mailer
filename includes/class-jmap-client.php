<?php
/**
 * Tahhan JMAP Mailer Client — session discovery, API requests, blob uploads.
 *
 * @package Tahhan_JMAP_Mailer
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postwave_JMAP_Client {

	private $server_url;
	private $auth_header;
	private $api_url;
	private $upload_url;
	private $account_id;
	private $session;

	public function __construct( $server_url, $username, $password ) {
		$this->server_url  = rtrim( $server_url, '/' );
		$this->auth_header = 'Basic ' . base64_encode( $username . ':' . $password );
	}

	/**
	 * Discover the JMAP session from .well-known/jmap.
	 *
	 * @return true|WP_Error
	 */
	public function discover_session() {
		$url      = $this->server_url . '/.well-known/jmap';
		$response = $this->fetch_session_document( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error(
				'postwave_session_http',
				/* translators: %d: HTTP status code */
				sprintf( __( 'Session discovery failed with HTTP %d.', 'tahhan-jmap-mailer' ), $code )
			);
		}

		$body          = wp_remote_retrieve_body( $response );
		$this->session = json_decode( $body, true );

		if ( ! is_array( $this->session ) ) {
			return new WP_Error( 'postwave_session_json', __( 'Session response is not valid JSON.', 'tahhan-jmap-mailer' ) );
		}

		if ( empty( $this->session['apiUrl'] ) ) {
			return new WP_Error( 'postwave_session_api', __( 'Session response is missing apiUrl.', 'tahhan-jmap-mailer' ) );
		}

		$this->session['apiUrl'] = $this->normalize_url( $this->session['apiUrl'] );
		if ( isset( $this->session['uploadUrl'] ) ) {
			$this->session['uploadUrl'] = $this->normalize_url( $this->session['uploadUrl'] );
		}

		$this->api_url    = $this->session['apiUrl'];
		$this->upload_url = $this->session['uploadUrl'] ?? null;

		if ( ! empty( $this->session['primaryAccounts']['urn:ietf:params:jmap:mail'] ) ) {
			$this->account_id = $this->session['primaryAccounts']['urn:ietf:params:jmap:mail'];
		} else {
			return new WP_Error( 'postwave_no_account', __( 'No primary mail account found in the JMAP session.', 'tahhan-jmap-mailer' ) );
		}

		return true;
	}

	/**
	 * Follow redirects manually so Authorization headers are preserved.
	 */
	private function fetch_session_document( $url, $depth = 3 ) {
		$response = wp_remote_get( $url, array(
			'headers'     => array(
				'Authorization' => $this->auth_header,
				'Accept'        => 'application/json',
			),
			'httpversion' => '1.1',
			'timeout'     => 30,
			'redirection' => 0,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
			if ( $depth < 1 ) {
				return new WP_Error( 'postwave_redirect_limit', __( 'Session discovery exceeded the redirect limit.', 'tahhan-jmap-mailer' ) );
			}
			$location = wp_remote_retrieve_header( $response, 'location' );
			if ( empty( $location ) ) {
				return new WP_Error( 'postwave_redirect_missing', __( 'Redirect response is missing a Location header.', 'tahhan-jmap-mailer' ) );
			}
			return $this->fetch_session_document( $this->resolve_url( $url, $location ), $depth - 1 );
		}

		return $response;
	}

	private function resolve_url( $base, $location ) {
		if ( preg_match( '#^https?://#i', $location ) ) {
			return $location;
		}

		$parts = wp_parse_url( $base );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return $location;
		}

		$origin = $parts['scheme'] . '://' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$origin .= ':' . $parts['port'];
		}

		if ( substr( $location, 0, 2 ) === '//' ) {
			return $parts['scheme'] . ':' . $location;
		}

		if ( substr( $location, 0, 1 ) === '/' ) {
			return $origin . $location;
		}

		$dir = preg_replace( '#/[^/]*$#', '/', $parts['path'] ?? '/' );
		return $origin . $dir . ltrim( $location, '/' );
	}

	/**
	 * Normalize advertised endpoint URLs to match the configured public origin.
	 * Handles reverse-proxy setups where the server advertises internal HTTP URLs.
	 */
	private function normalize_url( $url ) {
		$pub = wp_parse_url( $this->server_url );
		$tgt = wp_parse_url( $url );

		if ( empty( $pub['host'] ) || empty( $tgt['host'] ) ) {
			return $url;
		}

		if ( strtolower( $pub['host'] ) !== strtolower( $tgt['host'] ) ) {
			return $url;
		}

		$normalized = $pub['scheme'] . '://' . $pub['host'];
		if ( ! empty( $pub['port'] ) ) {
			$normalized .= ':' . $pub['port'];
		}

		$normalized .= $tgt['path'] ?? '';

		if ( isset( $tgt['query'] ) ) {
			$normalized .= '?' . $tgt['query'];
		}

		return $normalized;
	}

	/**
	 * Make a JMAP API request.
	 *
	 * @param array      $method_calls
	 * @param array|null $using         Capability URNs.
	 * @return array|WP_Error methodResponses or error.
	 */
	public function request( $method_calls, $using = null ) {
		if ( empty( $this->api_url ) ) {
			$result = $this->discover_session();
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( null === $using ) {
			$using = array(
				'urn:ietf:params:jmap:core',
				'urn:ietf:params:jmap:mail',
				'urn:ietf:params:jmap:submission',
			);
		}

		$response = wp_remote_post( $this->api_url, array(
			'headers'     => array(
				'Authorization' => $this->auth_header,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'        => wp_json_encode( array( 'using' => $using, 'methodCalls' => $method_calls ), JSON_UNESCAPED_SLASHES ),
			'data_format' => 'body',
			'httpversion' => '1.1',
			'timeout'     => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$body    = json_decode( wp_remote_retrieve_body( $response ), true );
			$detail  = is_array( $body ) && ! empty( $body['detail'] ) ? ': ' . $body['detail'] : '';
			return new WP_Error(
				'postwave_api_http',
				/* translators: %d: HTTP status code */
				sprintf( __( 'JMAP API request failed with HTTP %d', 'tahhan-jmap-mailer' ), $code ) . $detail
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['methodResponses'] ) ) {
			return new WP_Error( 'postwave_api_response', __( 'JMAP response is missing methodResponses.', 'tahhan-jmap-mailer' ) );
		}

		return $body['methodResponses'];
	}

	/**
	 * Upload a blob (file attachment) to the JMAP server.
	 *
	 * @param string $data Binary data.
	 * @param string $type MIME type.
	 * @return string|WP_Error blobId or error.
	 */
	public function upload_blob( $data, $type ) {
		if ( empty( $this->upload_url ) ) {
			$result = $this->discover_session();
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( empty( $this->upload_url ) ) {
			return new WP_Error( 'postwave_no_upload_url', __( 'JMAP server does not provide an upload URL.', 'tahhan-jmap-mailer' ) );
		}

		$url      = str_replace( '{accountId}', rawurlencode( $this->account_id ), $this->upload_url );
		$response = wp_remote_post( $url, array(
			'headers'     => array(
				'Authorization' => $this->auth_header,
				'Content-Type'  => $type,
			),
			'body'        => $data,
			'data_format' => 'body',
			'httpversion' => '1.1',
			'timeout'     => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'postwave_upload_http',
				/* translators: %d: HTTP status code */
				sprintf( __( 'Blob upload failed with HTTP %d.', 'tahhan-jmap-mailer' ), $code )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['blobId'] ) ) {
			return new WP_Error( 'postwave_upload_blob', __( 'Upload response is missing blobId.', 'tahhan-jmap-mailer' ) );
		}

		return $body['blobId'];
	}

	/**
	 * Resolve the JMAP Identity for the given from_email address.
	 *
	 * @param string $from_email
	 * @return array|WP_Error Identity array or error.
	 */
	public function get_identity( $from_email ) {
		$responses = $this->request( array(
			array( 'Identity/get', array( 'accountId' => $this->account_id ), '0' ),
		) );

		if ( is_wp_error( $responses ) ) {
			return $responses;
		}

		$identities = $responses[0][1]['list'] ?? array();

		if ( empty( $identities ) ) {
			return new WP_Error( 'postwave_no_identities', __( 'No sending identities found on the JMAP server.', 'tahhan-jmap-mailer' ) );
		}

		foreach ( $identities as $identity ) {
			if ( isset( $identity['email'] ) && strtolower( $identity['email'] ) === strtolower( $from_email ) ) {
				return $identity;
			}
		}

		return $identities[0];
	}

	/**
	 * @param string $from_email
	 * @return string|WP_Error Identity ID or error.
	 */
	public function get_identity_id( $from_email ) {
		$identity = $this->get_identity( $from_email );

		if ( is_wp_error( $identity ) ) {
			return $identity;
		}

		if ( empty( $identity['id'] ) ) {
			return new WP_Error( 'postwave_identity_id', __( 'The matched JMAP identity has no id.', 'tahhan-jmap-mailer' ) );
		}

		return $identity['id'];
	}

	/**
	 * @param string $role  JMAP mailbox role, e.g. 'sent'.
	 * @return string|WP_Error Mailbox ID or error.
	 */
	public function get_mailbox_id_by_role( $role ) {
		$responses = $this->request( array(
			array(
				'Mailbox/query',
				array( 'accountId' => $this->account_id, 'filter' => array( 'role' => $role ) ),
				'0',
			),
		) );

		if ( is_wp_error( $responses ) ) {
			return $responses;
		}

		if ( ! empty( $responses[0][1]['ids'][0] ) ) {
			return $responses[0][1]['ids'][0];
		}

		return new WP_Error(
			'postwave_no_mailbox',
			/* translators: %s: mailbox role */
			sprintf( __( 'No JMAP mailbox found with role "%s".', 'tahhan-jmap-mailer' ), $role )
		);
	}

	/**
	 * Get the best available mailbox ID for storing sent email.
	 * Tries: sent → archive → inbox → any first mailbox.
	 *
	 * JMAP requires every Email object to belong to at least one mailbox,
	 * so this method always returns an ID unless the account has no mailboxes at all.
	 *
	 * @return string|WP_Error Mailbox ID or error.
	 */
	public function get_sent_or_fallback_mailbox_id() {
		// Try preferred roles in order.
		foreach ( array( 'sent', 'archive', 'inbox' ) as $role ) {
			$result = $this->get_mailbox_id_by_role( $role );
			if ( ! is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Last resort: query for any mailbox (no role filter).
		$responses = $this->request( array(
			array(
				'Mailbox/query',
				array( 'accountId' => $this->account_id, 'limit' => 1 ),
				'0',
			),
		) );

		if ( ! is_wp_error( $responses ) && ! empty( $responses[0][1]['ids'][0] ) ) {
			return $responses[0][1]['ids'][0];
		}

		return new WP_Error(
			'postwave_no_mailbox',
			__( 'No JMAP mailbox found on this account. Please check your server configuration.', 'tahhan-jmap-mailer' )
		);
	}

	/**
	 * Fetch all sending identities from the JMAP server, with caching.
	 *
	 * @return array|WP_Error Array of identity objects or WP_Error.
	 */
	public function get_all_identities() {
		$cache_key = 'postwave_identities_' . substr( md5( $this->server_url . $this->auth_header ), 0, 12 );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		if ( empty( $this->api_url ) ) {
			$result = $this->discover_session();
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$responses = $this->request( array(
			array( 'Identity/get', array( 'accountId' => $this->account_id, 'ids' => null ), '0' ),
		) );

		if ( is_wp_error( $responses ) ) {
			return $responses;
		}

		$identities = $responses[0][1]['list'] ?? array();

		if ( is_array( $identities ) ) {
			set_transient( $cache_key, $identities, HOUR_IN_SECONDS );
		}

		return $identities;
	}

	public function get_account_id() {
		return $this->account_id;
	}

	public function get_session() {
		return $this->session;
	}

	public function supports_submission() {
		return isset( $this->session['capabilities']['urn:ietf:params:jmap:submission'] );
	}
}

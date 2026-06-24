<?php
/**
 * Postwave Mailer — intercepts wp_mail() and sends via JMAP.
 *
 * @package Postwave
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postwave_Mailer {

	private $options;

	public function __construct( array $options ) {
		$this->options = $options;
		add_filter( 'pre_wp_mail', array( $this, 'send' ), 10, 2 );
	}

	/**
	 * pre_wp_mail filter callback — validates args and delegates to do_send().
	 *
	 * @param null|bool $return
	 * @param array     $atts
	 * @return bool|null
	 */
	public function send( $return, $atts ) {
		return $this->do_send( $atts );
	}

	/**
	 * Core JMAP send logic. Used by both the wp_mail hook and the retry queue.
	 *
	 * @param array  $atts         wp_mail-style args (to, subject, message, headers, attachments).
	 * @param string $log_id       Optional: existing log entry ID to update on retry success.
	 * @param int    $retry_number Which retry attempt this is (0 = original send).
	 * @return bool
	 */
	public function do_send( array $atts, $log_id = '', $retry_number = 0 ) {
		$to          = $atts['to'];
		$subject     = $atts['subject'];
		$message     = $atts['message'];
		$headers     = $atts['headers'] ?? '';
		$attachments = $atts['attachments'] ?? array();

		$parsed       = $this->parse_headers( $headers );
		$from_name    = $parsed['from_name']  ?: $this->options['from_name'];
		$from_email   = $parsed['from_email'] ?: $this->options['from_email'];
		$content_type = $parsed['content_type'];

		$to_list       = $this->normalize_addresses( $to );
		$cc_list       = $this->normalize_addresses( $parsed['cc'] );
		$bcc_list      = $this->normalize_addresses( $parsed['bcc'] );
		$reply_to_list = $this->normalize_addresses( $parsed['reply_to'] );

		$log = array(
			'from'             => $from_email,
			'to'               => implode( ', ', $to_list ),
			'subject'          => $subject,
			'attachment_count' => is_array( $attachments ) ? count( $attachments ) : 0,
		);

		$is_retry = ( $retry_number > 0 );

		if ( empty( $to_list ) ) {
			return $this->fail( $log, __( 'No recipient address provided.', 'postwave' ), $atts, $is_retry, $log_id );
		}

		// ── Account routing ───────────────────────────────────────────────────────
		// Resolve identity from options first (before routing overrides it).
		$forced_identity_id = sanitize_text_field( isset( $this->options['identity_id'] ) ? $this->options['identity_id'] : '' );

		/**
		 * Filter the JMAP identity ID used for the current send.
		 *
		 * @param string $identity_id  Configured identity ID (empty = auto-resolve).
		 * @param array  $atts         wp_mail()-style arguments.
		 */
		$forced_identity_id = (string) apply_filters( 'postwave_identity_id', $forced_identity_id, $atts );

		$routed_account = Postwave_Router::resolve( $atts );

		if ( null !== $routed_account ) {
			$account_server_url = $routed_account['server_url'];
			$account_username   = $routed_account['username'];
			$account_password   = $routed_account['password'];
			// Allow the rule's identity_id to override the account's identity_id.
			if ( ! empty( $routed_account['identity_id'] ) && empty( $forced_identity_id ) ) {
				$forced_identity_id = $routed_account['identity_id'];
			}
			$log['routed_to'] = $routed_account['id'];
		} else {
			// Fall back to primary account credentials.
			$account_server_url = $this->options['server_url'];
			$account_username   = $this->options['username'];
			$account_password   = $this->options['password'];
		}

		$client = new Postwave_JMAP_Client( $account_server_url, $account_username, $account_password );

		$session = $client->discover_session();
		if ( is_wp_error( $session ) ) {
			return $this->fail( $log, $session->get_error_message(), $atts, $is_retry, $log_id );
		}

		// Resolve identity: forced_identity_id was set above (before routing). Auto-resolve by from_email as fallback.
		if ( ! empty( $forced_identity_id ) ) {
			$identity_id = $forced_identity_id;
		} else {
			$identity_id = $client->get_identity_id( $from_email );
			if ( is_wp_error( $identity_id ) ) {
				return $this->fail( $log, $identity_id->get_error_message(), $atts, $is_retry, $log_id );
			}
		}
		$log['identity_id'] = $identity_id;

		$account_id        = $client->get_account_id();
		$log['account_id'] = $account_id;

		// Resolve mailbox for storing the sent email.
		// Tries: sent → archive → inbox → any available mailbox.
		// JMAP requires every email to belong to at least one mailbox.
		$sent_mailbox = $client->get_sent_or_fallback_mailbox_id();
		if ( is_wp_error( $sent_mailbox ) ) {
			return $this->fail( $log, $sent_mailbox->get_error_message(), $atts, $is_retry, $log_id );
		}

		// Upload attachments.
		$jmap_attachments = array();
		foreach ( (array) $attachments as $file ) {
			if ( ! is_string( $file ) || ! file_exists( $file ) ) {
				continue;
			}
			$data = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $data ) {
				continue;
			}
			$mime    = wp_check_filetype( $file );
			$type    = ! empty( $mime['type'] ) ? $mime['type'] : 'application/octet-stream';
			$blob_id = $client->upload_blob( $data, $type );
			if ( is_wp_error( $blob_id ) ) {
				return $this->fail( $log, $blob_id->get_error_message(), $atts, $is_retry, $log_id );
			}
			$jmap_attachments[] = array(
				'blobId'      => $blob_id,
				'type'        => $type,
				'name'        => basename( $file ),
				'disposition' => 'attachment',
			);
		}

		$create_id = 'pw-' . wp_generate_password( 12, false );
		$is_html   = ( 'text/html' === $content_type );

		// Inject tracking pixel before building the email object (HTML only, if enabled, original send only).
		$tracking_token = '';
		if ( $is_html && ! empty( $this->options['tracking_enabled'] ) && ! $is_retry ) {
			// Generate a unique token stored in the log and embedded in the pixel URL.
			// Use a random HMAC seed so the token is not guessable even with knowledge of log IDs.
			$tracking_token     = hash_hmac( 'sha256', microtime( true ) . wp_rand(), wp_salt( 'auth' ) );
			$tracking_pixel_url = esc_url( rest_url( Postwave_Open_Tracker::REST_NAMESPACE . '/pixel/' . $tracking_token ) );
			$tracking_img       = '<img src="' . $tracking_pixel_url . '" width="1" height="1" alt="" style="display:none;border:0;outline:0;">';
			if ( false !== stripos( $message, '</body>' ) ) {
				$message = str_ireplace( '</body>', $tracking_img . '</body>', $message );
			} else {
				$message .= $tracking_img;
			}
		}

		$email = array(
			'from'       => array( array( 'name' => $from_name, 'email' => $from_email ) ),
			'to'         => $this->to_jmap( $to_list ),
			'subject'    => $subject,
			'keywords'   => array( '$seen' => true ),
			'mailboxIds' => array( $sent_mailbox => true ),
		);

		if ( ! empty( $cc_list ) ) {
			$email['cc'] = $this->to_jmap( $cc_list );
		}
		if ( ! empty( $bcc_list ) ) {
			$email['bcc'] = $this->to_jmap( $bcc_list );
		}
		if ( ! empty( $reply_to_list ) ) {
			$email['replyTo'] = $this->to_jmap( $reply_to_list );
		}

		if ( $is_html ) {
			$plain               = wp_strip_all_tags( $message );
			$email['bodyValues'] = array(
				'text' => array( 'value' => $plain ),
				'html' => array( 'value' => $message ),
			);
			$email['textBody']   = array( array( 'partId' => 'text', 'type' => 'text/plain' ) );
			$email['htmlBody']   = array( array( 'partId' => 'html', 'type' => 'text/html' ) );
		} else {
			$email['bodyValues'] = array( 'text' => array( 'value' => $message ) );
			$email['textBody']   = array( array( 'partId' => 'text', 'type' => 'text/plain' ) );
		}

		if ( ! empty( $jmap_attachments ) ) {
			$email['attachments'] = $jmap_attachments;
		}
		$log['attachment_count'] = count( $jmap_attachments );

		// Step 1: Email/set — create the email object.
		$create_resp = $client->request( array(
			array( 'Email/set', array( 'accountId' => $account_id, 'create' => array( $create_id => $email ) ), '0' ),
		) );

		if ( is_wp_error( $create_resp ) ) {
			return $this->fail( $log, $create_resp->get_error_message(), $atts, $is_retry, $log_id );
		}

		if ( ! empty( $create_resp[0][1]['notCreated'] ) ) {
			$err = reset( $create_resp[0][1]['notCreated'] );
			return $this->fail( $log, $err['description'] ?? __( 'Failed to create email via JMAP.', 'postwave' ), $atts, $is_retry, $log_id );
		}

		$email_id = $create_resp[0][1]['created'][ $create_id ]['id'] ?? '';
		if ( empty( $email_id ) ) {
			return $this->fail( $log, __( 'JMAP email creation returned no email id.', 'postwave' ), $atts, $is_retry, $log_id );
		}
		$log['email_id'] = $email_id;

		// Step 2: EmailSubmission/set — submit for delivery.
		$submit_resp = $client->request( array(
			array(
				'EmailSubmission/set',
				array(
					'accountId' => $account_id,
					'create'    => array(
						'sub-1' => array( 'emailId' => $email_id, 'identityId' => $identity_id ),
					),
				),
				'0',
			),
		) );

		if ( is_wp_error( $submit_resp ) ) {
			return $this->fail( $log, $submit_resp->get_error_message(), $atts, $is_retry, $log_id );
		}

		if ( ! empty( $submit_resp[0][1]['notCreated'] ) ) {
			$err = reset( $submit_resp[0][1]['notCreated'] );
			return $this->fail( $log, $err['description'] ?? __( 'Failed to submit email via JMAP.', 'postwave' ), $atts, $is_retry, $log_id );
		}

		foreach ( array_merge( $create_resp, $submit_resp ) as $resp ) {
			if ( isset( $resp[0] ) && 'error' === $resp[0] ) {
				return $this->fail( $log, $resp[1]['description'] ?? __( 'JMAP error.', 'postwave' ), $atts, $is_retry, $log_id );
			}
		}

		// Success.
		if ( $is_retry && ! empty( $log_id ) ) {
			// Update the existing log entry rather than creating a new one.
			Postwave_Mail_Log::mark_retry_success( $log_id, $retry_number );
		} else {
			$log['tracking_token'] = $tracking_token;
			Postwave_Mail_Log::add( array_merge( $log, array( 'status' => 'sent' ) ) );
		}

		Postwave_Router::clear_context();
		return true;
	}

	/**
	 * Called by the retry queue — creates a fresh mailer and sends directly.
	 *
	 * @param array  $options      Plugin settings.
	 * @param array  $atts         wp_mail-style args.
	 * @param string $log_id       Original log entry ID to update on success.
	 * @param int    $retry_number Which retry attempt this is.
	 * @return bool
	 */
	public static function retry( array $options, array $atts, $log_id, $retry_number ) {
		$mailer = new self( $options );
		remove_filter( 'pre_wp_mail', array( $mailer, 'send' ), 10 );
		$result = $mailer->do_send( $atts, $log_id, $retry_number );
		return $result;
	}

	/**
	 * Record a failure, optionally queue for retry.
	 *
	 * @param array  $log      Log data.
	 * @param string $message  Error message.
	 * @param array  $atts     Original wp_mail args (needed for retry queue).
	 * @param bool   $is_retry Whether this is already a retry attempt.
	 * @param string $log_id   Existing log entry ID (non-empty on retry).
	 * @return false
	 */
	private function fail( array $log, $message, array $atts = array(), $is_retry = false, $log_id = '' ) {
		if ( $is_retry && ! empty( $log_id ) ) {
			// On retry failure, just update the error on the existing entry — do not create a new one.
			Postwave_Mail_Log::update_by_id( $log_id, array( 'error' => $message ) );
		} else {
			$entry_id = Postwave_Mail_Log::add( array_merge( $log, array( 'status' => 'failed', 'error' => $message ) ) );
			do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $message ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			// Queue for retry if enabled and this is the original send attempt.
			if ( ! $is_retry && ! empty( $this->options['retry_enabled'] ) && ! empty( $atts ) ) {
				Postwave_Retry_Queue::add( $atts, $entry_id );
			}
		}

		Postwave_Router::clear_context();
		return false;
	}

	private function parse_headers( $headers ) {
		$result = array(
			'from_name'    => '',
			'from_email'   => '',
			'cc'           => array(),
			'bcc'          => array(),
			'reply_to'     => array(),
			'content_type' => 'text/plain',
		);

		if ( empty( $headers ) ) {
			return $result;
		}

		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		}

		foreach ( $headers as $header ) {
			if ( false === strpos( $header, ':' ) ) {
				continue;
			}
			list( $name, $value ) = explode( ':', trim( $header ), 2 );
			$name  = strtolower( trim( $name ) );
			$value = trim( $value );

			switch ( $name ) {
				case 'from':
					if ( preg_match( '/(.*)<(.+)>/', $value, $m ) ) {
						$result['from_name']  = trim( $m[1], ' "' );
						$result['from_email'] = trim( $m[2] );
					} else {
						$result['from_email'] = $value;
					}
					break;
				case 'cc':
					$result['cc'][] = $value;
					break;
				case 'bcc':
					$result['bcc'][] = $value;
					break;
				case 'reply-to':
					$result['reply_to'][] = $value;
					break;
				case 'content-type':
					if ( false !== stripos( $value, 'text/html' ) ) {
						$result['content_type'] = 'text/html';
					}
					break;
			}
		}

		return $result;
	}

	private function normalize_addresses( $addresses ) {
		if ( empty( $addresses ) ) {
			return array();
		}
		if ( is_string( $addresses ) ) {
			$addresses = array_map( 'trim', explode( ',', $addresses ) );
		}
		return array_values( array_filter( array_map( 'trim', (array) $addresses ) ) );
	}

	private function to_jmap( array $addresses ) {
		$result = array();
		foreach ( $addresses as $addr ) {
			if ( preg_match( '/(.*)<(.+)>/', $addr, $m ) ) {
				$result[] = array( 'name' => trim( $m[1], ' "' ), 'email' => trim( $m[2] ) );
			} else {
				$result[] = array( 'email' => trim( $addr ) );
			}
		}
		return $result;
	}
}

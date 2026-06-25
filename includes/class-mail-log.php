<?php
/**
 * Tahhan JMAP Mailer Mail Log — stores send attempt metadata (never message bodies).
 *
 * @package Tahhan_JMAP_Mailer
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postwave_Mail_Log {

	const MAX_ENTRIES = 100;

	/**
	 * Add a new log entry.
	 *
	 * @param array $entry Raw entry data.
	 * @return string      The generated entry ID.
	 */
	public static function add( array $entry ) {
		$entries = self::get_entries();

		$id = substr( md5( microtime( true ) . wp_rand() ), 0, 12 );

		$normalized = array(
			'id'               => $id,
			'timestamp'        => (int) time(),
			'status'           => ( isset( $entry['status'] ) && 'sent' === $entry['status'] ) ? 'sent' : 'failed',
			'to'               => sanitize_text_field( $entry['to'] ?? '' ),
			'from'             => sanitize_email( $entry['from'] ?? '' ),
			'subject'          => sanitize_text_field( wp_strip_all_tags( $entry['subject'] ?? '' ) ),
			'error'            => sanitize_text_field( $entry['error'] ?? '' ),
			'attachment_count' => max( 0, (int) ( $entry['attachment_count'] ?? 0 ) ),
			'account_id'       => sanitize_text_field( $entry['account_id'] ?? '' ),
			'identity_id'      => sanitize_text_field( $entry['identity_id'] ?? '' ),
			'email_id'         => sanitize_text_field( $entry['email_id'] ?? '' ),
			// v1.1 fields
			'retry_count'      => 0,
			'retry_status'     => null,  // null | 'retried' | 'exhausted'
			'opened_at'        => null,
			'tracking_token'   => sanitize_text_field( $entry['tracking_token'] ?? '' ),
		);

		array_unshift( $entries, $normalized );
		$entries = array_slice( $entries, 0, self::MAX_ENTRIES );

		update_option( POSTWAVE_LOG_OPTION, $entries, false );

		return $id;
	}

	/**
	 * Update specific fields of an existing log entry by ID.
	 *
	 * @param string $id     Entry ID.
	 * @param array  $fields Associative array of fields to update.
	 * @return bool
	 */
	public static function update_by_id( $id, array $fields ) {
		$entries = self::get_entries();
		$updated = false;

		// Whitelist updatable fields to prevent arbitrary data injection.
		$allowed = array( 'status', 'error', 'retry_count', 'retry_status', 'opened_at', 'email_id' );

		foreach ( $entries as &$entry ) {
			if ( isset( $entry['id'] ) && $entry['id'] === $id ) {
				foreach ( $fields as $key => $value ) {
					if ( in_array( $key, $allowed, true ) ) {
						$entry[ $key ] = $value;
					}
				}
				$updated = true;
				break;
			}
		}
		unset( $entry );

		if ( $updated ) {
			update_option( POSTWAVE_LOG_OPTION, $entries, false );
		}

		return $updated;
	}

	/**
	 * Mark a log entry as successfully retried.
	 *
	 * @param string $id          Entry ID.
	 * @param int    $retry_count Number of retries taken.
	 */
	public static function mark_retry_success( $id, $retry_count ) {
		self::update_by_id( $id, array(
			'status'       => 'sent',
			'retry_count'  => (int) $retry_count,
			'retry_status' => 'retried',
			'error'        => '',
		) );
	}

	/**
	 * Mark a log entry as retry-exhausted (gave up).
	 *
	 * @param string $id          Entry ID.
	 * @param int    $retry_count Number of retries attempted.
	 */
	public static function mark_retry_exhausted( $id, $retry_count ) {
		self::update_by_id( $id, array(
			'retry_count'  => (int) $retry_count,
			'retry_status' => 'exhausted',
		) );
	}

	/**
	 * Get all log entries, ensuring each has an `id` for backward compatibility.
	 *
	 * @return array
	 */
	public static function get_entries() {
		$entries = get_option( POSTWAVE_LOG_OPTION, array() );
		if ( ! is_array( $entries ) ) {
			return array();
		}

		$entries = array_values( array_filter( $entries, 'is_array' ) );

		// Back-fill IDs on old entries that lack them.
		$dirty = false;
		foreach ( $entries as &$entry ) {
			if ( empty( $entry['id'] ) ) {
				$entry['id'] = substr( md5( serialize( $entry ) ), 0, 12 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				$dirty       = true;
			}
		}
		unset( $entry );

		if ( $dirty ) {
			update_option( POSTWAVE_LOG_OPTION, $entries, false );
		}

		return $entries;
	}

	public static function clear() {
		delete_option( POSTWAVE_LOG_OPTION );
	}

	public static function get_stats() {
		$entries = self::get_entries();
		$now     = time();
		$day     = $now - DAY_IN_SECONDS;
		$week    = $now - WEEK_IN_SECONDS;

		$stats = array(
			'total'        => count( $entries ),
			'sent_today'   => 0,
			'failed_today' => 0,
			'sent_week'    => 0,
			'failed_week'  => 0,
		);

		foreach ( $entries as $entry ) {
			$ts      = (int) ( $entry['timestamp'] ?? 0 );
			$is_sent = 'sent' === ( $entry['status'] ?? '' );

			if ( $ts >= $day ) {
				$is_sent ? $stats['sent_today']++ : $stats['failed_today']++;
			}
			if ( $ts >= $week ) {
				$is_sent ? $stats['sent_week']++ : $stats['failed_week']++;
			}
		}

		return $stats;
	}

	/**
	 * Export all entries as a CSV string.
	 *
	 * @return string
	 */
	public static function to_csv() {
		$entries = self::get_entries();

		$cols = array(
			'Date',
			'Status',
			'Retry',
			'From',
			'To',
			'Subject',
			'Error',
			'Opened',
			'Account ID',
			'Identity ID',
			'Email ID',
			'Attachments',
		);

		$lines   = array();
		$lines[] = implode( ',', array_map( array( __CLASS__, 'csv_cell' ), $cols ) );

		foreach ( $entries as $e ) {
			$retry = '';
			if ( 'retried' === ( $e['retry_status'] ?? '' ) ) {
				$retry = 'retried (' . (int) $e['retry_count'] . 'x)';
			} elseif ( 'exhausted' === ( $e['retry_status'] ?? '' ) ) {
				$retry = 'exhausted (' . (int) $e['retry_count'] . 'x)';
			}

			$opened = ! empty( $e['opened_at'] )
				? gmdate( 'Y-m-d H:i:s', (int) $e['opened_at'] )
				: '';

			$row = array(
				gmdate( 'Y-m-d H:i:s', (int) ( $e['timestamp'] ?? 0 ) ),
				$e['status']          ?? '',
				$retry,
				$e['from']            ?? '',
				$e['to']              ?? '',
				$e['subject']         ?? '',
				$e['error']           ?? '',
				$opened,
				$e['account_id']      ?? '',
				$e['identity_id']     ?? '',
				$e['email_id']        ?? '',
				(int) ( $e['attachment_count'] ?? 0 ),
			);

			$lines[] = implode( ',', array_map( array( __CLASS__, 'csv_cell' ), $row ) );
		}

		return implode( "\r\n", $lines );
	}

	private static function csv_cell( $value ) {
		$value = (string) $value;
		if ( false !== strpos( $value, ',' ) || false !== strpos( $value, '"' ) || false !== strpos( $value, "\n" ) ) {
			$value = '"' . str_replace( '"', '""', $value ) . '"';
		}
		return $value;
	}
}

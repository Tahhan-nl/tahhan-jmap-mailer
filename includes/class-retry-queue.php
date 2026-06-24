<?php
/**
 * Postwave Retry Queue — automatically re-sends failed emails via WP-Cron.
 *
 * @package Postwave
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postwave_Retry_Queue {

	const CRON_HOOK = 'postwave_process_retry_queue';
	const MAX_ITEMS = 50;
	const LOCK_KEY  = 'postwave_retry_lock';

	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'process' ) );
		add_filter( 'cron_schedules',  array( __CLASS__, 'add_schedule' ) );
	}

	public static function add_schedule( $schedules ) {
		if ( ! isset( $schedules['postwave_5min'] ) ) {
			$schedules['postwave_5min'] = array(
				'interval' => 300,
				'display'  => __( 'Every 5 minutes (Postwave)', 'postwave' ),
			);
		}
		return $schedules;
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'postwave_5min', self::CRON_HOOK );
		}
	}

	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Add a failed email to the retry queue.
	 *
	 * @param array  $atts     wp_mail()-style array: to, subject, message, headers, attachments.
	 * @param string $log_id   ID of the corresponding mail log entry.
	 * @return string|false    Queue item ID or false on failure.
	 */
	public static function add( array $atts, $log_id = '' ) {
		$options = get_option( POSTWAVE_OPTION_KEY, array() );
		$max     = (int) ( $options['retry_max']   ?? 3 );
		$delay   = (int) ( $options['retry_delay'] ?? 300 );

		$queue = self::get_queue();

		if ( count( $queue ) >= self::MAX_ITEMS ) {
			return false;
		}

		// Do not queue if body is too large (> 200 KB) to protect wp_options.
		$body_size = strlen( $atts['message'] ?? '' );
		if ( $body_size > 204800 ) {
			return false;
		}

		$item = array(
			'id'           => substr( md5( microtime( true ) . wp_rand() ), 0, 12 ),
			'log_id'       => sanitize_text_field( $log_id ),
			'to'           => $atts['to'] ?? '',
			'subject'      => $atts['subject'] ?? '',
			'message'      => $atts['message'] ?? '',
			'headers'      => $atts['headers'] ?? array(),
			// Attachments are NOT queued — temp files may not exist on retry.
			'retry_count'  => 0,
			'max_retries'  => $max,
			'delay'        => $delay,
			'next_retry'   => time() + $delay,
			'first_failed' => time(),
		);

		$queue[] = $item;
		update_option( POSTWAVE_RETRY_OPTION, $queue, false );

		return $item['id'];
	}

	/** @return array */
	public static function get_queue() {
		$queue = get_option( POSTWAVE_RETRY_OPTION, array() );
		return is_array( $queue ) ? $queue : array();
	}

	/** @return int */
	public static function get_count() {
		return count( self::get_queue() );
	}

	public static function clear() {
		delete_option( POSTWAVE_RETRY_OPTION );
	}

	/**
	 * WP-Cron callback — process due retry items.
	 */
	public static function process() {
		$options = get_option( POSTWAVE_OPTION_KEY, array() );

		if ( empty( $options['retry_enabled'] ) ) {
			return;
		}

		// Prevent concurrent runs via a short transient lock.
		if ( get_transient( self::LOCK_KEY ) ) {
			return;
		}
		set_transient( self::LOCK_KEY, 1, 90 );

		$queue     = self::get_queue();
		$remaining = array();
		$now       = time();

		foreach ( $queue as $item ) {
			if ( $item['next_retry'] > $now ) {
				$remaining[] = $item;
				continue;
			}

			$atts = array(
				'to'          => $item['to'],
				'subject'     => $item['subject'],
				'message'     => $item['message'],
				'headers'     => $item['headers'],
				'attachments' => array(),
			);

			// Send directly through the JMAP mailer (not wp_mail, to avoid double-hook).
			$result = Postwave_Mailer::retry( $options, $atts, $item['log_id'], $item['retry_count'] + 1 );

			if ( true === $result ) {
				// Successfully re-sent — remove from queue, log entry already updated inside retry().
				continue;
			}

			// Failed again — back-off or give up.
			$item['retry_count']++;

			if ( $item['retry_count'] < $item['max_retries'] ) {
				// Exponential backoff: delay * 2^retry_count, capped at 24 h.
				$backoff            = min( $item['delay'] * pow( 2, $item['retry_count'] ), DAY_IN_SECONDS );
				$item['next_retry'] = $now + (int) $backoff;
				$remaining[]        = $item;
			} else {
				// Exhausted — mark log entry as permanently failed.
				Postwave_Mail_Log::mark_retry_exhausted( $item['log_id'], $item['retry_count'] );
			}
		}

		update_option( POSTWAVE_RETRY_OPTION, $remaining, false );
		delete_transient( self::LOCK_KEY );
	}
}

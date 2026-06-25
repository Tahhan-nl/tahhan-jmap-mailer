<?php
/**
 * Tahhan JMAP Mailer WooCommerce Integration — context detection for routing.
 *
 * @package Tahhan_JMAP_Mailer
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postwave_Integration_WooCommerce {

	public static function init() {
		// WC 5.6+: fired inside WC_Email::send() before wp_mail() is called.
		add_action( 'woocommerce_email_before_send', array( __CLASS__, 'on_before_send' ), 5 );

		// Fallback for older WC: hook on subject filters that fire just before send.
		// These fire once per email send cycle, giving us the WC_Email object.
		add_filter( 'woocommerce_email_subject_new_order',                array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_customer_processing_order', array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_customer_completed_order',  array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_customer_invoice',          array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_failed_order',              array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_cancelled_order',           array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_customer_refunded_order',   array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_customer_on_hold_order',    array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_customer_note',             array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_customer_new_account',      array( __CLASS__, 'on_subject_filter' ), 1, 2 );
		add_filter( 'woocommerce_email_subject_customer_reset_password',   array( __CLASS__, 'on_subject_filter' ), 1, 2 );
	}

	/**
	 * WC 5.6+ callback — receives the WC_Email object directly.
	 * @param object $email WC_Email instance.
	 */
	public static function on_before_send( $email ) {
		if ( is_object( $email ) && ! empty( $email->id ) ) {
			Postwave_Router::set_context( 'woocommerce', $email->id );
		}
	}

	/**
	 * Fallback: fires on woocommerce_email_subject_* filters.
	 * The filter receives ($subject, $email_object) in WC 3.1+.
	 * @param string $subject
	 * @param mixed  $email   WC_Email object or order object (WC 3.1+).
	 * @return string Unchanged subject.
	 */
	public static function on_subject_filter( $subject, $email = null ) {
		// Only set context if not already set by on_before_send.
		$ctx = Postwave_Router::get_context();
		if ( '' === $ctx['plugin'] ) {
			$email_id = '';
			if ( is_object( $email ) && ! empty( $email->id ) ) {
				$email_id = $email->id;
			}
			Postwave_Router::set_context( 'woocommerce', $email_id );
		}
		return $subject;
	}
}

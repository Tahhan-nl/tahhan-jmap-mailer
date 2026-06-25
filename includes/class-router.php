<?php
/**
 * Tahhan JMAP Mailer Router — email routing rules engine and plugin context tracking.
 *
 * @package Tahhan_JMAP_Mailer
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postwave_Router {

	/** Current email context set by integration hooks. */
	private static $context = array(
		'plugin'     => '',
		'email_type' => '',
	);

	// ── Context API ──────────────────────────────────────────────────────────

	/**
	 * Set the current email context (called by integration hooks before wp_mail).
	 * @param string $plugin     e.g. 'woocommerce', 'gravityforms', 'fluentform', 'cf7'
	 * @param string $email_type e.g. WC email ID like 'customer_processing_order'
	 */
	public static function set_context( $plugin, $email_type = '' ) {
		self::$context = array(
			'plugin'     => sanitize_key( $plugin ),
			'email_type' => sanitize_key( $email_type ),
		);
	}

	/** @return array */
	public static function get_context() {
		return self::$context;
	}

	public static function clear_context() {
		self::$context = array( 'plugin' => '', 'email_type' => '' );
	}

	// ── Rules storage API ─────────────────────────────────────────────────────

	/** @return array Ordered rules array. */
	public static function get_rules() {
		$rules = get_option( POSTWAVE_ROUTING_OPTION, array() );
		return is_array( $rules ) ? $rules : array();
	}

	/**
	 * Save (insert or update) a routing rule.
	 * @param array $data Rule data.
	 * @return string Rule ID.
	 */
	public static function save_rule( array $data ) {
		$rules = self::get_rules();

		$id = sanitize_key( isset( $data['id'] ) ? $data['id'] : '' );
		if ( empty( $id ) ) {
			$id = 'rule_' . substr( md5( microtime( true ) . wp_rand() ), 0, 10 );
		}

		// Sanitize conditions.
		$conditions = array();
		foreach ( (array) ( isset( $data['conditions'] ) ? $data['conditions'] : array() ) as $c ) {
			$field = sanitize_key( isset( $c['field'] ) ? $c['field'] : '' );
			$value = sanitize_text_field( isset( $c['value'] ) ? $c['value'] : '' );
			if ( $field && '' !== $value ) {
				$conditions[] = array( 'field' => $field, 'value' => $value );
			}
		}

		$condition_operator_raw = isset( $data['condition_operator'] ) ? $data['condition_operator'] : 'any';
		$condition_operator     = in_array( $condition_operator_raw, array( 'any', 'all' ), true )
		                          ? $condition_operator_raw : 'any';

		$rule = array(
			'id'                 => $id,
			'name'               => sanitize_text_field( isset( $data['name'] ) ? $data['name'] : __( 'Rule', 'tahhan-jmap-mailer' ) ),
			'enabled'            => ! empty( $data['enabled'] ),
			'conditions'         => $conditions,
			'condition_operator' => $condition_operator,
			'account_id'         => sanitize_key( isset( $data['account_id'] ) ? $data['account_id'] : 'acc_primary' ),
			'identity_id'        => sanitize_text_field( isset( $data['identity_id'] ) ? $data['identity_id'] : '' ),
		);

		// Update in place or append.
		$found = false;
		foreach ( $rules as &$existing ) {
			if ( $existing['id'] === $id ) {
				$existing = $rule;
				$found    = true;
				break;
			}
		}
		unset( $existing );

		if ( ! $found ) {
			$rules[] = $rule;
		}

		update_option( POSTWAVE_ROUTING_OPTION, $rules, false );
		return $id;
	}

	/**
	 * Delete a routing rule by ID.
	 * @param string $id
	 * @return bool
	 */
	public static function delete_rule( $id ) {
		$rules  = self::get_rules();
		$before = count( $rules );
		$rules  = array_values( array_filter( $rules, function( $r ) use ( $id ) {
			return $r['id'] !== $id;
		} ) );
		if ( count( $rules ) < $before ) {
			update_option( POSTWAVE_ROUTING_OPTION, $rules, false );
			return true;
		}
		return false;
	}

	/**
	 * Reorder rules to match the given ID sequence.
	 * @param array $ordered_ids Array of rule IDs in desired order.
	 */
	public static function reorder_rules( array $ordered_ids ) {
		$rules   = self::get_rules();
		$indexed = array();
		foreach ( $rules as $rule ) {
			$indexed[ $rule['id'] ] = $rule;
		}
		$sorted = array();
		foreach ( $ordered_ids as $id ) {
			$id = sanitize_key( $id );
			if ( isset( $indexed[ $id ] ) ) {
				$sorted[] = $indexed[ $id ];
			}
		}
		// Append any rules not in the given ID list.
		foreach ( $indexed as $id => $rule ) {
			if ( ! in_array( $id, $ordered_ids, true ) ) {
				$sorted[] = $rule;
			}
		}
		update_option( POSTWAVE_ROUTING_OPTION, $sorted, false );
	}

	// ── Routing engine ────────────────────────────────────────────────────────

	/**
	 * Resolve which account config to use for the given email.
	 * Returns the account array, or null to use the primary account.
	 *
	 * @param array $atts  wp_mail-style args (to, subject, message, headers, attachments).
	 * @return array|null  Account config or null.
	 */
	public static function resolve( array $atts ) {
		$rules = self::get_rules();

		foreach ( $rules as $rule ) {
			if ( empty( $rule['enabled'] ) ) {
				continue;
			}

			if ( self::matches_rule( $rule, $atts ) ) {
				$account = Postwave_Account_Manager::get( $rule['account_id'] );
				if ( null !== $account ) {
					// Optionally override identity from the rule.
					if ( ! empty( $rule['identity_id'] ) ) {
						$account['identity_id'] = $rule['identity_id'];
					}
					return $account;
				}
			}
		}

		return null; // Use primary account.
	}

	/**
	 * Check whether a rule matches the current email and context.
	 */
	private static function matches_rule( array $rule, array $atts ) {
		$conditions = isset( $rule['conditions'] ) ? $rule['conditions'] : array();

		// A rule with no conditions matches every email.
		if ( empty( $conditions ) ) {
			return true;
		}

		$operator = isset( $rule['condition_operator'] ) ? $rule['condition_operator'] : 'any';

		foreach ( $conditions as $condition ) {
			$matches = self::matches_condition( $condition, $atts );

			if ( $matches && 'any' === $operator ) {
				return true;
			}
			if ( ! $matches && 'all' === $operator ) {
				return false;
			}
		}

		return ( 'all' === $operator ); // 'all' → all passed; 'any' → none matched.
	}

	/**
	 * Evaluate a single condition against the email args and context.
	 */
	private static function matches_condition( array $condition, array $atts ) {
		$field = isset( $condition['field'] ) ? $condition['field'] : '';
		$value = strtolower( trim( isset( $condition['value'] ) ? $condition['value'] : '' ) );

		if ( '' === $field || '' === $value ) {
			return false;
		}

		switch ( $field ) {

			case 'to_email':
				$to         = isset( $atts['to'] ) ? $atts['to'] : '';
				$recipients = is_array( $to ) ? $to : array( $to );
				foreach ( $recipients as $r ) {
					if ( strtolower( trim( $r ) ) === $value ) {
						return true;
					}
				}
				return false;

			case 'to_domain':
				$to         = isset( $atts['to'] ) ? $atts['to'] : '';
				$recipients = is_array( $to ) ? $to : array( $to );
				foreach ( $recipients as $r ) {
					$parts = explode( '@', strtolower( trim( $r ) ) );
					if ( isset( $parts[1] ) && trim( $parts[1] ) === $value ) {
						return true;
					}
				}
				return false;

			case 'from_email':
				// from_email may be in headers array.
				$from = strtolower( self::extract_from_email( isset( $atts['headers'] ) ? $atts['headers'] : '' ) );
				return ( $from === $value );

			case 'subject_contains':
				$subject = isset( $atts['subject'] ) ? $atts['subject'] : '';
				return ( false !== stripos( $subject, isset( $condition['value'] ) ? $condition['value'] : '' ) );

			case 'plugin':
				$ctx    = self::get_context();
				$plugin = $ctx['plugin'];
				$type   = $ctx['email_type'];

				// 'woocommerce' matches any WC email; 'woocommerce:customer_processing_order' matches specific type.
				if ( false !== strpos( $value, ':' ) ) {
					$parts = explode( ':', $value, 2 );
					return ( $plugin === $parts[0] && $type === $parts[1] );
				}
				return ( $plugin === $value );

			default:
				return false;
		}
	}

	/**
	 * Extract the From email address from a headers string or array.
	 */
	private static function extract_from_email( $headers ) {
		if ( empty( $headers ) ) {
			return '';
		}
		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		}
		foreach ( $headers as $header ) {
			if ( false === strpos( $header, ':' ) ) {
				continue;
			}
			list( $name, $value ) = explode( ':', $header, 2 );
			if ( 'from' === strtolower( trim( $name ) ) ) {
				$value = trim( $value );
				if ( preg_match( '/<(.+)>/', $value, $m ) ) {
					return trim( $m[1] );
				}
				return $value;
			}
		}
		return '';
	}
}

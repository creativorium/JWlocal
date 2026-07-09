<?php
/**
 * Idempotency handler for JW Kit Auto Tagger.
 *
 * Prevents duplicate API calls for the same event within 24 hours.
 * Uses options-based storage (transients) for simplicity.
 *
 * @package JW_Kit_Auto_Tagger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_Kit_Idempotency
 */
class JW_Kit_Idempotency {

	/**
	 * Option name for idempotency log.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'jw_kit_idempotency_log';

	/**
	 * TTL in seconds (24 hours).
	 *
	 * @var int
	 */
	const TTL = 86400;

	/**
	 * Max entries to keep in log (prevent unbounded growth).
	 *
	 * @var int
	 */
	const MAX_ENTRIES = 5000;

	/**
	 * Check if this event was already processed recently.
	 *
	 * @param string $idempotency_key Unique key (e.g. hash of email + event_type + order_id).
	 * @return bool True if already processed (skip), false if new (proceed).
	 */
	public function was_processed( $idempotency_key ) {
		$log = $this->get_log();
		return isset( $log[ $idempotency_key ] );
	}

	/**
	 * Mark event as processed.
	 *
	 * @param string $idempotency_key Unique key.
	 */
	public function mark_processed( $idempotency_key ) {
		$log = $this->get_log();
		$log[ $idempotency_key ] = time();

		// Prune old entries (older than TTL).
		$now   = time();
		$log   = array_filter( $log, function ( $ts ) use ( $now ) {
			return ( $now - $ts ) < self::TTL;
		} );

		// Limit total entries.
		if ( count( $log ) > self::MAX_ENTRIES ) {
			arsort( $log );
			$log = array_slice( $log, 0, self::MAX_ENTRIES, true );
		}

		update_option( self::OPTION_KEY, $log, false );
	}

	/**
	 * Get idempotency key for an event.
	 *
	 * @param string $email     Subscriber email.
	 * @param string $event_type Event type (e.g. elementor_lm_roadmap, woo_checkout_started).
	 * @param int    $order_id  Optional order ID for WooCommerce events.
	 * @return string
	 */
	public function get_key( $email, $event_type, $order_id = 0 ) {
		$parts = array( sanitize_email( $email ), $event_type );
		if ( $order_id ) {
			$parts[] = (int) $order_id;
		}
		return 'jw_kit_' . md5( implode( '|', $parts ) );
	}

	/**
	 * Get the idempotency log.
	 *
	 * @return array
	 */
	private function get_log() {
		$log = get_option( self::OPTION_KEY, array() );
		return is_array( $log ) ? $log : array();
	}
}

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Duitku checkout performance guard.
 *
 * The Duitku gateway calls its `getpaymentmethod` endpoint on EVERY order-review
 * refresh (hooked to `woocommerce_review_order_before_payment`) with no caching
 * and a 90-second timeout. So applying a coupon or switching payment method waits
 * on a fresh cross-network round trip each time — fast when their API is happy,
 * multi-second when it isn't.
 *
 * We sit in front of that ONE request:
 *   - serve a cached response per amount (short TTL) → repeat refreshes are instant
 *   - cap the timeout so a slow Duitku degrades instead of freezing checkout
 *
 * The Duitku plugin itself is never modified, so this survives its updates.
 * Only genuine successes (`responseCode == "00"`) are cached — never errors.
 */
class JWT_Duitku_Perf {

	/** Distinctive part of the endpoint we guard. */
	const NEEDLE = '/api/merchant/paymentmethod/getpaymentmethod';

	/** How long a fee list stays valid. Short, so rate changes surface quickly. */
	const TTL = 300;

	/** Max seconds to wait (the plugin ships 90). */
	const TIMEOUT = 10;

	const PREFIX = 'jwt_duitku_pm_';

	public static function init() {
		add_filter( 'http_request_timeout', array( __CLASS__, 'cap_timeout' ), 10, 2 );
		add_filter( 'pre_http_request', array( __CLASS__, 'serve_cached' ), 10, 3 );
		add_filter( 'http_response', array( __CLASS__, 'store' ), 10, 3 );
	}

	protected static function is_target( $url ) {
		return is_string( $url ) && false !== strpos( $url, self::NEEDLE );
	}

	/**
	 * Cache key from merchant + amount only. The request body also carries
	 * `datetime` and `signature`, which change on every call — keying on the whole
	 * body would never hit. Fees vary by amount, so amount must be in the key.
	 */
	protected static function key( $args ) {
		$body = isset( $args['body'] ) ? $args['body'] : '';
		$data = is_string( $body ) ? json_decode( $body, true ) : ( is_array( $body ) ? $body : array() );

		if ( ! is_array( $data ) || ! isset( $data['amount'] ) ) {
			return '';
		}

		$merchant = isset( $data['merchantCode'] ) ? (string) $data['merchantCode'] : '';
		return self::PREFIX . md5( $merchant . '|' . $data['amount'] );
	}

	/** Never let this one endpoint block checkout for 90s. */
	public static function cap_timeout( $timeout, $url = '' ) {
		return self::is_target( $url ) ? min( (float) $timeout, (float) self::TIMEOUT ) : $timeout;
	}

	/** Short-circuit the HTTP call when we already have a fresh answer. */
	public static function serve_cached( $pre, $args, $url ) {
		if ( false !== $pre || ! self::is_target( $url ) ) {
			return $pre;
		}

		$key = self::key( $args );
		if ( '' === $key ) {
			return $pre;
		}

		$body = get_transient( $key );
		if ( false === $body ) {
			return $pre;
		}

		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/** Cache a successful live response for next time. */
	public static function store( $response, $args, $url ) {
		if ( ! self::is_target( $url ) || is_wp_error( $response ) ) {
			return $response;
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return $response;
		}

		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Only a genuine success is cacheable — an error payload must never stick.
		if ( ! is_array( $data ) || ! isset( $data['responseCode'] ) || '00' !== (string) $data['responseCode'] ) {
			return $response;
		}

		$key = self::key( $args );
		if ( '' !== $key ) {
			set_transient( $key, $body, self::TTL );
		}

		return $response;
	}
}

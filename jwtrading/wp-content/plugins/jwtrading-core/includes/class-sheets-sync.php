<?php
defined( 'ABSPATH' ) || exit;

/**
 * Google Sheets sync via existing Apps Script Web App.
 * POSTs order JSON + shared secret; the script validates the secret and appends a row.
 */
class JWT_Sheets_Sync {

	/**
	 * Sync one order to the Sheet. Returns true on success, WP_Error on failure.
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function sync_order( WC_Order $order ) {
		$webhook = trim( (string) get_option( 'jwt_sheets_webhook_url', '' ) );
		$secret  = trim( (string) get_option( 'jwt_sheets_secret', '' ) );

		if ( '' === $webhook ) {
			return new WP_Error( 'jwt_sheets_no_url', 'Sheets webhook URL not configured.' );
		}

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = array(
				'name' => $item->get_name(),
				'qty'  => $item->get_quantity(),
				'total' => (float) $item->get_total(),
			);
		}

		$payload = array(
			'secret'         => $secret,
			'order_id'       => $order->get_id(),
			'order_number'   => $order->get_order_number(),
			'date'           => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
			'status'         => $order->get_status(),
			'payment_method' => $order->get_payment_method_title(), // e.g. Duitku channel
			'customer_name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'email'          => $order->get_billing_email(),
			'phone'          => $order->get_billing_phone(),
			'items'          => $items,
			'total'          => (float) $order->get_total(),
			'currency'       => $order->get_currency(),
		);

		$response = wp_remote_post( $webhook, array(
			'timeout' => 15,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $payload ),
		) );

		// Strip secret before logging.
		$log_payload = $payload;
		unset( $log_payload['secret'] );

		if ( is_wp_error( $response ) ) {
			JWT_Sync_Log::log( $order->get_id(), 'sheets', 'failed', $log_payload, $response->get_error_message() );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// Apps Script Web Apps respond 200 (or 302 redirect that wp_remote_post follows).
		if ( $code < 200 || $code >= 400 ) {
			$err = new WP_Error( 'jwt_sheets_http_' . $code, 'Sheets webhook HTTP ' . $code . ': ' . $body );
			JWT_Sync_Log::log( $order->get_id(), 'sheets', 'failed', $log_payload, $err->get_error_message() );
			return $err;
		}

		JWT_Sync_Log::log( $order->get_id(), 'sheets', 'success', $log_payload, mb_substr( $body, 0, 500 ) );
		return true;
	}
}

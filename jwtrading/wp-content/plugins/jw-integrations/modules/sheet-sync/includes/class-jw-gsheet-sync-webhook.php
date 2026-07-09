<?php
/**
 * Webhook sender for JW WooCommerce Google Sheet Sync.
 *
 * @package JW_GSheet_Sync
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_GSheet_Sync_Webhook
 */
class JW_GSheet_Sync_Webhook {

	/**
	 * Send order data to the webhook.
	 *
	 * @param WC_Order $order   Order object.
	 * @param array    $payload Payload data (will be JSON encoded).
	 * @return array{success: bool, response: array, message: string}
	 */
	public function send( $order, $payload ) {
		$settings = JW_GSheet_Sync::instance()->get_settings();
		$url      = $settings->get( 'webhook_url' );

		if ( empty( $url ) ) {
			$this->log( 'Webhook URL is not configured.', $order, 'error' );
			return array(
				'success'  => false,
				'response' => array(),
				'message'  => __( 'Webhook URL is not configured.', 'jw-gsheet-sync' ),
			);
		}

		$body = wp_json_encode( $payload );
		if ( false === $body ) {
			$this->log( 'Failed to encode payload to JSON.', $order, 'error' );
			return array(
				'success'  => false,
				'response' => array(),
				'message'  => __( 'Failed to encode payload.', 'jw-gsheet-sync' ),
			);
		}

		// Don't follow redirects - Google Apps Script POST can return 400 when redirects are followed.
		$response = wp_remote_post(
			$url,
			array(
				'timeout'     => 30,
				'redirection' => 0, // Apps Script returns 400 if redirects are followed; a 302 means doPost ran (row appended) and is treated as success below
				'headers'     => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'        => $body,
			)
		);

		$result = $this->parse_response( $response, $order );
		$this->log_result( $result, $order );

		return $result;
	}

	/**
	 * Parse wp_remote_post response into a result array.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @param WC_Order       $order    Order object.
	 * @return array{success: bool, response: array, message: string}
	 */
	private function parse_response( $response, $order ) {
		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			return array(
				'success'  => false,
				'response' => array(),
				'message'  => $message,
			);
		}

		$code     = wp_remote_retrieve_response_code( $response );
		$body     = wp_remote_retrieve_body( $response );
		$decoded  = json_decode( $body, true );

		// Success: HTTP 2xx OR response body has success: true (Google Apps Script may return 400
		// even when doPost runs successfully due to redirect/Content-Type quirks).
		$body_success = is_array( $decoded ) && ! empty( $decoded['success'] );
		// A 302 from Apps Script /exec means doPost completed and is redirecting to its
		// output; the row is already appended. So accept 2xx AND 3xx (or an explicit
		// success:true body) as success.
		$success      = ( $code >= 200 && $code < 400 ) || $body_success;
		$summary      = $this->build_response_summary( $code, $body, $decoded, $success );

		return array(
			'success'  => $success,
			'response' => is_array( $decoded ) ? $decoded : array( 'raw' => $body ),
			'message'  => $summary,
		);
	}

	/**
	 * Build a short summary of the response for storage.
	 *
	 * @param int    $code    HTTP status code.
	 * @param string $body    Raw body.
	 * @param mixed  $decoded Decoded JSON.
	 * @param bool   $success Whether the operation was considered successful.
	 * @return string
	 */
	private function build_response_summary( $code, $body, $decoded, $success = false ) {
		if ( $success ) {
			if ( is_array( $decoded ) && isset( $decoded['message'] ) ) {
				return sanitize_text_field( $decoded['message'] );
			}
			return sprintf( __( 'Success (HTTP %d)', 'jw-gsheet-sync' ), $code );
		}

		$msg = is_array( $decoded ) && isset( $decoded['error'] )
			? $decoded['error']
			: substr( $body, 0, 200 );
		return sprintf( __( 'HTTP %d: %s', 'jw-gsheet-sync' ), $code, sanitize_text_field( $msg ) );
	}

	/**
	 * Log a message if logging is enabled.
	 *
	 * @param string   $message Message to log.
	 * @param WC_Order $order   Order object (optional).
	 * @param string   $level   Log level (debug, info, notice, warning, error).
	 */
	public function log( $message, $order = null, $level = 'info' ) {
		$settings = JW_GSheet_Sync::instance()->get_settings();
		if ( ! $settings->is_logging_enabled() ) {
			return;
		}

		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$context = array( 'source' => 'jw-gsheet-sync' );
			if ( $order ) {
				$context['order_id'] = $order->get_id();
			}
			$logger->log( $level, $message, $context );
		}
	}

	/**
	 * Log the send result.
	 *
	 * @param array    $result Result from send().
	 * @param WC_Order $order  Order object.
	 */
	private function log_result( $result, $order ) {
		if ( $result['success'] ) {
			$this->log( sprintf( 'Order #%d sent successfully: %s', $order->get_id(), $result['message'] ), $order, 'info' );
		} else {
			$this->log( sprintf( 'Order #%d send failed: %s', $order->get_id(), $result['message'] ), $order, 'error' );
		}
	}
}

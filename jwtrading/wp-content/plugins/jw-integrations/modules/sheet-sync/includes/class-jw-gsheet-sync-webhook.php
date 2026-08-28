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

		$response = self::post_and_read( $url, $body, 30 );

		$result = $this->parse_response( $response, $order );
		$this->log_result( $result, $order );

		return $result;
	}

	/**
	 * POST to an Apps Script web app and return its ACTUAL reply.
	 *
	 * Apps Script answers every POST with a 302 to script.googleusercontent.com.
	 * The script has already run by then; the redirect target only serves the
	 * stored output. Letting WP follow it re-sends the POST body and Google
	 * rejects the second request, so we take the redirect manually and GET the
	 * result. Without this the caller only ever sees "302" and cannot tell a
	 * written row apart from a refused one.
	 *
	 * @param string $url     Web app /exec URL.
	 * @param string $body    JSON body.
	 * @param int    $timeout Seconds.
	 * @return array|WP_Error
	 */
	public static function post_and_read( $url, $body, $timeout = 30 ) {
		$response = wp_remote_post(
			$url,
			array(
				'timeout'     => $timeout,
				'redirection' => 0,
				'headers'     => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'        => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
			$location = (string) wp_remote_retrieve_header( $response, 'location' );
			if ( '' === $location ) {
				return $response;
			}
			$followed = wp_remote_get( $location, array( 'timeout' => $timeout ) );
			if ( ! is_wp_error( $followed ) ) {
				return $followed;
			}
		}

		return $response;
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

		// The receiver always answers with {"success": true|false, "message": ...}.
		// When we can read that, it is the only thing worth trusting: a refused
		// payload (bad token, script error) still arrives over a 200/302, so
		// status-only checks report a delivered row that was never written.
		if ( is_array( $decoded ) && array_key_exists( 'success', $decoded ) ) {
			$success = ! empty( $decoded['success'] );
		} else {
			// No readable body (redirect target unreachable, HTML error page).
			// Fall back to the status code so a working sync is never broken by
			// an unexpected response shape.
			$success = ( $code >= 200 && $code < 400 );
		}
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

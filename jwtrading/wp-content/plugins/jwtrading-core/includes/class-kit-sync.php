<?php
defined( 'ABSPATH' ) || exit;

/**
 * Kit (ConvertKit) glue on top of the existing Kit plugin.
 * Subscribes/tags the buyer with product-specific tags on order completion.
 * API v4 docs: https://developers.kit.com/
 */
class JWT_Kit_Sync {

	const API_BASE = 'https://api.kit.com/v4';

	/**
	 * Sync one order to Kit. Returns true on success, WP_Error on failure.
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function sync_order( WC_Order $order ) {
		$api_key = trim( (string) get_option( 'jwt_kit_api_key', '' ) );

		if ( '' === $api_key ) {
			return new WP_Error( 'jwt_kit_no_key', 'Kit API key not configured.' );
		}

		$email      = $order->get_billing_email();
		$first_name = $order->get_billing_first_name();

		$payload = array(
			'email'      => $email,
			'first_name' => $first_name,
			'order_id'   => $order->get_id(),
			'tags'       => self::tags_for_order( $order ),
		);

		// 1) Ensure subscriber exists.
		$sub = self::request( $api_key, 'POST', '/subscribers', array(
			'email_address' => $email,
			'first_name'    => $first_name,
		) );

		if ( is_wp_error( $sub ) ) {
			JWT_Sync_Log::log( $order->get_id(), 'kit', 'failed', $payload, $sub->get_error_message() );
			return $sub;
		}

		// 2) Apply each product tag.
		foreach ( $payload['tags'] as $tag_id ) {
			$res = self::request( $api_key, 'POST', "/tags/{$tag_id}/subscribers", array(
				'email_address' => $email,
			) );

			if ( is_wp_error( $res ) ) {
				JWT_Sync_Log::log( $order->get_id(), 'kit', 'failed', $payload, $res->get_error_message() );
				return $res;
			}
		}

		JWT_Sync_Log::log( $order->get_id(), 'kit', 'success', $payload, 'OK' );
		return true;
	}

	/**
	 * Resolve Kit tag IDs for an order.
	 * Per-product tag: product meta `_jwt_kit_tag_id` (add via product edit screen or ACF).
	 * Fallback: global default tag from settings.
	 */
	protected static function tags_for_order( WC_Order $order ) {
		$tags = array();

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$tag_id     = get_post_meta( $product_id, '_jwt_kit_tag_id', true );
			if ( $tag_id ) {
				$tags[] = (string) $tag_id;
			}
		}

		$default = trim( (string) get_option( 'jwt_kit_default_tag', '' ) );
		if ( $default ) {
			$tags[] = $default;
		}

		return array_values( array_unique( $tags ) );
	}

	/**
	 * Thin wrapper around wp_remote_request for Kit v4.
	 */
	protected static function request( $api_key, $method, $path, $body = array() ) {
		$response = wp_remote_request( self::API_BASE . $path, array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Kit-Api-Key' => $api_key,
			),
			'body'    => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'jwt_kit_http_' . $code,
				'Kit API HTTP ' . $code . ': ' . wp_remote_retrieve_body( $response )
			);
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}
}

<?php
/**
 * Payload builder for JW WooCommerce Google Sheet Sync.
 *
 * Builds the JSON payload sent to the webhook with flexible field mapping.
 *
 * @package JW_GSheet_Sync
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_GSheet_Sync_Payload
 */
class JW_GSheet_Sync_Payload {

	/**
	 * Custom checkout field mapping.
	 *
	 * Each key is the output field name. Value is an array of possible meta keys
	 * to check (in order). First found value is used.
	 *
	 * Edit this array to add or change custom field mappings.
	 *
	 * @var array<string, array<string>>
	 */
	const CUSTOM_FIELD_MAPPING = array(
		'discord_username'   => array( '_discord_username', 'discord_username', 'billing_discord_username' ),
		'enrollment_tracker' => array( '_enrollment_tracker', 'enrollment_tracker', 'billing_enrollment_tracker' ),
		'website_label'      => array( '_website_label', 'website_label', 'billing_website_label' ),
		'whatsapp_number'    => array( '_whatsapp_number', 'whatsapp_number', 'billing_whatsapp_number' ),
	);

	/**
	 * Build the full payload for an order.
	 *
	 * @param WC_Order $order   The order object.
	 * @param string   $secret  Secret token.
	 * @param string   $label   Site label.
	 * @return array
	 */
	public static function build( $order, $secret, $label ) {
		if ( ! $order instanceof WC_Order ) {
			return array();
		}

		$payload = array(
			// General
			'secret_token'         => $secret,
			'site_label'           => $label,
			'order_id'             => $order->get_id(),
			'order_number'         => $order->get_order_number(),
			'order_status'         => $order->get_status(),
			'order_date'           => $order->get_date_created() ? $order->get_date_created()->format( 'Y-m-d H:i:s' ) : '',
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'transaction_id'       => $order->get_transaction_id(),
			'currency'             => $order->get_currency(),
			'total'                => (float) $order->get_total(),
			'subtotal'             => (float) $order->get_subtotal(),
			'discount_total'       => (float) $order->get_discount_total(),
			'customer_note'        => $order->get_customer_note(),
			// Customer / Billing
			'first_name'           => $order->get_billing_first_name(),
			'last_name'            => $order->get_billing_last_name(),
			'full_name'            => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'email'                => $order->get_billing_email(),
			'phone'                => $order->get_billing_phone(),
			'country'              => $order->get_billing_country(),
		);

		// Order items
		$payload = array_merge( $payload, self::get_order_items_data( $order ) );

		// Custom checkout fields
		$payload = array_merge( $payload, self::get_custom_fields( $order ) );

		return $payload;
	}

	/**
	 * Get order items summary data.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private static function get_order_items_data( $order ) {
		$product_names   = array();
		$quantities      = array();
		$skus            = array();
		$item_count      = 0;

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			$name    = $item->get_name();
			$qty     = (int) $item->get_quantity();

			$product_names[] = $name;
			$item_count     += $qty;

			if ( $product && $product->get_sku() ) {
				$skus[] = $product->get_sku() . ' x' . $qty;
			}

			$quantities[] = $name . ': ' . $qty;
		}

		return array(
			'product_names'      => implode( ' | ', $product_names ),
			'item_count'         => $item_count,
			'quantities_summary' => implode( ' | ', $quantities ),
			'sku_summary'        => implode( ' | ', $skus ),
		);
	}

	/**
	 * Get custom checkout fields from order meta.
	 *
	 * Uses CUSTOM_FIELD_MAPPING to find values from various possible meta keys.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private static function get_custom_fields( $order ) {
		$result = array();

		foreach ( self::CUSTOM_FIELD_MAPPING as $output_key => $meta_keys ) {
			$value = self::get_first_available_meta( $order, $meta_keys );
			$result[ $output_key ] = $value;
		}

		return $result;
	}

	/**
	 * Get the first available meta value from a list of keys.
	 *
	 * @param WC_Order $order     Order object.
	 * @param array    $meta_keys Array of meta keys to try.
	 * @return string
	 */
	private static function get_first_available_meta( $order, $meta_keys ) {
		foreach ( $meta_keys as $key ) {
			$value = $order->get_meta( $key );
			if ( '' !== $value && null !== $value ) {
				return is_string( $value ) ? $value : (string) $value;
			}
		}
		return '';
	}

	/**
	 * Get the custom field mapping config for documentation/extensibility.
	 *
	 * @return array
	 */
	public static function get_custom_field_mapping() {
		return self::CUSTOM_FIELD_MAPPING;
	}
}

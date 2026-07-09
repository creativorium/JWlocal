<?php
/**
 * Order sync logic for JW WooCommerce Google Sheet Sync.
 *
 * @package JW_GSheet_Sync
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order meta keys for sync tracking.
 */
define( 'JW_GSHEET_META_SENT', '_jw_gsheet_sent' );
define( 'JW_GSHEET_META_SENT_AT', '_jw_gsheet_sent_at' );
define( 'JW_GSHEET_META_RESPONSE', '_jw_gsheet_response' );

/**
 * Class JW_GSheet_Sync_Order_Sync
 */
class JW_GSheet_Sync_Order_Sync {

	/**
	 * Webhook sender instance.
	 *
	 * @var JW_GSheet_Sync_Webhook
	 */
	private $webhook;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->webhook = new JW_GSheet_Sync_Webhook();
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 4 );
	}

	/**
	 * Handle order status change.
	 *
	 * @param int      $order_id   Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order      Order object.
	 */
	public function on_order_status_changed( $order_id, $old_status, $new_status, $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		$settings = JW_GSheet_Sync::instance()->get_settings();
		if ( ! $settings->is_trigger_status( $new_status ) ) {
			return;
		}

		// Skip if already sent (unless manual resend - handled elsewhere).
		if ( $this->is_already_sent( $order ) ) {
			return;
		}

		$this->send_order( $order );
	}

	/**
	 * Check if order was already sent to Google Sheet.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	public function is_already_sent( $order ) {
		return 'yes' === $order->get_meta( JW_GSHEET_META_SENT );
	}

	/**
	 * Send order to webhook and update meta.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $force Force send even if already sent (for manual resend).
	 * @return array{success: bool, response: array, message: string}
	 */
	public function send_order( $order, $force = false ) {
		if ( ! $order instanceof WC_Order ) {
			return array(
				'success'  => false,
				'response' => array(),
				'message'  => __( 'Invalid order.', 'jw-gsheet-sync' ),
			);
		}

		if ( ! $force && $this->is_already_sent( $order ) ) {
			return array(
				'success'  => false,
				'response' => array(),
				'message'  => __( 'Order already sent. Use Resend to send again.', 'jw-gsheet-sync' ),
			);
		}

		$settings = JW_GSheet_Sync::instance()->get_settings();
		$secret   = $settings->get( 'secret_token' );
		$label    = $settings->get( 'site_label' );

		$payload = JW_GSheet_Sync_Payload::build( $order, $secret, $label );
		$result  = $this->webhook->send( $order, $payload );

		// Update order meta with result.
		$order->update_meta_data( JW_GSHEET_META_SENT, $result['success'] ? 'yes' : 'no' );
		$order->update_meta_data( JW_GSHEET_META_SENT_AT, current_time( 'mysql' ) );
		$order->update_meta_data( JW_GSHEET_META_RESPONSE, $result['message'] );
		$order->save();

		return $result;
	}

	/**
	 * Force resend order (manual resend).
	 *
	 * @param WC_Order $order Order object.
	 * @return array{success: bool, response: array, message: string}
	 */
	public function resend_order( $order ) {
		return $this->send_order( $order, true );
	}
}

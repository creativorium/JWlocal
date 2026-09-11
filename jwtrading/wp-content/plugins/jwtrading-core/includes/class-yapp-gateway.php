<?php
defined( 'ABSPATH' ) || exit;

/**
 * Yapp as a real WooCommerce payment gateway.
 *
 * WHY A GATEWAY
 * The standalone "Bayar dengan Yapp" button was fine as a parallel option, but it
 * sits outside WooCommerce's checkout submission: it has to re-implement the
 * Discord/terms/phone validation, it can't use Woo's coupon handling, and it never
 * fires woocommerce_checkout_order_created (so those buyers skipped the
 * Checkout_Started Kit tag). Registering a gateway instead means the normal
 * "Checkout →" button drives Yapp, and all of that comes back for free.
 *
 * FLOW
 *  1. Buyer fills the checkout and presses "Checkout →".
 *  2. WooCommerce validates, creates a PENDING order, then calls process_payment().
 *  3. We raise a Yapp invoice for that order and redirect to Yapp's hosted page.
 *  4. Yapp's webhook marks the existing order Completed (JWT_Yapp::complete_from_payload).
 *
 * Unpaid orders are swept by WooCommerce's own 24h cancel window, which
 * class-checkout.php already configures — same as the Duitku flow it replaces.
 */
class JWT_Yapp_Gateway extends WC_Payment_Gateway {

	const GATEWAY_ID = 'jwt_yapp';

	public function __construct() {
		$this->id                 = self::GATEWAY_ID;
		$this->method_title       = __( 'Yapp', 'jwtrading' );
		$this->method_description = __( 'Buyers pay on Yapp\'s hosted checkout. Yapp grants course access itself; the order here is marked paid by Yapp\'s webhook. Credentials live under WooCommerce → Yapp Checkout.', 'jwtrading' );
		$this->has_fields         = false;

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title', __( 'Yapp', 'jwtrading' ) );
		$this->description = $this->get_option( 'description', '' );
		$this->enabled     = $this->get_option( 'enabled', 'no' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'       => __( 'Enable/Disable', 'jwtrading' ),
				'type'        => 'checkbox',
				'label'       => __( 'Pay with Yapp', 'jwtrading' ),
				'default'     => 'no',
				'description' => __( 'Turning this on makes the main Checkout button send buyers to Yapp. Disable the Duitku methods separately when you cut over.', 'jwtrading' ),
			),
			'title'       => array(
				'title'       => __( 'Title', 'jwtrading' ),
				'type'        => 'text',
				'description' => __( 'Shown to the buyer in the payment method list.', 'jwtrading' ),
				'default'     => __( 'Pembayaran via Yapp', 'jwtrading' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'jwtrading' ),
				'type'        => 'textarea',
				'description' => __( 'Optional text under the title at checkout. Leave blank for none.', 'jwtrading' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Only offer Yapp when it can actually complete a payment, otherwise a buyer
	 * could select it and hit a dead end at the redirect.
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}
		if ( ! class_exists( 'JWT_Yapp' ) ) {
			return false;
		}
		/*
		 * In Mock Mode this pays with a simulated invoice, so it stays staff-only —
		 * but it must remain usable, otherwise the whole "Checkout →" flow can't be
		 * rehearsed on Local before going live (Local is http, and Yapp rejects a
		 * non-https redirectUrl, so a real invoice can't be raised there either).
		 */
		if ( JWT_Yapp::is_mock() && ! JWT_Yapp::can_simulate() ) {
			return false;
		}
		return parent::is_available();
	}

	/**
	 * Raise the invoice and hand the buyer to Yapp.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Pesanan tidak ditemukan.', 'jwtrading' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$result = JWT_Yapp::create_invoice_for_order( $order );

		if ( is_wp_error( $result ) ) {
			// Leave the order pending and keep the buyer on checkout with a reason;
			// the 24h unpaid sweep tidies up anything abandoned here.
			$order->add_order_note(
				sprintf(
					/* translators: %s: error message from Yapp. */
					__( 'Yapp invoice gagal dibuat: %s', 'jwtrading' ),
					$result->get_error_message()
				)
			);
			wc_add_notice( $result->get_error_message(), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s: Yapp invoice UUID. */
				__( 'Invoice Yapp dibuat (%s). Menunggu pembayaran.', 'jwtrading' ),
				$result['invoice_uuid']
			)
		);

		// Standard gateway behaviour: the order now holds the purchase.
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

		return array(
			'result'   => 'success',
			'redirect' => $result['checkout_link'],
		);
	}
}

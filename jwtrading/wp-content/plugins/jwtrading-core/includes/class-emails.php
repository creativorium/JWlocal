<?php
defined( 'ABSPATH' ) || exit;

/**
 * Order email + admin order screen tweaks — ported from the live site.
 * Admin "New order" email gets a tidy customer table (incl. Discord)
 * instead of the default billing-address block.
 */
class JWT_Emails {

	public static function init() {
		add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'admin_customer_table' ), 20, 4 );
		add_action( 'woocommerce_email_before_order_table', array( __CLASS__, 'remove_default_customer_details' ), 1, 4 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_order_discord' ) );
		add_filter( 'wp_mail', array( __CLASS__, 'strip_pro_elements_footer' ) );
	}

	/** Customer details table in the admin "New order" email. */
	public static function admin_customer_table( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! $sent_to_admin ) {
			return;
		}
		if ( empty( $email ) || empty( $email->id ) || 'new_order' !== $email->id ) {
			return;
		}

		$discord = $order->get_meta( '_discord_username' );
		$name    = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$phone   = $order->get_billing_phone();
		$mail    = $order->get_billing_email();

		if ( $plain_text ) {
			echo "\n\n=== Customer Details ===\n";
			echo 'Name: ' . esc_html( $name ) . "\n";
			echo 'WA Phone: ' . esc_html( $phone ) . "\n";
			echo 'Email: ' . esc_html( $mail ) . "\n";
			echo 'Discord Username: ' . esc_html( $discord ) . "\n";
			return;
		}

		echo '<h3 style="margin:24px 0 8px; font-size:16px;">Customer Details</h3>';
		echo '<table cellspacing="0" cellpadding="6" style="width:100%; border:1px solid #e5e5e5; border-collapse:collapse;" border="1"><tbody>';

		$rows = array(
			'Nama'             => $name,
			'WA Phone'         => $phone,
			'Email'            => $mail,
			'Discord Username' => $discord,
		);

		foreach ( $rows as $label => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			echo '<tr>';
			echo '<th scope="row" style="text-align:left; width:35%; padding:10px; background:#f7f7f7;">' . esc_html( $label ) . '</th>';
			echo '<td style="text-align:left; padding:10px;">' . esc_html( $value ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/** Drop the default billing-address block from the admin "New order" email. */
	public static function remove_default_customer_details( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! $sent_to_admin ) {
			return;
		}
		if ( empty( $email ) || empty( $email->id ) || 'new_order' !== $email->id ) {
			return;
		}

		remove_all_actions( 'woocommerce_email_customer_details' );
	}

	/** Discord username on the admin order edit screen. */
	public static function admin_order_discord( $order ) {
		$discord = $order->get_meta( '_discord_username' );
		if ( empty( $discord ) ) {
			return;
		}

		echo '<p><strong>' . esc_html__( 'Discord Username:', 'jwtrading' ) . '</strong> ' . esc_html( $discord ) . '</p>';
	}

	/**
	 * Strip the PRO Elements promo line from outgoing mail.
	 * Obsolete once Elementor/PRO Elements is removed — delete then.
	 */
	public static function strip_pro_elements_footer( $args ) {
		if ( ! empty( $args['message'] ) ) {
			$args['message'] = str_replace( 'Powered by: PRO Elements', '', $args['message'] );
		}
		return $args;
	}
}

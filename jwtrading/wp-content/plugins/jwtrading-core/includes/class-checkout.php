<?php
defined( 'ABSPATH' ) || exit;

/**
 * Slim virtual-only checkout — ported from the live site's child-theme code.
 *
 * Behavior (only when the cart needs no shipping):
 *  - Billing reduced to first/last name, WA phone, email (+ Discord field).
 *  - Terms checkbox required (link filterable via `jwt/terms_url`).
 *  - Buy-now flow: qty locked to 1, add-to-cart goes straight to checkout,
 *    cart page redirects to checkout, duplicate adds don't error.
 *  - Two-column checkout layout wrappers (styled in the child theme).
 *  - "Checkout_Started" Kit tagging via the `jw_kit_tag_subscriber` action
 *    (same contract as the legacy jw-kit-auto-tagger plugin).
 */
class JWT_Checkout {

	public static function init() {
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'slim_fields' ), 99999 );
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'strip_address_fields' ), 99999 );
		add_filter( 'woocommerce_enable_order_notes_field', array( __CLASS__, 'disable_order_notes' ) );

		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'discord_field' ), 20 );
		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'manual_transfer_cta' ), 30 );
		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'payment_notice' ), 40 );
		add_action( 'woocommerce_review_order_before_submit', array( __CLASS__, 'terms_checkbox' ), 9 );
		add_action( 'woocommerce_review_order_after_submit', array( __CLASS__, 'after_submit_extras' ), 10 );

		// Header (eyebrow + title + trust badges) above the form.
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'checkout_header' ), 1 );

		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_discord' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_checkout' ), 99999 );

		// Buy-now flow.
		add_filter( 'woocommerce_is_sold_individually', array( __CLASS__, 'sold_individually' ), 9999, 2 );
		add_filter( 'woocommerce_add_to_cart_quantity', array( __CLASS__, 'force_single_qty' ), 9999, 2 );
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'lock_cart_qty' ), 9999, 3 );
		add_filter( 'woocommerce_add_to_cart_redirect', array( __CLASS__, 'redirect_to_checkout' ) );
		add_action( 'template_redirect', array( __CLASS__, 'block_cart_page' ) );
		add_action( 'template_redirect', array( __CLASS__, 'readd_to_checkout' ), 5 );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'prevent_duplicate_add' ), 20, 3 );
		add_filter( 'woocommerce_get_notices', array( __CLASS__, 'strip_duplicate_notice' ), 9999, 2 );

		// Two-column layout wrappers.
		add_action( 'woocommerce_checkout_before_customer_details', array( __CLASS__, 'grid_open' ), 1 );
		add_action( 'woocommerce_checkout_after_customer_details', array( __CLASS__, 'grid_left_close' ), 999 );
		add_action( 'woocommerce_checkout_before_order_review_heading', array( __CLASS__, 'grid_right_open' ), 1 );
		add_action( 'woocommerce_checkout_after_order_review', array( __CLASS__, 'grid_close' ), 999 );

		add_filter( 'gettext', array( __CLASS__, 'subtotal_to_total' ), 9999, 3 );

		// Give Duitku VA/QR payers time to finish (24h before auto-cancel).
		add_filter( 'woocommerce_cancel_unpaid_orders_interval', array( __CLASS__, 'unpaid_cancel_interval' ) );

		// Abandoned-checkout tagging.
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'tag_checkout_started' ), 10, 3 );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'clear_checkout_started_flag' ) );
	}

	/** Virtual-only cart (no shipping needed)? */
	protected static function virtual_mode(): bool {
		return function_exists( 'WC' ) && WC()->cart && ! WC()->cart->needs_shipping();
	}

	/** During a wc-ajax/admin-ajax refresh of checkout fragments? */
	protected static function is_checkout_ajax(): bool {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! empty( $_GET['wc-ajax'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/** Keep only the fields a virtual course sale needs; localize labels. */
	public static function slim_fields( $fields ) {
		if ( is_admin() || ! self::virtual_mode() ) {
			return $fields;
		}

		$keep = array( 'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone' );

		if ( isset( $fields['billing'] ) ) {
			foreach ( array_keys( $fields['billing'] ) as $key ) {
				if ( ! in_array( $key, $keep, true ) ) {
					unset( $fields['billing'][ $key ] );
				}
			}
		}

		if ( isset( $fields['billing']['billing_first_name'] ) ) {
			$fields['billing']['billing_first_name'] = array_merge(
				$fields['billing']['billing_first_name'],
				array(
					'label'    => __( 'Nama Depan', 'jwtrading' ),
					'required' => true,
					'priority' => 10,
					'class'    => array( 'form-row-wide' ),
				)
			);
		}

		if ( isset( $fields['billing']['billing_last_name'] ) ) {
			$fields['billing']['billing_last_name'] = array_merge(
				$fields['billing']['billing_last_name'],
				array(
					'label'    => __( 'Nama Belakang', 'jwtrading' ),
					'required' => true,
					'priority' => 20,
					'class'    => array( 'form-row-wide' ),
				)
			);
		}

		$fields['billing']['billing_phone'] = array_merge(
			$fields['billing']['billing_phone'] ?? array(),
			array(
				'type'        => 'tel',
				'label'       => __( 'WA Phone', 'jwtrading' ),
				'required'    => true,
				'class'       => array( 'form-row-wide' ),
				'priority'    => 30,
				'placeholder' => 'e.g. +62 812 3456 7890',
			)
		);

		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email'] = array_merge(
				$fields['billing']['billing_email'],
				array(
					'label'    => __( 'Email Address', 'jwtrading' ),
					'required' => true,
					'priority' => 40,
					'class'    => array( 'form-row-wide' ),
				)
			);
		}

		unset( $fields['shipping'] );

		unset( $fields['order'] );

		return $fields;
	}

	/** Hide the "Additional information" / order-notes block on virtual sales. */
	public static function disable_order_notes( $enabled ) {
		return self::virtual_mode() ? false : $enabled;
	}

	/** Remove address UI entirely for virtual carts. */
	public static function strip_address_fields( $address_fields ) {
		if ( is_admin() || ! self::virtual_mode() ) {
			return $address_fields;
		}

		foreach ( array( 'country', 'address_1', 'address_2', 'city', 'state', 'postcode' ) as $key ) {
			unset( $address_fields[ $key ] );
		}

		return $address_fields;
	}

	/** Discord username field (required; the admin invites buyers manually). */
	public static function discord_field( $checkout ) {
		if ( ! self::virtual_mode() ) {
			return;
		}

		woocommerce_form_field(
			'discord_username',
			array(
				'type'        => 'text',
				'class'       => array( 'form-row-wide' ),
				'label'       => __( 'Discord Username', 'jwtrading' ),
				'placeholder' => 'e.g. johnsmith or johnsmith#1234',
				'required'    => true,
				'priority'    => 50,
			),
			$checkout->get_value( 'discord_username' )
		);
	}

	/** Manual bank-transfer alternative (Google Form). */
	public static function manual_transfer_cta() {
		$url = apply_filters(
			'jwt/manual_transfer_url',
			'https://docs.google.com/forms/d/e/1FAIpQLSfYjTEGC41lwQ9jl8oCVyMuSLOvh5JpXmfrkSLeiCvJlE-iDA/viewform?usp=sharing&ouid=111044366817694125987'
		);
		?>
		<div id="alt-transfer-box">
			<p class="alt-text"><?php esc_html_e( 'Ingin menggunakan pembayaran menggunakan bank transfer secara manual?', 'jwtrading' ); ?></p>
			<a href="<?php echo esc_url( $url ); ?>" class="alt-btn jwt-btn jwt-btn--ghost" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Gunakan Transfer Manual →', 'jwtrading' ); ?></a>
		</div>
		<?php
	}

	/** Checkout header: eyebrow + title + trust badges (matches the redesign). */
	public static function checkout_header() {
		if ( is_admin() || self::is_checkout_ajax() ) {
			return;
		}

		$eyebrow = apply_filters( 'jwt/checkout_eyebrow', '' );
		$title   = apply_filters( 'jwt/checkout_title', __( 'Checkout', 'jwtrading' ) );
		$badges  = apply_filters(
			'jwt/checkout_trust_badges',
			array(
				__( 'Akses langsung setelah konfirmasi', 'jwtrading' ),
				__( 'Satu kali bayar — akses seumur hidup', 'jwtrading' ),
				__( '5/5 rating di Trustpilot', 'jwtrading' ),
				__( 'Pembayaran terenkripsi & aman', 'jwtrading' ),
			)
		);
		?>
		<header class="jwt-checkout-head">
			<?php if ( $eyebrow ) : ?><span class="jwt-checkout-head__eyebrow"><?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
			<?php if ( $title ) : ?><h1 class="jwt-checkout-head__title"><?php echo esc_html( $title ); ?></h1><?php endif; ?>
			<?php if ( ! empty( $badges ) ) : ?>
				<ul class="jwt-checkout-trust">
					<?php foreach ( $badges as $i => $badge ) : ?>
						<li><span class="jwt-checkout-trust__ico" aria-hidden="true"><?php echo 3 === (int) $i ? '🔒' : '✓'; ?></span><?php echo esc_html( $badge ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</header>
		<?php
	}

	/** Under the place-order button: secure note. */
	public static function after_submit_extras() {
		if ( self::is_checkout_ajax() ) {
			return;
		}
		?>
		<p class="jwt-checkout-secure">🔒 <?php esc_html_e( 'Transaksi aman & terenkripsi', 'jwtrading' ); ?></p>
		<?php
	}

	/** Required terms checkbox with link (was hardcoded to the old dev URL). */
	public static function terms_checkbox() {
		if ( ! self::virtual_mode() ) {
			return;
		}

		$tc_url = apply_filters( 'jwt/terms_url', home_url( '/terms-condition/' ) );

		woocommerce_form_field(
			'jw_accept_terms',
			array(
				'type'     => 'checkbox',
				'class'    => array( 'form-row-wide' ),
				'label'    => 'Setuju dengan syarat dan ketentuan, lihat <a href="' . esc_url( $tc_url ) . '" target="_blank" rel="noopener">Syarat &amp; Ketentuan</a>',
				'required' => true,
			),
			WC()->checkout()->get_value( 'jw_accept_terms' )
		);
	}

	/** Persist Discord username on the order (HPOS-safe). */
	public static function save_discord( $order ) {
		if ( isset( $_POST['discord_username'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Woo checkout handles the nonce.
			$order->update_meta_data( '_discord_username', sanitize_text_field( wp_unslash( $_POST['discord_username'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	/** Discord + phone + terms validation. */
	public static function validate_checkout() {
		if ( ! self::virtual_mode() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Woo checkout handles the nonce.
		if ( empty( $_POST['discord_username'] ) ) {
			wc_add_notice( __( 'Please enter your Discord username.', 'jwtrading' ), 'error' );
		}

		if ( ! empty( $_POST['billing_phone'] ) ) {
			$digits = preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['billing_phone'] ) );
			if ( strlen( $digits ) > 0 && strlen( $digits ) < 7 ) {
				wc_add_notice( __( 'Phone number must be at least 7 digits.', 'jwtrading' ), 'error' );
			}
		}

		if ( empty( $_POST['jw_accept_terms'] ) ) {
			wc_add_notice( __( 'Please accept the terms and conditions.', 'jwtrading' ), 'error' );
		}
		// phpcs:enable
	}

	/** One per customer for the bootcamp product(s). */
	public static function sold_individually( $sold_individually, $product ) {
		$ids = apply_filters( 'jwt/sold_individually_products', array( 684 ) );

		if ( $product && in_array( (int) $product->get_id(), array_map( 'intval', $ids ), true ) ) {
			return true;
		}

		return $sold_individually;
	}

	public static function force_single_qty( $qty, $product_id ) {
		return 1;
	}

	public static function lock_cart_qty( $product_quantity, $cart_item_key, $cart_item ) {
		return '1';
	}

	public static function redirect_to_checkout() {
		return function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '';
	}

	/**
	 * Cart page is skipped entirely — straight to checkout.
	 * Empty cart goes to the bootcamp page instead: Woo bounces an empty
	 * checkout back to the cart, which would otherwise loop forever.
	 */
	public static function block_cart_page() {
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			$has_items = function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty();
			wp_safe_redirect( $has_items ? wc_get_checkout_url() : home_url( '/bootcamp/' ) );
			exit;
		}
	}

	/**
	 * Hitting an add-to-cart URL for something already in the cart: WooCommerce
	 * blocks the (sold-individually) re-add and, because the add "failed", never
	 * applies the add-to-cart redirect filter — so the buyer is left on the same
	 * page instead of checkout. Send them to checkout ourselves.
	 */
	public static function readd_to_checkout() {
		if ( is_admin() || wp_doing_ajax() || empty( $_GET['add-to-cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$pid = absint( wp_unslash( $_GET['add-to-cart'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $pid || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( (int) $item['product_id'] === $pid ) {
				wc_clear_notices();
				wp_safe_redirect( wc_get_checkout_url() );
				exit;
			}
		}
	}

	/** Clicking "buy" twice: no error, just go to checkout. */
	public static function prevent_duplicate_add( $passed, $product_id, $quantity ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return $passed;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( (int) $item['product_id'] === (int) $product_id ) {
				wc_clear_notices();
				add_filter(
					'woocommerce_add_to_cart_redirect',
					function () {
						return wc_get_checkout_url();
					},
					9999
				);
				return false;
			}
		}

		return $passed;
	}

	/** Suppress the "cannot add another" notice if anything still emits it. */
	public static function strip_duplicate_notice( $notices, $notice_type ) {
		if ( 'error' !== $notice_type ) {
			return $notices;
		}

		foreach ( $notices as $k => $notice ) {
			$msg = is_array( $notice ) ? ( $notice['notice'] ?? '' ) : (string) $notice;
			if ( false !== stripos( $msg, 'cannot add another' ) ) {
				unset( $notices[ $k ] );
			}
		}

		return $notices;
	}

	// --- Two-column layout wrappers (styles live in the child theme) --------

	public static function grid_open() {
		if ( ! self::is_checkout_ajax() ) {
			echo '<div class="jw-checkout-grid"><div class="jw-checkout-left">';
		}
	}

	public static function grid_left_close() {
		if ( ! self::is_checkout_ajax() ) {
			echo '</div>';
		}
	}

	public static function grid_right_open() {
		if ( ! self::is_checkout_ajax() ) {
			echo '<div class="jw-checkout-right">';
		}
	}

	public static function grid_close() {
		if ( ! self::is_checkout_ajax() ) {
			echo '</div></div>';
		}
	}

	/** Localize a few checkout strings to match the redesign copy. */
	public static function subtotal_to_total( $translated, $text, $domain ) {
		if ( ! is_checkout() || is_wc_endpoint_url() ) {
			return $translated;
		}

		if ( 'woocommerce' === $domain ) {
			switch ( $text ) {
				case 'Subtotal':
					return __( 'Total', 'jwtrading' );
				case 'Your order':
					return __( 'Pesanan Kamu', 'jwtrading' );
				case 'Billing details':
					return __( 'Detail Billing', 'jwtrading' );
			}
		}

		return $translated;
	}

	/** Duitku OTP/blank-page notice above the checkout form. */
	public static function payment_notice() {
		if ( is_admin() ) {
			return;
		}

		echo '<div class="jwt-checkout-notice">⚠️ <strong>Payment Notice:</strong><br>'
			. esc_html__( 'If the payment page appears blank after OTP verification, please wait a moment and refresh your browser. Your payment may still be processing.', 'jwtrading' )
			. '</div>';
	}

	public static function unpaid_cancel_interval( $seconds ) {
		return 60 * 60 * 24; // 24 hours.
	}

	/**
	 * Fire "Checkout_Started" tagging when the order is created (even if
	 * payment later fails) — Kit automation handles the abandoned timing.
	 */
	public static function tag_checkout_started( $order_id, $posted_data, $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		if ( $order->get_meta( '_jw_checkout_started_tagged' ) ) {
			return;
		}

		$email = $order->get_billing_email();
		if ( ! $email || ! is_email( $email ) ) {
			return;
		}

		// Mark before the external call to prevent doubles on re-submits.
		$order->update_meta_data( '_jw_checkout_started_tagged', 1 );
		$order->save();

		do_action(
			'jw_kit_tag_subscriber',
			array(
				'email'      => $email,
				'form_id'    => 'Checkout_Started',
				'order_id'   => (int) $order_id,
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
			)
		);
	}

	public static function clear_checkout_started_flag( $order_id ) {
		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( $order ) {
			$order->delete_meta_data( '_jw_checkout_started_tagged' );
			$order->save();
		}
	}
}

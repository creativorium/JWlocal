<?php
defined( 'ABSPATH' ) || exit;

/**
 * Thank-you / order-received page — ported from the live site.
 * Localized heading, "Langkah Selanjutnya" block, hidden billing details,
 * minimal totals table. Styles live in the child theme (_woocommerce.scss).
 */
class JWT_Thankyou {

	public static function init() {
		add_filter( 'woocommerce_thankyou_order_received_text', array( __CLASS__, 'received_text' ), 10, 2 );
		add_filter( 'the_title', array( __CLASS__, 'page_title' ) );
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'next_steps' ), 5 );
		add_filter( 'woocommerce_order_details_show_customer_details', array( __CLASS__, 'hide_customer_details' ), 9999 );
		add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'tidy_totals' ), 9999, 2 );
	}

	public static function received_text( $text, $order ) {
		return __( 'Terima kasih. Pesanan Anda telah berhasil kami terima.', 'jwtrading' );
	}

	public static function page_title( $title ) {
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() && in_the_loop() ) {
			return __( 'Pesanan Berhasil Diproses', 'jwtrading' );
		}
		return $title;
	}

	/** "Langkah Selanjutnya" card: check email + join Discord + WA help. */
	public static function next_steps( $order_id ) {
		if ( ! is_order_received_page() || ! $order_id ) {
			return;
		}

		$discord_url  = apply_filters( 'jwt/discord_url', home_url( '/discord/' ) );
		$whatsapp     = apply_filters( 'jwt/whatsapp_number', '628113931505' );
		$whatsapp_url = 'https://wa.me/' . $whatsapp;
		$wa_display   = '+' . $whatsapp;
		?>
		<section class="jw-thankyou-card jw-next-steps">
			<h3><?php esc_html_e( 'Langkah Selanjutnya', 'jwtrading' ); ?></h3>

			<div class="jw-steps-grid">
				<div class="jw-step">
					<div class="jw-step-title">1️⃣ <?php esc_html_e( 'Cek Email Anda', 'jwtrading' ); ?></div>
					<p><?php esc_html_e( 'Silakan periksa email Anda untuk mendapatkan link akses ke video course Bootcamp.', 'jwtrading' ); ?></p>
				</div>

				<div class="jw-step">
					<div class="jw-step-title">2️⃣ <?php esc_html_e( 'Bergabung ke Discord', 'jwtrading' ); ?></div>
					<p><?php esc_html_e( 'Pastikan Anda sudah bergabung ke komunitas Discord kami. Admin kami akan menambahkan Anda ke channel khusus member dalam beberapa jam setelah pembelian dikonfirmasi.', 'jwtrading' ); ?></p>
					<a class="jw-btn" href="<?php echo esc_url( $discord_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Join Discord', 'jwtrading' ); ?></a>
				</div>
			</div>

			<div class="jw-help">
				<div class="jw-help-left">
					<div class="jw-help-text"><?php esc_html_e( 'Butuh bantuan?', 'jwtrading' ); ?></div>
					<div class="jw-help-wa">
						<?php esc_html_e( 'Hubungi WA:', 'jwtrading' ); ?> <a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $wa_display ); ?></a>
					</div>
					<div class="jw-help-meta">
						<strong>JW Trading Academy</strong><br>
						Email: <a href="mailto:info@jwtradingacademy.com">info@jwtradingacademy.com</a>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/** No billing-address block on thank-you / view-order. */
	public static function hide_customer_details( $show ) {
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return false;
		}
		if ( function_exists( 'is_view_order_page' ) && is_view_order_page() ) {
			return false;
		}
		return $show;
	}

	/** Final total only (no subtotal/discount rows) on thank-you / view-order. */
	public static function tidy_totals( $totals, $order ) {
		$on_ty  = function_exists( 'is_order_received_page' ) && is_order_received_page();
		$on_vo  = function_exists( 'is_view_order_page' ) && is_view_order_page();

		if ( ! $on_ty && ! $on_vo ) {
			return $totals;
		}

		unset( $totals['cart_subtotal'], $totals['discount'] );

		return $totals;
	}
}

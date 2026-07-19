<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manual bank-transfer payments — on-site capture instead of an off-grid Google Form.
 *
 * Flow:
 *  1. On checkout, "Gunakan Transfer Manual" is gated on the required data
 *     (nama/WA/email/Discord + terms). Missing data → themed warning, no proceed.
 *  2. Valid → a manual-payment RECORD is created (status "pending"), snapshotting
 *     the buyer's billing data + cart (products, coupons, amount). No WooCommerce
 *     order exists yet. Buyer lands on a themed instruction screen with the JW bank
 *     account + amount to transfer.
 *  3. After paying, buyer clicks "Verifikasi Pesanan" and tells us which bank they
 *     sent from + the account-holder name → record moves to "submitted" and admin
 *     is notified (email + WhatsApp link). Buyer sees a "wait for confirmation" page.
 *  4. Admin → "Manual Payment" page → Confirm → a real WooCommerce order is built
 *     from the snapshot and marked Completed, which fires Kit + Sheets + Thinkific
 *     automatically. Record → "confirmed", linked to the order.
 *
 * The record store is our own table {prefix}jwt_manual_payments; the WooCommerce
 * order only ever exists once admin confirms.
 */
class JWT_Manual_Payment {

	const OPT       = 'jwt_manual_payment';   // Settings array.
	const DB_OPT    = 'jwt_manual_db_version';
	const DB_VER    = '1';
	const NONCE     = 'jwt_manual';
	const ADM_NONCE = 'jwt_manual_admin';

	const S_PENDING   = 'pending';    // Clicked manual transfer, awaiting their payment + verify.
	const S_SUBMITTED = 'submitted';  // Verify form submitted, awaiting admin confirmation.
	const S_CONFIRMED = 'confirmed';  // Admin confirmed → order created + completed.
	const S_CANCELLED = 'cancelled';  // Buyer cancelled / abandoned.

	public static function init() {
		// Ensure the table exists without needing a plugin reactivation.
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_table' ) );

		// Front-end.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_checkout' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render_screen' ), 5 );
		add_action( 'wp_ajax_jwt_manual_create', array( __CLASS__, 'ajax_create' ) );
		add_action( 'wp_ajax_nopriv_jwt_manual_create', array( __CLASS__, 'ajax_create' ) );
		add_action( 'wp_ajax_jwt_manual_verify', array( __CLASS__, 'ajax_verify' ) );
		add_action( 'wp_ajax_nopriv_jwt_manual_verify', array( __CLASS__, 'ajax_verify' ) );
		add_action( 'wp_ajax_jwt_manual_cancel', array( __CLASS__, 'ajax_cancel' ) );
		add_action( 'wp_ajax_nopriv_jwt_manual_cancel', array( __CLASS__, 'ajax_cancel' ) );

		// Admin.
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_ajax_jwt_manual_admin_confirm', array( __CLASS__, 'ajax_admin_confirm' ) );
		add_action( 'wp_ajax_jwt_manual_admin_cancel', array( __CLASS__, 'ajax_admin_cancel' ) );
	}

	// --- Data store -----------------------------------------------------------

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'jwt_manual_payments';
	}

	public static function create_table() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = self::table();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			token VARCHAR(64) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			first_name VARCHAR(100) NULL,
			last_name VARCHAR(100) NULL,
			email VARCHAR(191) NULL,
			phone VARCHAR(50) NULL,
			discord VARCHAR(100) NULL,
			amount DECIMAL(12,2) NOT NULL DEFAULT 0,
			currency VARCHAR(10) NULL,
			items LONGTEXT NULL,
			sender_bank VARCHAR(100) NULL,
			sender_account_name VARCHAR(150) NULL,
			order_id BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY token (token),
			KEY status (status)
		) {$charset};" );

		update_option( self::DB_OPT, self::DB_VER );
	}

	public static function maybe_create_table() {
		if ( get_option( self::DB_OPT ) !== self::DB_VER ) {
			self::create_table();
		}
	}

	// --- Settings -------------------------------------------------------------

	public static function settings() {
		$defaults = array(
			'enabled'             => 1,
			'dest_bank'           => 'JW-BANK',
			'dest_account_name'   => 'JW-Account',
			'dest_account_number' => '0000-0000-0000',
			'wa_number'           => '620000000000',
			'notify_email'        => 'info@jwtradingacademy.com',
			// Where the checkout button goes when the on-site flow is OFF (original method).
			'form_url'            => 'https://docs.google.com/forms/d/e/1FAIpQLSfYjTEGC41lwQ9jl8oCVyMuSLOvh5JpXmfrkSLeiCvJlE-iDA/viewform',
		);
		$saved = get_option( self::OPT, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	/** Master switch — when off, the whole manual-transfer flow is disabled (old checkout format). */
	public static function is_enabled() {
		return ! empty( self::settings()['enabled'] );
	}

	public static function register_settings() {
		register_setting(
			'jwt_manual_group',
			self::OPT,
			array(
				'sanitize_callback' => static function ( $in ) {
					$in = is_array( $in ) ? $in : array();
					return array(
						'enabled'             => empty( $in['enabled'] ) ? 0 : 1,
						'dest_bank'           => sanitize_text_field( $in['dest_bank'] ?? '' ),
						'dest_account_name'   => sanitize_text_field( $in['dest_account_name'] ?? '' ),
						'dest_account_number' => sanitize_text_field( $in['dest_account_number'] ?? '' ),
						'wa_number'           => preg_replace( '/\D+/', '', (string) ( $in['wa_number'] ?? '' ) ),
						'notify_email'        => sanitize_email( $in['notify_email'] ?? '' ),
						'form_url'            => esc_url_raw( $in['form_url'] ?? '' ),
					);
				},
			)
		);
	}

	/** Major Indonesian banks offered on the verify form. */
	public static function banks() {
		return apply_filters(
			'jwt/manual_banks',
			array( 'Mandiri', 'BCA', 'BNI', 'BRI', 'CIMB Niaga', 'Permata', 'Bank Mega', 'Danamon', 'BSI', 'BTN', 'Lainnya' )
		);
	}

	// --- Front-end assets -----------------------------------------------------

	public static function enqueue_checkout() {
		if ( ! self::is_enabled() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) {
			return;
		}
		self::enqueue_assets();
	}

	protected static function enqueue_assets( $token = '' ) {
		wp_enqueue_style( 'jwt-manual', JWT_CORE_URL . 'assets/manual-payment.css', array(), JWT_CORE_VERSION );
		wp_enqueue_script( 'jwt-manual', JWT_CORE_URL . 'assets/manual-payment.js', array(), JWT_CORE_VERSION, true );
		wp_localize_script(
			'jwt-manual',
			'JWT_MANUAL',
			array(
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( self::NONCE ),
				'token'          => $token,
				'labels'         => array(
					'first'   => __( 'Nama Depan', 'jwtrading' ),
					'last'    => __( 'Nama Belakang', 'jwtrading' ),
					'email'   => __( 'Email', 'jwtrading' ),
					'phone'   => __( 'WA Phone', 'jwtrading' ),
					'discord' => __( 'Discord Username', 'jwtrading' ),
					'terms'   => __( 'Syarat & Ketentuan', 'jwtrading' ),
				),
				'msg_incomplete' => __( 'Mohon lengkapi data berikut sebelum melanjutkan:', 'jwtrading' ),
				'msg_email'      => __( 'Format email tidak valid.', 'jwtrading' ),
				'msg_generic'    => __( 'Terjadi kesalahan. Silakan coba lagi.', 'jwtrading' ),
				'msg_account'    => __( 'Mohon isi nama pemilik rekening.', 'jwtrading' ),
				'msg_bank'       => __( 'Mohon pilih / isi nama bank.', 'jwtrading' ),
				'confirm_cancel' => __( 'Batalkan pesanan transfer manual ini?', 'jwtrading' ),
			)
		);
	}

	// --- Front-end: create (from the gated checkout button) -------------------

	public static function ajax_create() {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! self::is_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Transfer manual sedang tidak tersedia.', 'jwtrading' ) ) );
		}

		$first   = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last    = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$discord = sanitize_text_field( wp_unslash( $_POST['discord'] ?? '' ) );

		$missing = array();
		if ( '' === $first ) { $missing[] = __( 'Nama Depan', 'jwtrading' ); }
		if ( '' === $last ) { $missing[] = __( 'Nama Belakang', 'jwtrading' ); }
		if ( '' === $email || ! is_email( $email ) ) { $missing[] = __( 'Email', 'jwtrading' ); }
		if ( '' === $phone ) { $missing[] = __( 'WA Phone', 'jwtrading' ); }
		if ( '' === $discord ) { $missing[] = __( 'Discord Username', 'jwtrading' ); }
		if ( $missing ) {
			wp_send_json_error( array( 'message' => __( 'Data belum lengkap: ', 'jwtrading' ) . implode( ', ', $missing ) ) );
		}

		// Load the buyer's cart in this AJAX context.
		if ( ( ! WC()->cart || WC()->cart->is_empty() ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			wp_send_json_error( array( 'message' => __( 'Keranjang kosong. Silakan pilih produk terlebih dahulu.', 'jwtrading' ) ) );
		}

		WC()->cart->calculate_totals();
		$items = array();
		foreach ( WC()->cart->get_cart() as $ci ) {
			$items[] = array(
				'product_id' => (int) $ci['product_id'],
				'qty'        => (int) $ci['quantity'],
			);
		}
		$totals = WC()->cart->get_totals();
		$amount = isset( $totals['total'] ) ? (float) $totals['total'] : 0.0;

		global $wpdb;
		$token = wp_generate_password( 32, false );
		$now   = current_time( 'mysql' );

		$wpdb->insert(
			self::table(),
			array(
				'token'      => $token,
				'status'     => self::S_PENDING,
				'first_name' => $first,
				'last_name'  => $last,
				'email'      => $email,
				'phone'      => $phone,
				'discord'    => $discord,
				'amount'     => $amount,
				'currency'   => get_woocommerce_currency(),
				'items'      => wp_json_encode(
					array(
						'lines'   => $items,
						'coupons' => WC()->cart->get_applied_coupons(),
					)
				),
				'created_at' => $now,
				'updated_at' => $now,
			)
		);

		wp_send_json_success(
			array(
				'url' => add_query_arg(
					array(
						'jwt_manual' => '1',
						'token'      => $token,
					),
					home_url( '/' )
				),
			)
		);
	}

	// --- Front-end: verify (buyer confirms the transfer) ----------------------

	public static function ajax_verify() {
		check_ajax_referer( self::NONCE, 'nonce' );

		$token   = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
		$bank    = sanitize_text_field( wp_unslash( $_POST['bank'] ?? '' ) );
		$acct     = sanitize_text_field( wp_unslash( $_POST['account_name'] ?? '' ) );

		$record = self::get_by_token( $token );
		if ( ! $record || self::S_PENDING !== $record->status ) {
			wp_send_json_error( array( 'message' => __( 'Pesanan tidak ditemukan atau sudah diproses.', 'jwtrading' ) ) );
		}
		if ( '' === $bank ) {
			wp_send_json_error( array( 'message' => __( 'Mohon pilih / isi nama bank.', 'jwtrading' ) ) );
		}
		if ( '' === $acct ) {
			wp_send_json_error( array( 'message' => __( 'Mohon isi nama pemilik rekening.', 'jwtrading' ) ) );
		}

		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'status'              => self::S_SUBMITTED,
				'sender_bank'         => $bank,
				'sender_account_name' => $acct,
				'updated_at'          => current_time( 'mysql' ),
			),
			array( 'id' => $record->id )
		);

		// Refresh + notify admin (email + WhatsApp link).
		$record = self::get_by_token( $token );
		self::notify_admin( $record );

		// They've paid — clear the cart so returning to checkout doesn't re-add.
		if ( ( WC()->cart || function_exists( 'wc_load_cart' ) ) ) {
			if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
				wc_load_cart();
			}
			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}
		}

		wp_send_json_success(
			array(
				'url' => add_query_arg(
					array(
						'jwt_manual' => '1',
						'token'      => $token,
					),
					home_url( '/' )
				),
			)
		);
	}

	// --- Front-end: cancel (Batal) --------------------------------------------

	public static function ajax_cancel() {
		check_ajax_referer( self::NONCE, 'nonce' );

		$token  = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
		$record = self::get_by_token( $token );
		if ( $record && in_array( $record->status, array( self::S_PENDING, self::S_SUBMITTED ), true ) ) {
			global $wpdb;
			$wpdb->update(
				self::table(),
				array( 'status' => self::S_CANCELLED, 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $record->id )
			);
		}

		wp_send_json_success( array( 'url' => wc_get_checkout_url() ) );
	}

	protected static function get_by_token( $token ) {
		if ( '' === $token ) {
			return null;
		}
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE token = %s', $token ) ); // phpcs:ignore
	}

	protected static function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ) ); // phpcs:ignore
	}

	// --- Front-end: themed screens (instruction / verify / thank-you) ---------

	public static function render_screen() {
		if ( empty( $_GET['jwt_manual'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! self::is_enabled() ) {
			wp_safe_redirect( home_url( '/bootcamp/' ) );
			exit;
		}
		$token  = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$record = self::get_by_token( $token );
		if ( ! $record ) {
			wp_safe_redirect( home_url( '/bootcamp/' ) );
			exit;
		}

		self::enqueue_assets( $token );

		// Tag the body so CSS can hide the fixed floating WhatsApp button here
		// (this screen has its own WhatsApp button) — avoids overlap/z-index wars.
		add_filter( 'body_class', static function ( $classes ) {
			$classes[] = 'jwt-manual-screen';
			return $classes;
		} );

		get_header();
		echo '<div class="jwt-manual-wrap"><div class="jwt-manual-card">';

		switch ( $record->status ) {
			case self::S_PENDING:
				self::screen_instruction( $record );
				break;
			case self::S_CANCELLED:
				self::screen_cancelled();
				break;
			default: // submitted / confirmed.
				self::screen_thankyou( $record );
				break;
		}

		echo '</div></div>';
		get_footer();
		exit;
	}

	protected static function screen_instruction( $record ) {
		$s      = self::settings();
		$amount = wc_price( (float) $record->amount, array( 'currency' => $record->currency ) );
		$banks  = self::banks();
		?>
		<span class="jwt-manual-eyebrow"><?php esc_html_e( 'Transfer Manual', 'jwtrading' ); ?></span>
		<h1 class="jwt-manual-title"><?php esc_html_e( 'Selesaikan Pembayaran', 'jwtrading' ); ?></h1>

		<p class="jwt-manual-lead">
			<?php
			printf(
				/* translators: 1: account name, 2: bank, 3: amount. */
				wp_kses_post( __( 'Mohon lakukan transfer manual antar bank ke Rekening: <strong>%1$s</strong>, Bank: <strong>%2$s</strong>, sebesar: <strong>%3$s</strong>', 'jwtrading' ) ),
				esc_html( $s['dest_account_name'] ),
				esc_html( $s['dest_bank'] ),
				wp_kses_post( $amount )
			);
			?>
		</p>

		<div class="jwt-manual-bank">
			<div class="jwt-manual-bank__row"><span><?php esc_html_e( 'Bank Tujuan', 'jwtrading' ); ?></span><strong><?php echo esc_html( $s['dest_bank'] ); ?></strong></div>
			<div class="jwt-manual-bank__row"><span><?php esc_html_e( 'Nomor Rekening', 'jwtrading' ); ?></span><strong><?php echo esc_html( $s['dest_account_number'] ); ?></strong></div>
			<div class="jwt-manual-bank__row"><span><?php esc_html_e( 'Atas Nama', 'jwtrading' ); ?></span><strong><?php echo esc_html( $s['dest_account_name'] ); ?></strong></div>
			<div class="jwt-manual-bank__row jwt-manual-bank__total"><span><?php esc_html_e( 'Jumlah', 'jwtrading' ); ?></span><strong><?php echo wp_kses_post( $amount ); ?></strong></div>
		</div>

		<p class="jwt-manual-note"><?php esc_html_e( 'Setelah melakukan transfer, klik "Verifikasi Pesanan" dan isi data rekening pengirim agar admin dapat memverifikasi pembayaran Anda.', 'jwtrading' ); ?></p>

		<div class="jwt-manual-actions">
			<button type="button" class="jwt-btn jwt-btn--ghost" id="jwt-manual-cancel"><?php esc_html_e( 'Batal', 'jwtrading' ); ?></button>
			<button type="button" class="jwt-btn" id="jwt-manual-verify-toggle"><?php esc_html_e( 'Verifikasi Pesanan →', 'jwtrading' ); ?></button>
		</div>

		<form class="jwt-manual-verify" id="jwt-manual-verify-form" hidden>
			<h2 class="jwt-manual-subtitle"><?php esc_html_e( 'Konfirmasi Transfer Anda', 'jwtrading' ); ?></h2>
			<p class="jwt-manual-note"><?php esc_html_e( 'Beri tahu kami transfer dilakukan atas nama siapa dan dari bank mana.', 'jwtrading' ); ?></p>

			<label class="jwt-manual-field">
				<span><?php esc_html_e( 'Nama Pemilik Rekening', 'jwtrading' ); ?></span>
				<input type="text" id="jwt-manual-account-name" autocomplete="name" placeholder="<?php esc_attr_e( 'Nama sesuai rekening pengirim', 'jwtrading' ); ?>">
			</label>

			<label class="jwt-manual-field">
				<span><?php esc_html_e( 'Bank Pengirim', 'jwtrading' ); ?></span>
				<select id="jwt-manual-bank">
					<option value=""><?php esc_html_e( '— Pilih Bank —', 'jwtrading' ); ?></option>
					<?php foreach ( $banks as $b ) : ?>
						<option value="<?php echo esc_attr( $b ); ?>"><?php echo esc_html( $b ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="jwt-manual-field" id="jwt-manual-bank-other-wrap" hidden>
				<span><?php esc_html_e( 'Nama Bank (Lainnya)', 'jwtrading' ); ?></span>
				<input type="text" id="jwt-manual-bank-other" placeholder="<?php esc_attr_e( 'Tulis nama bank Anda', 'jwtrading' ); ?>">
			</label>

			<div class="jwt-manual-msg" id="jwt-manual-verify-msg" role="alert" hidden></div>

			<button type="submit" class="jwt-btn" id="jwt-manual-verify-submit"><?php esc_html_e( 'Kirim Konfirmasi', 'jwtrading' ); ?></button>
		</form>
		<?php
	}

	protected static function screen_thankyou( $record ) {
		$s        = self::settings();
		$wa_text  = rawurlencode(
			sprintf(
				/* translators: 1: name, 2: amount. */
				__( 'Halo, saya %1$s sudah melakukan transfer manual sebesar %2$s. Mohon dikonfirmasi ya. Terima kasih!', 'jwtrading' ),
				trim( $record->first_name . ' ' . $record->last_name ),
				html_entity_decode( wp_strip_all_tags( wc_price( (float) $record->amount, array( 'currency' => $record->currency ) ) ) )
			)
		);
		$wa_link  = 'https://wa.me/' . rawurlencode( $s['wa_number'] ) . '?text=' . $wa_text;
		$discord  = apply_filters( 'jwt/manual_discord_url', home_url( '/discord/' ) );
		$contact  = apply_filters( 'jwt/manual_contact_url', home_url( '/contact/' ) );
		$confirmed = self::S_CONFIRMED === $record->status;
		?>
		<span class="jwt-manual-eyebrow"><?php echo $confirmed ? esc_html__( 'Terkonfirmasi', 'jwtrading' ) : esc_html__( 'Menunggu Konfirmasi', 'jwtrading' ); ?></span>
		<h1 class="jwt-manual-title"><?php echo $confirmed ? esc_html__( 'Pembayaran Terkonfirmasi 🎉', 'jwtrading' ) : esc_html__( 'Terima Kasih!', 'jwtrading' ); ?></h1>

		<?php if ( $confirmed ) : ?>
			<p class="jwt-manual-lead"><?php esc_html_e( 'Pembayaran Anda sudah kami konfirmasi. Detail akses dikirim ke email Anda. Sampai jumpa di dalam!', 'jwtrading' ); ?></p>
		<?php else : ?>
			<p class="jwt-manual-lead"><?php esc_html_e( 'Konfirmasi transfer Anda sudah kami terima. Mohon tunggu — admin kami akan memverifikasi pembayaran Anda.', 'jwtrading' ); ?></p>
			<p class="jwt-manual-info"><?php esc_html_e( 'Setelah pembayaran Anda dikonfirmasi admin, Anda akan menerima email berisi detail akses produk — termasuk undangan akun kelas Anda. Mohon cek juga folder Spam/Promosi.', 'jwtrading' ); ?></p>
			<p class="jwt-manual-note"><?php esc_html_e( 'Jika belum terverifikasi dalam 15 menit, jangan ragu menghubungi kami:', 'jwtrading' ); ?></p>
			<div class="jwt-manual-actions jwt-manual-actions--wrap">
				<a class="jwt-btn" href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Konfirmasi via WhatsApp', 'jwtrading' ); ?></a>
				<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( $contact ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Kontak', 'jwtrading' ); ?></a>
				<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( $discord ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Discord', 'jwtrading' ); ?></a>
			</div>
		<?php endif; ?>
		<?php
	}

	protected static function screen_cancelled() {
		?>
		<span class="jwt-manual-eyebrow"><?php esc_html_e( 'Dibatalkan', 'jwtrading' ); ?></span>
		<h1 class="jwt-manual-title"><?php esc_html_e( 'Pesanan Dibatalkan', 'jwtrading' ); ?></h1>
		<p class="jwt-manual-lead"><?php esc_html_e( 'Pesanan transfer manual ini sudah dibatalkan. Anda bisa memulai pemesanan kembali kapan saja.', 'jwtrading' ); ?></p>
		<div class="jwt-manual-actions">
			<a class="jwt-btn" href="<?php echo esc_url( home_url( '/bootcamp/' ) ); ?>"><?php esc_html_e( 'Kembali ke Bootcamp', 'jwtrading' ); ?></a>
		</div>
		<?php
	}

	// --- Admin notification ---------------------------------------------------

	protected static function notify_admin( $record ) {
		$s     = self::settings();
		$admin = admin_url( 'admin.php?page=jwt-manual-payment' );
		$name  = trim( $record->first_name . ' ' . $record->last_name );
		$amount = html_entity_decode( wp_strip_all_tags( wc_price( (float) $record->amount, array( 'currency' => $record->currency ) ) ) );

		$lines = array(
			__( 'Konfirmasi transfer manual baru diterima:', 'jwtrading' ),
			'',
			sprintf( '%s: %s', __( 'Nama', 'jwtrading' ), $name ),
			sprintf( '%s: %s', __( 'Email', 'jwtrading' ), $record->email ),
			sprintf( '%s: %s', __( 'WA', 'jwtrading' ), $record->phone ),
			sprintf( '%s: %s', __( 'Discord', 'jwtrading' ), $record->discord ),
			sprintf( '%s: %s', __( 'Jumlah', 'jwtrading' ), $amount ),
			sprintf( '%s: %s', __( 'Bank Pengirim', 'jwtrading' ), $record->sender_bank ),
			sprintf( '%s: %s', __( 'Nama Pemilik Rekening', 'jwtrading' ), $record->sender_account_name ),
			'',
			sprintf( '%s: %s', __( 'Konfirmasi di', 'jwtrading' ), $admin ),
		);

		if ( ! empty( $s['notify_email'] ) && is_email( $s['notify_email'] ) ) {
			wp_mail(
				$s['notify_email'],
				sprintf( __( '[Transfer Manual] %s — %s', 'jwtrading' ), $name, $amount ),
				implode( "\n", $lines )
			);
		}
	}

	// --- Admin: Manual Payment page -------------------------------------------

	public static function admin_menu() {
		add_menu_page(
			__( 'Manual Payment', 'jwtrading' ),
			__( 'Manual Payment', 'jwtrading' ),
			'manage_woocommerce',
			'jwt-manual-payment',
			array( __CLASS__, 'admin_page' ),
			'dashicons-money-alt',
			56
		);
	}

	protected static function counts() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT status, COUNT(*) AS n FROM ' . self::table() . ' GROUP BY status' ); // phpcs:ignore
		$out  = array( self::S_PENDING => 0, self::S_SUBMITTED => 0, self::S_CONFIRMED => 0, self::S_CANCELLED => 0 );
		foreach ( (array) $rows as $r ) {
			$out[ $r->status ] = (int) $r->n;
		}
		return $out;
	}

	protected static function status_label( $status ) {
		$map = array(
			self::S_PENDING   => __( 'Menunggu Pembayaran', 'jwtrading' ),
			self::S_SUBMITTED => __( 'Menunggu Verifikasi', 'jwtrading' ),
			self::S_CONFIRMED => __( 'Selesai', 'jwtrading' ),
			self::S_CANCELLED => __( 'Dibatalkan', 'jwtrading' ),
		);
		return $map[ $status ] ?? $status;
	}

	public static function admin_page() {
		global $wpdb;
		$rows   = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC LIMIT 200' ); // phpcs:ignore
		$counts = self::counts();
		$s      = self::settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Manual Payment', 'jwtrading' ); ?></h1>

			<div class="jwt-mp-counts" style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
				<?php
				foreach ( array( self::S_PENDING, self::S_SUBMITTED, self::S_CONFIRMED, self::S_CANCELLED ) as $st ) :
					?>
					<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:10px 16px;min-width:140px;">
						<div style="font-size:22px;font-weight:700;"><?php echo (int) $counts[ $st ]; ?></div>
						<div style="color:#646970;"><?php echo esc_html( self::status_label( $st ) ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Tanggal', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Pembeli', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Kontak', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Discord', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Jumlah', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Bank Pengirim', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Status', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Aksi', 'jwtrading' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="8"><em><?php esc_html_e( 'Belum ada permintaan transfer manual.', 'jwtrading' ); ?></em></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r->created_at ); ?></td>
							<td><strong><?php echo esc_html( trim( $r->first_name . ' ' . $r->last_name ) ); ?></strong></td>
							<td><?php echo esc_html( $r->email ); ?><br><span style="color:#646970;"><?php echo esc_html( $r->phone ); ?></span></td>
							<td><?php echo esc_html( $r->discord ); ?></td>
							<td><?php echo wp_kses_post( wc_price( (float) $r->amount, array( 'currency' => $r->currency ) ) ); ?></td>
							<td><?php echo esc_html( $r->sender_bank ? $r->sender_bank . ' — ' . $r->sender_account_name : '—' ); ?></td>
							<td>
								<?php
								$colors = array(
									self::S_PENDING   => '#8a6d00',
									self::S_SUBMITTED => '#0073aa',
									self::S_CONFIRMED => '#0a7d33',
									self::S_CANCELLED => '#b32d2e',
								);
								$c = $colors[ $r->status ] ?? '#646970';
								?>
								<span style="color:<?php echo esc_attr( $c ); ?>;font-weight:600;"><?php echo esc_html( self::status_label( $r->status ) ); ?></span>
								<?php if ( $r->order_id ) : ?>
									<br><a href="<?php echo esc_url( get_edit_post_link( $r->order_id ) ? get_edit_post_link( $r->order_id ) : admin_url( 'admin.php?page=wc-orders&action=edit&id=' . (int) $r->order_id ) ); ?>">#<?php echo (int) $r->order_id; ?></a>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( in_array( $r->status, array( self::S_PENDING, self::S_SUBMITTED ), true ) ) : ?>
									<button type="button" class="button button-primary jwt-mp-confirm" data-id="<?php echo (int) $r->id; ?>"><?php esc_html_e( 'Confirm', 'jwtrading' ); ?></button>
									<button type="button" class="button jwt-mp-cancel" data-id="<?php echo (int) $r->id; ?>"><?php esc_html_e( 'Hapus', 'jwtrading' ); ?></button>
								<?php else : ?>
									<span style="color:#646970;">—</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2 style="margin-top:2em;"><?php esc_html_e( 'Pengaturan', 'jwtrading' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'jwt_manual_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Aktifkan Transfer Manual', 'jwtrading' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPT ); ?>[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ), true ); ?>> <?php esc_html_e( 'Gunakan alur transfer manual di dalam situs (layar instruksi + verifikasi). Jika dimatikan, tombol "Gunakan Transfer Manual" tetap tampil tapi mengarah ke Google Form lama.', 'jwtrading' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'URL Google Form (mode lama)', 'jwtrading' ); ?></th>
						<td><input type="url" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[form_url]" value="<?php echo esc_attr( $s['form_url'] ); ?>" placeholder="https://docs.google.com/forms/...">
						<p class="description"><?php esc_html_e( 'Dipakai hanya saat alur di dalam situs DIMATIKAN — tombol checkout akan membuka link ini di tab baru.', 'jwtrading' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Bank Tujuan', 'jwtrading' ); ?></th>
						<td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[dest_bank]" value="<?php echo esc_attr( $s['dest_bank'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Nomor Rekening', 'jwtrading' ); ?></th>
						<td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[dest_account_number]" value="<?php echo esc_attr( $s['dest_account_number'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Atas Nama', 'jwtrading' ); ?></th>
						<td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[dest_account_name]" value="<?php echo esc_attr( $s['dest_account_name'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Nomor WhatsApp (notifikasi)', 'jwtrading' ); ?></th>
						<td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[wa_number]" value="<?php echo esc_attr( $s['wa_number'] ); ?>" placeholder="628xxxxxxxxxx">
						<p class="description"><?php esc_html_e( 'Format internasional tanpa tanda +, contoh 628123456789.', 'jwtrading' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email Notifikasi', 'jwtrading' ); ?></th>
						<td><input type="email" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[notify_email]" value="<?php echo esc_attr( $s['notify_email'] ); ?>"></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>

		<script>
		( function () {
			var nonce = '<?php echo esc_js( wp_create_nonce( self::ADM_NONCE ) ); ?>';
			function post( action, id, done ) {
				var body = new URLSearchParams( { action: action, nonce: nonce, id: id } );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body } )
					.then( function ( r ) { return r.json(); } ).then( done )
					.catch( function () { alert( '<?php echo esc_js( __( 'Request gagal.', 'jwtrading' ) ); ?>' ); } );
			}
			document.querySelectorAll( '.jwt-mp-confirm' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
	if ( b.dataset.armed !== '1' ) {
						b.dataset.armed = '1';
						b.dataset.label = b.textContent;
						b.textContent = '<?php echo esc_js( __( 'Confirm?', 'jwtrading' ) ); ?>';
						b.style.color = '#fff'; b.style.fontWeight = '700';
						setTimeout( function () { if ( b.dataset.armed === '1' ) { b.dataset.armed = '0'; b.textContent = b.dataset.label || 'Confirm'; b.style.color = ''; b.style.fontWeight = ''; } }, 4000 );
						return;
					}
					b.dataset.armed = '0';
					b.disabled = true; b.textContent = '…';
					post( 'jwt_manual_admin_confirm', b.dataset.id, function ( res ) {
						if ( res.success ) { location.reload(); }
						else { b.disabled = false; b.textContent = 'Confirm'; alert( ( res.data && res.data.message ) || 'Error' ); }
					} );
				} );
			} );
			document.querySelectorAll( '.jwt-mp-cancel' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
	if ( b.dataset.armed !== '1' ) {
						b.dataset.armed = '1';
						b.dataset.label = b.textContent;
						b.textContent = '<?php echo esc_js( __( 'Yakin?', 'jwtrading' ) ); ?>';
						b.style.color = '#b32d2e'; b.style.fontWeight = '700';
						setTimeout( function () { if ( b.dataset.armed === '1' ) { b.dataset.armed = '0'; b.textContent = b.dataset.label || 'Hapus'; b.style.color = ''; b.style.fontWeight = ''; } }, 4000 );
						return;
					}
					b.dataset.armed = '0';
					b.disabled = true;
					post( 'jwt_manual_admin_cancel', b.dataset.id, function ( res ) {
						if ( res.success ) { location.reload(); }
						else { b.disabled = false; alert( ( res.data && res.data.message ) || 'Error' ); }
					} );
				} );
			} );
		} )();
		</script>
		<?php
	}

	// --- Admin: confirm → build + complete the WooCommerce order --------------

	public static function ajax_admin_confirm() {
		check_ajax_referer( self::ADM_NONCE, 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'jwtrading' ) ) );
		}

		$record = self::get_by_id( absint( $_POST['id'] ?? 0 ) );
		if ( ! $record ) {
			wp_send_json_error( array( 'message' => __( 'Data tidak ditemukan.', 'jwtrading' ) ) );
		}
		if ( self::S_CONFIRMED === $record->status && $record->order_id ) {
			wp_send_json_error( array( 'message' => __( 'Sudah dikonfirmasi.', 'jwtrading' ) ) );
		}

		$order_id = self::create_order_from_record( $record );
		if ( is_wp_error( $order_id ) || ! $order_id ) {
			$msg = is_wp_error( $order_id ) ? $order_id->get_error_message() : __( 'Gagal membuat order.', 'jwtrading' );
			wp_send_json_error( array( 'message' => $msg ) );
		}

		global $wpdb;
		$wpdb->update(
			self::table(),
			array( 'status' => self::S_CONFIRMED, 'order_id' => $order_id, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $record->id )
		);

		wp_send_json_success( array( 'order_id' => $order_id ) );
	}

	/**
	 * Cancel = permanently remove the manual-payment record. Only for records that
	 * have NOT been confirmed (no WooCommerce order exists yet), so nothing else is
	 * affected. The JS shows a warning first — this never deletes without confirming.
	 */
	public static function ajax_admin_cancel() {
		check_ajax_referer( self::ADM_NONCE, 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'jwtrading' ) ) );
		}
		$record = self::get_by_id( absint( $_POST['id'] ?? 0 ) );
		if ( ! $record ) {
			wp_send_json_error( array( 'message' => __( 'Data tidak ditemukan.', 'jwtrading' ) ) );
		}
		if ( self::S_CONFIRMED === $record->status ) {
			wp_send_json_error( array( 'message' => __( 'Order yang sudah dikonfirmasi tidak bisa dihapus dari sini.', 'jwtrading' ) ) );
		}
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => $record->id ), array( '%d' ) );
		wp_send_json_success();
	}

	/**
	 * Build a WooCommerce order from a manual-payment snapshot and mark it
	 * Completed. The status transition fires Kit + Sheets + Thinkific.
	 *
	 * @return int|WP_Error Order ID or error.
	 */
	protected static function create_order_from_record( $record ) {
		if ( ! function_exists( 'wc_create_order' ) ) {
			return new WP_Error( 'no_wc', 'WooCommerce not available.' );
		}

		$snapshot = json_decode( (string) $record->items, true );
		$lines    = ( is_array( $snapshot ) && ! empty( $snapshot['lines'] ) ) ? $snapshot['lines'] : array();
		$coupons  = ( is_array( $snapshot ) && ! empty( $snapshot['coupons'] ) ) ? $snapshot['coupons'] : array();
		if ( ! $lines ) {
			return new WP_Error( 'no_items', __( 'Tidak ada produk pada snapshot.', 'jwtrading' ) );
		}

		$order = wc_create_order();
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		foreach ( $lines as $line ) {
			$product = wc_get_product( (int) ( $line['product_id'] ?? 0 ) );
			if ( $product ) {
				$order->add_product( $product, max( 1, (int) ( $line['qty'] ?? 1 ) ) );
			}
		}

		$order->set_billing_first_name( $record->first_name );
		$order->set_billing_last_name( $record->last_name );
		$order->set_billing_email( $record->email );
		$order->set_billing_phone( $record->phone );

		foreach ( (array) $coupons as $code ) {
			try {
				$order->apply_coupon( $code );
			} catch ( Exception $e ) {
				// A coupon that no longer validates shouldn't block the order.
			}
		}

		$order->set_payment_method( 'jwt_manual' );
		$order->set_payment_method_title( __( 'Transfer Manual (Bank)', 'jwtrading' ) );
		$order->set_created_via( 'jwt_manual' );
		$order->update_meta_data( '_discord_username', $record->discord );
		$order->update_meta_data( '_jwt_manual_payment_id', $record->id );
		$order->update_meta_data( '_jwt_manual_sender_bank', $record->sender_bank );
		$order->update_meta_data( '_jwt_manual_sender_name', $record->sender_account_name );

		$order->calculate_totals();
		$order->set_date_paid( time() );
		$order->save();

		// Direct pending → completed transition; fires the completion integrations once.
		$order->update_status( 'completed', __( 'Pembayaran transfer manual dikonfirmasi admin.', 'jwtrading' ) );

		return $order->get_id();
	}
}

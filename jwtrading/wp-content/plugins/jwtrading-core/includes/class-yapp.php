<?php
defined( 'ABSPATH' ) || exit;

/**
 * Yapp hosted-checkout integration (Stage 3).
 *
 * Flow:
 *  1. Buyer fills the mini form under a product's Add to Cart and hits
 *     "Bayar dengan Yapp" → AJAX creates an INVOICE (our own row, mirrors
 *     Yapp's own invoice) and redirects to Yapp's checkoutLink.
 *  2. Buyer pays on Yapp's hosted page (out of our hands).
 *  3. Yapp POSTs `order.payment_succeeded` to our webhook → we verify the
 *     signature, build a WooCommerce order from the invoice snapshot, and
 *     mark it Completed — which fires the same Kit/Sheets sync every other
 *     order already fires. Yapp itself grants course access; we never call
 *     an "enroll" API the way the retired Thinkific integration did.
 *  4. A reconciliation cron re-checks any invoice stuck pending, in case a
 *     webhook delivery was ever lost.
 *
 * MOCK MODE (default while Client ID / product UUIDs aren't in hand yet):
 * step 1 fabricates a local invoice and sends the buyer to our own themed
 * "simulated" checkout screen instead of yapp.ink. Clicking "Simulate
 * Payment Success" there feeds a fake payload through the exact same
 * order-building code the real webhook uses — so flipping Mock Mode off
 * once real credentials arrive changes nothing else.
 */
class JWT_Yapp {

	const NONCE      = 'jwt_yapp';
	const DB_OPT      = 'jwt_yapp_db_version';
	const DB_VER      = '3';

	const S_PENDING   = 'pending';
	const S_COMPLETED = 'completed';
	const S_EXPIRED   = 'expired';
	const S_FAILED    = 'failed';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_table' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'render_product_field' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_field' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_front' ) );
		// Same real-estate as "Gunakan Transfer Manual" (class-checkout.php, priority
		// 30) — this is where buyers actually land (Bootcamp's "Akses Sekarang" goes
		// straight to /?add-to-cart=ID → checkout; the single-product template is
		// never rendered in this site's buy-now flow).
		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'checkout_cta' ), 31 );
		add_action( 'template_redirect', array( __CLASS__, 'render_screen' ), 5 );

		add_action( 'wp_ajax_jwt_yapp_create_invoice', array( __CLASS__, 'ajax_create_invoice' ) );
		add_action( 'wp_ajax_nopriv_jwt_yapp_create_invoice', array( __CLASS__, 'ajax_create_invoice' ) );
		add_action( 'wp_ajax_jwt_yapp_mock_pay', array( __CLASS__, 'ajax_mock_pay' ) );
		add_action( 'wp_ajax_nopriv_jwt_yapp_mock_pay', array( __CLASS__, 'ajax_mock_pay' ) );
		add_action( 'wp_ajax_jwt_yapp_generate_keys', array( __CLASS__, 'ajax_generate_keys' ) );

		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'register_gateway' ) );

		add_action( 'rest_api_init', array( __CLASS__, 'register_webhook_route' ) );

		add_action( 'jwt_yapp_reconcile', array( __CLASS__, 'run_reconciliation' ) );
		if ( ! wp_next_scheduled( 'jwt_yapp_reconcile' ) ) {
			wp_schedule_event( time() + 300, 'jwt_15min', 'jwt_yapp_reconcile' );
		}
	}

	// --- Data store -------------------------------------------------------

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'jwt_yapp_invoices';
	}

	public static function create_table() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = self::table();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			invoice_uuid VARCHAR(64) NOT NULL,
			order_uuid VARCHAR(64) NULL,
			reference_id VARCHAR(64) NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			amount DECIMAL(12,2) NOT NULL DEFAULT 0,
			currency VARCHAR(10) NULL,
			buyer_name VARCHAR(150) NULL,
			buyer_email VARCHAR(191) NULL,
			buyer_phone VARCHAR(50) NULL,
			discord_username VARCHAR(100) NULL,
			promo_code VARCHAR(64) NULL,
			checkout_link TEXT NULL,
			is_mock TINYINT UNSIGNED NOT NULL DEFAULT 0,
			order_id BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY invoice_uuid (invoice_uuid),
			KEY reference_id (reference_id),
			KEY status (status)
		) {$charset};" );

		update_option( self::DB_OPT, self::DB_VER );
	}

	public static function maybe_create_table() {
		if ( get_option( self::DB_OPT ) !== self::DB_VER ) {
			self::create_table();
		}
	}

	protected static function get_by_uuid( $invoice_uuid ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE invoice_uuid = %s', $invoice_uuid ) ); // phpcs:ignore
	}

	protected static function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ) ); // phpcs:ignore
	}

	// --- Settings -----------------------------------------------------------

	public static function settings() {
		$defaults = array(
			'mock_mode'  => 1,
			'client_id'  => '',
			'public_key' => '',
			'private_key' => '',
			'base_url'   => 'https://api.yapp.ink',
			// Yapp's per-account origin whitelisting isn't built yet; support told us
			// (2026-09-09) to send https://yapp.ink meanwhile. Without this header the
			// API answers 403 "not allowed to access this API" even with a valid
			// signature. Swap this for our own domain once they've whitelisted it.
			'origin'     => 'https://yapp.ink',
			// Ships ON: the button stays invisible to customers until someone has
			// deliberately proven a real payment end to end and unticks it.
			'staff_only' => 1,
			'webhook_public_key' => '',
		);
		return wp_parse_args( array(
			'mock_mode'  => get_option( 'jwt_yapp_mock_mode', 1 ),
			'client_id'  => get_option( 'jwt_yapp_client_id', '' ),
			// Always derived from the private key — see derived_public_key(). The old
			// jwt_yapp_public_key option is left registered so the next Save clears it.
			'public_key' => self::derived_public_key(),
			'private_key' => get_option( 'jwt_yapp_private_key', '' ),
			'base_url'   => get_option( 'jwt_yapp_base_url', 'https://api.yapp.ink' ),
			'origin'     => get_option( 'jwt_yapp_origin', 'https://yapp.ink' ),
			'staff_only' => get_option( 'jwt_yapp_staff_only', 1 ),
			'webhook_public_key' => get_option( 'jwt_yapp_webhook_public_key', '' ),
		), $defaults );
	}

	/**
	 * The public half of whatever private key is configured.
	 *
	 * Derived, never stored: an ED25519 secret key is seed(32) || public(32), so the
	 * public key is always recoverable from it. Keeping a separate copy in the
	 * options table only created something that could drift out of sync with the
	 * private key — which is exactly what happened when the keypair was regenerated.
	 */
	public static function derived_public_key() {
		$priv = base64_decode( (string) get_option( 'jwt_yapp_private_key', '' ), true ); // phpcs:ignore
		if ( false === $priv || SODIUM_CRYPTO_SIGN_SECRETKEYBYTES !== strlen( $priv ) ) {
			return '';
		}
		return base64_encode( substr( $priv, -SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ) ); // phpcs:ignore
	}

	/**
	 * WooCommerce coupon code => Yapp promo code.
	 *
	 * Configured as one mapping per line in the settings page, so adding a new promo
	 * never needs a code change:
	 *
	 *   DISKON50            (same code both sides)
	 *   EARLYBIRD = EARLY26 (different code on Yapp)
	 *
	 * Deliberately an allowlist. Yapp owns the pricing, so sending a code they don't
	 * recognise risks charging the buyer something we never showed them.
	 */
	public static function promo_map() {
		$raw = (string) get_option( 'jwt_yapp_promo_map', '' );
		$map = array();

		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || 0 === strpos( $line, '#' ) ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '=', $line, 2 ) );
			$woo   = strtoupper( $parts[0] );
			if ( '' === $woo ) {
				continue;
			}
			$yapp = ( isset( $parts[1] ) && '' !== $parts[1] ) ? $parts[1] : $parts[0];
			$map[ $woo ] = $yapp;
		}

		return apply_filters( 'jwt/yapp_promo_map', $map );
	}

	/**
	 * Work out which Yapp promo code (if any) to send for the applied Woo coupons.
	 *
	 * @return array{code:string,error:string}
	 */
	public static function resolve_promo( array $coupons ) {
		if ( empty( $coupons ) ) {
			return array( 'code' => '', 'error' => '' );
		}

		// A Yapp invoice carries a single promoCode, so we can't represent a stack.
		if ( count( $coupons ) > 1 ) {
			return array(
				'code'  => '',
				'error' => __( 'Hanya satu kode promo yang bisa dipakai untuk pembayaran Yapp.', 'jwtrading' ),
			);
		}

		$applied = strtoupper( trim( (string) reset( $coupons ) ) );
		$map     = self::promo_map();

		if ( ! isset( $map[ $applied ] ) ) {
			return array(
				'code'  => '',
				/* translators: %s: the coupon code the buyer applied. */
				'error' => sprintf( __( 'Kode promo "%s" belum tersedia untuk pembayaran Yapp. Hapus kode promo, atau gunakan metode pembayaran lain.', 'jwtrading' ), $applied ),
			);
		}

		return array( 'code' => $map[ $applied ], 'error' => '' );
	}

	public static function is_mock() {
		$s = self::settings();
		// Force mock whenever real credentials aren't in yet, regardless of the toggle,
		// so this can never silently try (and fail) to hit production with blank keys.
		if ( empty( $s['client_id'] ) || empty( $s['private_key'] ) ) {
			return true;
		}
		return ! empty( $s['mock_mode'] );
	}

	public static function register_settings() {
		$fields = array(
			'jwt_yapp_mock_mode'  => 'absint',
			'jwt_yapp_client_id'  => 'sanitize_text_field',
			'jwt_yapp_public_key' => 'sanitize_text_field',
			'jwt_yapp_private_key' => 'sanitize_text_field',
			'jwt_yapp_base_url'   => 'esc_url_raw',
			'jwt_yapp_origin'     => 'esc_url_raw',
			'jwt_yapp_staff_only' => 'absint',
			'jwt_yapp_promo_map'  => 'sanitize_textarea_field',
		);
		foreach ( $fields as $option => $sanitize ) {
			register_setting( 'jwt_yapp_settings', $option, array( 'sanitize_callback' => $sanitize ) );
		}
	}

	// --- Admin: Settings + Product UUIDs + Invoice log -----------------------

	public static function admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Yapp Checkout', 'jwtrading' ),
			__( 'Yapp Checkout', 'jwtrading' ),
			'manage_woocommerce',
			'jwt-yapp',
			array( __CLASS__, 'admin_page' )
		);
	}

	public static function admin_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$s = self::settings();
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC LIMIT 100' ); // phpcs:ignore
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Yapp Checkout', 'jwtrading' ); ?></h1>

			<?php if ( self::is_mock() ) : ?>
				<div class="notice notice-warning"><p>
					<strong><?php esc_html_e( 'Mock Mode is active.', 'jwtrading' ); ?></strong>
					<?php esc_html_e( 'No real Yapp API calls are made — the buy button sends buyers to a simulated checkout screen on this site. Fill in the Client ID and Private Key below (once Yapp sends them) to go live.', 'jwtrading' ); ?>
				</p></div>
			<?php endif; ?>

			<?php if ( ! function_exists( 'sodium_crypto_sign_keypair' ) ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'The sodium PHP extension is required for ED25519 signing and is not available on this server.', 'jwtrading' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Credentials', 'jwtrading' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'jwt_yapp_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Mock Mode', 'jwtrading' ); ?></th>
						<td>
							<label><input type="checkbox" name="jwt_yapp_mock_mode" value="1" <?php checked( $s['mock_mode'], 1 ); ?>> <?php esc_html_e( 'Simulate checkout locally instead of calling api.yapp.ink', 'jwtrading' ); ?></label>
							<p class="description"><?php esc_html_e( 'Forced ON automatically whenever Client ID or Private Key is blank.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Admin-only button', 'jwtrading' ); ?></th>
						<td>
							<label><input type="checkbox" name="jwt_yapp_staff_only" value="1" <?php checked( $s['staff_only'], 1 ); ?>> <?php esc_html_e( 'Only show "Bayar dengan Yapp" to logged-in admins', 'jwtrading' ); ?></label>
							<p class="description"><?php esc_html_e( 'Leave ticked until a real payment has been proven end to end on this site — otherwise customers can reach a payment path nobody has verified here yet. Untick to open it to everyone.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_yapp_client_id"><?php esc_html_e( 'Client ID', 'jwtrading' ); ?></label></th>
						<td><input type="text" class="regular-text" id="jwt_yapp_client_id" name="jwt_yapp_client_id" value="<?php echo esc_attr( $s['client_id'] ); ?>" placeholder="<?php esc_attr_e( 'Sent back by Yapp after signup', 'jwtrading' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_yapp_base_url"><?php esc_html_e( 'API Base URL', 'jwtrading' ); ?></label></th>
						<td><input type="url" class="regular-text" id="jwt_yapp_base_url" name="jwt_yapp_base_url" value="<?php echo esc_attr( $s['base_url'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_yapp_origin"><?php esc_html_e( 'Origin (X-Origin header)', 'jwtrading' ); ?></label></th>
						<td>
							<input type="url" class="regular-text" id="jwt_yapp_origin" name="jwt_yapp_origin" value="<?php echo esc_attr( $s['origin'] ); ?>">
							<p class="description"><?php esc_html_e( 'Yapp rejects signed requests with 403 unless this is sent. Their per-account whitelisting is still being built, so support told us to use https://yapp.ink for now — change this to our own domain once they confirm it is whitelisted.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Our Public Key (base64)', 'jwtrading' ); ?></th>
						<td>
							<?php $derived = self::derived_public_key(); ?>
							<input type="text" class="large-text" id="jwt_yapp_public_key" value="<?php echo esc_attr( $derived ); ?>" readonly onclick="this.select();">
							<p class="description">
								<?php esc_html_e( 'Derived from the private key below, so it always matches it — nothing to fill in. This is the half you would share with Yapp; the private key never leaves this site.', 'jwtrading' ); ?>
							</p>
							<?php if ( '' === $derived && ! empty( $s['private_key'] ) ) : ?>
								<p class="description" style="color:#b32d2e;">
									<?php esc_html_e( 'Could not derive a public key — the private key does not look like a valid base64 ED25519 secret key (64 bytes).', 'jwtrading' ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_yapp_private_key"><?php esc_html_e( 'Our Private Key (base64, secret)', 'jwtrading' ); ?></label></th>
						<td><input type="password" class="large-text" id="jwt_yapp_private_key" name="jwt_yapp_private_key" value="<?php echo esc_attr( $s['private_key'] ); ?>" autocomplete="off"></td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td>
							<button type="button" class="button" id="jwt-yapp-genkeys"><?php esc_html_e( 'Generate New Keypair', 'jwtrading' ); ?></button>
							<span id="jwt-yapp-genkeys-msg" style="margin-left:8px;color:#646970;"></span>
							<p class="description" style="color:#b32d2e;">
								<strong><?php esc_html_e( 'Only use this if Yapp asked you to send them a public key.', 'jwtrading' ); ?></strong>
								<?php esc_html_e( 'If Yapp issued your Client ID and keypair together from their dashboard, generating here replaces the key they have on file and every request will fail with 401. Requires a confirmation, and still needs Save Changes.', 'jwtrading' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_yapp_promo_map"><?php esc_html_e( 'Promo codes (coupon → Yapp)', 'jwtrading' ); ?></label></th>
						<td>
							<textarea id="jwt_yapp_promo_map" name="jwt_yapp_promo_map" rows="5" class="large-text code" placeholder="DISKON50&#10;EARLYBIRD = EARLY26"><?php echo esc_textarea( (string) get_option( 'jwt_yapp_promo_map', '' ) ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'One per line. Just the code if it is identical in WooCommerce and Yapp, or "WooCode = YappCode" if they differ. Lines starting with # are ignored.', 'jwtrading' ); ?>
							</p>
							<p class="description">
								<strong><?php esc_html_e( 'Create the promo in Yapp first', 'jwtrading' ); ?></strong>
								<?php esc_html_e( '(Yapp dashboard → Products → Promotions), then list it here. Yapp applies its own discount, so keep the amount identical on both sides — otherwise our checkout total will not match what Yapp charges.', 'jwtrading' ); ?>
							</p>
							<p class="description">
								<?php esc_html_e( 'A coupon that is not listed is refused at checkout rather than silently billing full price. Only one coupon at a time works with Yapp.', 'jwtrading' ); ?>
							</p>
							<?php $promo_map = self::promo_map(); ?>
							<?php if ( $promo_map ) : ?>
								<p class="description" style="margin-top:8px;">
									<strong><?php esc_html_e( 'Active:', 'jwtrading' ); ?></strong>
									<?php
									$pairs = array();
									foreach ( $promo_map as $woo_code => $yapp_code ) {
										$pairs[] = $woo_code === $yapp_code ? $woo_code : $woo_code . ' → ' . $yapp_code;
									}
									echo esc_html( implode( ' · ', $pairs ) );
									?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Products', 'jwtrading' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Set each course\'s Yapp Product UUID on its product edit screen (General tab). Blank is fine while testing in Mock Mode.', 'jwtrading' ); ?></p>

			<h2><?php esc_html_e( 'Recent Invoices', 'jwtrading' ); ?></h2>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Created', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Product', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Buyer', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Promo', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Status', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Mode', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Order', 'jwtrading' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="8"><em><?php esc_html_e( 'No invoices yet — try the buy button on a product page.', 'jwtrading' ); ?></em></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r->created_at ); ?></td>
							<td><?php echo esc_html( get_the_title( $r->product_id ) ); ?></td>
							<td><?php echo esc_html( $r->buyer_name ); ?><br><span style="color:#646970;"><?php echo esc_html( $r->buyer_email ); ?></span></td>
							<td><?php echo wp_kses_post( wc_price( (float) $r->amount, array( 'currency' => $r->currency ) ) ); ?></td>
							<td><?php echo $r->promo_code ? '<code>' . esc_html( $r->promo_code ) . '</code>' : '—'; ?></td>
							<td>
								<?php
								$colors = array( self::S_PENDING => '#8a6d00', self::S_COMPLETED => '#0a7d33', self::S_EXPIRED => '#646970', self::S_FAILED => '#b32d2e' );
								$c = $colors[ $r->status ] ?? '#646970';
								?>
								<span style="color:<?php echo esc_attr( $c ); ?>;font-weight:600;"><?php echo esc_html( ucfirst( $r->status ) ); ?></span>
							</td>
							<td><?php echo $r->is_mock ? esc_html__( 'Mock', 'jwtrading' ) : esc_html__( 'Live', 'jwtrading' ); ?></td>
							<td><?php echo $r->order_id ? '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $r->order_id . '&action=edit' ) ) . '">#' . (int) $r->order_id . '</a>' : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<script>
		( function () {
			var btn = document.getElementById( 'jwt-yapp-genkeys' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function () {
				// Two-step arm rather than confirm(): browsers with "prevent additional
				// dialogs" ticked silently return false from confirm(), which is why the
				// Manual Payment screens use this same pattern.
				if ( btn.dataset.armed !== '1' ) {
					btn.dataset.armed = '1';
					btn.dataset.label = btn.textContent;
					btn.textContent = '<?php echo esc_js( __( 'Replace the key Yapp has on file? Click again', 'jwtrading' ) ); ?>';
					btn.style.color = '#b32d2e';
					btn.style.fontWeight = '700';
					setTimeout( function () {
						if ( btn.dataset.armed === '1' ) {
							btn.dataset.armed = '0';
							btn.textContent = btn.dataset.label;
							btn.style.color = '';
							btn.style.fontWeight = '';
						}
					}, 5000 );
					return;
				}
				btn.dataset.armed = '0';
				btn.textContent = btn.dataset.label;
				btn.style.color = '';
				btn.style.fontWeight = '';

				btn.disabled = true;
				var msg = document.getElementById( 'jwt-yapp-genkeys-msg' );
				msg.textContent = '<?php echo esc_js( __( 'Generating…', 'jwtrading' ) ); ?>';
				var body = new URLSearchParams( { action: 'jwt_yapp_generate_keys', nonce: '<?php echo esc_js( wp_create_nonce( self::NONCE . '_admin' ) ); ?>' } );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						btn.disabled = false;
						if ( res.success ) {
							document.getElementById( 'jwt_yapp_public_key' ).value = res.data.public_key;
							document.getElementById( 'jwt_yapp_private_key' ).value = res.data.private_key;
							msg.textContent = '<?php echo esc_js( __( 'Generated — click Save Changes, then copy the Public Key to Yapp.', 'jwtrading' ) ); ?>';
						} else {
							msg.textContent = ( res.data && res.data.message ) || 'Error';
						}
					} )
					.catch( function () { btn.disabled = false; msg.textContent = 'Error'; } );
			} );
		} )();
		</script>
		<?php
	}

	public static function ajax_generate_keys() {
		check_ajax_referer( self::NONCE . '_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'jwtrading' ) ) );
		}
		if ( ! function_exists( 'sodium_crypto_sign_keypair' ) ) {
			wp_send_json_error( array( 'message' => __( 'sodium extension not available.', 'jwtrading' ) ) );
		}
		$kp = sodium_crypto_sign_keypair();
		wp_send_json_success( array(
			'public_key'  => base64_encode( sodium_crypto_sign_publickey( $kp ) ), // phpcs:ignore
			'private_key' => base64_encode( sodium_crypto_sign_secretkey( $kp ) ), // phpcs:ignore
		) );
	}

	// --- Product meta: Yapp Product UUID -------------------------------------

	public static function render_product_field() {
		global $post;
		echo '<div class="options_group">';
		woocommerce_wp_text_input( array(
			'id'          => '_jwt_yapp_product_uuid',
			'label'       => __( 'Yapp Product UUID', 'jwtrading' ),
			'desc_tip'    => true,
			'description' => __( 'From the Yapp Creator Dashboard → Products. Leave blank while testing in Mock Mode.', 'jwtrading' ),
			'value'       => get_post_meta( $post->ID, '_jwt_yapp_product_uuid', true ),
		) );
		echo '</div>';
	}

	public static function save_product_field( $product_id ) {
		if ( isset( $_POST['_jwt_yapp_product_uuid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $product_id, '_jwt_yapp_product_uuid', sanitize_text_field( wp_unslash( $_POST['_jwt_yapp_product_uuid'] ) ) ); // phpcs:ignore
		}
	}

	// --- Front-end: CTA on the checkout page ----------------------------------

	public static function enqueue_front() {
		$on_checkout = function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url();
		$on_own_screen = ! empty( $_GET['jwt_yapp_mock'] ) || ! empty( $_GET['jwt_yapp_thanks'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $on_checkout && ! $on_own_screen ) {
			return;
		}
		// No point shipping the script (or a nonce) to visitors who can't see the
		// button. The thank-you screen is exempt — buyers land there after paying.
		if ( $on_checkout && ! self::can_see_button() ) {
			return;
		}
		wp_enqueue_style( 'jwt-manual', JWT_CORE_URL . 'assets/manual-payment.css', array(), JWT_CORE_VERSION );
		wp_enqueue_script( 'jwt-yapp', JWT_CORE_URL . 'assets/yapp.js', array(), JWT_CORE_VERSION, true );
		wp_localize_script( 'jwt-yapp', 'JWT_YAPP', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE ),
			'msg_incomplete' => __( 'Mohon lengkapi nama depan, nama belakang, email, nomor WA, dan Discord username di form di atas.', 'jwtrading' ),
			'msg_email'      => __( 'Format email tidak valid.', 'jwtrading' ),
			'msg_terms'      => __( 'Mohon setujui Syarat & Ketentuan terlebih dahulu.', 'jwtrading' ),
			'msg_generic'    => __( 'Terjadi kesalahan. Silakan coba lagi.', 'jwtrading' ),
		) );
	}

	/**
	 * "Bayar dengan Yapp" CTA — same slot/style as class-checkout.php's
	 * "Gunakan Transfer Manual" button (woocommerce_after_checkout_billing_form,
	 * priority 30), placed right after it. Reads the buyer's info straight off
	 * the checkout form fields instead of asking again (yapp.js).
	 */
	public static function checkout_cta() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() || WC()->cart->needs_shipping() ) {
			return;
		}

		if ( ! self::can_see_button() ) {
			return;
		}

		$mock       = self::is_mock();
		$staff_only = ! $mock && ! empty( self::settings()['staff_only'] );
		?>
		<div id="jwt-yapp-box">
			<p class="alt-text">
				<?php esc_html_e( 'Ingin bayar via Yapp?', 'jwtrading' ); ?>
				<?php if ( $mock ) : ?>
					<span style="color:#b3791a;">(<?php esc_html_e( 'Demo — Mock Mode, belum ke Yapp sungguhan', 'jwtrading' ); ?>)</span>
				<?php elseif ( $staff_only ) : ?>
					<span style="color:#b3791a;">(<?php esc_html_e( 'Uji coba admin — pembayaran SUNGGUHAN, hanya terlihat oleh admin', 'jwtrading' ); ?>)</span>
				<?php endif; ?>
			</p>
			<button type="button" class="alt-btn jwt-btn jwt-btn--ghost" id="jwt-yapp-btn"><?php esc_html_e( 'Bayar dengan Yapp →', 'jwtrading' ); ?></button>
			<div class="jwt-manual-warning" id="jwt-yapp-warning" role="alert" hidden></div>
		</div>
		<?php
	}

	public static function ajax_create_invoice() {
		check_ajax_referer( self::NONCE, 'nonce' );

		// Same gate as the button — the button being hidden must not be the only
		// thing standing between a visitor and this endpoint.
		if ( ! self::can_see_button() ) {
			wp_send_json_error( array( 'message' => __( 'Pembayaran Yapp belum aktif.', 'jwtrading' ) ), 403 );
		}

		$first   = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last    = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$discord = sanitize_text_field( wp_unslash( $_POST['discord'] ?? '' ) );
		$terms   = ! empty( $_POST['terms'] );
		$name    = trim( $first . ' ' . $last );

		/*
		 * This path bypasses WooCommerce's own checkout submission, so
		 * JWT_Checkout::validate_checkout() never runs. Mirror its rules here or the
		 * Yapp button quietly accepts orders the normal button would reject.
		 */
		if ( '' === $first || '' === $last || ! is_email( $email ) || '' === $phone ) {
			wp_send_json_error( array( 'message' => __( 'Mohon lengkapi nama depan, nama belakang, email, dan nomor WA.', 'jwtrading' ) ) );
		}
		if ( strlen( preg_replace( '/\D+/', '', $phone ) ) < 7 ) {
			wp_send_json_error( array( 'message' => __( 'Nomor WA minimal 7 digit.', 'jwtrading' ) ) );
		}
		if ( '' === $discord ) {
			wp_send_json_error( array( 'message' => __( 'Mohon isi Discord username.', 'jwtrading' ) ) );
		}
		if ( ! $terms ) {
			wp_send_json_error( array( 'message' => __( 'Mohon setujui Syarat & Ketentuan.', 'jwtrading' ) ) );
		}

		if ( ( ! WC()->cart || WC()->cart->is_empty() ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			wp_send_json_error( array( 'message' => __( 'Keranjang kosong.', 'jwtrading' ) ) );
		}

		/*
		 * A Yapp invoice covers exactly one product, priced by Yapp. Nothing actually
		 * guarantees a single-item cart (add-to-cart never clears it), so refuse
		 * rather than silently invoicing only the first line and undercharging.
		 */
		$cart = WC()->cart->get_cart();
		if ( count( $cart ) > 1 ) {
			wp_send_json_error( array( 'message' => __( 'Pembayaran Yapp hanya untuk satu produk. Hapus produk lain dari keranjang.', 'jwtrading' ) ) );
		}

		/*
		 * Yapp applies its own promo, not WooCommerce's. We only forward codes that
		 * have been mapped in the settings — an unmapped one would show a discount
		 * here and bill full price there.
		 */
		$promo = self::resolve_promo( (array) WC()->cart->get_applied_coupons() );
		if ( '' !== $promo['error'] ) {
			wp_send_json_error( array( 'message' => $promo['error'] ) );
		}

		$cart_item = current( $cart );
		$product   = $cart_item ? wc_get_product( $cart_item['product_id'] ) : null;
		if ( ! $product || ! $product->is_purchasable() ) {
			wp_send_json_error( array( 'message' => __( 'Produk tidak ditemukan.', 'jwtrading' ) ) );
		}

		$result = self::create_invoice( $product, $name, $email, $phone, $discord, $promo['code'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'url' => $result['checkout_link'] ) );
	}

	// --- Client: invoice creation + signing ----------------------------------

	/**
	 * Create an invoice — real Yapp API call, or a local mock row.
	 *
	 * @return array{invoice_uuid:string,checkout_link:string}|WP_Error
	 */
	public static function create_invoice( WC_Product $product, $name, $email, $phone, $discord = '', $promo_code = '' ) {
		global $wpdb;

		// Schema changes ship with a deploy, but the migration only runs on admin_init.
		// Without this, the window between deploying and the next wp-admin load would
		// fail every checkout on a missing column. Cheap: a cached option compare.
		self::maybe_create_table();

		/*
		 * Reuse a still-open invoice for the same buyer + product instead of minting a
		 * fresh referenceId on every click. Yapp's retry protection is keyed on
		 * referenceId — a new one each time defeats it, so a double-click or a timed-out
		 * request would raise two separately payable invoices for one purchase.
		 * Invoices expire after 24h, so anything older starts clean.
		 */
		// Promo code is part of the match: if the buyer changes or removes their coupon,
		// the old invoice is priced wrong and must not be handed back.
		$existing = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE product_id = %d AND buyer_email = %s AND status = %s AND is_mock = %d AND COALESCE(promo_code, %s) = %s AND created_at > %s ORDER BY id DESC LIMIT 1', // phpcs:ignore
				$product->get_id(),
				$email,
				self::S_PENDING,
				self::is_mock() ? 1 : 0,
				'',
				$promo_code,
				gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) - DAY_IN_SECONDS )
			)
		);

		if ( $existing && ! empty( $existing->checkout_link ) ) {
			return array(
				'invoice_uuid'  => $existing->invoice_uuid,
				'checkout_link' => $existing->checkout_link,
			);
		}

		$reference_id = 'JWT-' . $product->get_id() . '-' . time() . '-' . wp_generate_password( 4, false );
		$amount       = (float) $product->get_price();
		$currency     = get_woocommerce_currency();
		$is_mock      = self::is_mock();
		$now          = current_time( 'mysql' );

		if ( $is_mock ) {
			$invoice_uuid  = wp_generate_uuid4();
			$checkout_link = add_query_arg(
				array( 'jwt_yapp_mock' => '1', 'invoice' => $invoice_uuid ),
				home_url( '/' )
			);
		} else {
			$product_uuid = trim( (string) get_post_meta( $product->get_id(), '_jwt_yapp_product_uuid', true ) );
			if ( '' === $product_uuid ) {
				return new WP_Error( 'jwt_yapp_no_uuid', __( 'This product has no Yapp Product UUID configured yet.', 'jwtrading' ) );
			}

			$payload = array(
				'productUUID' => $product_uuid,
				'name'        => $name,
				'email'       => $email,
				'phoneNumber' => $phone,
				'referenceId' => $reference_id,
				'redirectUrl' => add_query_arg( array( 'jwt_yapp_thanks' => '1', 'ref' => $reference_id ), home_url( '/' ) ),
			);

			// Only ever a code that resolve_promo() matched against the settings map.
			if ( '' !== $promo_code ) {
				$payload['promoCode'] = $promo_code;
			}

			$body = wp_json_encode( $payload );

			$response = self::signed_request( 'POST', '/api/v1/invoices', $body );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$data = $response['data'] ?? array();
			if ( empty( $data['invoiceUUID'] ) || empty( $data['checkoutLink'] ) ) {
				return new WP_Error( 'jwt_yapp_bad_response', __( 'Unexpected response from Yapp.', 'jwtrading' ) );
			}

			$invoice_uuid  = $data['invoiceUUID'];
			$checkout_link = $data['checkoutLink'];
		}

		$inserted = $wpdb->insert(
			self::table(),
			array(
				'invoice_uuid'  => $invoice_uuid,
				'reference_id'  => $reference_id,
				'product_id'    => $product->get_id(),
				'status'        => self::S_PENDING,
				'amount'        => $amount,
				'currency'      => $currency,
				'buyer_name'    => $name,
				'buyer_email'   => $email,
				'buyer_phone'   => $phone,
				'discord_username' => $discord,
				'promo_code'    => $promo_code,
				'checkout_link' => $checkout_link,
				'is_mock'       => $is_mock ? 1 : 0,
				'created_at'    => $now,
				'updated_at'    => $now,
			)
		);

		/*
		 * Fail closed. Without a local row the webhook has nothing to match the
		 * payment against, so the buyer could pay on Yapp and never get an order —
		 * far worse than making them retry a checkout that hasn't charged them yet.
		 */
		if ( false === $inserted ) {
			return new WP_Error(
				'jwt_yapp_db_error',
				__( 'Tidak bisa menyimpan invoice. Silakan coba lagi.', 'jwtrading' )
			);
		}

		return array( 'invoice_uuid' => $invoice_uuid, 'checkout_link' => $checkout_link );
	}

	/**
	 * Raise a Yapp invoice for an existing WooCommerce order (the gateway flow).
	 *
	 * Differs from create_invoice() in two ways that matter:
	 *  - referenceId is derived from the order, so a buyer who retries payment on the
	 *    same order reuses it. That is exactly the retry protection Yapp documents:
	 *    resubmitting a live referenceId returns the original checkoutLink instead of
	 *    raising a second payable invoice.
	 *  - the invoice is linked to the order up front, so the webhook completes THAT
	 *    order rather than building a second one.
	 *
	 * @return array{invoice_uuid:string,checkout_link:string}|WP_Error
	 */
	public static function create_invoice_for_order( WC_Order $order ) {
		global $wpdb;

		self::maybe_create_table();

		$items = $order->get_items();
		if ( count( $items ) !== 1 ) {
			return new WP_Error(
				'jwt_yapp_multi_item',
				__( 'Pembayaran Yapp hanya untuk satu produk per pesanan.', 'jwtrading' )
			);
		}

		$item    = reset( $items );
		$product = $item->get_product();
		if ( ! $product ) {
			return new WP_Error( 'jwt_yapp_no_product', __( 'Produk tidak ditemukan.', 'jwtrading' ) );
		}

		$promo = self::resolve_promo( (array) $order->get_coupon_codes() );
		if ( '' !== $promo['error'] ) {
			return new WP_Error( 'jwt_yapp_promo', $promo['error'] );
		}

		$reference_id = 'JWT-ORDER-' . $order->get_id();

		// Reuse this order's own open invoice rather than raising another.
		$existing = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'SELECT * FROM ' . self::table() . ' WHERE reference_id = %s AND status = %s ORDER BY id DESC LIMIT 1', // phpcs:ignore
			$reference_id,
			self::S_PENDING
		) );
		if ( $existing && ! empty( $existing->checkout_link ) ) {
			return array(
				'invoice_uuid'  => $existing->invoice_uuid,
				'checkout_link' => $existing->checkout_link,
			);
		}

		$name    = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$discord = (string) $order->get_meta( '_discord_username' );
		$is_mock = self::is_mock();
		$now     = current_time( 'mysql' );

		if ( $is_mock ) {
			$invoice_uuid  = wp_generate_uuid4();
			$checkout_link = add_query_arg(
				array( 'jwt_yapp_mock' => '1', 'invoice' => $invoice_uuid ),
				home_url( '/' )
			);
		} else {
			$product_uuid = trim( (string) get_post_meta( $product->get_id(), '_jwt_yapp_product_uuid', true ) );
			if ( '' === $product_uuid ) {
				return new WP_Error( 'jwt_yapp_no_uuid', __( 'Produk ini belum punya Yapp Product UUID.', 'jwtrading' ) );
			}

			$payload = array(
				'productUUID' => $product_uuid,
				'name'        => $name,
				'email'       => $order->get_billing_email(),
				'phoneNumber' => $order->get_billing_phone(),
				'referenceId' => $reference_id,
				'redirectUrl' => $order->get_checkout_order_received_url(),
			);
			if ( '' !== $promo['code'] ) {
				$payload['promoCode'] = $promo['code'];
			}

			$response = self::signed_request( 'POST', '/api/v1/invoices', wp_json_encode( $payload ) );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$data = $response['data'] ?? array();
			if ( empty( $data['invoiceUUID'] ) || empty( $data['checkoutLink'] ) ) {
				return new WP_Error( 'jwt_yapp_bad_response', __( 'Respons Yapp tidak sesuai.', 'jwtrading' ) );
			}

			$invoice_uuid  = $data['invoiceUUID'];
			$checkout_link = $data['checkoutLink'];
		}

		$inserted = $wpdb->insert(
			self::table(),
			array(
				'invoice_uuid'     => $invoice_uuid,
				'reference_id'     => $reference_id,
				'product_id'       => $product->get_id(),
				'status'           => self::S_PENDING,
				'amount'           => (float) $order->get_total(),
				'currency'         => $order->get_currency(),
				'buyer_name'       => $name,
				'buyer_email'      => $order->get_billing_email(),
				'buyer_phone'      => $order->get_billing_phone(),
				'discord_username' => $discord,
				'promo_code'       => $promo['code'],
				'checkout_link'    => $checkout_link,
				'is_mock'          => $is_mock ? 1 : 0,
				'order_id'         => $order->get_id(),
				'created_at'       => $now,
				'updated_at'       => $now,
			)
		);

		if ( false === $inserted ) {
			return new WP_Error( 'jwt_yapp_db_error', __( 'Tidak bisa menyimpan invoice. Silakan coba lagi.', 'jwtrading' ) );
		}

		$order->update_meta_data( '_jwt_yapp_invoice_uuid', $invoice_uuid );
		$order->update_meta_data( '_jwt_yapp_reference_id', $reference_id );
		$order->save();

		return array( 'invoice_uuid' => $invoice_uuid, 'checkout_link' => $checkout_link );
	}

	/**
	 * GET status recheck (invoice-keyed) — real mode only.
	 */
	public static function get_invoice_status( $invoice_uuid ) {
		return self::signed_request( 'GET', '/api/v1/invoices/' . rawurlencode( $invoice_uuid ) . '/payment/status/creator', '' );
	}

	/**
	 * Sign + send a request per Section 6.1: message = client_id + "\n" + timestamp + "\n" + body.
	 */
	protected static function signed_request( $method, $path, $body ) {
		if ( ! function_exists( 'sodium_crypto_sign_detached' ) ) {
			return new WP_Error( 'jwt_yapp_no_sodium', 'sodium extension not available.' );
		}

		$s = self::settings();
		if ( empty( $s['client_id'] ) || empty( $s['private_key'] ) ) {
			return new WP_Error( 'jwt_yapp_no_creds', 'Client ID / Private Key not configured.' );
		}

		$timestamp   = time();
		$message     = $s['client_id'] . "\n" . $timestamp . "\n" . $body;
		$private_key = base64_decode( $s['private_key'] ); // phpcs:ignore
		$signature   = base64_encode( sodium_crypto_sign_detached( $message, $private_key ) ); // phpcs:ignore

		$headers = array(
			'Content-Type' => 'application/json',
			'X-Client-ID'  => $s['client_id'],
			'X-Timestamp'  => (string) $timestamp,
			'X-Signature'  => $signature,
		);

		if ( ! empty( $s['origin'] ) ) {
			$headers['X-Origin'] = $s['origin'];
		}

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => $headers,
		);
		if ( 'GET' !== $method ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( rtrim( $s['base_url'], '/' ) . $path, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = is_array( $decoded ) && ! empty( $decoded['message'] ) ? $decoded['message'] : ( 'HTTP ' . $code );
			return new WP_Error( 'jwt_yapp_http_' . $code, $msg );
		}

		return is_array( $decoded ) ? $decoded : array();
	}

	// --- Webhook receiver (real mode) ----------------------------------------

	public static function register_webhook_route() {
		register_rest_route( 'jwt/v1', '/yapp-webhook', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_webhook_request' ),
			'permission_callback' => '__return_true', // Auth is via signature, not WP auth.
		) );
	}

	public static function handle_webhook_request( WP_REST_Request $request ) {
		$raw_body  = $request->get_body();
		$signature = $request->get_header( 'x-xellar-signature' );

		if ( empty( $signature ) || ! self::verify_webhook_signature( $raw_body, $signature ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid signature' ), 401 );
		}

		$payload = json_decode( $raw_body, true );
		if ( ! is_array( $payload ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid payload' ), 400 );
		}

		if ( 'test.ping' === ( $payload['event'] ?? '' ) ) {
			return new WP_REST_Response( array( 'message' => 'pong' ), 200 );
		}

		if ( 'order.payment_succeeded' === ( $payload['event'] ?? '' ) && ! empty( $payload['data'] ) ) {
			try {
				self::complete_from_payload( $payload['data'], false );
			} catch ( Throwable $e ) {
				JWT_Sync_Log::log( 0, 'yapp', 'failed', $payload, $e->getMessage() );

				// Answer 500 rather than a polite 200. Yapp retries a failed delivery
				// on its own schedule (1m/5m/30m/2h), which recovers a transient fault
				// far sooner than waiting for our reconciliation cron — and a 200 here
				// would tell them it landed, burning the only other chance we get.
				return new WP_REST_Response( array( 'message' => 'Processing failed' ), 500 );
			}
		}

		return new WP_REST_Response( array( 'message' => 'OK' ), 200 );
	}

	/**
	 * Verify an inbound webhook. Retries once against a freshly fetched key so a
	 * rotation on Yapp's side recovers by itself instead of rejecting real payments.
	 */
	protected static function verify_webhook_signature( $raw_body, $signature_b64 ) {
		if ( ! function_exists( 'sodium_crypto_sign_verify_detached' ) ) {
			return false;
		}

		$signature = base64_decode( $signature_b64, true ); // phpcs:ignore
		if ( false === $signature || SODIUM_CRYPTO_SIGN_BYTES !== strlen( $signature ) ) {
			return false;
		}

		foreach ( array( false, true ) as $force_refetch ) {
			$key = self::raw_public_key( self::get_webhook_public_key( $force_refetch ) );
			if ( '' === $key ) {
				continue;
			}
			try {
				if ( sodium_crypto_sign_verify_detached( $signature, $raw_body, $key ) ) {
					return true;
				}
			} catch ( Throwable $e ) {
				// Malformed key — fall through and try a refetch.
				continue;
			}
		}

		return false;
	}

	/**
	 * Normalise whatever the public-key endpoint hands us into 32 raw ED25519 bytes.
	 *
	 * Yapp returns the key base64-encoded PEM (Section 6.2's Node sample decodes it
	 * to text before calling createPublicKey), i.e. base64( "-----BEGIN PUBLIC
	 * KEY-----\nMCow...\n-----END PUBLIC KEY-----" ). Sodium needs the 32 raw bytes
	 * out of the SPKI DER, so unwrap both layers. Plain PEM, bare base64 and hex are
	 * accepted too, purely so a format change on their side doesn't break payments.
	 */
	protected static function raw_public_key( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		// base64-wrapped PEM (what Yapp actually returns).
		$decoded = base64_decode( $value, true ); // phpcs:ignore
		if ( false !== $decoded && false !== strpos( $decoded, '-----BEGIN' ) ) {
			$value = $decoded;
		}

		// PEM in any form -> the DER between the armour lines.
		if ( false !== strpos( $value, '-----BEGIN' ) ) {
			$body = preg_replace( '/-----(BEGIN|END)[^-]*-----|\s+/', '', $value );
			$der  = base64_decode( (string) $body, true ); // phpcs:ignore
			return ( false !== $der && strlen( $der ) >= SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES )
				? substr( $der, -SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES )
				: '';
		}

		// Bare 32-byte base64, or the 44-byte SPKI DER base64'd without armour.
		if ( false !== $decoded ) {
			$len = strlen( $decoded );
			if ( SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES === $len || 44 === $len ) {
				return substr( $decoded, -SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES );
			}
		}

		// Hex.
		if ( preg_match( '/^[0-9a-f]{64}$/i', $value ) ) {
			return (string) hex2bin( $value );
		}

		return '';
	}

	/**
	 * Fetch + cache Yapp's public key for verifying inbound webhooks.
	 * Re-fetches on demand (e.g. call with $force) if verification ever starts failing — key rotation.
	 */
	protected static function get_webhook_public_key( $force = false ) {
		$cached = get_option( 'jwt_yapp_webhook_public_key', '' );
		if ( $cached && ! $force ) {
			return $cached;
		}

		$s = self::settings();

		// This endpoint needs no signature, but it IS behind the same origin check
		// as the rest of the API — without X-Origin it answers 403 and we would
		// never get a key to verify with.
		$headers = array();
		if ( ! empty( $s['origin'] ) ) {
			$headers['X-Origin'] = $s['origin'];
		}

		$response = wp_remote_get(
			rtrim( $s['base_url'], '/' ) . '/api/v1/webhook-endpoint/public-key',
			array(
				'timeout' => 10,
				'headers' => $headers,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $cached;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$key  = $data['data']['publicKey'] ?? $data['data']['public_key'] ?? $data['publicKey'] ?? $data['public_key'] ?? '';
		if ( $key ) {
			update_option( 'jwt_yapp_webhook_public_key', $key );
			return $key;
		}
		return $cached;
	}

	// --- Order building (shared by real webhook + mock pay) ------------------

	/**
	 * Build/complete a WooCommerce order from a webhook payload (real) or a
	 * fabricated one with the same shape (mock). Idempotent on invoiceUUID.
	 */
	protected static function complete_from_payload( array $data, $is_mock ) {
		global $wpdb;

		$invoice_uuid = $data['invoiceUUID'] ?? '';
		$reference_id = $data['referenceId'] ?? '';

		// invoiceUUID is absent in one documented case (a late payment against a
		// re-posted expired referenceId), so referenceId is the reliable fallback.
		$invoice = $invoice_uuid ? self::get_by_uuid( $invoice_uuid ) : null;
		if ( ! $invoice && $reference_id ) {
			$invoice = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE reference_id = %s', $reference_id ) ); // phpcs:ignore
		}
		if ( ! $invoice ) {
			throw new Exception( 'No matching invoice for webhook payload (invoiceUUID=' . $invoice_uuid . ', referenceId=' . $reference_id . ')' );
		}

		// Already done — a retry, or the cron and a webhook covering the same payment.
		if ( self::S_COMPLETED === $invoice->status && $invoice->order_id ) {
			return (int) $invoice->order_id;
		}

		// Refuse to complete an invoice against a different product than the one it
		// was raised for; the payload is the only thing telling us what was paid.
		$payload_product = $data['productUUID'] ?? '';
		if ( '' !== $payload_product && ! $invoice->is_mock ) {
			$expected = trim( (string) get_post_meta( $invoice->product_id, '_jwt_yapp_product_uuid', true ) );
			if ( '' !== $expected && ! hash_equals( $expected, $payload_product ) ) {
				throw new Exception( 'productUUID mismatch for invoice ' . $invoice->invoice_uuid . ' (expected ' . $expected . ', got ' . $payload_product . ')' );
			}
		}

		/*
		 * Claim the invoice atomically. Two webhook deliveries, or a delivery racing
		 * the reconciliation cron, could otherwise both read "pending" and each build
		 * an order — the window is real because order completion fires the Kit and
		 * Sheets calls before we get to mark it done. Only the request whose UPDATE
		 * actually changes a row proceeds.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$claimed = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::table() . ' SET status = %s, updated_at = %s WHERE id = %d AND status = %s', // phpcs:ignore
				'processing',
				current_time( 'mysql' ),
				$invoice->id,
				self::S_PENDING
			)
		);

		if ( ! $claimed ) {
			// Someone else has it. If they finished, hand back their order.
			$fresh = self::get_by_id( $invoice->id );
			if ( $fresh && $fresh->order_id ) {
				return (int) $fresh->order_id;
			}
			throw new Exception( 'Invoice ' . $invoice->invoice_uuid . ' is already being processed elsewhere.' );
		}

		try {
			// Gateway flow: WooCommerce already created the order when the buyer
			// pressed Checkout, so complete THAT one. Only the older button/mock flow
			// (no order attached) still builds an order from the invoice snapshot.
			$order_id = $invoice->order_id
				? self::complete_existing_order( (int) $invoice->order_id, $data )
				: self::build_order( $invoice, $data );
		} catch ( Throwable $e ) {
			// Release the claim so a retry can pick it up rather than stranding it.
			$wpdb->update(
				self::table(),
				array( 'status' => self::S_PENDING, 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $invoice->id )
			);
			throw $e;
		}

		$wpdb->update(
			self::table(),
			array(
				'status'     => self::S_COMPLETED,
				'order_uuid' => $data['orderUUID'] ?? null,
				'order_id'   => $order_id,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $invoice->id )
		);

		return $order_id;
	}

	/**
	 * Mark an order the gateway already created as paid.
	 *
	 * Deliberately does NOT rewrite the line items: WooCommerce built them at
	 * checkout with the coupon applied, and Yapp's amount includes their transaction
	 * fee, so overwriting the total here would make the order disagree with the
	 * invoice the buyer actually saw. Yapp's figures are recorded as meta instead.
	 */
	protected static function complete_existing_order( $order_id, array $data = array() ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			throw new Exception( 'Order #' . $order_id . ' no longer exists.' );
		}

		if ( $order->is_paid() ) {
			return $order->get_id();
		}

		self::stamp_yapp_meta( $order, $data );

		$order->save();

		// payment_complete() moves it to processing/completed via WooCommerce's own
		// path, firing the Kit/Sheets/Journal sync exactly like any other order.
		$order->payment_complete( $data['orderUUID'] ?? '' );
		$order->update_status( 'completed', __( 'Pembayaran Yapp berhasil.', 'jwtrading' ) );

		return $order->get_id();
	}

	/** Record Yapp's own figures on an order; their status endpoint never returns fees. */
	protected static function stamp_yapp_meta( WC_Order $order, array $data ) {
		foreach ( array(
			'orderUUID'          => '_jwt_yapp_order_uuid',
			'amount'             => '_jwt_yapp_amount_paid',
			'originalPrice'      => '_jwt_yapp_original_price',
			'priceAfterDiscount' => '_jwt_yapp_price_after_discount',
			'platformFee'        => '_jwt_yapp_platform_fee',
			'paymentGatewayFee'  => '_jwt_yapp_gateway_fee',
			'currency'           => '_jwt_yapp_currency',
			'paidAt'             => '_jwt_yapp_paid_at',
		) as $field => $meta_key ) {
			if ( isset( $data[ $field ] ) && '' !== $data[ $field ] ) {
				$order->update_meta_data( $meta_key, $data[ $field ] );
			}
		}
	}

	protected static function build_order( $invoice, array $data = array() ) {
		$product = wc_get_product( $invoice->product_id );
		if ( ! $product ) {
			throw new Exception( 'Product #' . $invoice->product_id . ' no longer exists.' );
		}

		$order = wc_create_order();
		if ( is_wp_error( $order ) ) {
			throw new Exception( $order->get_error_message() );
		}

		/*
		 * Yapp is the authority on what was actually charged — it owns the product
		 * price, any promo, and the payment itself. Letting WooCommerce re-derive the
		 * line total from the product's CURRENT price would silently disagree with the
		 * real payment whenever the price changed while the invoice was open, or a
		 * promo applied on their side. Prefer the webhook amount, then the amount we
		 * recorded when raising the invoice.
		 */
		$paid = isset( $data['amount'] ) && is_numeric( $data['amount'] )
			? (float) $data['amount']
			: (float) $invoice->amount;

		$order->add_product(
			$product,
			1,
			array(
				'subtotal' => $paid,
				'total'    => $paid,
			)
		);

		$name_parts = explode( ' ', trim( (string) $invoice->buyer_name ), 2 );
		$order->set_billing_first_name( $name_parts[0] ?? '' );
		$order->set_billing_last_name( $name_parts[1] ?? '' );
		$order->set_billing_email( $invoice->buyer_email );
		$order->set_billing_phone( $invoice->buyer_phone );

		$order->set_payment_method( 'jwt_yapp' );
		$order->set_payment_method_title( $invoice->is_mock ? __( 'Yapp (Mock)', 'jwtrading' ) : __( 'Yapp', 'jwtrading' ) );
		$order->set_created_via( 'jwt_yapp' );
		$order->update_meta_data( '_jwt_yapp_invoice_id', $invoice->id );
		$order->update_meta_data( '_jwt_yapp_invoice_uuid', $invoice->invoice_uuid );
		$order->update_meta_data( '_jwt_yapp_reference_id', $invoice->reference_id );
		// Same meta key class-checkout.php and class-manual-payment.php use — sheet-sync
		// (jw-integrations/modules/sheet-sync) already reads _discord_username, so this
		// is all that's needed for the Sheet row to carry it for Yapp orders too.
		if ( ! empty( $invoice->discord_username ) ) {
			$order->update_meta_data( '_discord_username', $invoice->discord_username );
		}

		// Which Yapp promo was sent, for support ("why was this one cheaper?").
		if ( ! empty( $invoice->promo_code ) ) {
			$order->update_meta_data( '_jwt_yapp_promo_code', $invoice->promo_code );
		}

		// Keep Yapp's own financial breakdown on the order. Their status endpoint
		// doesn't return fees, so the webhook is the only place these ever appear —
		// if we drop them here, reconciling payouts later becomes guesswork.
		foreach ( array(
			'orderUUID'         => '_jwt_yapp_order_uuid',
			'originalPrice'     => '_jwt_yapp_original_price',
			'priceAfterDiscount' => '_jwt_yapp_price_after_discount',
			'platformFee'       => '_jwt_yapp_platform_fee',
			'paymentGatewayFee' => '_jwt_yapp_gateway_fee',
			'currency'          => '_jwt_yapp_currency',
			'paidAt'            => '_jwt_yapp_paid_at',
		) as $field => $meta_key ) {
			if ( isset( $data[ $field ] ) && '' !== $data[ $field ] ) {
				$order->update_meta_data( $meta_key, $data[ $field ] );
			}
		}

		$order->calculate_totals( false ); // false = don't recalculate item prices from the product.
		$order->set_date_paid( time() );
		$order->save();

		// Same transition manual-payment uses — fires the site's existing
		// order-completion sync (Kit + Sheets) for free. Yapp itself already
		// granted course access; we're not calling any enrollment API here.
		$order->update_status( 'completed', __( 'Pembayaran Yapp berhasil.', 'jwtrading' ) );

		return $order->get_id();
	}

	// --- Mock checkout screens (front-end) ------------------------------------

	public static function render_screen() {
		$is_mock_screen   = ! empty( $_GET['jwt_yapp_mock'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_thanks_screen = ! empty( $_GET['jwt_yapp_thanks'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $is_mock_screen && ! $is_thanks_screen ) {
			return;
		}

		/*
		 * Never let these be cached. They render per-order, time-sensitive state, and
		 * EasyWP's page cache sits in front of nginx serving `cache-control: public`
		 * for anything on this path — a cached "menunggu konfirmasi" would keep
		 * showing after the payment had actually landed.
		 */
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();

		if ( $is_mock_screen ) {
			self::render_mock_checkout();
			exit;
		}

		self::render_thanks();
		exit;
	}

	protected static function render_mock_checkout() {
		// Staff-only: this screen's button creates a real Completed order.
		if ( ! self::is_mock() || ! self::can_simulate() ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$invoice_uuid = isset( $_GET['invoice'] ) ? sanitize_text_field( wp_unslash( $_GET['invoice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$invoice = self::get_by_uuid( $invoice_uuid );
		if ( ! $invoice ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		add_filter( 'body_class', static function ( $classes ) { $classes[] = 'jwt-manual-screen'; return $classes; } );
		get_header();
		?>
		<div class="jwt-manual-wrap"><div class="jwt-manual-card">
			<span class="jwt-manual-eyebrow"><?php esc_html_e( 'Yapp Checkout — Simulated', 'jwtrading' ); ?></span>
			<h1 class="jwt-manual-title"><?php esc_html_e( 'Simulated Payment Page', 'jwtrading' ); ?></h1>
			<p class="jwt-manual-lead"><?php esc_html_e( 'This stands in for Yapp\'s hosted checkout page while Mock Mode is on. Nothing here talks to Yapp — it only lets you see the buy → pay → confirmed loop before real credentials arrive.', 'jwtrading' ); ?></p>

			<div class="jwt-manual-bank">
				<div class="jwt-manual-bank__row"><span><?php esc_html_e( 'Produk', 'jwtrading' ); ?></span><strong><?php echo esc_html( get_the_title( $invoice->product_id ) ); ?></strong></div>
				<div class="jwt-manual-bank__row"><span><?php esc_html_e( 'Pembeli', 'jwtrading' ); ?></span><strong><?php echo esc_html( $invoice->buyer_name ); ?></strong></div>
				<div class="jwt-manual-bank__row"><span><?php esc_html_e( 'Reference ID', 'jwtrading' ); ?></span><strong><?php echo esc_html( $invoice->reference_id ); ?></strong></div>
				<div class="jwt-manual-bank__row jwt-manual-bank__total"><span><?php esc_html_e( 'Jumlah', 'jwtrading' ); ?></span><strong><?php echo wp_kses_post( wc_price( (float) $invoice->amount, array( 'currency' => $invoice->currency ) ) ); ?></strong></div>
			</div>

			<div class="jwt-manual-msg" id="jwt-yapp-mock-msg" role="alert" hidden></div>

			<div class="jwt-manual-actions">
				<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( home_url( '/bootcamp/' ) ); ?>"><?php esc_html_e( '← Batal', 'jwtrading' ); ?></a>
				<button type="button" class="jwt-btn" id="jwt-yapp-mock-pay" data-invoice="<?php echo esc_attr( $invoice->invoice_uuid ); ?>"><?php esc_html_e( 'Simulate Payment Success', 'jwtrading' ); ?></button>
			</div>
		</div></div>
		<?php
		get_footer();
	}

	/**
	 * Whoever may simulate a payment. A mock "payment" builds a real, Completed
	 * WooCommerce order and fires every downstream integration, so this is staff
	 * only — never something an anonymous visitor can reach.
	 */
	public static function can_simulate() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Whether this visitor should see the Yapp button at all.
	 *
	 * Two separate gates, both landing here:
	 *  - Mock mode: the button leads to a simulation that mints a real order, so it
	 *    is never shown to the public.
	 *  - Staff-only (ships on): live payments work, but nobody has proven the round
	 *    trip on this site yet. Keeps real customers off an unverified payment path
	 *    while staff test with a genuine purchase.
	 */
	/**
	 * Register the gateway so "Checkout →" itself can drive Yapp.
	 *
	 * Loaded here rather than from the plugin loader on purpose: the class extends
	 * WC_Payment_Gateway, which does not exist until WooCommerce has booted. A
	 * top-level require would fatal the whole site if WooCommerce were ever late or
	 * absent. This filter only ever runs from inside WooCommerce.
	 */
	public static function register_gateway( $gateways ) {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return $gateways;
		}
		if ( ! class_exists( 'JWT_Yapp_Gateway' ) ) {
			require_once JWT_CORE_PATH . 'includes/class-yapp-gateway.php';
		}
		$gateways[] = 'JWT_Yapp_Gateway';
		return $gateways;
	}

	/** Is the Yapp gateway switched on and usable at checkout? */
	public static function gateway_active() {
		if ( ! function_exists( 'WC' ) || ! class_exists( 'JWT_Yapp_Gateway' ) ) {
			return false;
		}
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : array();
		return isset( $gateways[ JWT_Yapp_Gateway::GATEWAY_ID ] );
	}

	public static function can_see_button() {
		// Once the gateway is live the main Checkout button IS Yapp, so the separate
		// button would just be a duplicate route to the same place.
		if ( self::gateway_active() ) {
			return false;
		}
		if ( self::is_mock() ) {
			return self::can_simulate();
		}
		if ( ! empty( self::settings()['staff_only'] ) ) {
			return self::can_simulate();
		}
		return true;
	}

	public static function ajax_mock_pay() {
		check_ajax_referer( self::NONCE, 'nonce' );

		// Mock mode must still be on: otherwise a leftover mock invoice from testing
		// stays "payable" forever and could mint a free order after go-live.
		if ( ! self::is_mock() ) {
			wp_send_json_error( array( 'message' => __( 'Mock Mode sudah dimatikan.', 'jwtrading' ) ) );
		}
		if ( ! self::can_simulate() ) {
			wp_send_json_error( array( 'message' => __( 'Hanya admin yang bisa mensimulasikan pembayaran.', 'jwtrading' ) ), 403 );
		}

		$invoice_uuid = sanitize_text_field( wp_unslash( $_POST['invoice'] ?? '' ) );
		$invoice = self::get_by_uuid( $invoice_uuid );
		if ( ! $invoice || ! $invoice->is_mock ) {
			wp_send_json_error( array( 'message' => __( 'Invoice tidak ditemukan.', 'jwtrading' ) ) );
		}
		if ( self::S_PENDING !== $invoice->status ) {
			wp_send_json_error( array( 'message' => __( 'Invoice ini sudah diproses.', 'jwtrading' ) ) );
		}

		// Fabricate the exact shape of a real `order.payment_succeeded` payload
		// (Section 5.2) and feed it through the same code the real webhook uses.
		$fake_payload = array(
			'orderUUID'   => wp_generate_uuid4(),
			'productUUID' => get_post_meta( $invoice->product_id, '_jwt_yapp_product_uuid', true ),
			'amount'      => (float) $invoice->amount,
			'currency'    => $invoice->currency,
			'paidAt'      => gmdate( 'c' ),
			'invoiceUUID' => $invoice->invoice_uuid,
			'referenceId' => $invoice->reference_id,
		);

		try {
			self::complete_from_payload( $fake_payload, true );
		} catch ( Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		wp_send_json_success( array(
			'url' => add_query_arg( array( 'jwt_yapp_thanks' => '1', 'ref' => $invoice->reference_id ), home_url( '/' ) ),
		) );
	}

	protected static function render_thanks() {
		global $wpdb;
		$reference_id = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$invoice = $reference_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE reference_id = %s', $reference_id ) ) : null; // phpcs:ignore

		/*
		 * Once the webhook has built the order, hand the buyer to WooCommerce's own
		 * order-received page — the one class-thankyou.php dresses up with Langkah
		 * Selanjutnya, the Discord/WA links and the PDF download. A Yapp buyer should
		 * land exactly where a Duitku buyer lands; this screen is only the waiting
		 * room for the seconds before the webhook arrives.
		 */
		if ( $invoice && $invoice->order_id ) {
			$order = wc_get_order( $invoice->order_id );
			if ( $order ) {
				wp_safe_redirect( $order->get_checkout_order_received_url() );
				exit;
			}
		}

		// Still waiting: re-check shortly rather than stranding them on a dead page.
		// Capped so a genuinely failed payment doesn't reload forever.
		$attempt = isset( $_GET['try'] ) ? absint( $_GET['try'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$waiting = $invoice && self::S_PENDING === $invoice->status && $attempt < 10;

		add_filter( 'body_class', static function ( $classes ) { $classes[] = 'jwt-manual-screen'; return $classes; } );
		get_header();
		$completed = $invoice && self::S_COMPLETED === $invoice->status;
		?>
		<div class="jwt-manual-wrap"><div class="jwt-manual-card">
			<span class="jwt-manual-eyebrow"><?php echo $completed ? esc_html__( 'Terkonfirmasi', 'jwtrading' ) : esc_html__( 'Diproses', 'jwtrading' ); ?></span>
			<h1 class="jwt-manual-title"><?php echo $completed ? esc_html__( 'Pembayaran Berhasil 🎉', 'jwtrading' ) : esc_html__( 'Menunggu Konfirmasi', 'jwtrading' ); ?></h1>
			<?php if ( $completed ) : ?>
				<p class="jwt-manual-lead"><?php esc_html_e( 'Akses kelas kamu sudah aktif di Yapp — cek email atau Library di akun Yapp kamu.', 'jwtrading' ); ?></p>
				<?php if ( $invoice->is_mock ) : ?>
					<p class="jwt-manual-note"><?php esc_html_e( '(Simulated — a real WooCommerce order was created and marked Completed from this mock payment, exactly like a real Yapp webhook would.)', 'jwtrading' ); ?></p>
				<?php endif; ?>
			<?php elseif ( $waiting ) : ?>
				<p class="jwt-manual-lead"><?php esc_html_e( 'Pembayaran kamu sedang dikonfirmasi oleh Yapp. Halaman ini akan otomatis lanjut begitu konfirmasi diterima — biasanya hanya beberapa detik.', 'jwtrading' ); ?></p>
				<p class="jwt-manual-note"><?php esc_html_e( 'Jangan tutup halaman ini dulu. Kalau sudah membayar, akses kelas kamu tetap aman meski halaman ini ditutup.', 'jwtrading' ); ?></p>
			<?php else : ?>
				<p class="jwt-manual-lead"><?php esc_html_e( 'Kami belum menerima konfirmasi pembayaran dari Yapp. Kalau kamu sudah membayar, akses kamu tetap diproses otomatis — cek email kamu sebentar lagi.', 'jwtrading' ); ?></p>
				<p class="jwt-manual-note"><?php esc_html_e( 'Butuh bantuan? Hubungi kami dan sebutkan nomor referensi di bawah.', 'jwtrading' ); ?></p>
				<?php if ( $invoice ) : ?>
					<p class="jwt-manual-note"><code><?php echo esc_html( $invoice->reference_id ); ?></code></p>
				<?php endif; ?>
			<?php endif; ?>
			<div class="jwt-manual-actions">
				<a class="jwt-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Kembali ke Beranda', 'jwtrading' ); ?></a>
			</div>
		</div></div>
		<?php
		if ( $waiting ) :
			// Poll by reloading with an incrementing counter. The redirect at the top of
			// this method takes over the moment the webhook has built the order.
			$next = add_query_arg(
				array(
					'jwt_yapp_thanks' => '1',
					'ref'             => $invoice->reference_id,
					'try'             => $attempt + 1,
				),
				home_url( '/' )
			);
			?>
			<script>
			setTimeout( function () {
				window.location.replace( <?php echo wp_json_encode( $next ); ?> );
			}, 3000 );
			</script>
			<?php
		endif;
		get_footer();
	}

	// --- Reconciliation cron (real mode) --------------------------------------

	public static function run_reconciliation() {
		if ( self::is_mock() ) {
			return; // Nothing to reconcile against — no real API to ask.
		}
		global $wpdb;

		/*
		 * created_at is written with current_time('mysql'), i.e. SITE local time, so
		 * the cutoff has to be local too. Comparing it against gmdate() shifted the
		 * window by the site's UTC offset (+7 for Asia/Jakarta), delaying every
		 * reconciliation by hours instead of the intended ten minutes.
		 */
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) - 10 * MINUTE_IN_SECONDS );

		// Mock rows are local fabrications Yapp has never heard of — asking about them
		// just burns API calls and can crowd out real invoices under the LIMIT.
		$stale = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'SELECT * FROM ' . self::table() . ' WHERE status = %s AND is_mock = 0 AND created_at < %s ORDER BY id ASC LIMIT 20', // phpcs:ignore
			self::S_PENDING,
			$cutoff
		) );

		foreach ( $stale as $invoice ) {
			$response = self::get_invoice_status( $invoice->invoice_uuid );
			if ( is_wp_error( $response ) ) {
				continue;
			}
			$status = $response['data']['status'] ?? '';

			if ( 'completed' === $status ) {
				try {
					self::complete_from_payload( $response['data'], false );
				} catch ( Throwable $e ) {
					JWT_Sync_Log::log( 0, 'yapp', 'failed', (array) $invoice, $e->getMessage() );
				}
				continue;
			}

			/*
			 * Only 'expired' is terminal for an invoice. A 'failed' status describes one
			 * payment ATTEMPT — the buyer can come back and pay the same invoice while
			 * it's still inside its 24h window, so keep polling until it truly expires.
			 */
			if ( 'expired' === $status ) {
				$wpdb->update(
					self::table(),
					array( 'status' => self::S_EXPIRED, 'updated_at' => current_time( 'mysql' ) ),
					array( 'id' => $invoice->id )
				);
			}
		}
	}
}

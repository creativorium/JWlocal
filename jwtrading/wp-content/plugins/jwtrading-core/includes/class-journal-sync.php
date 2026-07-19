<?php
defined( 'ABSPATH' ) || exit;

/**
 * Journal (jwtradingjurnal.com) whitelist webhook.
 *
 * When a WooCommerce order is COMPLETED (paid + fulfilled), POST the buyer's
 * email to the journal team's endpoint so they're whitelisted/invited. Runs on
 * its own enable toggle (independent of the gated core order-sync), is
 * idempotent per order, logs every attempt to the shared sync log, and reads
 * the secret from a WP option — never hardcoded.
 *
 * Settings: wp-admin → Settings → JW Journal.
 */
class JWT_Journal_Sync {

	const ENDPOINT    = 'https://api.jwtradingjurnal.com/api/webhook/whitelist';
	const OPT_SECRET  = 'jwt_journal_secret';
	const OPT_ENABLED = 'jwt_journal_enabled';
	const DONE_META   = '_jwt_journal_synced';

	public static function init() {
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'sync' ), 20, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'wp_ajax_jwt_journal_test', array( __CLASS__, 'ajax_test' ) );
	}

	/**
	 * Send the buyer's email to the journal webhook on order completion.
	 *
	 * @param int           $order_id Order ID.
	 * @param WC_Order|null $order    Order object (WooCommerce passes it).
	 */
	public static function sync( $order_id, $order = null ) {
		if ( ! get_option( self::OPT_ENABLED ) ) {
			return;
		}
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}
		// Idempotent — only fire once per order (retries / status re-transitions).
		if ( $order->get_meta( self::DONE_META ) ) {
			return;
		}

		$secret = trim( (string) get_option( self::OPT_SECRET, '' ) );
		if ( '' === $secret ) {
			JWT_Sync_Log::log( $order_id, 'journal', 'failed', array(), 'Missing journal webhook secret' );
			return;
		}

		$email = $order->get_billing_email();
		if ( ! $email || ! is_email( $email ) ) {
			JWT_Sync_Log::log( $order_id, 'journal', 'failed', array(), 'No valid billing email' );
			return;
		}

		$payload  = array(
			'email'     => $email,
			'requestId' => 'WC-' . (int) $order_id,
		);
		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'headers' => array(
					'Content-Type'     => 'application/json',
					'x-webhook-secret' => $secret,
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			JWT_Sync_Log::log( $order_id, 'journal', 'failed', $payload, $response->get_error_message() );
			return;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		$ok   = is_array( $data ) && ! empty( $data['ok'] );

		if ( $ok ) {
			// Success — including status=duplicate (already registered) which is
			// a normal, non-error outcome.
			$note = ( isset( $data['status'] ) && 'duplicate' === $data['status'] ) ? 'duplicate' : 'whitelisted';
			$order->update_meta_data( self::DONE_META, current_time( 'mysql' ) );
			$order->save();
			JWT_Sync_Log::log( $order_id, 'journal', 'success', $payload, $note . ' | ' . mb_substr( $body, 0, 500 ) );
		} else {
			$err = is_array( $data ) && isset( $data['error'] ) ? $data['error'] : ( 'HTTP ' . $code );
			JWT_Sync_Log::log( $order_id, 'journal', 'failed', $payload, $err . ' | ' . mb_substr( $body, 0, 500 ) );
		}
	}

	/**
	 * Ping the webhook with an empty-email payload to verify the secret without
	 * whitelisting anyone. The server checks auth before body validation, so a
	 * bad token returns an auth error while a good token only trips validation.
	 *
	 * @return array{ok:bool,message:string,detail:string}
	 */
	public static function test_connection() {
		$secret = trim( (string) get_option( self::OPT_SECRET, '' ) );
		if ( '' === $secret ) {
			return array( 'ok' => false, 'message' => 'Secret belum diisi. Simpan token dulu, lalu test.', 'detail' => '' );
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'headers' => array(
					'Content-Type'     => 'application/json',
					'x-webhook-secret' => $secret,
				),
				'body'    => wp_json_encode( array( 'email' => '', 'requestId' => 'PING-' . time() ) ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'ok' => false, 'message' => 'Tidak bisa menghubungi endpoint: ' . $response->get_error_message(), 'detail' => 'WP_Error: ' . $response->get_error_code() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		$err  = is_array( $data ) && isset( $data['error'] ) ? (string) $data['error'] : '';

		$detail = self::diagnostic( $response, $code, $body, $secret );

		// Auth rejected — wrong/expired secret.
		if ( in_array( $code, array( 401, 403 ), true ) || false !== stripos( $err . ' ' . $body, 'secret' ) || false !== stripos( $err, 'unauthor' ) ) {
			// Distinguish a genuine app-level reject (JSON body from their code) from
			// an infrastructure block (WAF/Cloudflare HTML, or an IP allowlist) that
			// never reached their auth logic — the token is irrelevant in that case.
			$is_app_json  = is_array( $data );
			$cf           = self::header_val( $response, 'cf-ray' );
			$looks_waf    = ( '' !== $cf ) || false !== stripos( $body, '<html' ) || false !== stripos( $body, 'cloudflare' );
			if ( ! $is_app_json && $looks_waf ) {
				$msg = 'DIBLOKIR infrastruktur (WAF/Cloudflare/IP allowlist), BUKAN token — request tidak sampai ke aplikasi mereka. HTTP ' . $code . '.';
			} else {
				$msg = 'Secret DITOLAK aplikasi (HTTP ' . $code . '). Token salah / salah environment / belum aktif.';
			}
			return array( 'ok' => false, 'message' => $msg, 'detail' => $detail );
		}

		// Reached and got past auth (2xx, or a body-validation error for the empty email).
		return array( 'ok' => true, 'message' => 'Terhubung — endpoint tercapai & secret diterima (HTTP ' . $code . ').', 'detail' => $detail );
	}

	/**
	 * Read a single response header value (case-insensitive) as a string.
	 */
	protected static function header_val( $response, $name ) {
		$headers = wp_remote_retrieve_headers( $response );
		if ( is_object( $headers ) && method_exists( $headers, 'offsetGet' ) ) {
			$v = isset( $headers[ $name ] ) ? $headers[ $name ] : '';
		} elseif ( is_array( $headers ) ) {
			$v = isset( $headers[ $name ] ) ? $headers[ $name ] : '';
		} else {
			$v = '';
		}
		return is_array( $v ) ? implode( ', ', $v ) : (string) $v;
	}

	/**
	 * Build a human-readable dump of the raw response for the Test Connection UI:
	 * status, the headers that reveal a WAF/proxy, and a truncated body. This is
	 * what tells us "their app rejected the token" vs "we never reached their app".
	 */
	protected static function diagnostic( $response, $code, $body, $secret ) {
		$lines   = array();
		$lines[] = 'HTTP status : ' . $code;
		$lines[] = 'Endpoint    : ' . self::ENDPOINT;
		$lines[] = 'Secret len  : ' . strlen( $secret ) . ' chars (dikirim di header x-webhook-secret)';
		$lines[] = '';
		$lines[] = '--- Response headers (petunjuk infrastruktur) ---';
		foreach ( array( 'server', 'cf-ray', 'cf-cache-status', 'www-authenticate', 'x-powered-by', 'content-type', 'via', 'x-vercel-id' ) as $h ) {
			$val = self::header_val( $response, $h );
			if ( '' !== $val ) {
				$lines[] = str_pad( $h, 16 ) . ': ' . $val;
			}
		}
		$lines[] = '';
		$lines[] = '--- Response body (maks 1500 char) ---';
		$lines[] = '' === trim( $body ) ? '(kosong)' : mb_substr( $body, 0, 1500 );
		return implode( "\n", $lines );
	}

	public static function ajax_test() {
		check_ajax_referer( 'jwt_journal_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		$result = self::test_connection();
		$detail = isset( $result['detail'] ) ? $result['detail'] : '';
		if ( $result['ok'] ) {
			wp_send_json_success( array( 'message' => $result['message'], 'detail' => $detail ) );
		}
		wp_send_json_error( array( 'message' => $result['message'], 'detail' => $detail ) );
	}

	// --- Admin settings (Settings → JW Journal) -------------------------------

	public static function menu() {
		add_options_page(
			__( 'JW Journal', 'jwtrading' ),
			__( 'JW Journal', 'jwtrading' ),
			'manage_options',
			'jwt-journal',
			array( __CLASS__, 'page' )
		);
	}

	public static function register() {
		register_setting( 'jwt_journal_group', self::OPT_SECRET, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting(
			'jwt_journal_group',
			self::OPT_ENABLED,
			array(
				'sanitize_callback' => static function ( $v ) {
					return empty( $v ) ? 0 : 1;
				},
			)
		);
	}

	/**
	 * Last 20 journal sync-log rows (this is the viewer for "tercatat di sync log").
	 */
	protected static function render_log() {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . JWT_Sync_Log::table() . ' WHERE target = %s ORDER BY updated_at DESC LIMIT 20', 'journal' ) ); // phpcs:ignore
		if ( ! $rows ) {
			echo '<p><em>' . esc_html__( 'Belum ada log. Selesaikan sebuah order (status Completed) untuk melihat hasilnya di sini.', 'jwtrading' ) . '</em></p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>Order</th><th>Status</th><th>Attempts</th><th>Response</th><th>Updated</th>';
		echo '</tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$color = ( 'success' === $r->status ) ? '#0a7d33' : '#b32d2e';
			echo '<tr>';
			echo '<td>#' . esc_html( $r->order_id ) . '</td>';
			echo '<td style="color:' . esc_attr( $color ) . ';font-weight:600;">' . esc_html( $r->status ) . '</td>';
			echo '<td>' . esc_html( $r->attempts ) . '</td>';
			echo '<td><code>' . esc_html( mb_substr( (string) $r->response, 0, 160 ) ) . '</code></td>';
			echo '<td>' . esc_html( $r->updated_at ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	public static function page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'JW Journal Integration', 'jwtrading' ); ?></h1>
			<p style="max-width:640px;"><?php esc_html_e( 'Saat order WooCommerce berstatus "Completed", email pembeli dikirim otomatis ke webhook jwtradingjurnal.com untuk di-whitelist / diundang. Hasil setiap kiriman tercatat di sync log.', 'jwtrading' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'jwt_journal_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Aktifkan', 'jwtrading' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPT_ENABLED ); ?>" value="1" <?php checked( get_option( self::OPT_ENABLED ), 1 ); ?>> <?php esc_html_e( 'Kirim ke Journal saat order "Completed"', 'jwtrading' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_journal_secret"><?php esc_html_e( 'Webhook Secret', 'jwtrading' ); ?></label></th>
						<td>
							<input type="password" class="regular-text" id="jwt_journal_secret" name="<?php echo esc_attr( self::OPT_SECRET ); ?>" value="<?php echo esc_attr( get_option( self::OPT_SECRET, '' ) ); ?>" autocomplete="off">
							<p class="description"><?php esc_html_e( 'Token dari tim Journal (dikirim sebagai header x-webhook-secret). Disimpan di database, tidak pernah di-hardcode.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Endpoint', 'jwtrading' ); ?></th>
						<td><code><?php echo esc_html( self::ENDPOINT ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Test Connection', 'jwtrading' ); ?></th>
						<td>
							<button type="button" class="button button-secondary" id="jwt-journal-test"><?php esc_html_e( 'Test Connection', 'jwtrading' ); ?></button>
							<span id="jwt-journal-test-result" style="margin-left:10px;font-weight:600;"></span>
							<p class="description"><?php esc_html_e( 'Simpan token dulu, lalu test. Mengirim ping ke webhook (tanpa whitelist siapa pun) untuk memastikan secret diterima.', 'jwtrading' ); ?></p>
							<pre id="jwt-journal-test-detail" style="display:none;margin-top:8px;padding:12px;background:#1d2327;color:#c3c4c7;border-radius:4px;max-width:760px;max-height:360px;overflow:auto;white-space:pre-wrap;word-break:break-word;font-size:12px;line-height:1.5;"></pre>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2 style="margin-top:2em;"><?php esc_html_e( 'Log Terakhir (Journal)', 'jwtrading' ); ?></h2>
			<?php self::render_log(); ?>
		</div>
		<script>
		( function () {
			var btn = document.getElementById( 'jwt-journal-test' );
			var out = document.getElementById( 'jwt-journal-test-result' );
			var dtl = document.getElementById( 'jwt-journal-test-detail' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function () {
				btn.disabled = true;
				out.style.color = '#646970';
				out.textContent = 'Testing…';
				if ( dtl ) { dtl.style.display = 'none'; dtl.textContent = ''; }
				var body = new URLSearchParams( { action: 'jwt_journal_test', nonce: '<?php echo esc_js( wp_create_nonce( 'jwt_journal_admin' ) ); ?>' } );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						out.style.color = res.success ? '#0a7d33' : '#b32d2e';
						out.textContent = ( res.data && res.data.message ) ? res.data.message : ( res.success ? 'OK' : 'Gagal' );
						if ( dtl && res.data && res.data.detail ) { dtl.textContent = res.data.detail; dtl.style.display = 'block'; }
					} )
					.catch( function () { out.style.color = '#b32d2e'; out.textContent = 'Request gagal.'; } )
					.finally( function () { btn.disabled = false; } );
			} );
		} )();
		</script>
		<?php
	}
}

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Mentorship funnel — opt-in → application → thank you. (Phase 2)
 *
 * Flow:
 *  1. /mentorship/ — opt-in form (nama / email / WhatsApp, all required).
 *     Creates a LEAD record (status "optin") and fires `jw_kit_tag_subscriber`
 *     with form_id `mentorship_optin` (mapped below → Kit tags), then redirects
 *     to the application page carrying the lead token.
 *  2. /mentorship/application/ — VSL + 6-question quiz. On submit the answers
 *     are attached to the lead (status "applied"), POSTed to the Google Apps
 *     Script receiver (when configured), and `application_submitted` is pushed
 *     to the dataLayer by the front end. Then → thank you.
 *  3. /mentorship/thank-you/ — token-gated: reachable only after a real submit
 *     (cookie set server-side in step 2). No form.
 *
 * Deliberately NOT built: call booking. The team filters leads and books over
 * WhatsApp by design (client decision) — see PHASE2-PLAN.md.
 *
 * Why our own leads table instead of JWT_Sync_Log: that table is order-keyed
 * and upserts per (order_id, target), so every lead would collide on order_id 0.
 * {prefix}jwt_funnel_leads also means the funnel works end-to-end before the
 * Apps Script endpoint exists — nothing is ever lost, the Sheet is a mirror.
 */
class JWT_Funnel {

	const OPT     = 'jwt_funnel';        // Settings array.
	const DB_OPT  = 'jwt_funnel_db_version';
	const DB_VER  = '1';
	const NONCE   = 'jwt_funnel';

	const COOKIE_LEAD = 'jwt_funnel_lead'; // Carries the lead token between steps.
	const COOKIE_DONE = 'jwt_funnel_done'; // Unlocks the thank-you page.
	const COOKIE_DAYS = 2;

	const S_OPTIN   = 'optin';
	const S_APPLIED = 'applied';

	/** Kit form_id for the opt-in (registered in the kit-tagger map below). */
	const KIT_FORM_ID = 'mentorship_optin';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_table' ) );

		add_action( 'wp_ajax_jwt_funnel_optin', array( __CLASS__, 'ajax_optin' ) );
		add_action( 'wp_ajax_nopriv_jwt_funnel_optin', array( __CLASS__, 'ajax_optin' ) );
		add_action( 'wp_ajax_jwt_funnel_apply', array( __CLASS__, 'ajax_apply' ) );
		add_action( 'wp_ajax_nopriv_jwt_funnel_apply', array( __CLASS__, 'ajax_apply' ) );

		add_action( 'template_redirect', array( __CLASS__, 'gate_thank_you' ), 5 );

		// Kit tag mapping for the opt-in form_id.
		add_filter( 'jw_kit_custom_form_map', array( __CLASS__, 'kit_form_map' ) );

		// Funnel pages use the stripped landing chrome (logo only, legal footer).
		add_filter( 'jwt/minimal_header', array( __CLASS__, 'filter_minimal_header' ) );
		add_filter( 'jwt/funnel_chrome', array( __CLASS__, 'filter_funnel_chrome' ) );

		// Turnstile widget script, only on pages that actually carry a funnel form.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_turnstile' ) );

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	// --- Settings ---------------------------------------------------------------

	public static function settings(): array {
		$defaults = array(
			'optin_slug'      => 'mentorship',
			'application_slug' => 'mentorship/application',
			'thankyou_slug'   => 'mentorship/thank-you',
			'turnstile_site'   => '',
			'turnstile_secret' => '',
			'sheets_url'       => '',
			'sheets_secret'    => '',
			'notify_email'     => '',
			'kit_tags'         => 'Mentorship_Optin, Stage_Warm',
			// Ships GATED (1). Turn off only to review the design; an open
			// thank-you page is shareable, which breaks the URL-based
			// conversion trigger in GTM.
			'thankyou_gate'    => 1,
		);
		$saved = get_option( self::OPT, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	public static function register_settings() {
		register_setting(
			'jwt_funnel_group',
			self::OPT,
			array(
				'sanitize_callback' => static function ( $in ) {
					$in  = is_array( $in ) ? $in : array();
					$out = array();
					foreach ( array( 'optin_slug', 'application_slug', 'thankyou_slug', 'turnstile_site', 'turnstile_secret', 'sheets_secret', 'kit_tags' ) as $k ) {
						$out[ $k ] = sanitize_text_field( $in[ $k ] ?? '' );
					}
					$out['sheets_url']    = esc_url_raw( $in['sheets_url'] ?? '' );
					$out['notify_email']  = sanitize_email( $in['notify_email'] ?? '' );
					$out['thankyou_gate'] = empty( $in['thankyou_gate'] ) ? 0 : 1;
					return $out;
				},
			)
		);
	}

	// --- URLs -------------------------------------------------------------------

	/** Resolve a settings slug to a permalink (falls back to home_url + slug). */
	protected static function url_for( string $key ): string {
		$slug = trim( (string) ( self::settings()[ $key ] ?? '' ), '/' );
		if ( '' === $slug ) {
			return home_url( '/' );
		}
		$page = get_page_by_path( $slug );
		return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
	}

	public static function optin_url(): string {
		return apply_filters( 'jwt/funnel_optin_url', self::url_for( 'optin_slug' ) );
	}

	public static function application_url(): string {
		return apply_filters( 'jwt/funnel_application_url', self::url_for( 'application_slug' ) );
	}

	public static function thankyou_url(): string {
		return apply_filters( 'jwt/funnel_thankyou_url', self::url_for( 'thankyou_slug' ) );
	}

	/** True on any of the three funnel pages. */
	public static function is_funnel_page(): bool {
		if ( ! function_exists( 'is_page' ) || ! is_page() ) {
			return false;
		}
		$s     = self::settings();
		$slugs = array();
		foreach ( array( 'optin_slug', 'application_slug', 'thankyou_slug' ) as $k ) {
			$parts   = explode( '/', trim( (string) $s[ $k ], '/' ) );
			$slugs[] = end( $parts );
		}
		return is_page( array_filter( $slugs ) );
	}

	public static function filter_minimal_header( $minimal ) {
		return $minimal || self::is_funnel_page();
	}

	/** Funnel chrome = centred logo, no nav CTA, legal-only footer (per the layout PDFs). */
	public static function filter_funnel_chrome( $on ) {
		return $on || self::is_funnel_page();
	}

	// --- Data store -------------------------------------------------------------

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'jwt_funnel_leads';
	}

	public static function create_table() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = self::table();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			token VARCHAR(64) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'optin',
			name VARCHAR(150) NULL,
			email VARCHAR(191) NULL,
			phone VARCHAR(50) NULL,
			answers LONGTEXT NULL,
			source VARCHAR(191) NULL,
			sheet_status VARCHAR(20) NULL,
			sheet_response TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY token (token),
			KEY status (status),
			KEY email (email)
		) {$charset};" );

		update_option( self::DB_OPT, self::DB_VER );
	}

	public static function maybe_create_table() {
		if ( get_option( self::DB_OPT ) !== self::DB_VER ) {
			self::create_table();
		}
	}

	protected static function lead_by_token( string $token ) {
		global $wpdb;
		if ( '' === $token ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE token = %s', $token ) );
	}

	// --- Spam protection (Cloudflare Turnstile) ---------------------------------

	public static function turnstile_site_key(): string {
		return trim( (string) self::settings()['turnstile_site'] );
	}

	/** Turnstile is optional: with no keys configured the forms still work. */
	protected static function turnstile_active(): bool {
		$s = self::settings();
		return '' !== trim( (string) $s['turnstile_site'] ) && '' !== trim( (string) $s['turnstile_secret'] );
	}

	public static function enqueue_turnstile() {
		if ( ! self::turnstile_active() || ! self::is_funnel_page() ) {
			return;
		}
		wp_enqueue_script( 'jwt-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
	}

	/** @return true|WP_Error */
	protected static function verify_turnstile( string $token ) {
		if ( ! self::turnstile_active() ) {
			return true;
		}
		if ( '' === $token ) {
			return new WP_Error( 'jwt_funnel_captcha', __( 'Verifikasi keamanan belum selesai. Coba lagi.', 'jwtrading' ) );
		}

		$res = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => trim( (string) self::settings()['turnstile_secret'] ),
					'response' => $token,
					'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			)
		);

		if ( is_wp_error( $res ) ) {
			// Never block a real lead on our own network failure.
			return true;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( empty( $body['success'] ) ) {
			return new WP_Error( 'jwt_funnel_captcha', __( 'Verifikasi keamanan gagal. Muat ulang halaman dan coba lagi.', 'jwtrading' ) );
		}
		return true;
	}

	// --- Kit --------------------------------------------------------------------

	/** Register the opt-in form_id → tags mapping for the jw-integrations kit-tagger. */
	public static function kit_form_map( $map ) {
		if ( ! is_array( $map ) ) {
			return $map;
		}
		$tags = array_values( array_filter( array_map( 'trim', explode( ',', (string) self::settings()['kit_tags'] ) ) ) );
		if ( empty( $tags ) ) {
			$tags = array( 'Mentorship_Optin', 'Stage_Warm' );
		}
		if ( ! isset( $map[ self::KIT_FORM_ID ] ) ) {
			$map[ self::KIT_FORM_ID ] = array(
				'tags'  => $tags,
				'stage' => 'Stage_Warm',
			);
		}
		return $map;
	}

	// --- Step 1: opt-in ----------------------------------------------------------

	public static function ajax_optin() {
		self::check_nonce();

		$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone = self::sanitize_phone( wp_unslash( $_POST['phone'] ?? '' ) );

		// All three are required — the team filters over WhatsApp, so a lead
		// without a phone number is unusable (client requirement).
		if ( '' === $name || '' === $email || '' === $phone ) {
			wp_send_json_error( array( 'message' => __( 'Mohon lengkapi nama, email, dan nomor WhatsApp.', 'jwtrading' ) ), 400 );
		}
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Email tidak valid.', 'jwtrading' ) ), 400 );
		}
		if ( strlen( $phone ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'Nomor WhatsApp tidak valid.', 'jwtrading' ) ), 400 );
		}

		$captcha = self::verify_turnstile( sanitize_text_field( wp_unslash( $_POST['cf_token'] ?? '' ) ) );
		if ( is_wp_error( $captcha ) ) {
			wp_send_json_error( array( 'message' => $captcha->get_error_message() ), 400 );
		}

		global $wpdb;
		$token = wp_generate_password( 32, false, false );
		$now   = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			self::table(),
			array(
				'token'      => $token,
				'status'     => self::S_OPTIN,
				'name'       => $name,
				'email'      => $email,
				'phone'      => $phone,
				'source'     => esc_url_raw( wp_unslash( $_POST['source'] ?? '' ) ),
				'created_at' => $now,
				'updated_at' => $now,
			)
		);

		// Kit: tagged via the jw-integrations kit-tagger (idempotent on its side).
		$parts = preg_split( '/\s+/', $name, 2 );
		do_action(
			'jw_kit_tag_subscriber',
			array(
				'email'      => $email,
				'form_id'    => self::KIT_FORM_ID,
				'first_name' => $parts[0] ?? $name,
				'last_name'  => $parts[1] ?? '',
			)
		);

		self::set_cookie( self::COOKIE_LEAD, $token );

		wp_send_json_success(
			array(
				'token'    => $token,
				'redirect' => add_query_arg( 'lead', $token, self::application_url() ),
			)
		);
	}

	// --- Step 2: application -----------------------------------------------------

	public static function ajax_apply() {
		self::check_nonce();

		$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
		if ( '' === $token ) {
			$token = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_LEAD ] ?? '' ) );
		}

		$lead = self::lead_by_token( $token );
		if ( ! $lead ) {
			wp_send_json_error(
				array(
					'message'  => __( 'Sesi tidak ditemukan. Silakan isi form pendaftaran dulu.', 'jwtrading' ),
					'redirect' => self::optin_url(),
				),
				403
			);
		}

		$captcha = self::verify_turnstile( sanitize_text_field( wp_unslash( $_POST['cf_token'] ?? '' ) ) );
		if ( is_wp_error( $captcha ) ) {
			wp_send_json_error( array( 'message' => $captcha->get_error_message() ), 400 );
		}

		// answers[] arrives as a JSON string: [{ q: "...", a: "..." }, …] in page order.
		$raw     = wp_unslash( $_POST['answers'] ?? '' );
		$decoded = json_decode( (string) $raw, true );
		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Jawaban belum lengkap.', 'jwtrading' ) ), 400 );
		}

		$answers = array();
		foreach ( $decoded as $row ) {
			$q = sanitize_text_field( (string) ( $row['q'] ?? '' ) );
			$a = sanitize_textarea_field( (string) ( $row['a'] ?? '' ) );
			if ( '' === $q ) {
				continue;
			}
			$answers[] = array(
				'q' => $q,
				'a' => $a,
			);
		}

		if ( empty( $answers ) ) {
			wp_send_json_error( array( 'message' => __( 'Jawaban belum lengkap.', 'jwtrading' ) ), 400 );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			self::table(),
			array(
				'status'     => self::S_APPLIED,
				'answers'    => wp_json_encode( $answers ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $lead->id )
		);

		$lead->answers = wp_json_encode( $answers );
		$lead->status  = self::S_APPLIED;

		// Never let an external hiccup lose the application — it is already saved
		// in our table above; Sheets + email are best-effort mirrors.
		try {
			self::dispatch_to_sheets( $lead, $answers );
		} catch ( Throwable $e ) {
			self::record_sheet_result( (int) $lead->id, 'failed', $e->getMessage() );
		}

		self::notify_admin( $lead, $answers );
		self::set_cookie( self::COOKIE_DONE, $token );

		wp_send_json_success( array( 'redirect' => self::thankyou_url() ) );
	}

	/** POST the application to the Apps Script receiver (own tab/endpoint). */
	protected static function dispatch_to_sheets( $lead, array $answers ) {
		$s   = self::settings();
		$url = trim( (string) $s['sheets_url'] );

		if ( '' === $url ) {
			self::record_sheet_result( (int) $lead->id, 'skipped', 'Sheets webhook URL not configured.' );
			return;
		}

		$flat = array();
		foreach ( $answers as $i => $row ) {
			$flat[ 'q' . ( $i + 1 ) ] = $row['a'];
		}

		$payload = array_merge(
			array(
				'secret'  => trim( (string) $s['sheets_secret'] ),
				'type'    => 'mentorship_application',
				'lead_id' => (int) $lead->id,
				'date'    => current_time( 'mysql' ),
				'name'    => $lead->name,
				'email'   => $lead->email,
				'phone'   => $lead->phone,
				'source'  => $lead->source,
				'answers' => $answers,
			),
			$flat
		);

		$res = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $res ) ) {
			self::record_sheet_result( (int) $lead->id, 'failed', $res->get_error_message() );
			return;
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = (string) wp_remote_retrieve_body( $res );

		if ( $code < 200 || $code >= 400 ) {
			self::record_sheet_result( (int) $lead->id, 'failed', 'HTTP ' . $code . ': ' . $body );
			return;
		}

		self::record_sheet_result( (int) $lead->id, 'success', $body );
	}

	protected static function record_sheet_result( int $lead_id, string $status, string $response ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			self::table(),
			array(
				'sheet_status'   => $status,
				'sheet_response' => mb_substr( $response, 0, 2000 ),
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $lead_id )
		);
	}

	/** Optional heads-up email. The client checks the sheet/admin list manually. */
	protected static function notify_admin( $lead, array $answers ) {
		$to = trim( (string) self::settings()['notify_email'] );
		if ( '' === $to || ! is_email( $to ) ) {
			return;
		}

		$lines = array(
			'Aplikasi mentorship baru:',
			'',
			'Nama  : ' . $lead->name,
			'Email : ' . $lead->email,
			'WA    : ' . $lead->phone,
			'',
		);
		foreach ( $answers as $i => $row ) {
			$lines[] = ( $i + 1 ) . '. ' . $row['q'];
			$lines[] = '   → ' . $row['a'];
		}

		wp_mail( $to, '[JW Mentorship] Aplikasi baru — ' . $lead->name, implode( "\n", $lines ) );
	}

	// --- Step 3: thank-you gate ---------------------------------------------------

	/**
	 * The thank-you page is only reachable after a real submission — it doubles as
	 * a clean URL-based conversion trigger in GTM, so it must not be shareable.
	 */
	public static function gate_thank_you() {
		if ( is_admin() || ! function_exists( 'is_page' ) ) {
			return;
		}

		// Temporarily open for design review (Mentorship → Pengaturan).
		if ( empty( self::settings()['thankyou_gate'] ) ) {
			return;
		}

		$parts = explode( '/', trim( (string) self::settings()['thankyou_slug'], '/' ) );
		$slug  = end( $parts );
		if ( '' === $slug || ! is_page( $slug ) ) {
			return;
		}

		if ( is_user_logged_in() && current_user_can( 'edit_pages' ) ) {
			return; // Editors previewing the page.
		}

		$token = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_DONE ] ?? '' ) );
		$lead  = self::lead_by_token( $token );

		if ( $lead && self::S_APPLIED === $lead->status ) {
			return;
		}

		wp_safe_redirect( self::optin_url(), 302 );
		exit;
	}

	// --- Helpers ------------------------------------------------------------------

	protected static function check_nonce() {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_send_json_error( array( 'message' => __( 'Sesi kedaluwarsa. Muat ulang halaman.', 'jwtrading' ) ), 403 );
		}
	}

	/** Keep a leading + but drop everything else non-numeric. */
	public static function sanitize_phone( $raw ): string {
		$raw = trim( (string) $raw );
		$plus = str_starts_with( $raw, '+' ) ? '+' : '';
		return $plus . preg_replace( '/\D+/', '', $raw );
	}

	/**
	 * Phone → wa.me digits. Leads type their number the Indonesian way
	 * ("08123…"), but wa.me needs the country code and rejects the leading 0 —
	 * so a raw digit-strip produces a dead click-to-chat link.
	 *
	 * @param string $phone Stored phone value.
	 */
	public static function wa_digits( $phone ): string {
		$digits = preg_replace( '/\D+/', '', (string) $phone );

		if ( '' === $digits ) {
			return '';
		}
		if ( str_starts_with( $digits, '0' ) ) {
			return apply_filters( 'jwt/funnel_country_code', '62' ) . ltrim( $digits, '0' );
		}
		return $digits;
	}

	protected static function set_cookie( string $name, string $value ) {
		if ( headers_sent() ) {
			return;
		}
		setcookie(
			$name,
			$value,
			array(
				'expires'  => time() + ( self::COOKIE_DAYS * DAY_IN_SECONDS ),
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		$_COOKIE[ $name ] = $value;
	}

	/** Shared by both funnel forms: nonce + Turnstile widget markup. */
	public static function form_security_html(): string {
		$html = '<input type="hidden" name="nonce" value="' . esc_attr( wp_create_nonce( self::NONCE ) ) . '">';

		$site = self::turnstile_site_key();
		if ( '' !== $site ) {
			$html .= '<div class="cf-turnstile jwt-funnel__captcha" data-sitekey="' . esc_attr( $site ) . '" data-theme="dark"></div>';
		}
		return $html;
	}

	// --- Admin --------------------------------------------------------------------

	public static function admin_menu() {
		add_menu_page(
			__( 'Mentorship Funnel', 'jwtrading' ),
			__( 'Mentorship', 'jwtrading' ),
			'manage_options',
			'jwt-funnel',
			array( __CLASS__, 'render_admin' ),
			'dashicons-forms',
			57
		);
	}

	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$s = self::settings();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$leads = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 100' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mentorship Funnel', 'jwtrading' ); ?></h1>

			<h2><?php esc_html_e( 'Aplikasi & Lead Terbaru', 'jwtrading' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>#</th>
						<th><?php esc_html_e( 'Tanggal', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Nama', 'jwtrading' ); ?></th>
						<th>Email</th>
						<th>WhatsApp</th>
						<th>Status</th>
						<th>Sheet</th>
						<th><?php esc_html_e( 'Jawaban', 'jwtrading' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $leads ) ) : ?>
					<tr><td colspan="8"><?php esc_html_e( 'Belum ada lead.', 'jwtrading' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $leads as $lead ) : ?>
						<?php $answers = json_decode( (string) $lead->answers, true ); ?>
						<tr>
							<td><?php echo (int) $lead->id; ?></td>
							<td><?php echo esc_html( $lead->created_at ); ?></td>
							<td><?php echo esc_html( (string) $lead->name ); ?></td>
							<td><?php echo esc_html( (string) $lead->email ); ?></td>
							<td>
								<?php $wa = self::wa_digits( $lead->phone ); ?>
								<?php if ( '' !== $wa ) : ?>
									<a href="<?php echo esc_url( 'https://wa.me/' . $wa ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) $lead->phone ); ?></a>
								<?php else : ?>
									<?php echo esc_html( (string) $lead->phone ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( (string) $lead->status ); ?></td>
							<td><?php echo esc_html( (string) ( $lead->sheet_status ?: '—' ) ); ?></td>
							<td>
								<?php if ( is_array( $answers ) && $answers ) : ?>
									<details>
										<summary><?php echo esc_html( sprintf( /* translators: %d: answer count. */ __( '%d jawaban', 'jwtrading' ), count( $answers ) ) ); ?></summary>
										<ol style="margin:.5em 0 0 1.2em">
											<?php foreach ( $answers as $row ) : ?>
												<li><strong><?php echo esc_html( (string) $row['q'] ); ?></strong><br><?php echo esc_html( (string) $row['a'] ); ?></li>
											<?php endforeach; ?>
										</ol>
									</details>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2 style="margin-top:2em"><?php esc_html_e( 'Pengaturan', 'jwtrading' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'jwt_funnel_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Slug halaman', 'jwtrading' ); ?></th>
						<td>
							<p><label>Opt-in<br><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[optin_slug]" value="<?php echo esc_attr( $s['optin_slug'] ); ?>"></label></p>
							<p><label>Application<br><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[application_slug]" value="<?php echo esc_attr( $s['application_slug'] ); ?>"></label></p>
							<p><label>Thank you<br><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[thankyou_slug]" value="<?php echo esc_attr( $s['thankyou_slug'] ); ?>"></label></p>
							<p class="description"><?php esc_html_e( 'Path halaman tanpa slash awal, mis. mentorship/application', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Kunci halaman Thank You', 'jwtrading' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPT ); ?>[thankyou_gate]" value="1" <?php checked( ! empty( $s['thankyou_gate'] ) ); ?>>
								<?php esc_html_e( 'Hanya bisa dibuka setelah kirim aplikasi', 'jwtrading' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Matikan sementara kalau mau lihat desainnya langsung. WAJIB dinyalakan lagi sebelum live — halaman thank you yang terbuka bisa di-share, dan itu merusak conversion trigger berbasis URL di GTM.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">Cloudflare Turnstile</th>
						<td>
							<p><label>Site key<br><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[turnstile_site]" value="<?php echo esc_attr( $s['turnstile_site'] ); ?>"></label></p>
							<p><label>Secret key<br><input type="password" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[turnstile_secret]" value="<?php echo esc_attr( $s['turnstile_secret'] ); ?>"></label></p>
							<p class="description"><?php esc_html_e( 'Kosongkan keduanya untuk mematikan proteksi bot (form tetap jalan).', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Google Sheets (aplikasi)', 'jwtrading' ); ?></th>
						<td>
							<p><label>Webhook URL (Apps Script)<br><input type="url" class="large-text" name="<?php echo esc_attr( self::OPT ); ?>[sheets_url]" value="<?php echo esc_attr( $s['sheets_url'] ); ?>"></label></p>
							<p><label>Shared secret<br><input type="password" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[sheets_secret]" value="<?php echo esc_attr( $s['sheets_secret'] ); ?>"></label></p>
							<p class="description"><?php esc_html_e( 'Butuh receiver baru di Apps Script (kolom jawaban aplikasi, bukan kolom order). Selama kosong, jawaban tetap tersimpan di tabel di atas.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Kit tags (opt-in)', 'jwtrading' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[kit_tags]" value="<?php echo esc_attr( $s['kit_tags'] ); ?>">
							<p class="description"><?php esc_html_e( 'Dipisah koma. Tag harus sudah ada di konfigurasi kit-tagger.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email notifikasi', 'jwtrading' ); ?></th>
						<td>
							<input type="email" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[notify_email]" value="<?php echo esc_attr( $s['notify_email'] ); ?>">
							<p class="description"><?php esc_html_e( 'Opsional — kosongkan kalau cukup cek tabel/Sheet manual.', 'jwtrading' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

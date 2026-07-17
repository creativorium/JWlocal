<?php
defined( 'ABSPATH' ) || exit;

/**
 * Promo banner — a scheduled, timezone-aware countdown strip shown at the top
 * of the site (above the header). Configured from a simple admin page:
 * on/off, message, coupon code, link, timezone, and start/end date-time. The
 * countdown targets an absolute moment (end time interpreted in the chosen
 * timezone) so it reads correctly for every visitor. The WooCommerce coupon
 * itself is created in Woo → Marketing → Coupons; this only announces it.
 */
class JWT_Promo_Banner {

	const OPTION = 'jwt_promo_banner';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'wp_body_open', array( __CLASS__, 'render' ), 5 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/** Flag pages that show the banner so the fixed strip can offset the layout. */
	public static function body_class( $classes ) {
		if ( ! is_admin() && self::is_active() ) {
			$classes[] = 'jwt-has-promo';
		}
		return $classes;
	}

	public static function defaults() {
		return array(
			'enabled'         => 0,
			'headline'        => 'Diskon 20% Bootcamp',
			'code'            => 'BOOTCAMP20',
			'url'             => '/bootcamp/',
			'start'           => '',
			'end'             => '',
			'timezone'        => 'Asia/Jakarta',
			// Auto-extension: when `end` passes, keep the banner alive counting to
			// `extend_end` with an "EXTENDED" badge (same headline + code).
			'extend'          => 0,
			'extend_end'      => '',
		);
	}

	public static function get() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	/** Interpret a `datetime-local` value in the given timezone → UTC epoch seconds. */
	protected static function ts( $datetime, $tz ) {
		$datetime = trim( (string) $datetime );
		if ( '' === $datetime ) {
			return 0;
		}
		try {
			return ( new DateTime( $datetime, new DateTimeZone( $tz ?: 'UTC' ) ) )->getTimestamp();
		} catch ( Exception $e ) {
			return 0;
		}
	}

	protected static function end_ts() {
		$o = self::get();
		return self::ts( $o['end'], $o['timezone'] );
	}

	/** Extension target (only when the extension is enabled + set). */
	protected static function extend_ts() {
		$o = self::get();
		return ( ! empty( $o['extend'] ) && '' !== trim( (string) $o['extend_end'] ) ) ? self::ts( $o['extend_end'], $o['timezone'] ) : 0;
	}

	/** The moment the banner finally disappears (extension end if set, else end). */
	protected static function final_ts() {
		$ext = self::extend_ts();
		return $ext ?: self::end_ts();
	}

	/** True once the primary end has passed and an extension is configured/upcoming. */
	protected static function is_extended_now() {
		$end = self::end_ts();
		$ext = self::extend_ts();
		return $ext && $end && time() >= $end;
	}

	public static function is_active() {
		$o = self::get();
		if ( empty( $o['enabled'] ) ) {
			return false;
		}
		$now   = time();
		$start = self::ts( $o['start'], $o['timezone'] );
		$final = self::final_ts();
		if ( $start && $now < $start ) {
			return false;
		}
		if ( $final && $now >= $final ) {
			return false;
		}
		return true;
	}

	// --- Front-end render -----------------------------------------------------

	public static function render() {
		if ( is_admin() || ! self::is_active() ) {
			return;
		}
		$o         = self::get();
		$end_ms    = self::end_ts() * 1000;
		$ext_ms    = self::extend_ts() * 1000;
		$extended  = self::is_extended_now();
		$target_ms = $extended ? $ext_ms : $end_ms;

		$url = '' !== trim( (string) $o['url'] ) ? $o['url'] : '/';
		if ( 0 === strpos( $url, '/' ) ) {
			$url = home_url( $url );
		}
		?>
		<a class="jwt-promo-banner<?php echo $extended ? ' is-extended' : ''; ?>" href="<?php echo esc_url( $url ); ?>" data-jwt-promo>
			<?php if ( $extended ) : ?>
				<span class="jwt-promo-banner__badge"><?php esc_html_e( 'EXTENDED', 'jwtrading' ); ?></span>
			<?php endif; ?>

			<span class="jwt-promo-banner__msg"><?php echo wp_kses_post( self::highlight( $o['headline'] ) ); ?></span>

			<?php if ( '' !== trim( (string) $o['code'] ) ) : ?>
				<span class="jwt-promo-banner__sep" aria-hidden="true"></span>
				<span class="jwt-promo-banner__code"><?php esc_html_e( 'Kode:', 'jwtrading' ); ?> <code><?php echo esc_html( $o['code'] ); ?></code></span>
			<?php endif; ?>

			<?php if ( $target_ms > 0 ) : ?>
				<span class="jwt-promo-banner__sep" aria-hidden="true"></span>
				<span
					class="jwt-promo-banner__timer"
					data-jwt-countdown="<?php echo esc_attr( (string) $target_ms ); ?>"
					<?php if ( ! $extended && $ext_ms > 0 ) : ?>data-jwt-extend="<?php echo esc_attr( (string) $ext_ms ); ?>"<?php endif; ?>
				>
					<span class="jwt-promo-banner__unit"><b data-d>00</b><small><?php esc_html_e( 'HARI', 'jwtrading' ); ?></small></span>
					<span class="jwt-promo-banner__colon">:</span>
					<span class="jwt-promo-banner__unit"><b data-h>00</b><small><?php esc_html_e( 'JAM', 'jwtrading' ); ?></small></span>
					<span class="jwt-promo-banner__colon">:</span>
					<span class="jwt-promo-banner__unit"><b data-m>00</b><small><?php esc_html_e( 'MENIT', 'jwtrading' ); ?></small></span>
				</span>
			<?php endif; ?>
		</a>
		<?php
	}

	/** Wrap any "NN%" in <mark> so the discount figure pops. */
	protected static function highlight( $text ) {
		return preg_replace( '/(\d+%)/', '<mark>$1</mark>', esc_html( $text ) );
	}

	// --- Admin ----------------------------------------------------------------

	public static function menu() {
		add_menu_page(
			__( 'Promo Banner', 'jwtrading' ),
			__( 'Promo Banner', 'jwtrading' ),
			'manage_options',
			'jwt-promo-banner',
			array( __CLASS__, 'page' ),
			'dashicons-megaphone',
			58
		);
	}

	public static function register() {
		register_setting( 'jwt_promo_group', self::OPTION, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	public static function sanitize( $input ) {
		$input = (array) $input;
		$d     = self::defaults();
		$tz    = $input['timezone'] ?? '';
		if ( ! in_array( $tz, timezone_identifiers_list(), true ) ) {
			$tz = 'Asia/Jakarta';
		}
		return array(
			'enabled'         => empty( $input['enabled'] ) ? 0 : 1,
			'headline'        => sanitize_text_field( $input['headline'] ?? $d['headline'] ),
			'code'            => sanitize_text_field( $input['code'] ?? '' ),
			'url'             => esc_url_raw( $input['url'] ?? $d['url'] ),
			'start'           => sanitize_text_field( $input['start'] ?? '' ),
			'end'             => sanitize_text_field( $input['end'] ?? '' ),
			'timezone'        => $tz,
			'extend'          => empty( $input['extend'] ) ? 0 : 1,
			'extend_end'      => sanitize_text_field( $input['extend_end'] ?? '' ),
		);
	}

	public static function page() {
		$o        = self::get();
		$active   = self::is_active();
		$extended = self::is_extended_now();
		$tzs      = array( 'Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'Asia/Singapore', 'Asia/Kuala_Lumpur', 'UTC' );
		$name     = self::OPTION;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Promo Banner', 'jwtrading' ); ?></h1>
			<p style="font-size:14px;">
				<?php if ( $active ) : ?>
					<span style="color:#1a7f37;font-weight:600;">● <?php echo $extended ? esc_html__( 'AKTIF — fase EXTENDED', 'jwtrading' ) : esc_html__( 'AKTIF — banner sedang tampil', 'jwtrading' ); ?></span>
				<?php else : ?>
					<span style="color:#a00;font-weight:600;">● <?php esc_html_e( 'Tidak aktif', 'jwtrading' ); ?></span>
				<?php endif; ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'jwt_promo_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Aktifkan banner', 'jwtrading' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $o['enabled'], 1 ); ?>> <?php esc_html_e( 'Tampilkan di seluruh situs', 'jwtrading' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Teks promo', 'jwtrading' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[headline]" value="<?php echo esc_attr( $o['headline'] ); ?>">
							<p class="description"><?php esc_html_e( 'Angka + % otomatis di-highlight. Contoh: "Diskon 20% Bootcamp".', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Kode kupon', 'jwtrading' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[code]" value="<?php echo esc_attr( $o['code'] ); ?>">
							<p class="description"><?php esc_html_e( 'Buat kuponnya di WooCommerce → Marketing → Coupons (atur juga tanggal kadaluarsanya). Kolom ini hanya menampilkan kodenya di banner. Kosongkan untuk menyembunyikan.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Link (URL)', 'jwtrading' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $o['url'] ); ?>">
							<p class="description"><?php esc_html_e( 'Ke mana banner mengarah saat diklik. Contoh: /bootcamp/', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Zona waktu', 'jwtrading' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( $name ); ?>[timezone]">
								<?php foreach ( $tzs as $tz ) : ?>
									<option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $o['timezone'], $tz ); ?>><?php echo esc_html( $tz ); ?></option>
								<?php endforeach; ?>
								<?php if ( ! in_array( $o['timezone'], $tzs, true ) ) : ?>
									<option value="<?php echo esc_attr( $o['timezone'] ); ?>" selected><?php echo esc_html( $o['timezone'] ); ?></option>
								<?php endif; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Waktu Mulai/Berakhir di bawah dibaca dalam zona ini.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Mulai (opsional)', 'jwtrading' ); ?></label></th>
						<td>
							<input type="datetime-local" name="<?php echo esc_attr( $name ); ?>[start]" value="<?php echo esc_attr( $o['start'] ); ?>">
							<p class="description"><?php esc_html_e( 'Kosongkan = langsung tampil begitu diaktifkan.', 'jwtrading' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Berakhir', 'jwtrading' ); ?></label></th>
						<td>
							<input type="datetime-local" name="<?php echo esc_attr( $name ); ?>[end]" value="<?php echo esc_attr( $o['end'] ); ?>">
							<p class="description"><?php esc_html_e( 'Hitung mundur menuju waktu ini.', 'jwtrading' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Perpanjangan otomatis', 'jwtrading' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[extend]" value="1" <?php checked( $o['extend'], 1 ); ?>> <?php esc_html_e( 'Saat "Berakhir" lewat, banner tetap tampil dan lanjut hitung mundur ke waktu perpanjangan (dengan badge "EXTENDED"). Tanpa ini, banner langsung hilang saat "Berakhir".', 'jwtrading' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Berakhir setelah perpanjangan', 'jwtrading' ); ?></label></th>
						<td>
							<input type="datetime-local" name="<?php echo esc_attr( $name ); ?>[extend_end]" value="<?php echo esc_attr( $o['extend_end'] ); ?>">
							<p class="description"><?php esc_html_e( 'Contoh: "Berakhir" = Rabu 23:59 → isi ini Kamis 23:59. Saat fase ini, badge "EXTENDED" muncul dan hitung mundur lanjut ke sini. Setelah lewat, banner hilang.', 'jwtrading' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Free Content Preview gate — ported from the live site.
 *
 * Server-side lock on the preview page + name/email popup. Unlock sets a
 * 30-day cookie, emails the admin, and fires `jw_kit_tag_subscriber`
 * (Preview_Optin mapping in the Kit integration).
 *
 * Contract kept IDENTICAL to the legacy version so existing unlocked
 * visitors stay unlocked and Kit mappings keep working:
 * cookie `jw_free_preview_unlocked`, AJAX action `jw_gate_unlock`,
 * form_id `free_preview_gate_keep`. Popup styles: child theme _gate.scss.
 */
class JWT_Preview_Gate {

	const PAGE_SLUG   = 'free-content-preview';
	const COOKIE      = 'jw_free_preview_unlocked';
	const COOKIE_DAYS = 30;

	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'lock_content' ), 9999 );
		add_action( 'wp_footer', array( __CLASS__, 'popup' ), 99 );
		add_action( 'wp_ajax_jw_gate_unlock', array( __CLASS__, 'unlock_handler' ) );
		add_action( 'wp_ajax_nopriv_jw_gate_unlock', array( __CLASS__, 'unlock_handler' ) );
	}

	protected static function page_slug(): string {
		return apply_filters( 'jwt/preview_gate_slug', self::PAGE_SLUG );
	}

	protected static function should_bypass(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		return current_user_can( 'manage_options' ) || current_user_can( 'edit_pages' );
	}

	protected static function is_unlocked(): bool {
		return isset( $_COOKIE[ self::COOKIE ] ) && '1' === $_COOKIE[ self::COOKIE ];
	}

	/** Server-side: no unlock, no content. */
	public static function lock_content( $content ) {
		if ( is_admin() ) {
			return $content;
		}
		if ( ! function_exists( 'is_page' ) || ! is_page( self::page_slug() ) ) {
			return $content;
		}
		if ( self::should_bypass() || self::is_unlocked() ) {
			return $content;
		}

		return '<div class="jw-gate-locked-msg">' . esc_html__( 'This content is locked.', 'jwtrading' ) . '</div>';
	}

	/** Popup overlay with the unlock form. */
	public static function popup() {
		if ( is_admin() ) {
			return;
		}
		if ( ! function_exists( 'is_page' ) || ! is_page( self::page_slug() ) ) {
			return;
		}
		if ( self::should_bypass() || self::is_unlocked() ) {
			return;
		}

		$back_url = home_url( '/' );
		$nonce    = wp_create_nonce( 'jw_gate_unlock' );
		?>
		<div class="jw-gate-overlay">
			<div class="jw-gate-modal">
				<p class="jw-gate-title"><?php esc_html_e( 'Akses Free Content Preview', 'jwtrading' ); ?></p>
				<p class="jw-gate-sub"><?php esc_html_e( 'Isi nama dan email untuk membuka halaman preview.', 'jwtrading' ); ?></p>

				<form id="jwGateForm" name="Free Preview" data-form-name="Free Preview">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

					<div class="jw-field"><input type="text" name="first_name" placeholder="<?php esc_attr_e( 'Nama Depan *', 'jwtrading' ); ?>" required></div>
					<div class="jw-field"><input type="text" name="last_name" placeholder="<?php esc_attr_e( 'Nama Belakang *', 'jwtrading' ); ?>" required></div>
					<div class="jw-field"><input type="email" name="email" placeholder="Email Address *" required></div>

					<div id="jwGateError" class="jw-error"></div>

					<div class="jw-gate-actions">
						<button type="submit" class="jwt-btn jwt-btn--primary" id="jwGateSubmit"><?php esc_html_e( 'Buka Preview', 'jwtrading' ); ?></button>
						<a href="<?php echo esc_url( $back_url ); ?>" class="jwt-btn jwt-btn--ghost"><?php esc_html_e( 'Kembali / Explore', 'jwtrading' ); ?></a>
					</div>

					<div class="jw-note">
						<?php
						/* translators: %d: number of days the unlock cookie lasts. */
						printf( esc_html__( 'Halaman ini akan terbuka selama %d hari di browser ini.', 'jwtrading' ), (int) self::COOKIE_DAYS );
						?>
					</div>
				</form>
			</div>
		</div>

		<script>
		(function(){
			document.body.classList.add('jw-gate-locked');

			function setCookie(name,value,days){
				var d=new Date();
				d.setTime(d.getTime()+(days*24*60*60*1000));
				document.cookie=name+"="+value+";expires="+d.toUTCString()+";path=/";
			}

			var form = document.getElementById('jwGateForm');
			var err  = document.getElementById('jwGateError');
			var btn  = document.getElementById('jwGateSubmit');

			form.addEventListener('submit', async function(e){
				e.preventDefault();

				err.style.display = 'none';
				btn.disabled = true;
				btn.textContent = 'Memproses...';

				var formData = new FormData(form);

				try{
					var res = await fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						body: new URLSearchParams({
							action: 'jw_gate_unlock',
							nonce: formData.get('nonce'),
							first_name: formData.get('first_name'),
							last_name: formData.get('last_name'),
							email: formData.get('email')
						})
					});

					var data = await res.json();

					if(!data.success){
						err.textContent = (data && data.data && data.data.message) ? data.data.message : 'Terjadi kesalahan. Coba lagi.';
						err.style.display = 'block';
						btn.disabled = false;
						btn.textContent = 'Buka Preview';
						return;
					}

					setCookie('<?php echo esc_js( self::COOKIE ); ?>','1',<?php echo (int) self::COOKIE_DAYS; ?>);
					btn.textContent = 'Berhasil ✓';
					setTimeout(function(){ window.location.reload(); }, 500);

				}catch(ex){
					err.textContent = 'Koneksi gagal. Coba lagi.';
					err.style.display = 'block';
					btn.disabled = false;
					btn.textContent = 'Buka Preview';
				}
			});
		})();
		</script>
		<?php
	}

	/** AJAX: validate, notify admin, hand off to Kit tagging. */
	public static function unlock_handler() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'jw_gate_unlock' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid session. Refresh page.' ), 403 );
		}

		$first = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		if ( empty( $first ) || empty( $last ) || empty( $email ) ) {
			wp_send_json_error( array( 'message' => 'Mohon isi semua field.' ), 400 );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Email tidak valid.' ), 400 );
		}

		$subject  = '[JW Gate] Free Preview Access';
		$message  = "New Free Preview Submission:\n\n";
		$message .= "Name: {$first} {$last}\n";
		$message .= "Email: {$email}\n";
		$message .= 'Time: ' . current_time( 'mysql' ) . "\n";

		wp_mail( get_option( 'admin_email' ), $subject, $message );

		// Kit mapping: Preview_Optin + Stage_Warm (handled by the Kit integration).
		do_action(
			'jw_kit_tag_subscriber',
			array(
				'email'      => $email,
				'form_id'    => 'free_preview_gate_keep',
				'first_name' => $first,
				'last_name'  => $last,
			)
		);

		wp_send_json_success();
	}
}

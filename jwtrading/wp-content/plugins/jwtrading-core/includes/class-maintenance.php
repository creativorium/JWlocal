<?php
defined( 'ABSPATH' ) || exit;

/**
 * Maintenance mode — toggled from the admin bar by a single trusted user
 * (login: it.cular). When ON, front-end visitors get a styled 503 "under
 * maintenance" page; wp-login and wp-admin stay open so the team can log in,
 * and the trusted user still sees the live site (to work on it).
 */
class JWT_Maintenance {

	const OPTION = 'jwt_maintenance_mode';

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_block' ), 0 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'toolbar_toggle' ), 100 );
		add_action( 'admin_post_jwt_toggle_maintenance', array( __CLASS__, 'handle_toggle' ) );
	}

	/** Only this user (by login) bypasses maintenance + sees the toggle. Filterable. */
	protected static function is_operator() {
		$user  = wp_get_current_user();
		$login = ( $user && $user->exists() ) ? $user->user_login : '';
		return (bool) apply_filters( 'jwt/maintenance_operator', 'it.cular' === $login, $login );
	}

	public static function is_on() {
		return (bool) get_option( self::OPTION, 0 );
	}

	/** Front-end gate. Admin + wp-login.php are unaffected (template_redirect doesn't fire there). */
	public static function maybe_block() {
		if ( ! self::is_on() || self::is_operator() ) {
			return;
		}
		nocache_headers();
		status_header( 503 );
		header( 'Retry-After: 3600' );
		header( 'Content-Type: text/html; charset=utf-8' );
		self::render_page();
		exit;
	}

	/** Admin-bar ON/OFF button — visible only to the operator, front-end and admin. */
	public static function toolbar_toggle( $bar ) {
		if ( ! self::is_operator() ) {
			return;
		}
		$on  = self::is_on();
		$bar->add_node(
			array(
				'id'    => 'jwt-maintenance',
				'title' => $on ? '🔧 Maintenance: ON' : '🌐 Maintenance: OFF',
				'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=jwt_toggle_maintenance' ), 'jwt_toggle_maintenance' ),
				'meta'  => array( 'title' => $on ? __( 'Matikan mode maintenance', 'jwtrading' ) : __( 'Nyalakan mode maintenance', 'jwtrading' ) ),
			)
		);
	}

	public static function handle_toggle() {
		if ( ! self::is_operator() || ! check_admin_referer( 'jwt_toggle_maintenance' ) ) {
			wp_die( esc_html__( 'Tidak diizinkan.', 'jwtrading' ) );
		}
		update_option( self::OPTION, self::is_on() ? 0 : 1 );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/** Self-contained styled page (inline CSS so it works even mid-deploy). */
	protected static function render_page() {
		$brand   = esc_html( get_bloginfo( 'name' ) );
		$heading = esc_html__( 'Sedang Dalam Perbaikan', 'jwtrading' );
		$line1   = esc_html__( 'Our website is currently under maintenance.', 'jwtrading' );
		$line2   = esc_html__( 'We will be back online in a few hours. Thank you for your patience.', 'jwtrading' );
		?><!doctype html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?php echo $brand; // phpcs:ignore WordPress.Security.EscapeOutput ?> — <?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput ?></title>
<style>
	:root{--bg:#08070e;--accent:#7c4dff;--text:#f5f4f4;--muted:#a9a4be}
	*{box-sizing:border-box}
	html,body{height:100%;margin:0}
	body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem;
		background:radial-gradient(120% 90% at 50% 0%,rgba(124,77,255,.18),transparent 60%),var(--bg);
		color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;text-align:center}
	.card{max-width:560px}
	.dot{display:inline-flex;align-items:center;gap:.55rem;padding:.5rem 1.1rem;border-radius:999px;
		border:1px solid rgba(124,77,255,.4);background:rgba(124,77,255,.1);color:#b9a6ff;
		font-size:.72rem;letter-spacing:.18em;text-transform:uppercase;font-weight:700}
	.dot::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--accent);
		animation:p 1.8s ease-out infinite}
	@keyframes p{0%{box-shadow:0 0 0 0 rgba(124,77,255,.5)}100%{box-shadow:0 0 0 12px rgba(124,77,255,0)}}
	.brand{margin:1.6rem 0 .4rem;font-size:1.1rem;font-weight:800;letter-spacing:-.01em}
	h1{margin:.4rem 0 1rem;font-size:clamp(1.8rem,5vw,2.6rem);line-height:1.12;letter-spacing:-.02em}
	p{margin:.4rem 0;color:var(--muted);line-height:1.65;font-size:1.02rem}
</style>
</head>
<body>
	<div class="card">
		<span class="dot"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<div class="brand"><?php echo $brand; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<h1><?php echo $line1; // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
		<p><?php echo $line2; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
	</div>
</body>
</html>
		<?php
	}
}

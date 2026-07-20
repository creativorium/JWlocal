<?php
/**
 * 404 — page not found. Branded dark/purple screen with a search box and the
 * most useful destinations, so a dead link never becomes a dead end.
 *
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$jwt_blog_url = get_permalink( (int) get_option( 'page_for_posts' ) );
?>
<main class="jwt-404">
	<div class="jwt-container jwt-404__inner">
		<span class="jwt-badge jwt-404__eyebrow"><span class="jwt-eyebrow__dot"></span><?php esc_html_e( 'Error 404', 'jwtrading' ); ?></span>

		<p class="jwt-404__code" aria-hidden="true">404</p>

		<h1 class="jwt-404__title"><?php esc_html_e( 'Halaman tidak ditemukan', 'jwtrading' ); ?></h1>

		<p class="jwt-404__lead"><?php esc_html_e( 'Halaman yang kamu cari mungkin sudah dipindahkan, berganti alamat, atau memang tidak pernah ada. Coba cari lagi, atau langsung menuju halaman di bawah ini.', 'jwtrading' ); ?></p>

		<form class="jwt-404__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg class="jwt-404__search-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
			<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Cari artikel…', 'jwtrading' ); ?>" aria-label="<?php esc_attr_e( 'Cari', 'jwtrading' ); ?>">
			<button type="submit" class="jwt-btn jwt-btn--primary"><?php esc_html_e( 'Cari', 'jwtrading' ); ?></button>
		</form>

		<nav class="jwt-404__links" aria-label="<?php esc_attr_e( 'Halaman populer', 'jwtrading' ); ?>">
			<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'jwtrading' ); ?></a>
			<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( home_url( '/bootcamp/' ) ); ?>"><?php esc_html_e( 'Bootcamp', 'jwtrading' ); ?></a>
			<?php if ( $jwt_blog_url ) : ?>
				<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( $jwt_blog_url ); ?>"><?php esc_html_e( 'Blog', 'jwtrading' ); ?></a>
			<?php endif; ?>
			<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( home_url( '/discord/' ) ); ?>"><?php esc_html_e( 'Discord', 'jwtrading' ); ?></a>
			<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'jwtrading' ); ?></a>
		</nav>
	</div>
</main>
<?php
get_footer();

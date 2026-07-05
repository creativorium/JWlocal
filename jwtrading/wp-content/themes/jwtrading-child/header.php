<?php
/**
 * Custom header — replaces Kadence's header builder AND the old Elementor
 * mainHeader template. Presentation only, token-styled (src/scss/_header.scss).
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#jwt-content"><?php esc_html_e( 'Lompat ke konten', 'jwtrading' ); ?></a>

<header class="jwt-header">
	<div class="jwt-container jwt-header__bar">
		<div class="jwt-brand"><?php echo jwt_brand_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?></div>

		<!-- Desktop pill nav — short menu, hidden entirely on mobile (own <nav> so it
		     never has to share a menu location with the mobile drawer below). -->
		<nav class="jwt-header__nav" id="jwt-primary-nav" aria-label="<?php esc_attr_e( 'Menu utama', 'jwtrading' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'jwt-primary',
					'container'      => false,
					'menu_class'     => 'jwt-nav',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</nav>

		<div class="jwt-header__actions">
			<?php
			$jwt_cta = jwt_header_cta();
			if ( ! empty( $jwt_cta['text'] ) ) :
				?>
				<a class="jwt-btn jwt-btn--light jwt-header__cta" href="<?php echo esc_url( $jwt_cta['url'] ); ?>">
					<span><?php echo esc_html( $jwt_cta['text'] ); ?></span>
					<?php if ( false !== stripos( $jwt_cta['url'] . $jwt_cta['text'], 'discord' ) ) : ?>
						<svg width="22" height="17" viewBox="0 0 22 17" fill="none" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 0C6.174 0 4.793.308 3.28 1.051a.43.43 0 0 0-.209.192C2.032 2.975 0 7.796 0 13.5c0 .13.05.255.14.348.952.981 1.817 1.703 2.745 2.213.933.512 1.905.797 3.055.935a.43.43 0 0 0 .489-.239l.51-.852c-.743-.26-1.502-.605-1.97-1.073a.75.75 0 1 1 1.061-1.06c.255.255.854.545 1.7.816.699.183 1.916.412 3.27.412s2.571-.229 3.27-.412c.846-.271 1.445-.562 1.7-.816a.75.75 0 0 1 1.06 1.06c-.467.468-1.226.813-1.97 1.073l.511.852a.43.43 0 0 0 .489.24c1.15-.139 2.122-.424 3.055-.936.928-.51 1.793-1.232 2.744-2.213a.5.5 0 0 0 .141-.348c0-5.704-2.032-10.525-3.071-12.257a.43.43 0 0 0-.209-.192C17.207.308 15.826 0 14 0a.43.43 0 0 0-.474.342L13.19 1.35A7.918 7.918 0 0 0 11 1a7.918 7.918 0 0 0-2.19.35L8.474.342A.43.43 0 0 0 8 0ZM9 9.25C9 10.216 8.328 11 7.5 11S6 10.216 6 9.25 6.672 7.5 7.5 7.5 9 8.284 9 9.25ZM14.5 11c.828 0 1.5-.784 1.5-1.75s-.672-1.75-1.5-1.75-1.5.784-1.5 1.75.672 1.75 1.5 1.75Z" fill="currentColor"/></svg>
					<?php endif; ?>
				</a>
			<?php endif; ?>

			<button class="jwt-nav-toggle" aria-expanded="false" aria-controls="jwt-mobile-nav" aria-label="<?php esc_attr_e( 'Buka menu', 'jwtrading' ); ?>">
				<span class="jwt-nav-toggle__bar"></span>
				<span class="jwt-nav-toggle__bar"></span>
				<span class="jwt-nav-toggle__bar"></span>
			</button>
		</div>
	</div>
</header>

<div class="jwt-nav-backdrop" data-jwt-nav-backdrop></div>

<!-- Mobile slide-in drawer — own menu location (jwt-mobile), independent of
     the desktop pill's shorter menu. Only ever visible <=880px (see CSS). -->
<nav class="jwt-mobile-nav" id="jwt-mobile-nav" aria-label="<?php esc_attr_e( 'Menu utama (mobile)', 'jwtrading' ); ?>">
	<?php
	wp_nav_menu(
		array(
			'theme_location' => 'jwt-mobile',
			'container'      => false,
			'menu_class'     => 'jwt-nav',
			'fallback_cb'    => false,
			'depth'          => 1,
		)
	);

	$jwt_social = jwt_social_links_html();
	if ( '' !== $jwt_social ) :
		?>
		<div class="jwt-nav-follow">
			<span class="jwt-nav-follow__label"><?php esc_html_e( 'Follow Us :', 'jwtrading' ); ?></span>
			<?php echo $jwt_social; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
		</div>
	<?php endif; ?>
</nav>

<div id="jwt-content" class="jwt-site-content">

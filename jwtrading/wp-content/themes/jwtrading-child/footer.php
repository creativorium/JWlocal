<?php
/**
 * Custom footer — rebuilt to match the live site's Elementor mainFooter:
 * rounded purple-gradient card, Quick Links / Contact / Lokasi columns,
 * Cular Creative credit, and the floating WhatsApp button.
 * Menus: jwt-footer (Quick Links), jwt-legal (fallback: static links), jwt-social.
 */

defined( 'ABSPATH' ) || exit;
?>
</div><!-- #jwt-content -->

<footer class="jwt-footer">
	<div class="jwt-footer__card">
		<div class="jwt-container">
			<div class="jwt-footer__grid">
				<div class="jwt-footer__brand">
					<?php echo jwt_brand_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
					<p class="jwt-footer__tagline"><?php esc_html_e( 'Fokus. Disiplin. Tenang', 'jwtrading' ); ?></p>
					<?php echo jwt_social_links_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
				</div>

				<div class="jwt-footer__cols">
					<?php if ( has_nav_menu( 'jwt-footer' ) ) : ?>
						<nav class="jwt-footer__col" aria-label="<?php esc_attr_e( 'Quick Links', 'jwtrading' ); ?>">
							<h2 class="jwt-footer__heading"><?php esc_html_e( 'Quick Links', 'jwtrading' ); ?></h2>
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'jwt-footer',
									'container'      => false,
									'menu_class'     => 'jwt-footer__menu',
									'fallback_cb'    => false,
									'depth'          => 1,
								)
							);
							?>
						</nav>
					<?php endif; ?>

					<div class="jwt-footer__col">
						<h2 class="jwt-footer__heading"><?php esc_html_e( 'Contact', 'jwtrading' ); ?></h2>
						<ul class="jwt-footer__menu">
							<li><a href="mailto:info@jwtradingacademy.com">info@jwtradingacademy.com</a></li>
						</ul>

						<h2 class="jwt-footer__heading"><?php esc_html_e( 'Lokasi', 'jwtrading' ); ?></h2>
						<ul class="jwt-footer__menu">
							<li><?php esc_html_e( 'Bali, Indonesia', 'jwtrading' ); ?></li>
						</ul>
					</div>
				</div>
			</div>

			<div class="jwt-footer__bottom">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All Rights Reserved.', 'jwtrading' ); ?></p>
				<?php
				if ( has_nav_menu( 'jwt-legal' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'jwt-legal',
							'container'      => false,
							'menu_class'     => 'jwt-footer__legal',
							'fallback_cb'    => false,
							'depth'          => 1,
						)
					);
				} else {
					?>
					<ul class="jwt-footer__legal">
						<li><a href="<?php echo esc_url( home_url( '/terms-condition/' ) ); ?>"><?php esc_html_e( 'Terms & Conditions', 'jwtrading' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'jwtrading' ); ?></a></li>
					</ul>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</footer>

<div class="jwt-wa-float">
	<div class="jwt-wa-float__tip"><?php esc_html_e( 'Butuh Bantuan? 👋', 'jwtrading' ); ?></div>
	<a class="jwt-wa-float__circle" href="<?php echo esc_url( apply_filters( 'jwt/whatsapp_float_url', 'https://wa.me/628113931505?text=Halo!%20Saya%20butuh%20bantuan%20tentang%20service%20JWtrading' ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chat WhatsApp', 'jwtrading' ); ?>">
		<svg width="30" height="30" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<path d="M16 2C8.268 2 2 8.268 2 16c0 2.47.664 4.782 1.822 6.775L2 30l7.443-1.789A13.93 13.93 0 0016 30c7.732 0 14-6.268 14-14S23.732 2 16 2z" fill="white"/>
			<path d="M23.473 19.89c-.31-.156-1.836-.906-2.12-1.01-.284-.102-.49-.154-.696.156-.207.31-.8 1.01-.98 1.217-.18.208-.36.232-.67.078-.31-.156-1.308-.482-2.49-1.537-.92-.822-1.54-1.836-1.72-2.146-.18-.31-.019-.477.135-.631.14-.138.31-.362.465-.542.155-.18.207-.31.31-.517.104-.206.052-.387-.026-.542-.077-.155-.696-1.677-.953-2.297-.251-.604-.506-.522-.696-.531l-.594-.01c-.207 0-.542.077-.826.387-.284.31-1.083 1.058-1.083 2.58 0 1.522 1.109 2.993 1.264 3.2.155.207 2.182 3.33 5.286 4.671.74.319 1.317.51 1.766.652.742.236 1.418.203 1.952.123.595-.089 1.836-.75 2.095-1.474.26-.724.26-1.344.182-1.474-.077-.13-.284-.207-.594-.362z" fill="#25D366"/>
		</svg>
	</a>
</div>

<?php wp_footer(); ?>
</body>
</html>

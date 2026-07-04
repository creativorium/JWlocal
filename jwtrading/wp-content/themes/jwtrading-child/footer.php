<?php
/**
 * Custom footer — replaces Kadence's footer builder AND the old Elementor
 * mainFooter template. Menus: jwt-footer (nav), jwt-legal, jwt-social.
 */

defined( 'ABSPATH' ) || exit;
?>
</div><!-- #jwt-content -->

<footer class="jwt-footer">
	<div class="jwt-container">
		<div class="jwt-footer__grid">
			<div class="jwt-footer__brand">
				<?php echo jwt_brand_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
				<p class="jwt-footer__tagline"><?php esc_html_e( 'Fokus. Disiplin. Tenang.', 'jwtrading' ); ?></p>
				<?php echo jwt_social_links_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
			</div>

			<?php if ( has_nav_menu( 'jwt-footer' ) ) : ?>
				<nav class="jwt-footer__col" aria-label="<?php esc_attr_e( 'Navigasi footer', 'jwtrading' ); ?>">
					<h2 class="jwt-footer__heading"><?php esc_html_e( 'Navigasi', 'jwtrading' ); ?></h2>
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
				<h2 class="jwt-footer__heading"><?php esc_html_e( 'Kontak', 'jwtrading' ); ?></h2>
				<ul class="jwt-footer__menu">
					<li><a href="mailto:info@jwtradingacademy.com">info@jwtradingacademy.com</a></li>
					<li><?php esc_html_e( 'Bali, Indonesia', 'jwtrading' ); ?></li>
				</ul>
			</div>
		</div>

		<div class="jwt-footer__bottom">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'jwtrading' ); ?></p>
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
			}
			?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

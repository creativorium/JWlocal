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

			$jwt_cta = jwt_header_cta();
			if ( ! empty( $jwt_cta['text'] ) ) :
				?>
				<a class="jwt-btn jwt-btn--primary jwt-header__cta" href="<?php echo esc_url( $jwt_cta['url'] ); ?>"><?php echo esc_html( $jwt_cta['text'] ); ?></a>
			<?php endif; ?>
		</nav>

		<button class="jwt-nav-toggle" aria-expanded="false" aria-controls="jwt-primary-nav" aria-label="<?php esc_attr_e( 'Buka menu', 'jwtrading' ); ?>">
			<span class="jwt-nav-toggle__bar"></span>
			<span class="jwt-nav-toggle__bar"></span>
			<span class="jwt-nav-toggle__bar"></span>
		</button>
	</div>
</header>

<div id="jwt-content" class="jwt-site-content">

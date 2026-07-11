<?php
/**
 * jwtrading-child — enqueues only, no business logic (that lives in jwtrading-core).
 */

defined( 'ABSPATH' ) || exit;

define( 'JWT_THEME_DIR', get_stylesheet_directory() );
define( 'JWT_THEME_URI', get_stylesheet_directory_uri() );

require_once JWT_THEME_DIR . '/inc/woo-tweaks.php';
require_once JWT_THEME_DIR . '/inc/blocks.php';
require_once JWT_THEME_DIR . '/inc/theme-setup.php';
require_once JWT_THEME_DIR . '/inc/kadence-palette.php';
require_once JWT_THEME_DIR . '/inc/editor-lock.php';

/**
 * Vite bridge.
 * Dev mode: `npm run dev` creates dist/hot → load from Vite dev server (HMR).
 * Prod mode: read dist/.vite/manifest.json → enqueue hashed files.
 * The `hot` file must NEVER be deployed to EasyWP.
 */
add_action( 'wp_enqueue_scripts', function () {
	$hot      = JWT_THEME_DIR . '/dist/hot';
	$manifest = JWT_THEME_DIR . '/dist/.vite/manifest.json';

	if ( file_exists( $hot ) ) {
		$dev_server = trim( (string) file_get_contents( $hot ) ); // e.g. http://localhost:5173
		wp_enqueue_script( 'jwt-vite-client', $dev_server . '/@vite/client', array(), null, true );
		wp_enqueue_script( 'jwt-main', $dev_server . '/src/main.js', array(), null, true );
		return;
	}

	if ( ! file_exists( $manifest ) ) {
		return;
	}

	$assets = json_decode( (string) file_get_contents( $manifest ), true );
	$entry  = $assets['src/main.js'] ?? null;

	if ( ! $entry ) {
		return;
	}

	wp_enqueue_script( 'jwt-main', JWT_THEME_URI . '/dist/' . $entry['file'], array(), null, true );

	foreach ( $entry['css'] ?? array() as $css ) {
		wp_enqueue_style( 'jwt-style-' . md5( $css ), JWT_THEME_URI . '/dist/' . $css, array(), null );
	}
} );

/**
 * Preload the two above-the-fold fonts (Space Grotesk = hero title / LCP,
 * Manrope = nav + body). They're otherwise only discovered late via the CSS
 * @font-face, which delays the first text paint on mobile. JetBrains Mono and
 * Montserrat are not above the fold, so they're intentionally NOT preloaded.
 * `crossorigin` is required even same-origin — fonts are fetched anonymously.
 */
add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	foreach ( array( 'space-grotesk-var.woff2', 'manrope-var.woff2' ) as $jwt_font ) {
		printf(
			'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin>' . "\n",
			esc_url( JWT_THEME_URI . '/dist/fonts/' . $jwt_font )
		);
	}
}, 1 );

/**
 * Load Vite entries as ES modules.
 * (Filter name is `script_loader_tag` — `script_tag` does not exist.)
 */
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( in_array( $handle, array( 'jwt-vite-client', 'jwt-main', 'jwt-blocks-editor' ), true ) ) {
		$tag = str_replace( '<script ', '<script type="module" ', $tag );
	}
	return $tag;
}, 10, 2 );

/**
 * Mark pages that already have a jwt/hero so we can hide Kadence's duplicate
 * page-title banner (entry-hero). Cross-environment — no hard-coded page IDs.
 */
add_filter( 'body_class', function ( $classes ) {
	$id = get_queried_object_id();
	if ( is_singular() && ( has_block( 'jwt/hero', $id ) || has_block( 'jwt/roadmap-hero', $id ) ) ) {
		$classes[] = 'jwt-has-hero';
	}
	// Focused opt-in landing pages (minimal header, no content-area top margin).
	if ( is_singular() && has_block( 'jwt/roadmap-hero', $id ) ) {
		$classes[] = 'jwt-landing';
	}
	return $classes;
} );

/**
 * Sleek "waterfall" scroll-down cue on key landing/marketing pages.
 * Rendered once at wp_footer (fixed position); hidden on mobile + after scroll
 * via main.js / _blocks.scss.
 */
add_action( 'wp_footer', function () {
	$show = is_front_page() || ( function_exists( 'is_page' ) && is_page( array( 'bootcamp', 'testimonials', 'trader-roadmap', 'ifvg-strategy' ) ) );
	if ( ! apply_filters( 'jwt/show_scrollcue', $show ) ) {
		return;
	}
	echo '<div class="jwt-scrollcue" data-jwt-scrollcue aria-hidden="true">'
		. '<span class="jwt-scrollcue__label">' . esc_html__( 'Scroll', 'jwtrading' ) . '</span>'
		. '<span class="jwt-scrollcue__track"><span class="jwt-scrollcue__drop"></span></span></div>';
}, 20 );

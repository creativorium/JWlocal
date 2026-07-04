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
 * Load Vite entries as ES modules.
 * (Filter name is `script_loader_tag` — `script_tag` does not exist.)
 */
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( in_array( $handle, array( 'jwt-vite-client', 'jwt-main', 'jwt-blocks-editor' ), true ) ) {
		$tag = str_replace( '<script ', '<script type="module" ', $tag );
	}
	return $tag;
}, 10, 2 );

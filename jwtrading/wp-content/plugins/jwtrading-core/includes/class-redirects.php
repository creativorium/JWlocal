<?php
defined( 'ABSPATH' ) || exit;

/**
 * SEO-safe redirects for the blog migration (Elementor pages → posts).
 *
 * The articles used to live at root level (/<slug>/) as pages; as posts they
 * live under /blog/<slug>/. Any 404 whose single path segment matches a
 * published post 301s to that post — covers all migrated articles and any
 * future page→post move without maintaining a manual map.
 */
class JWT_Redirects {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'page_to_post_301' ) );
	}

	public static function page_to_post_301() {
		if ( ! is_404() ) {
			return;
		}

		$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		// Root-level single segments only (e.g. /order-block/).
		if ( '' === $path || false !== strpos( $path, '/' ) ) {
			return;
		}

		$post = get_page_by_path( sanitize_title( $path ), OBJECT, 'post' );

		if ( $post && 'publish' === $post->post_status ) {
			wp_safe_redirect( get_permalink( $post ), 301 );
			exit;
		}
	}
}

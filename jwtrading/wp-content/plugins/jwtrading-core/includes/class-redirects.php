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
		// Priority 1: these pages still exist and would render normally, so this
		// has to run before anything else decides to show them.
		add_action( 'template_redirect', array( __CLASS__, 'retired_pages_301' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'page_to_post_301' ) );
	}

	/**
	 * Retired Thinkific member pages.
	 *
	 * /my-dashboard/ ([thinkific_dashboard]) and /my-login/
	 * ([thinkific_custom_login] — a shortcode nothing registers any more, so that
	 * page was already dead) belonged to the Thinkific course platform. Yapp hosts
	 * the courses now and signs members in itself, so both are 301'd rather than
	 * deleted: old bookmarks and any stale link in an email keep working, and the
	 * pages stay in the database if their content is ever needed.
	 *
	 * Filter `jwt/retired_page_redirects` to retarget (slug => absolute URL).
	 */
	public static function retired_pages_301() {
		if ( is_admin() ) {
			return;
		}

		$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( '' === $path || false !== strpos( $path, '/' ) ) {
			return;
		}

		$map = apply_filters(
			'jwt/retired_page_redirects',
			array(
				'my-dashboard' => home_url( '/' ),
				'my-login'     => home_url( '/' ),
			)
		);

		$slug = sanitize_title( $path );
		if ( empty( $map[ $slug ] ) ) {
			return;
		}

		// wp_redirect, not wp_safe_redirect: the filter is meant to be pointed at
		// Yapp (an external host), which the safe variant would refuse.
		wp_redirect( esc_url_raw( (string) $map[ $slug ] ), 301 ); // phpcs:ignore WordPress.Security.SafeRedirect
		exit;
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

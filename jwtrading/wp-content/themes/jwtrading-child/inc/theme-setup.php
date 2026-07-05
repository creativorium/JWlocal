<?php
defined( 'ABSPATH' ) || exit;

/**
 * Setup for the fully custom header/footer (replaces the old Elementor
 * mainHeader/mainFooter templates — see header.php / footer.php).
 */

add_action( 'after_setup_theme', function () {
	register_nav_menus(
		array(
			'jwt-primary' => __( 'Header — Menu Utama', 'jwtrading' ),
			'jwt-footer'  => __( 'Footer — Navigasi', 'jwtrading' ),
			'jwt-legal'   => __( 'Footer — Legal', 'jwtrading' ),
			'jwt-social'  => __( 'Footer — Social (isi custom links)', 'jwtrading' ),
		)
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 96,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
} );

/**
 * Header CTA button (filter `jwt/header_cta` to change).
 */
function jwt_header_cta(): array {
	return apply_filters(
		'jwt/header_cta',
		array(
			'text' => __( 'Discord', 'jwtrading' ),
			'url'  => home_url( '/discord/' ),
		)
	);
}

/**
 * Brand mark: custom logo when set, otherwise the site name as text.
 */
function jwt_brand_html(): string {
	if ( has_custom_logo() ) {
		return get_custom_logo();
	}

	return sprintf(
		'<a class="jwt-brand__name" href="%s" rel="home">%s</a>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Social links list built from the "jwt-social" menu (custom links).
 * The icon is picked from the link's host, so the client manages socials
 * from Appearance → Menus without touching code.
 */
function jwt_social_links_html(): string {
	$locations = get_nav_menu_locations();

	if ( empty( $locations['jwt-social'] ) ) {
		return '';
	}

	$items = wp_get_nav_menu_items( $locations['jwt-social'] );

	if ( ! $items ) {
		return '';
	}

	$icons = array(
		'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="0.5" fill="currentColor" stroke="none"/></svg>',
		'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.6 15.6V8.4L15.8 12l-6.2 3.6z"/></svg>',
		'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
		'discord'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.32 4.37a19.8 19.8 0 0 0-4.93-1.51 13.78 13.78 0 0 0-.64 1.28 18.27 18.27 0 0 0-5.5 0 12.64 12.64 0 0 0-.64-1.28h-.05A19.74 19.74 0 0 0 3.68 4.4 20.26 20.26 0 0 0 .1 18.06a19.9 19.9 0 0 0 6.07 3.03 14.44 14.44 0 0 0 1.23-1.99 12.6 12.6 0 0 1-1.87-.9c.16-.12.31-.24.46-.37a14.24 14.24 0 0 0 12.02 0c.15.13.3.25.46.37-.6.35-1.22.66-1.87.9a14.82 14.82 0 0 0 1.23 2 19.84 19.84 0 0 0 6.07-3.04 20.14 20.14 0 0 0-3.58-13.69zM8.02 15.33c-1.18 0-2.16-1.08-2.16-2.42s.95-2.43 2.16-2.43 2.18 1.1 2.16 2.43c0 1.34-.95 2.42-2.16 2.42zm7.97 0c-1.18 0-2.16-1.08-2.16-2.42s.95-2.43 2.16-2.43 2.18 1.1 2.16 2.43c0 1.34-.95 2.42-2.16 2.42z"/></svg>',
	);

	$fallback = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg>';

	$html = '<ul class="jwt-social">';

	foreach ( $items as $item ) {
		$url  = (string) $item->url;
		$icon = $fallback;

		foreach ( $icons as $key => $svg ) {
			if ( false !== stripos( $url, $key ) ) {
				$icon = $svg;
				break;
			}
		}

		$html .= sprintf(
			'<li><a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a></li>',
			esc_url( $url ),
			esc_attr( $item->title ),
			$icon
		);
	}

	return $html . '</ul>';
}

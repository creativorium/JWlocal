<?php
defined( 'ABSPATH' ) || exit;

/**
 * Custom blocks: registration, editor assets, shared render helpers.
 *
 * Every block in /blocks/<name>/ is DYNAMIC (block.json + render.php):
 * markup is rendered by PHP on every request, so design changes never
 * require re-saving content, and the HTML shipped to Google is always
 * current + semantic. The editor bundle (src/editor.jsx) only provides
 * the editing UI.
 */

/**
 * Block category "JW Trading" at the top of the inserter.
 */
add_filter( 'block_categories_all', function ( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'jwtrading',
			'title' => __( 'JW Trading', 'jwtrading' ),
		)
	);
	return $categories;
} );

/**
 * Register the shared editor script handle, then every block folder.
 * Dev (dist/hot present): script comes from the Vite dev server.
 * Prod: hashed file resolved from the Vite manifest.
 */
add_action( 'init', function () {
	$deps = array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' );
	$hot  = JWT_THEME_DIR . '/dist/hot';

	if ( file_exists( $hot ) ) {
		$dev_server = trim( (string) file_get_contents( $hot ) );
		wp_register_script( 'jwt-blocks-editor', $dev_server . '/src/editor.jsx', $deps, null, true );
	} else {
		$manifest_file = JWT_THEME_DIR . '/dist/.vite/manifest.json';

		if ( file_exists( $manifest_file ) ) {
			$manifest = json_decode( (string) file_get_contents( $manifest_file ), true );
			$entry    = $manifest['src/editor.jsx'] ?? null;

			if ( $entry ) {
				wp_register_script( 'jwt-blocks-editor', JWT_THEME_URI . '/dist/' . $entry['file'], $deps, null, true );
			}
		}
	}

	foreach ( glob( JWT_THEME_DIR . '/blocks/*/block.json' ) as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}

	// Pattern category for /patterns/*.php (block category is separate, above).
	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category( 'jwtrading', array( 'label' => __( 'JW Trading', 'jwtrading' ) ) );
	}
} );

/**
 * Editor canvas styles: load the built front-end CSS (+ editor extras) inside
 * the editor so the client sees the real design while editing.
 * Requires a production build — run `npm run build` at least once.
 */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'editor-styles' );

	$manifest_file = get_stylesheet_directory() . '/dist/.vite/manifest.json';
	if ( ! file_exists( $manifest_file ) ) {
		return;
	}

	$manifest = json_decode( (string) file_get_contents( $manifest_file ), true );

	foreach ( array( 'src/main.js', 'src/editor.jsx' ) as $entry_key ) {
		foreach ( $manifest[ $entry_key ]['css'] ?? array() as $css ) {
			add_editor_style( get_stylesheet_directory_uri() . '/dist/' . $css );
		}
	}
} );

/**
 * Shared section header (eyebrow / title / lead). All output escaped here.
 *
 * @param array $attributes Block attributes (eyebrow, title, lead, center).
 */
function jwt_section_header_html( array $attributes ): string {
	$eyebrow = trim( (string) ( $attributes['eyebrow'] ?? '' ) );
	$title   = trim( (string) ( $attributes['title'] ?? '' ) );
	$lead    = trim( (string) ( $attributes['lead'] ?? '' ) );

	if ( '' === $eyebrow && '' === $title && '' === $lead ) {
		return '';
	}

	$class = 'jwt-section-header' . ( ! empty( $attributes['center'] ) ? ' is-center' : '' );
	$html  = '<div class="' . esc_attr( $class ) . '" data-jwt-reveal>';

	if ( '' !== $eyebrow ) {
		$html .= '<span class="jwt-eyebrow">' . esc_html( $eyebrow ) . '</span>';
	}
	if ( '' !== $title ) {
		// Standalone single-section pages (contact, discord) pass titleTag=h1 so
		// the page still has exactly one top-level heading for SEO.
		$tag = in_array( $attributes['titleTag'] ?? '', array( 'h1', 'h2', 'h3' ), true ) ? $attributes['titleTag'] : 'h2';
		$html .= '<' . $tag . ' class="jwt-title">' . wp_kses_post( $title ) . '</' . $tag . '>';
	}
	if ( '' !== $lead ) {
		$html .= '<p class="jwt-lead">' . wp_kses_post( $lead ) . '</p>';
	}

	return $html . '</div>';
}

/**
 * Extract the 11-char YouTube video ID from any of the URL shapes the client
 * might paste (watch?v=, youtu.be/, /embed/, /shorts/) — or a bare ID.
 * Returns '' when nothing usable is found, which renders the empty video frame.
 *
 * @param string $url Raw URL or ID.
 */
function jwt_youtube_id( string $url ): string {
	$url = trim( $url );

	if ( '' === $url ) {
		return '';
	}

	if ( preg_match( '#^[A-Za-z0-9_-]{11}$#', $url ) ) {
		return $url;
	}

	if ( preg_match( '#(?:youtu\.be/|v=|/embed/|/shorts/|/live/)([A-Za-z0-9_-]{11})#', $url, $m ) ) {
		return $m[1];
	}

	return '';
}

/**
 * YouTube facade markup — poster now, iframe only after a click (the handler is
 * [data-jwt-ytembed] in main.js). Shared by jwt/video-embed, jwt/faq-video-item
 * and the prop-firm guide videos, all of which need the identical behaviour.
 *
 * With no usable video ID it renders the empty frame from the layout PDFs, so a
 * page can ship before the client's links arrive.
 *
 * @param array $args video, posterId, label, class, red (YouTube-red button),
 *                    placeholder (text shown in the empty frame).
 */
function jwt_ytembed_html( array $args ): string {
	$id       = jwt_youtube_id( (string) ( $args['video'] ?? '' ) );
	$poster   = (int) ( $args['posterId'] ?? 0 );
	$label    = trim( (string) ( $args['label'] ?? '' ) );
	$class    = trim( 'jwt-ytembed ' . (string) ( $args['class'] ?? '' ) );
	$alt      = '' !== $label ? $label : __( 'Putar video', 'jwtrading' );
	$is_empty = '' === $id;

	$html = '<figure class="' . esc_attr( $class . ( $is_empty ? ' is-empty' : '' ) ) . '"'
		. ( $is_empty ? '' : ' data-jwt-ytembed data-video="' . esc_attr( $id ) . '"' )
		. '><div class="jwt-ytembed__frame">';

	if ( $poster ) {
		$html .= wp_get_attachment_image(
			$poster,
			'large',
			false,
			array(
				'class'    => 'jwt-ytembed__poster',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => $alt,
			)
		);
	} elseif ( ! $is_empty ) {
		// YouTube's own still. maxres does not exist for every video, so the
		// smaller hq thumbnail is the safe default outside the big hero VSL.
		$size  = ! empty( $args['maxres'] ) ? 'maxresdefault' : 'hqdefault';
		$html .= sprintf(
			'<img class="jwt-ytembed__poster" src="%s" alt="%s" loading="lazy" decoding="async" width="1280" height="720">',
			esc_url( 'https://i.ytimg.com/vi/' . $id . '/' . $size . '.jpg' ),
			esc_attr( $alt )
		);
	}

	$html .= '<button type="button" class="jwt-ytembed__play' . ( ! empty( $args['red'] ) ? ' is-yt' : '' ) . '"'
		. ' data-jwt-ytembed-play aria-label="' . esc_attr( $alt ) . '"' . ( $is_empty ? ' disabled' : '' ) . '>'
		. '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></button>';

	$placeholder = trim( (string) ( $args['placeholder'] ?? '' ) );
	if ( $is_empty && '' !== $placeholder ) {
		$html .= '<span class="jwt-ytembed__placeholder">[ ' . esc_html( $placeholder ) . ' ]</span>';
	}

	return $html . '</div></figure>';
}

/**
 * Inline SVG icon set for feature cards (stroke = currentColor).
 *
 * @param string $name Icon key.
 */
function jwt_icon( string $name ): string {
	$svg_open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';

	$icons = array(
		'video'     => '<circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8" fill="currentColor" stroke="none"/>',
		'community' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
		'live'      => '<circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49"/><path d="M7.76 16.24a6 6 0 0 1 0-8.49"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M4.93 19.07a10 10 0 0 1 0-14.14"/>',
		'chart'     => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
		'target'    => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
		'docs'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
		'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
		'spark'     => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
	);

	$body = $icons[ $name ] ?? $icons['spark'];

	return $svg_open . $body . '</svg>';
}

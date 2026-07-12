<?php
/**
 * Blog: template helpers + editor/query tweaks for the redesigned blog
 * (archive grid + single article). Presentation-only — no business logic.
 *
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Classic Editor for posts only — the client writes articles in one TinyMCE box
 * (Visual for prose, Text for raw HTML/code). Pages + other types keep Gutenberg.
 */
add_filter(
	'use_block_editor_for_post_type',
	static function ( $use, $post_type ) {
		return 'post' === $post_type ? false : $use;
	},
	10,
	2
);

/** Show the Excerpt box (article subtitle) by default on the post editor. */
add_filter(
	'default_hidden_meta_boxes',
	static function ( $hidden, $screen ) {
		if ( isset( $screen->post_type ) && 'post' === $screen->post_type ) {
			$hidden = array_diff( (array) $hidden, array( 'postexcerpt' ) );
		}
		return $hidden;
	},
	10,
	2
);

/** 9 posts per page on the blog index and category/tag/author archives. */
add_action(
	'pre_get_posts',
	static function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) {
			$query->set( 'posts_per_page', 9 );
		}
	}
);

/**
 * Post's primary category term — prefers a real category over "Uncategorized".
 *
 * @return WP_Term|null
 */
function jwt_post_primary_category( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$terms = get_the_terms( $post, 'category' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}
	foreach ( $terms as $term ) {
		if ( 'uncategorized' !== $term->slug ) {
			return $term;
		}
	}
	return $terms[0];
}

/** Estimated read time in Indonesian, e.g. "5 menit baca". */
function jwt_read_time( $post = null ) {
	$post  = get_post( $post );
	$words = $post ? str_word_count( wp_strip_all_tags( (string) $post->post_content ) ) : 0;
	$mins  = max( 1, (int) round( $words / 200 ) );
	return sprintf(
		/* translators: %d: number of minutes. */
		_n( '%d menit baca', '%d menit baca', $mins, 'jwtrading' ),
		$mins
	);
}

/** Post date formatted with Indonesian month names, regardless of site locale. */
function jwt_blog_date( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$months = array(
		1  => 'Januari',
		2  => 'Februari',
		3  => 'Maret',
		4  => 'April',
		5  => 'Mei',
		6  => 'Juni',
		7  => 'Juli',
		8  => 'Agustus',
		9  => 'September',
		10 => 'Oktober',
		11 => 'November',
		12 => 'Desember',
	);
	$ts = (int) get_post_time( 'U', false, $post );
	return sprintf( '%d %s %d', (int) wp_date( 'j', $ts ), $months[ (int) wp_date( 'n', $ts ) ], (int) wp_date( 'Y', $ts ) );
}

/**
 * Slug list of a post's categories (for the JS instant filter's data attribute).
 *
 * @return string Space-separated category slugs.
 */
function jwt_post_category_slugs( $post = null ) {
	$post  = get_post( $post );
	$terms = $post ? get_the_terms( $post, 'category' ) : array();
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}
	return implode( ' ', wp_list_pluck( $terms, 'slug' ) );
}

<?php
/**
 * Search results — reuses the blog archive body (card grid + filter + search),
 * with a search-specific header and "nothing found" message.
 *
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$jwt_query = get_search_query();
$jwt_found = (int) $GLOBALS['wp_query']->found_posts;

get_template_part(
	'template-parts/blog-archive',
	null,
	array(
		'eyebrow' => __( 'Pencarian', 'jwtrading' ),
		/* translators: %s = search term. */
		'title'   => sprintf( __( 'Hasil untuk “%s”', 'jwtrading' ), $jwt_query ),
		'lead'    => $jwt_found
			/* translators: %d = number of results found. */
			? sprintf( _n( '%d artikel ditemukan.', '%d artikel ditemukan.', $jwt_found, 'jwtrading' ), $jwt_found )
			: '',
		'current' => '',
		/* translators: %s = search term. */
		'empty'   => sprintf( __( 'Tidak ada hasil untuk “%s”. Coba kata kunci lain, atau jelajahi artikel kami.', 'jwtrading' ), $jwt_query ),
	)
);

get_footer();

<?php
/**
 * Blog index (the "Posts page" set in Settings → Reading, #2957).
 *
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$jwt_posts_page = (int) get_option( 'page_for_posts' );

get_template_part(
	'template-parts/blog-archive',
	null,
	array(
		'title'   => __( 'JW Trading Academy Article', 'jwtrading' ),
		'lead'    => __( 'Insight, strategi, dan edukasi trading ICT untuk trader Indonesia.', 'jwtrading' ),
		'current' => '',
	)
);

get_footer();

<?php
/**
 * Blog archives — category / tag / date / author. Reuses the archive body.
 *
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$jwt_obj     = get_queried_object();
$jwt_title   = single_term_title( '', false );
$jwt_current = ( $jwt_obj instanceof WP_Term ) ? $jwt_obj->slug : '';
$jwt_lead    = ( $jwt_obj instanceof WP_Term && '' !== (string) $jwt_obj->description ) ? wp_strip_all_tags( $jwt_obj->description ) : '';

if ( '' === (string) $jwt_title ) {
	$jwt_title = wp_strip_all_tags( get_the_archive_title() );
}

get_template_part(
	'template-parts/blog-archive',
	null,
	array(
		'title'   => $jwt_title,
		'lead'    => $jwt_lead,
		'current' => $jwt_current,
	)
);

get_footer();

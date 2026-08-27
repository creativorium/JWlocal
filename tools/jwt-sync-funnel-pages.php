<?php
/**
 * ONE-SHOT: push the mentorship funnel patterns into their page post_content.
 *
 * WHY THIS EXISTS
 * Page copy lives in the DATABASE (wp_posts.post_content), not in the theme.
 * Deploying the pattern files therefore changes nothing on its own -- the
 * patterns are only the source we copy FROM. This script does the copying.
 *
 * HOW TO RUN (EasyWP has no SSH/WP-CLI, so: SFTP + browser)
 *   1. Upload this file to the WordPress ROOT on live (next to wp-load.php).
 *   2. Visit https://jwtradingacademy.com/jwt-sync-funnel-pages.php?t=jwt9021
 *   3. Read the output -- every line should say ok / EXACT.
 *   4. DELETE THE FILE. It rewrites page content; do not leave it on the server.
 *   5. Purge the EasyWP cache (Namecheap panel), or check pages with ?cb=123.
 *
 * Uses $wpdb->update directly. wp_update_post() runs the content through
 * unslashing and would mangle the block markup (em dashes, &amp;, quotes).
 */

require __DIR__ . '/wp-load.php';

if ( ( $_GET['t'] ?? '' ) !== 'jwt9021' ) {
	die( 'no' );
}

header( 'Content-Type: text/plain; charset=utf-8' );

global $wpdb;

// Pages are resolved by PATH, not by id -- live ids may differ from Local.
$map = array(
	'mentorship'              => 'mentorship-optin.php',
	'mentorship/application'  => 'mentorship-application.php',
	'mentorship/thank-you'    => 'mentorship-thankyou.php',
);

$dir     = get_stylesheet_directory() . '/patterns/';
$changed = 0;
$failed  = 0;

foreach ( $map as $path => $file ) {
	$page = get_page_by_path( $path );

	if ( ! $page ) {
		echo "MISSING PAGE: /{$path}/ -- create it first.\n";
		$failed++;
		continue;
	}

	$raw = @file_get_contents( $dir . $file );
	if ( false === $raw ) {
		echo "MISSING PATTERN: {$file} -- is the theme deployed?\n";
		$failed++;
		continue;
	}

	$parts   = explode( "?>\n", $raw, 2 );
	$content = isset( $parts[1] ) ? rtrim( $parts[1] ) : '';

	if ( '' === $content || false === strpos( $content, '<!-- wp:' ) ) {
		echo "EMPTY BODY: {$file} -- refusing to blank /{$path}/.\n";
		$failed++;
		continue;
	}

	$before = (string) $page->post_content;

	if ( $before === $content ) {
		printf( "#%-5d /%-24s already up to date (%d bytes)\n", $page->ID, $path . '/', strlen( $content ) );
		continue;
	}

	$wpdb->update(
		$wpdb->posts,
		array(
			'post_content'      => $content,
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 ),
		),
		array( 'ID' => $page->ID )
	);

	clean_post_cache( $page->ID );

	$after = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $page->ID ) );
	$exact = ( $after === $content );

	printf(
		"#%-5d /%-24s %s  %d -> %d bytes  roundtrip=%s\n",
		$page->ID,
		$path . '/',
		$exact ? 'ok' : 'WROTE BUT DIFFERS',
		strlen( $before ),
		strlen( (string) $after ),
		$exact ? 'EXACT' : '*** CHECK ***'
	);

	$exact ? $changed++ : $failed++;
}

echo "\n--- what the pages now contain ---\n";
foreach ( array_keys( $map ) as $path ) {
	$page = get_page_by_path( $path );
	if ( ! $page ) {
		continue;
	}
	preg_match_all( '#<!-- wp:(jwt/[a-z-]+)#', (string) $page->post_content, $m );
	echo str_pad( '/' . $path . '/', 26 );
	foreach ( array_count_values( $m[1] ) as $block => $n ) {
		echo $block . ' x' . $n . '  ';
	}
	echo "\n";
}

printf( "\n%d updated, %d failed.\n", $changed, $failed );
echo $failed
	? "Something needs attention -- see above.\n"
	: "Done. DELETE THIS FILE, then purge the EasyWP cache.\n";

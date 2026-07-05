<?php
/**
 * One-off migration: Elementor → Gutenberg blocks.
 * Run: wp eval-file convert-elementor-to-blocks.php
 *
 * 1) SEO article PAGES become POSTS with the same slug (URL unchanged);
 *    the old page is drafted with a "-old-elementor" slug. Yoast metas copied.
 * 2) Every remaining Elementor-built page is converted IN PLACE to flat
 *    blocks (heading/html/image/embed/list/button) and unflagged so WP
 *    renders post_content. `_elementor_data` is kept as a backup.
 */

defined( 'ABSPATH' ) || exit;

// Pages that are actually blog articles → become posts.
$jwt_article_ids = array( 2915, 2913, 2911, 2909, 2908, 2906 );

function jwt_el_walk( array $elements, array &$out ) {
	foreach ( $elements as $el ) {
		$wtype = $el['widgetType'] ?? null;
		$s     = $el['settings'] ?? array();

		if ( 'heading' === $wtype ) {
			$title = trim( wp_strip_all_tags( (string) ( $s['title'] ?? '' ) ) );
			if ( '' !== $title ) {
				$out[] = array( 'h', (string) ( $s['header_size'] ?? 'h2' ), $title );
			}
		} elseif ( 'text-editor' === $wtype ) {
			$html = trim( (string) ( $s['editor'] ?? '' ) );
			if ( '' !== $html ) {
				$out[] = array( 'html', $html );
			}
		} elseif ( 'image' === $wtype ) {
			$img = $s['image'] ?? array();
			if ( ! empty( $img['id'] ) ) {
				$out[] = array( 'img', (int) $img['id'] );
			} elseif ( ! empty( $img['url'] ) ) {
				$out[] = array( 'imgurl', (string) $img['url'] );
			}
		} elseif ( 'video' === $wtype ) {
			$url = (string) ( $s['youtube_url'] ?? ( $s['vimeo_url'] ?? '' ) );
			if ( '' !== $url ) {
				$out[] = array( 'embed', $url );
			}
		} elseif ( 'icon-list' === $wtype ) {
			$items = array();
			foreach ( (array) ( $s['icon_list'] ?? array() ) as $item ) {
				$txt = trim( wp_strip_all_tags( (string) ( $item['text'] ?? '' ) ) );
				if ( '' !== $txt ) {
					$items[] = $txt;
				}
			}
			if ( $items ) {
				$out[] = array( 'list', $items );
			}
		} elseif ( 'button' === $wtype ) {
			$text = trim( (string) ( $s['text'] ?? '' ) );
			if ( '' !== $text ) {
				$out[] = array( 'btn', $text, (string) ( $s['link']['url'] ?? '' ) );
			}
		} elseif ( 'divider' === $wtype ) {
			$out[] = array( 'hr' );
		}

		if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
			jwt_el_walk( $el['elements'], $out );
		}
	}
}

function jwt_el_to_blocks( int $post_id ): string {
	$data = get_post_meta( $post_id, '_elementor_data', true );
	$arr  = json_decode( (string) $data, true );

	if ( ! is_array( $arr ) || ! $arr ) {
		return '';
	}

	$out = array();
	jwt_el_walk( $arr, $out );

	$blocks = '';

	foreach ( $out as $node ) {
		switch ( $node[0] ) {
			case 'h':
				$lv = (int) preg_replace( '/\D/', '', $node[1] );
				if ( $lv < 1 || $lv > 6 ) {
					$lv = 2;
				}
				$attr    = 2 === $lv ? '' : ' {"level":' . $lv . '}';
				$blocks .= "<!-- wp:heading{$attr} -->\n<h{$lv} class=\"wp-block-heading\">" . esc_html( $node[2] ) . "</h{$lv}>\n<!-- /wp:heading -->\n\n";
				break;

			case 'html':
				$blocks .= "<!-- wp:html -->\n" . $node[1] . "\n<!-- /wp:html -->\n\n";
				break;

			case 'img':
				$img_html = wp_get_attachment_image( $node[1], 'large' );
				if ( $img_html ) {
					$blocks .= '<!-- wp:image {"id":' . $node[1] . ',"sizeSlug":"large"} -->' . "\n<figure class=\"wp-block-image size-large\">{$img_html}</figure>\n<!-- /wp:image -->\n\n";
				}
				break;

			case 'imgurl':
				$blocks .= "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"" . esc_url( $node[1] ) . "\" alt=\"\"/></figure>\n<!-- /wp:image -->\n\n";
				break;

			case 'embed':
				$url     = esc_url( $node[1] );
				$blocks .= '<!-- wp:embed {"url":"' . $url . '","type":"video","providerNameSlug":"youtube","responsive":true} -->' . "\n<figure class=\"wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube\"><div class=\"wp-block-embed__wrapper\">\n{$url}\n</div></figure>\n<!-- /wp:embed -->\n\n";
				break;

			case 'list':
				$lis = '';
				foreach ( $node[1] as $li ) {
					$lis .= '<li>' . esc_html( $li ) . '</li>';
				}
				$blocks .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">{$lis}</ul>\n<!-- /wp:list -->\n\n";
				break;

			case 'btn':
				$url     = esc_url( $node[2] ? $node[2] : '#' );
				$blocks .= "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"{$url}\">" . esc_html( $node[1] ) . "</a></div>\n<!-- /wp:button --></div>\n<!-- /wp:buttons -->\n\n";
				break;

			case 'hr':
				$blocks .= "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->\n\n";
				break;
		}
	}

	return trim( $blocks );
}

$jwt_yoast_keys = array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw' );

// --- 1) Articles: page → post, same slug --------------------------------------
foreach ( $jwt_article_ids as $pid ) {
	$page = get_post( $pid );
	if ( ! $page ) {
		echo "SKIP {$pid}: not found\n";
		continue;
	}

	$blocks = jwt_el_to_blocks( $pid );
	if ( '' === $blocks ) {
		$blocks = "<!-- wp:html -->\n" . $page->post_content . "\n<!-- /wp:html -->";
	}

	$slug = $page->post_name;

	wp_update_post(
		array(
			'ID'          => $pid,
			'post_name'   => $slug . '-old-elementor',
			'post_status' => 'draft',
		)
	);

	$new_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $page->post_title,
			'post_name'    => $slug,
			'post_content' => $blocks,
			'post_date'    => $page->post_date,
			'post_author'  => $page->post_author,
			'post_excerpt' => $page->post_excerpt,
		),
		true
	);

	if ( is_wp_error( $new_id ) ) {
		echo "ERROR {$pid}: " . $new_id->get_error_message() . "\n";
		continue;
	}

	foreach ( $jwt_yoast_keys as $mk ) {
		$v = get_post_meta( $pid, $mk, true );
		if ( $v ) {
			update_post_meta( $new_id, $mk, $v );
		}
	}

	echo "POST {$new_id} <= page {$pid} ({$slug})\n";
}

// --- 2) Remaining Elementor pages: convert in place ------------------------------
$jwt_builder_pages = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_elementor_edit_mode',
		'meta_value'     => 'builder',
	)
);

foreach ( $jwt_builder_pages as $pid ) {
	if ( in_array( $pid, $jwt_article_ids, true ) ) {
		continue;
	}

	$blocks = jwt_el_to_blocks( (int) $pid );

	if ( '' !== $blocks ) {
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => $blocks,
			)
		);
	}

	delete_post_meta( $pid, '_elementor_edit_mode' );
	echo "PAGE {$pid} converted in place" . ( '' === $blocks ? ' (kept existing content)' : '' ) . "\n";
}

echo "DONE\n";

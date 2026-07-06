<?php
defined( 'ABSPATH' ) || exit;

/**
 * Locks the page-builder-style pages (post_type=page) to their built
 * section structure. Editors can still change everything CONTENT-shaped —
 * text and links inline in the canvas, images/colors/toggles/URLs via each
 * block's Inspector panel — and can still add/remove/reorder REPEATABLE
 * items within a section (testimonials, FAQ entries, feature cards, stats,
 * curriculum modules, CTA cards — those InnerBlocks areas are deliberately
 * left unlocked in editor.jsx). What's fixed is the top-level list of
 * sections itself: which ones, how many, in what order.
 *
 * Only takes effect once a page already HAS content — a brand-new blank
 * page stays unlocked so it can be built out for the first time (insert a
 * pattern, add blocks); the moment it has content saved, it locks on the
 * next load. For a one-off page that must stay fully open regardless,
 * hook `jwt/lock_page_editor` and return false for that $post.
 */
add_filter( 'block_editor_settings_all', function ( $settings, $context ) {
	$post = $context->post ?? null;

	if ( ! $post || 'page' !== $post->post_type ) {
		return $settings;
	}

	if ( '' === trim( (string) $post->post_content ) ) {
		return $settings;
	}

	if ( ! apply_filters( 'jwt/lock_page_editor', true, $post ) ) {
		return $settings;
	}

	$settings['templateLock'] = 'all';

	return $settings;
}, 10, 2 );

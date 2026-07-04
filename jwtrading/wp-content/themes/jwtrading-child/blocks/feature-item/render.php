<?php
/**
 * Render: jwt/feature-item
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="jwt-card jwt-feature" data-jwt-reveal>
	<span class="jwt-feature__icon" aria-hidden="true"><?php echo jwt_icon( $attributes['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG map. ?></span>
	<h3 class="jwt-feature__title"><?php echo esc_html( $attributes['title'] ); ?></h3>
	<p class="jwt-feature__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
</article>

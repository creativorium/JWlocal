<?php
/**
 * Render: jwt/showcase-item — one clickable tab-card.
 * Carries the media (or a placeholder label) in data-* attributes; the parent's
 * JS swaps the stage to this card's media when it becomes active.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_media_id  = (int) ( $attributes['mediaId'] ?? 0 );
$jwt_media_url = $jwt_media_id ? wp_get_attachment_url( $jwt_media_id ) : '';
?>
<div
	class="jwt-showcase-card"
	data-jwt-showcase-card
	data-placeholder="<?php echo esc_attr( (string) ( $attributes['placeholder'] ?? '' ) ); ?>"
	data-media="<?php echo esc_url( (string) $jwt_media_url ); ?>"
	role="button"
	tabindex="0"
>
	<?php if ( '' !== trim( (string) $attributes['title'] ) ) : ?>
		<h3 class="jwt-showcase-card__title"><?php echo esc_html( $attributes['title'] ); ?></h3>
	<?php endif; ?>
	<?php if ( '' !== trim( (string) $attributes['text'] ) ) : ?>
		<p class="jwt-showcase-card__text"><?php echo esc_html( $attributes['text'] ); ?></p>
	<?php endif; ?>
</div>

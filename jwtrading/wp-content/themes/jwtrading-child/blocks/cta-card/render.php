<?php
/**
 * Render: jwt/cta-card — one card inside jwt/duo-cta.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_accent = ! empty( $attributes['accent'] );
?>
<div class="jwt-cta-card<?php echo $jwt_accent ? ' is-accent' : ''; ?>" data-jwt-reveal>
	<?php if ( '' !== trim( $attributes['eyebrow'] ) ) : ?>
		<span class="jwt-eyebrow<?php echo $jwt_accent ? '' : ' is-muted'; ?>"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
	<?php endif; ?>
	<?php if ( '' !== trim( $attributes['title'] ) ) : ?>
		<h3 class="jwt-cta-card__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h3>
	<?php endif; ?>
	<?php if ( '' !== trim( $attributes['text'] ) ) : ?>
		<p class="jwt-cta-card__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== trim( $attributes['buttonText'] ) ) : ?>
		<a class="jwt-btn <?php echo $jwt_accent ? 'jwt-btn--primary' : 'jwt-btn--ghost'; ?>" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
	<?php endif; ?>
</div>

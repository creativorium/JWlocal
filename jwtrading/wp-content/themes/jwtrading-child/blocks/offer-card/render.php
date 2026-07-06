<?php
/**
 * Render: jwt/offer-card — 2-column offer card.
 * Left: eyebrow (+ optional "Gratis" badge), title, description.
 * Right: feature list + CTA button. Variant `accent` (purple gradient +
 * glow, primary button, purple bullets) or `dark` (surface, ghost button,
 * green bullets) — colors per the approved design spec.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_variant   = ( 'dark' === ( $attributes['variant'] ?? '' ) ) ? 'dark' : 'accent';
$jwt_is_accent = 'accent' === $jwt_variant;
$jwt_btn_class = $jwt_is_accent ? 'jwt-btn--primary' : 'jwt-btn--ghost';
?>
<div class="jwt-offer-card is-<?php echo esc_attr( $jwt_variant ); ?>">
	<?php if ( $jwt_is_accent ) : ?>
		<span class="jwt-offer-card__glow" aria-hidden="true"></span>
	<?php endif; ?>

	<div class="jwt-offer-card__main">
		<?php if ( '' !== trim( (string) ( $attributes['eyebrow'] ?? '' ) ) || '' !== trim( (string) ( $attributes['badge'] ?? '' ) ) ) : ?>
			<div class="jwt-offer-card__head">
				<?php if ( '' !== trim( (string) ( $attributes['eyebrow'] ?? '' ) ) ) : ?>
					<span class="jwt-offer-card__eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['badge'] ?? '' ) ) ) : ?>
					<span class="jwt-badge-gratis"><?php echo esc_html( $attributes['badge'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ) : ?>
			<h3 class="jwt-offer-card__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h3>
		<?php endif; ?>

		<?php if ( '' !== trim( (string) ( $attributes['text'] ?? '' ) ) ) : ?>
			<p class="jwt-offer-card__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
		<?php endif; ?>
	</div>

	<div class="jwt-offer-card__aside">
		<?php if ( '' !== trim( wp_strip_all_tags( (string) ( $attributes['features'] ?? '' ) ) ) ) : ?>
			<ul class="jwt-offer-card__features"><?php echo wp_kses_post( $attributes['features'] ); ?></ul>
		<?php endif; ?>

		<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
			<a class="jwt-btn <?php echo esc_attr( $jwt_btn_class ); ?> jwt-offer-card__btn" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
		<?php endif; ?>
	</div>
</div>

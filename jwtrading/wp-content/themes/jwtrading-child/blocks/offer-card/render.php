<?php
/**
 * Render: jwt/offer-card — offer/pricing card.
 *
 * Default (homepage): 2-column card — left eyebrow/title/text, right features +
 * CTA. Variant `accent` (purple gradient + glow) or `dark` (surface).
 *
 * `vertical` mode (bootcamp pricing): single centered column that also shows an
 * optional star rating, price (+ struck old price), a footnote, and a disabled
 * "coming soon" button. All the extra fields default empty, so non-vertical
 * cards render exactly as before.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_variant   = ( 'dark' === ( $attributes['variant'] ?? '' ) ) ? 'dark' : 'accent';
$jwt_is_accent = 'accent' === $jwt_variant;
$jwt_vertical  = ! empty( $attributes['vertical'] );
$jwt_btn_class = $jwt_is_accent ? 'jwt-btn--primary' : 'jwt-btn--ghost';
$jwt_card_cls  = 'jwt-offer-card is-' . $jwt_variant . ( $jwt_vertical ? ' is-vertical' : '' );

$jwt_rating   = ! empty( $attributes['rating'] );
$jwt_price    = trim( (string) ( $attributes['price'] ?? '' ) );
$jwt_price_old = trim( (string) ( $attributes['priceOld'] ?? '' ) );
$jwt_footnote = trim( (string) ( $attributes['footnote'] ?? '' ) );
$jwt_btn_off  = ! empty( $attributes['buttonDisabled'] );
$jwt_btn_text = trim( (string) ( $attributes['buttonText'] ?? '' ) );
?>
<div class="<?php echo esc_attr( $jwt_card_cls ); ?>">
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

		<?php if ( $jwt_rating ) : ?>
			<div class="jwt-offer-card__rating">
				<span class="jwt-offer-card__stars" aria-hidden="true">
					<?php for ( $jwt_s = 0; $jwt_s < 5; $jwt_s++ ) : ?>
						<svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.47 5.53 6.03.58-4.55 4.03 1.33 5.9L10 14.62l-5.28 2.92 1.33-5.9L1.5 7.61l6.03-.58L10 1.5z"/></svg>
					<?php endfor; ?>
				</span>
				<?php if ( '' !== trim( (string) ( $attributes['ratingText'] ?? '' ) ) ) : ?>
					<span class="jwt-offer-card__rating-text"><?php echo esc_html( $attributes['ratingText'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $jwt_price ) : ?>
			<div class="jwt-offer-card__price">
				<span class="jwt-offer-card__price-now"><?php echo esc_html( $jwt_price ); ?></span>
				<?php if ( '' !== $jwt_price_old ) : ?>
					<span class="jwt-offer-card__price-old"><?php echo esc_html( $jwt_price_old ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== trim( (string) ( $attributes['text'] ?? '' ) ) ) : ?>
			<p class="jwt-offer-card__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
		<?php endif; ?>
	</div>

	<div class="jwt-offer-card__aside">
		<?php if ( '' !== trim( wp_strip_all_tags( (string) ( $attributes['features'] ?? '' ) ) ) ) : ?>
			<ul class="jwt-offer-card__features"><?php echo wp_kses_post( $attributes['features'] ); ?></ul>
		<?php endif; ?>

		<?php if ( $jwt_btn_off ) : ?>
			<span class="jwt-btn jwt-btn--ghost is-disabled jwt-offer-card__btn" aria-disabled="true"><?php echo esc_html( $jwt_btn_text ?: __( 'Segera Hadir', 'jwtrading' ) ); ?></span>
		<?php elseif ( '' !== $jwt_btn_text ) : ?>
			<a class="jwt-btn <?php echo esc_attr( $jwt_btn_class ); ?> jwt-offer-card__btn" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $jwt_btn_text ); ?></a>
		<?php endif; ?>

		<?php if ( '' !== $jwt_footnote ) : ?>
			<p class="jwt-offer-card__footnote"><?php echo esc_html( $jwt_footnote ); ?></p>
		<?php endif; ?>
	</div>
</div>

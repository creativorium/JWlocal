<?php
/**
 * Render: jwt/pricing-card — wide horizontal pricing card.
 * Left: eyebrow, title, feature checklist. Right: star rating, price (+ struck
 * old price), subtext, CTA, payment note.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_price     = trim( (string) ( $attributes['price'] ?? '' ) );
$jwt_price_old = trim( (string) ( $attributes['priceOld'] ?? '' ) );
$jwt_footnote  = trim( (string) ( $attributes['footnote'] ?? '' ) );
$jwt_wrap      = get_block_wrapper_attributes( array( 'class' => 'jwt-pricing' ) );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-pricing__card" data-jwt-reveal>
			<span class="jwt-pricing__glow" aria-hidden="true"></span>

			<div class="jwt-pricing__left">
				<?php if ( '' !== trim( (string) ( $attributes['eyebrow'] ?? '' ) ) ) : ?>
					<span class="jwt-eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ) : ?>
					<h2 class="jwt-pricing__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( wp_strip_all_tags( (string) ( $attributes['features'] ?? '' ) ) ) ) : ?>
					<ul class="jwt-pricing__features"><?php echo wp_kses_post( $attributes['features'] ); ?></ul>
				<?php endif; ?>
			</div>

			<div class="jwt-pricing__right">
				<?php if ( ! empty( $attributes['rating'] ) ) : ?>
					<div class="jwt-pricing__rating">
						<span class="jwt-pricing__stars" aria-hidden="true">
							<?php for ( $jwt_s = 0; $jwt_s < 5; $jwt_s++ ) : ?>
								<svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.47 5.53 6.03.58-4.55 4.03 1.33 5.9L10 14.62l-5.28 2.92 1.33-5.9L1.5 7.61l6.03-.58L10 1.5z"/></svg>
							<?php endfor; ?>
						</span>
						<?php if ( '' !== trim( (string) ( $attributes['ratingText'] ?? '' ) ) ) : ?>
							<span class="jwt-pricing__rating-text"><?php echo esc_html( $attributes['ratingText'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( '' !== $jwt_price ) : ?>
					<div class="jwt-pricing__price">
						<span class="jwt-pricing__price-now"><?php echo esc_html( $jwt_price ); ?></span>
						<?php if ( '' !== $jwt_price_old ) : ?>
							<span class="jwt-pricing__price-old"><?php echo esc_html( $jwt_price_old ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( '' !== trim( (string) ( $attributes['text'] ?? '' ) ) ) : ?>
					<p class="jwt-pricing__text"><?php echo esc_html( $attributes['text'] ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
					<a class="jwt-btn jwt-btn--primary jwt-pricing__btn" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
				<?php endif; ?>

				<?php if ( '' !== $jwt_footnote ) : ?>
					<p class="jwt-pricing__footnote"><?php echo esc_html( $jwt_footnote ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

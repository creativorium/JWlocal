<?php
/**
 * Render: jwt/features (wrapper — items come from inner blocks).
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-features' . ( ! empty( $attributes['centerCards'] ) ? ' jwt-features--center' : '' ) ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php if ( ! empty( $attributes['showTrustBadge'] ) ) : ?>
			<div class="jwt-features__trust">
				<a class="jwt-hero__rating" href="https://www.trustpilot.com/review/jwtradingacademy.com" target="_blank" rel="noopener noreferrer">
					<strong>Excellent</strong>
					<span>5/5</span>
					<svg class="jwt-hero__rating-star" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.47 5.53 6.03.58-4.55 4.03 1.33 5.9L10 14.62l-5.28 2.92 1.33-5.9L1.5 7.61l6.03-.58L10 1.5z"/></svg>
					<span class="jwt-hero__rating-sep">|</span>
					<strong>Trustpilot</strong>
				</a>
			</div>
		<?php endif; ?>
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
		<div class="jwt-features__grid">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
		</div>
		<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
			<div class="jwt-features__cta">
				<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</section>

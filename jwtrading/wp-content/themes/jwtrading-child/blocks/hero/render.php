<?php
/**
 * Render: jwt/hero
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_tag     = in_array( $attributes['titleTag'], array( 'h1', 'h2', 'p' ), true ) ? $attributes['titleTag'] : 'h1';
$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-hero' . ( ! empty( $attributes['compact'] ) ? ' jwt-hero--compact' : '' ) ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container" data-jwt-reveal>
		<?php if ( ! empty( $attributes['showTrustBadge'] ) ) : ?>
			<!-- Fixed content by design — not meant to be per-page editable text. -->
			<a class="jwt-hero__rating" href="https://www.trustpilot.com/review/jwtradingacademy.com" target="_blank" rel="noopener noreferrer">
				<strong>Excellent</strong>
				<span>5/5</span>
				<svg class="jwt-hero__rating-star" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.47 5.53 6.03.58-4.55 4.03 1.33 5.9L10 14.62l-5.28 2.92 1.33-5.9L1.5 7.61l6.03-.58L10 1.5z"/></svg>
				<span class="jwt-hero__rating-sep">|</span>
				<strong>Trustpilot</strong>
			</a>
		<?php elseif ( '' !== trim( $attributes['eyebrow'] ) ) : ?>
			<span class="jwt-badge jwt-hero__badge"><span class="jwt-eyebrow__dot"></span><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
		<?php endif; ?>

		<?php if ( '' !== trim( $attributes['title'] ) ) : ?>
			<<?php echo $jwt_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="jwt-hero__title"><?php echo wp_kses_post( $attributes['title'] ); ?></<?php echo $jwt_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php endif; ?>

		<?php if ( '' !== trim( $attributes['lead'] ) ) : ?>
			<p class="jwt-hero__lead"><?php echo wp_kses_post( $attributes['lead'] ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== trim( $attributes['primaryText'] ) || '' !== trim( $attributes['secondaryText'] ) ) : ?>
			<div class="jwt-hero__actions">
				<?php if ( '' !== trim( $attributes['primaryText'] ) ) : ?>
					<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $attributes['primaryUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['primaryText'] ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['secondaryText'] ) ) : ?>
					<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( $attributes['secondaryUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['secondaryText'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== trim( $attributes['note'] ) ) : ?>
			<p class="jwt-hero__note"><?php echo esc_html( $attributes['note'] ); ?></p>
		<?php endif; ?>

		<?php
		if ( '' !== trim( $attributes['chips'] ) ) :
			$jwt_chips = array_filter( array_map( 'trim', preg_split( '/[•|]/u', $attributes['chips'] ) ) );
			?>
			<div class="jwt-hero__chips">
				<?php foreach ( $jwt_chips as $jwt_chip ) : ?>
					<span class="jwt-pill"><?php echo esc_html( $jwt_chip ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
/**
 * Render: jwt/hero
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_tag = in_array( $attributes['titleTag'], array( 'h1', 'h2', 'p' ), true ) ? $attributes['titleTag'] : 'h1';

// Optional lead-magnet auto-download (thank-you pages): when a downloadUrl is
// set, the file is fetched automatically on load (main.js) and the primary CTA
// falls back to it so the manual button and auto-download share one source.
$jwt_download = trim( (string) ( $attributes['downloadUrl'] ?? '' ) );
$jwt_wrap_attrs = array( 'class' => 'jwt-hero' . ( ! empty( $attributes['compact'] ) ? ' jwt-hero--compact' : '' ) );
if ( '' !== $jwt_download ) {
	$jwt_wrap_attrs['data-jwt-autodownload'] = esc_url( $jwt_download );
}
$jwt_wrapper = get_block_wrapper_attributes( $jwt_wrap_attrs );
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
				<?php
				if ( '' !== trim( $attributes['primaryText'] ) ) :
					$jwt_primary_url = $attributes['primaryUrl'] ?: ( $jwt_download ?: '#' );
					$jwt_is_dl       = ( '' !== $jwt_download && $jwt_primary_url === $jwt_download );
					?>
					<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $jwt_primary_url ); ?>"<?php echo $jwt_is_dl ? ' download' : ''; ?>><?php echo esc_html( $attributes['primaryText'] ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['secondaryText'] ) ) : ?>
					<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( $attributes['secondaryUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['secondaryText'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		$jwt_note_wa = preg_replace( '/\D+/', '', (string) ( $attributes['noteWa'] ?? '' ) );
		if ( '' !== trim( $attributes['note'] ) || '' !== $jwt_note_wa ) :
			?>
			<p class="jwt-hero__note">
				<?php echo esc_html( $attributes['note'] ); ?>
				<?php
				if ( '' !== $jwt_note_wa && function_exists( 'jwt_cloak_wa' ) ) {
					echo ' ' . jwt_cloak_wa( $jwt_note_wa, esc_html__( 'Chat WhatsApp', 'jwtrading' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper.
				}
				?>
			</p>
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

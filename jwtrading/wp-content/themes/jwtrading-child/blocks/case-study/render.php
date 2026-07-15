<?php
/**
 * Render: jwt/case-study — featured member story card.
 * Content (eyebrow/heading/body/quote/CTA) on one side, image(s) on the other.
 * `imageSide` = left|right. With multiple images (imageIds, pipe-separated) the
 * media becomes a carousel (arrows + dots); a single image renders as before.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_side = ( 'left' === ( $attributes['imageSide'] ?? '' ) ) ? 'left' : 'right';

// New multi-image field takes priority; fall back to the legacy single imageId.
$jwt_ids = array_values( array_filter( array_map( 'intval', explode( '|', (string) ( $attributes['imageIds'] ?? '' ) ) ) ) );
if ( empty( $jwt_ids ) && (int) ( $attributes['imageId'] ?? 0 ) > 0 ) {
	$jwt_ids = array( (int) $attributes['imageId'] );
}

// One zoomable image (opens the lightbox). Output is fully escaped.
$jwt_render_img = static function ( $id ) {
	return sprintf(
		'<a class="jwt-zoom" href="%s" target="_blank" rel="noopener">%s</a>',
		esc_url( (string) wp_get_attachment_image_url( $id, 'full' ) ),
		wp_get_attachment_image(
			$id,
			'large',
			false,
			array(
				'class'    => 'jwt-casestudy__img',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => '',
			)
		)
	);
};

$jwt_multi = count( $jwt_ids ) > 1;
$jwt_wrap  = get_block_wrapper_attributes( array( 'class' => 'jwt-casestudy' ) );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-casestudy__card is-image-<?php echo esc_attr( $jwt_side ); ?>" data-jwt-reveal>
			<div class="jwt-casestudy__content">
				<?php if ( '' !== trim( (string) ( $attributes['eyebrow'] ?? '' ) ) ) : ?>
					<span class="jwt-eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ) : ?>
					<h2 class="jwt-casestudy__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['body'] ?? '' ) ) ) : ?>
					<div class="jwt-casestudy__body"><?php echo wp_kses_post( wpautop( $attributes['body'] ) ); ?></div>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['quote'] ?? '' ) ) ) : ?>
					<p class="jwt-casestudy__quote"><?php echo wp_kses_post( $attributes['quote'] ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
					<a class="jwt-btn jwt-btn--primary jwt-casestudy__btn" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
				<?php endif; ?>
			</div>

			<div class="jwt-casestudy__media<?php echo $jwt_multi ? ' jwt-carousel' : ''; ?>"<?php echo $jwt_multi ? ' data-jwt-carousel' : ''; ?>>
				<?php if ( $jwt_multi ) : ?>
					<div class="jwt-carousel__track" data-jwt-carousel-track>
						<?php foreach ( $jwt_ids as $jwt_id ) : ?>
							<div class="jwt-carousel__slide"><?php echo $jwt_render_img( $jwt_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						<?php endforeach; ?>
					</div>
					<button type="button" class="jwt-carousel__arrow jwt-carousel__arrow--prev" data-jwt-carousel-prev aria-label="<?php esc_attr_e( 'Sebelumnya', 'jwtrading' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6" /></svg>
					</button>
					<button type="button" class="jwt-carousel__arrow jwt-carousel__arrow--next" data-jwt-carousel-next aria-label="<?php esc_attr_e( 'Berikutnya', 'jwtrading' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6" /></svg>
					</button>
					<div class="jwt-carousel__dots" data-jwt-carousel-dots></div>
				<?php elseif ( ! empty( $jwt_ids ) ) : ?>
					<?php echo $jwt_render_img( $jwt_ids[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<div class="jwt-casestudy__placeholder"><span><?php esc_html_e( 'Screenshot', 'jwtrading' ); ?></span></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

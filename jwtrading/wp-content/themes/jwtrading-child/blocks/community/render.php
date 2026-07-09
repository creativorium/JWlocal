<?php
/**
 * Render: jwt/community — Discord/community card.
 * Left: image carousel (imageIds = pipe-separated attachment IDs), auto-advancing
 * with arrows + dots (see [data-jwt-community] in main.js). Right: heading, text,
 * CTA. With one image it's just a static picture; with none, nothing renders.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_ids = array_values( array_filter( array_map(
	'intval',
	explode( '|', (string) ( $attributes['imageIds'] ?? '' ) )
) ) );

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-community' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-community__card" data-jwt-reveal>
			<div class="jwt-community__media">
				<div class="jwt-community__track" data-jwt-community>
					<?php foreach ( $jwt_ids as $jwt_id ) : ?>
						<div class="jwt-community__slide">
							<?php
							echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
								$jwt_id,
								'large',
								false,
								array(
									'class'    => 'jwt-community__img',
									'loading'  => 'lazy',
									'decoding' => 'async',
									'alt'      => '',
								)
							);
							?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php if ( count( $jwt_ids ) > 1 ) : ?>
					<button type="button" class="jwt-community__arrow jwt-community__arrow--prev" data-jwt-community-prev aria-label="<?php esc_attr_e( 'Sebelumnya', 'jwtrading' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
					</button>
					<button type="button" class="jwt-community__arrow jwt-community__arrow--next" data-jwt-community-next aria-label="<?php esc_attr_e( 'Berikutnya', 'jwtrading' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
					</button>
					<div class="jwt-community__dots" data-jwt-community-dots></div>
				<?php endif; ?>
			</div>

			<div class="jwt-community__content">
				<?php if ( '' !== trim( (string) ( $attributes['eyebrow'] ?? '' ) ) ) : ?>
					<span class="jwt-eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ) : ?>
					<h2 class="jwt-community__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['text'] ?? '' ) ) ) : ?>
					<div class="jwt-community__text"><?php echo wp_kses_post( wpautop( $attributes['text'] ) ); ?></div>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
					<a class="jwt-btn jwt-btn--primary jwt-community__btn" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

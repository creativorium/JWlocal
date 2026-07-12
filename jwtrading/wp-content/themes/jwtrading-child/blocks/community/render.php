<?php
/**
 * Render: jwt/community — Discord/community card.
 * Left: image carousel (imageIds = pipe-separated attachment IDs), auto-advancing
 * with arrows + dots (see [data-jwt-community] in main.js). Right: Discord logo,
 * eyebrow, heading, text, a green checklist (points, pipe-separated), CTA with a
 * Discord glyph, and an optional copy-paste fallback line showing the URL.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_ids = array_values( array_filter( array_map(
	'intval',
	explode( '|', (string) ( $attributes['imageIds'] ?? '' ) )
) ) );

$jwt_points = array_values( array_filter( array_map(
	'trim',
	explode( '|', (string) ( $attributes['points'] ?? '' ) )
), static function ( $v ) {
	return '' !== $v;
} ) );

$jwt_btn_url  = trim( (string) ( $attributes['buttonUrl'] ?? '' ) );
$jwt_fallback = trim( (string) ( $attributes['fallbackNote'] ?? '' ) );
$jwt_discord_svg = '<svg viewBox="0 0 24 18" fill="currentColor" aria-hidden="true"><path d="M20.33 1.5A19.8 19.8 0 0 0 15.4 0l-.25.5a14.6 14.6 0 0 1 4.37 1.4A17.6 17.6 0 0 0 12 1.06 17.6 17.6 0 0 0 4.48 1.9 14.6 14.6 0 0 1 8.85.5L8.6 0A19.8 19.8 0 0 0 3.67 1.5 22.9 22.9 0 0 0 .1 16.2a19.5 19.5 0 0 0 5.9 3 14.4 14.4 0 0 0 1.26-2.06 12.7 12.7 0 0 1-1.99-.96l.49-.36a13.9 13.9 0 0 0 12.48 0l.49.36c-.63.38-1.3.7-1.99.96a14.4 14.4 0 0 0 1.26 2.06 19.4 19.4 0 0 0 5.9-3A22.9 22.9 0 0 0 20.33 1.5ZM8.03 12.9c-1.16 0-2.11-1.06-2.11-2.37 0-1.3.93-2.37 2.11-2.37 1.19 0 2.14 1.07 2.12 2.37 0 1.31-.94 2.37-2.12 2.37Zm7.94 0c-1.16 0-2.11-1.06-2.11-2.37 0-1.3.93-2.37 2.11-2.37 1.19 0 2.14 1.07 2.12 2.37 0 1.31-.93 2.37-2.12 2.37Z"/></svg>';

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-community' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-community__card" data-jwt-reveal>
			<div class="jwt-community__media">
				<div class="jwt-community__track" data-jwt-community>
					<?php foreach ( $jwt_ids as $jwt_id ) : ?>
						<div class="jwt-community__slide">
							<a class="jwt-zoom" href="<?php echo esc_url( (string) wp_get_attachment_image_url( $jwt_id, 'full' ) ); ?>" target="_blank" rel="noopener">
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
							</a>
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
				<span class="jwt-community__logo" aria-hidden="true"><?php echo $jwt_discord_svg; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>

				<?php if ( '' !== trim( (string) ( $attributes['eyebrow'] ?? '' ) ) ) : ?>
					<span class="jwt-eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
				<?php endif; ?>

				<?php
				if ( '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ) :
					$jwt_ctag = in_array( $attributes['titleTag'] ?? '', array( 'h1', 'h2', 'h3' ), true ) ? $attributes['titleTag'] : 'h2';
					?>
					<<?php echo $jwt_ctag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="jwt-community__title"><?php echo wp_kses_post( $attributes['title'] ); ?></<?php echo $jwt_ctag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php endif; ?>

				<?php if ( '' !== trim( (string) ( $attributes['text'] ?? '' ) ) ) : ?>
					<div class="jwt-community__text"><?php echo wp_kses_post( wpautop( $attributes['text'] ) ); ?></div>
				<?php endif; ?>

				<?php if ( $jwt_points ) : ?>
					<ul class="jwt-community__points">
						<?php foreach ( $jwt_points as $jwt_p ) : ?>
							<li class="jwt-community__point"><?php echo esc_html( $jwt_p ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
					<a class="jwt-btn jwt-btn--primary jwt-community__btn" href="<?php echo esc_url( $jwt_btn_url ?: '#' ); ?>" target="_blank" rel="noopener">
						<span class="jwt-community__btn-icon"><?php echo $jwt_discord_svg; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
						<?php echo esc_html( $attributes['buttonText'] ); ?>
					</a>
				<?php endif; ?>

				<?php if ( '' !== $jwt_fallback && '' !== $jwt_btn_url ) : ?>
					<p class="jwt-community__fallback">
						<?php echo esc_html( $jwt_fallback ); ?><br>
						<a href="<?php echo esc_url( $jwt_btn_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $jwt_btn_url ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

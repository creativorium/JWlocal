<?php
/**
 * Render: jwt/case-study — featured member story card.
 * Content (eyebrow/heading/body/quote/CTA) on one side, image on the other.
 * `imageSide` = left|right controls which side the image sits on.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_side  = ( 'left' === ( $attributes['imageSide'] ?? '' ) ) ? 'left' : 'right';
$jwt_img   = (int) ( $attributes['imageId'] ?? 0 );
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
			<div class="jwt-casestudy__media">
				<?php
				if ( $jwt_img ) {
					printf( '<a class="jwt-zoom" href="%s" target="_blank" rel="noopener">', esc_url( (string) wp_get_attachment_image_url( $jwt_img, 'full' ) ) );
					echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
						$jwt_img,
						'large',
						false,
						array(
							'class'    => 'jwt-casestudy__img',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'alt'      => '',
						)
					);
					echo '</a>';
				} else {
					echo '<div class="jwt-casestudy__placeholder"><span>' . esc_html__( 'Screenshot', 'jwtrading' ) . '</span></div>';
				}
				?>
			</div>
		</div>
	</div>
</section>

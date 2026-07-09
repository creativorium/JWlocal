<?php
/**
 * Render: jwt/gallery — auto-scrolling image marquee (payout / feedback).
 * Two identical groups translateX-loop for a seamless scroll (pure CSS; pauses
 * on hover). Each image opens in the shared lightbox on click.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_ids = array_values( array_filter( array_map(
	'intval',
	explode( '|', (string) ( $attributes['imageIds'] ?? '' ) )
) ) );

$jwt_speed = max( 20, min( 140, (int) ( $attributes['speed'] ?? 55 ) ) );
$jwt_rev   = ! empty( $attributes['reverse'] ) ? ' is-reverse' : '';
$jwt_wrap  = get_block_wrapper_attributes( array( 'class' => 'jwt-gallery' ) );

$jwt_group = static function () use ( $jwt_ids ) {
	foreach ( $jwt_ids as $jwt_id ) {
		$jwt_full = wp_get_attachment_image_url( $jwt_id, 'full' );
		echo '<figure class="jwt-gallery__card">';
		echo '<button type="button" class="jwt-gallery__zoom" data-jwt-lightbox="' . esc_url( (string) $jwt_full ) . '" aria-label="' . esc_attr__( 'Perbesar', 'jwtrading' ) . '">';
		echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
			$jwt_id,
			'medium_large',
			false,
			array(
				'class'    => 'jwt-gallery__img',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'sizes'    => '(max-width: 480px) 70vw, 300px',
				'alt'      => '',
			)
		);
		echo '</button></figure>';
	}
};
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
	</div>
	<?php if ( $jwt_ids ) : ?>
		<div class="jwt-gallery__viewport">
			<div class="jwt-gallery__track<?php echo esc_attr( $jwt_rev ); ?>" style="--jwt-gallery-speed:<?php echo esc_attr( $jwt_speed ); ?>s">
				<div class="jwt-gallery__group"><?php $jwt_group(); ?></div>
				<div class="jwt-gallery__group" aria-hidden="true"><?php $jwt_group(); ?></div>
			</div>
		</div>
	<?php endif; ?>
</section>

<?php
/**
 * Render: jwt/proof-item — one testimonial card.
 * With an image: shows it at natural ratio (card is fixed-height/auto-width)
 * and wraps it in a zoom button that opens the lightbox on click.
 * Without: a mono placeholder label.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_label = trim( (string) ( $attributes['label'] ?? '' ) );
$jwt_id    = (int) ( $attributes['imageId'] ?? 0 );

if ( $jwt_id ) :
	$jwt_full = wp_get_attachment_image_url( $jwt_id, 'full' );
	?>
	<figure class="jwt-proof-card">
		<button type="button" class="jwt-proof-card__zoom" data-jwt-lightbox="<?php echo esc_url( (string) $jwt_full ); ?>" aria-label="<?php esc_attr_e( 'Perbesar gambar testimoni', 'jwtrading' ); ?>">
			<?php
			echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
				$jwt_id,
				'medium_large',
				false,
				array(
					'class'    => 'jwt-proof-card__img',
					'loading'  => 'lazy',
					'decoding' => 'async',
					// Cards are a fixed 300px (80vw on the smallest phones), NOT
					// full-width — override WP's default `100vw` sizes so mobile
					// fetches a ~300px source instead of a 1024px one.
					'sizes'    => '(max-width: 374px) 80vw, 300px',
					'alt'      => $attributes['imageAlt'] ?: $jwt_label,
				)
			);
			?>
		</button>
	</figure>
<?php else : ?>
	<figure class="jwt-proof-card jwt-proof-card--placeholder">
		<span class="jwt-proof-card__placeholder">[ <?php echo esc_html( $jwt_label ?: 'hasil member' ); ?> ]</span>
	</figure>
<?php endif; ?>

<?php
/**
 * Render: jwt/proof-item — one portrait result card.
 * Shows the uploaded image; otherwise a mono placeholder label.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_label = trim( (string) ( $attributes['label'] ?? '' ) );
?>
<figure class="jwt-proof-card">
	<?php if ( ! empty( $attributes['imageId'] ) ) : ?>
		<?php
		echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
			(int) $attributes['imageId'],
			'large',
			false,
			array(
				'class'   => 'jwt-proof-card__img',
				'loading' => 'lazy',
				'alt'     => $attributes['imageAlt'] ?: $jwt_label,
			)
		);
		?>
	<?php else : ?>
		<span class="jwt-proof-card__placeholder">[ <?php echo esc_html( $jwt_label ?: 'hasil member' ); ?> ]</span>
	<?php endif; ?>
</figure>

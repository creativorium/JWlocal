<?php
/**
 * Render: jwt/testimonial-item
 * Two variants: screenshot card (imageId set — the site's current style)
 * or text-quote card (quote/name/role).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_image_id = (int) $attributes['imageId'];

if ( $jwt_image_id ) :
	$jwt_img = wp_get_attachment_image(
		$jwt_image_id,
		'large',
		false,
		array(
			'loading' => 'lazy',
			'alt'     => $attributes['imageAlt'] ?: $attributes['name'],
		)
	);
	if ( $jwt_img ) :
		?>
		<figure class="jwt-card jwt-testimonial jwt-testimonial--image">
			<?php echo $jwt_img; // phpcs:ignore WordPress.Security.EscapeOutput -- core-generated img tag. ?>
		</figure>
		<?php
	endif;
else :
	?>
	<figure class="jwt-card jwt-testimonial">
		<blockquote class="jwt-testimonial__quote"><?php echo wp_kses_post( $attributes['quote'] ); ?></blockquote>
		<figcaption class="jwt-testimonial__who">
			<span class="jwt-testimonial__name"><?php echo esc_html( $attributes['name'] ); ?></span>
			<?php if ( '' !== trim( $attributes['role'] ) ) : ?>
				<span class="jwt-testimonial__role"><?php echo esc_html( $attributes['role'] ); ?></span>
			<?php endif; ?>
		</figcaption>
	</figure>
	<?php
endif;

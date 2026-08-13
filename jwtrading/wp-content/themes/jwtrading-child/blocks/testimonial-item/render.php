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
	<?php
	$jwt_avatar_id = (int) ( $attributes['avatarId'] ?? 0 );
	$jwt_name      = trim( (string) $attributes['name'] );
	// Blank card (no quote AND no name) = a layout placeholder while the real
	// testimonials are being collected.
	$jwt_blank     = '' === trim( (string) $attributes['quote'] ) && '' === $jwt_name;
	?>
	<figure class="jwt-card jwt-testimonial<?php echo $jwt_blank ? ' is-blank' : ''; ?>">
		<blockquote class="jwt-testimonial__quote"><?php echo wp_kses_post( $attributes['quote'] ); ?></blockquote>
		<figcaption class="jwt-testimonial__who">
			<?php if ( $jwt_avatar_id ) : ?>
				<?php
				echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
					$jwt_avatar_id,
					'thumbnail',
					false,
					array(
						'class'    => 'jwt-testimonial__avatar',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '44px',
						'alt'      => $jwt_name,
					)
				);
				?>
			<?php else : ?>
				<span class="jwt-testimonial__avatar jwt-testimonial__avatar--placeholder" aria-hidden="true">
					<?php echo esc_html( '' !== $jwt_name ? mb_strtoupper( mb_substr( $jwt_name, 0, 1 ) ) : '' ); ?>
				</span>
			<?php endif; ?>

			<span class="jwt-testimonial__meta">
				<span class="jwt-testimonial__name"><?php echo esc_html( $jwt_name ); ?></span>
				<?php if ( '' !== trim( $attributes['role'] ) ) : ?>
					<span class="jwt-testimonial__role"><?php echo esc_html( $attributes['role'] ); ?></span>
				<?php endif; ?>
			</span>
		</figcaption>
	</figure>
	<?php
endif;

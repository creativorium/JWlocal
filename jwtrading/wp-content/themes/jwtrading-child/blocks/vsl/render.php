<?php
/**
 * Render: jwt/vsl — framed sales video.
 *
 * Facade pattern: the poster image renders immediately; the (heavy) video file
 * is NOT requested until the visitor clicks play (see the [data-jwt-vsl]
 * handler in src/main.js). Keeps the 35 MB VSL off the critical path.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_poster_id = (int) ( $attributes['posterId'] ?? 0 );
$jwt_video_id  = (int) ( $attributes['videoId'] ?? 0 );
$jwt_video_url = $jwt_video_id ? wp_get_attachment_url( $jwt_video_id ) : '';
$jwt_video_mime = $jwt_video_id ? (string) get_post_mime_type( $jwt_video_id ) : 'video/webm';

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-vsl-section' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container" data-jwt-reveal>
		<figure
			class="jwt-vsl"
			data-jwt-vsl
			data-jwt-vsl-src="<?php echo esc_url( (string) $jwt_video_url ); ?>"
			data-jwt-vsl-type="<?php echo esc_attr( $jwt_video_mime ); ?>"
		>
			<div class="jwt-vsl__frame">
				<?php
				if ( $jwt_poster_id ) {
					echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
						$jwt_poster_id,
						'large',
						false,
						array(
							'class'    => 'jwt-vsl__poster',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 980px) 100vw, 960px',
							'alt'      => __( 'Putar video', 'jwtrading' ),
						)
					);
				}
				?>
				<?php if ( '' !== $jwt_video_url ) : ?>
					<button type="button" class="jwt-vsl__play" data-jwt-vsl-play aria-label="<?php esc_attr_e( 'Putar video', 'jwtrading' ); ?>">
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z" /></svg>
					</button>
				<?php endif; ?>
			</div>
		</figure>
	</div>
</section>

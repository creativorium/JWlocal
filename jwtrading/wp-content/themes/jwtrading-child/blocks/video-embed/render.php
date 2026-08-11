<?php
/**
 * Render: jwt/video-embed — YouTube facade.
 *
 * Nothing from YouTube is requested on page load: we render our own poster (or
 * YouTube's still image) and only swap in the youtube-nocookie iframe when the
 * visitor clicks play (see [data-jwt-ytembed] in main.js). Funnel VSLs must be
 * hosted on YouTube, never self-hosted — page speed is the reason.
 *
 * With no URL set yet the block renders the empty frame from the layout PDF, so
 * the page can ship and go live before the client's video link arrives.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_id     = jwt_youtube_id( (string) ( $attributes['videoUrl'] ?? '' ) );
$jwt_label  = trim( (string) ( $attributes['label'] ?? '' ) );
$jwt_poster = (int) ( $attributes['posterId'] ?? 0 );

$jwt_wrap = get_block_wrapper_attributes(
	array(
		'class' => 'jwt-ytembed-section' . ( ! empty( $attributes['narrow'] ) ? ' is-narrow' : '' ),
	)
);
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container" data-jwt-reveal>
		<figure class="jwt-ytembed<?php echo '' === $jwt_id ? ' is-empty' : ''; ?>" <?php echo '' !== $jwt_id ? 'data-jwt-ytembed data-video="' . esc_attr( $jwt_id ) . '"' : ''; ?>>
			<div class="jwt-ytembed__frame">
				<?php
				if ( $jwt_poster ) {
					echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
						$jwt_poster,
						'large',
						false,
						array(
							'class'    => 'jwt-ytembed__poster',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 980px) 100vw, 960px',
							'alt'      => $jwt_label ?: __( 'Putar video', 'jwtrading' ),
						)
					);
				} elseif ( '' !== $jwt_id ) {
					printf(
						'<img class="jwt-ytembed__poster" src="%s" alt="%s" loading="lazy" decoding="async" width="1280" height="720">',
						esc_url( 'https://i.ytimg.com/vi/' . $jwt_id . '/maxresdefault.jpg' ),
						esc_attr( $jwt_label ?: __( 'Putar video', 'jwtrading' ) )
					);
				}
				?>

				<button type="button" class="jwt-ytembed__play" data-jwt-ytembed-play aria-label="<?php esc_attr_e( 'Putar video', 'jwtrading' ); ?>"<?php echo '' === $jwt_id ? ' disabled' : ''; ?>>
					<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
				</button>

				<?php if ( '' === $jwt_id && '' !== $jwt_label ) : ?>
					<span class="jwt-ytembed__placeholder">[ <?php echo esc_html( $jwt_label ); ?> ]</span>
				<?php endif; ?>
			</div>
		</figure>
	</div>
</section>

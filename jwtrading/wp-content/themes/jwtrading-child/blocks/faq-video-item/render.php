<?php
/**
 * Render: jwt/faq-video-item — question + YouTube facade answer.
 *
 * Same facade approach as jwt/video-embed: with eleven of these on the thank-you
 * page, loading eleven YouTube iframes up front would wreck the page. Nothing is
 * fetched from YouTube until a card is clicked.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_question = trim( (string) ( $attributes['question'] ?? '' ) );
$jwt_id       = jwt_youtube_id( (string) ( $attributes['videoUrl'] ?? '' ) );
$jwt_poster   = (int) ( $attributes['posterId'] ?? 0 );
?>
<article class="jwt-faqvid__item">
	<?php if ( '' !== $jwt_question ) : ?>
		<h3 class="jwt-faqvid__q">
			<span class="jwt-faqvid__mark" aria-hidden="true">?</span>
			<?php echo esc_html( $jwt_question ); ?>
		</h3>
	<?php endif; ?>

	<figure class="jwt-ytembed jwt-faqvid__video<?php echo '' === $jwt_id ? ' is-empty' : ''; ?>" <?php echo '' !== $jwt_id ? 'data-jwt-ytembed data-video="' . esc_attr( $jwt_id ) . '"' : ''; ?>>
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
						'alt'      => $jwt_question,
					)
				);
			} elseif ( '' !== $jwt_id ) {
				printf(
					'<img class="jwt-ytembed__poster" src="%s" alt="%s" loading="lazy" decoding="async" width="1280" height="720">',
					esc_url( 'https://i.ytimg.com/vi/' . $jwt_id . '/hqdefault.jpg' ),
					esc_attr( $jwt_question )
				);
			}
			?>
			<button type="button" class="jwt-ytembed__play is-yt" data-jwt-ytembed-play aria-label="<?php echo esc_attr( $jwt_question ?: __( 'Putar video', 'jwtrading' ) ); ?>"<?php echo '' === $jwt_id ? ' disabled' : ''; ?>>
				<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
			</button>
			<?php if ( '' === $jwt_id ) : ?>
				<span class="jwt-ytembed__placeholder">[ <?php esc_html_e( 'video menyusul', 'jwtrading' ); ?> ]</span>
			<?php endif; ?>
		</div>
	</figure>
</article>

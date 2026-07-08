<?php
/**
 * Render: jwt/discord-cta — Discord join banner (adapted to the JW dark/purple
 * design). Logo + heading + button on the left; a screenshot (or labelled
 * placeholder) on the right.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_media_id  = (int) ( $attributes['mediaId'] ?? 0 );
$jwt_wrapper   = get_block_wrapper_attributes( array( 'class' => 'jwt-discord' ) );
$jwt_ph        = trim( (string) ( $attributes['placeholder'] ?? '' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-discord__panel" data-jwt-reveal>
			<div class="jwt-discord__left">
				<span class="jwt-discord__logo" aria-hidden="true">
					<svg viewBox="0 0 24 18" fill="currentColor"><path d="M20.33 1.5A19.8 19.8 0 0 0 15.4 0l-.25.5a14.6 14.6 0 0 1 4.37 1.4A17.6 17.6 0 0 0 12 1.06 17.6 17.6 0 0 0 4.48 1.9 14.6 14.6 0 0 1 8.85.5L8.6 0A19.8 19.8 0 0 0 3.67 1.5 22.9 22.9 0 0 0 .1 16.2a19.5 19.5 0 0 0 5.9 3 14.4 14.4 0 0 0 1.26-2.06 12.7 12.7 0 0 1-1.99-.96l.49-.36a13.9 13.9 0 0 0 12.48 0l.49.36c-.63.38-1.3.7-1.99.96a14.4 14.4 0 0 0 1.26 2.06 19.4 19.4 0 0 0 5.9-3A22.9 22.9 0 0 0 20.33 1.5ZM8.03 12.9c-1.16 0-2.11-1.06-2.11-2.37 0-1.3.93-2.37 2.11-2.37 1.19 0 2.14 1.07 2.12 2.37 0 1.31-.94 2.37-2.12 2.37Zm7.94 0c-1.16 0-2.11-1.06-2.11-2.37 0-1.3.93-2.37 2.11-2.37 1.19 0 2.14 1.07 2.12 2.37 0 1.31-.93 2.37-2.12 2.37Z"/></svg>
				</span>
				<?php if ( '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ) : ?>
					<h2 class="jwt-discord__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
					<a class="jwt-btn jwt-btn--light jwt-discord__btn" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
				<?php endif; ?>
			</div>
			<div class="jwt-discord__media">
				<?php
				if ( $jwt_media_id ) {
					echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
						$jwt_media_id,
						'large',
						false,
						array(
							'class'    => 'jwt-discord__img',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'alt'      => '',
						)
					);
				} else {
					?>
					<div class="jwt-discord__placeholder">
						<span class="jwt-discord__ph-label"><?php esc_html_e( 'Screenshot', 'jwtrading' ); ?></span>
						<?php if ( '' !== $jwt_ph ) : ?>
							<span class="jwt-discord__ph-title">[ <?php echo esc_html( $jwt_ph ); ?> ]</span>
						<?php endif; ?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</section>

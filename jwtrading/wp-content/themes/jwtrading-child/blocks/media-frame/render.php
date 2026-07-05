<?php
/**
 * Render: jwt/media-frame — browser-chrome frame; media comes from inner blocks.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-media-frame' ) );
$jwt_has_media = '' !== trim( wp_strip_all_tags( $content ) ) || false !== strpos( $content, '<' );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container jwt-media-frame__wrap" data-jwt-reveal>
		<div class="jwt-media-frame__box">
			<div class="jwt-media-frame__bar">
				<div class="jwt-media-frame__dots"><span></span><span></span><span></span></div>
				<?php if ( '' !== trim( $attributes['labelLeft'] ) ) : ?>
					<div class="jwt-media-frame__label"><?php echo esc_html( $attributes['labelLeft'] ); ?></div>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['labelRight'] ) ) : ?>
					<div class="jwt-media-frame__label is-green"><?php echo esc_html( $attributes['labelRight'] ); ?></div>
				<?php endif; ?>
			</div>
			<div class="jwt-media-frame__body<?php echo $jwt_has_media ? '' : ' is-empty'; ?>">
				<?php if ( $jwt_has_media ) : ?>
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
				<?php else : ?>
					<span class="jwt-media-frame__placeholder">[ intro video / VSL ]</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

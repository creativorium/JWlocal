<?php
/**
 * Render: jwt/methodology-item — one market box (emoji/icon + title + subtitle).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="jwt-methodology-card">
	<?php if ( ! empty( $attributes['imageId'] ) ) : ?>
		<span class="jwt-methodology-card__icon jwt-methodology-card__icon--img">
			<?php echo wp_get_attachment_image( (int) $attributes['imageId'], 'thumbnail', false, array( 'class' => 'jwt-methodology-card__img', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</span>
	<?php elseif ( '' !== trim( (string) ( $attributes['icon'] ?? '' ) ) ) : ?>
		<span class="jwt-methodology-card__icon"><?php echo esc_html( $attributes['icon'] ); ?></span>
	<?php endif; ?>
	<div class="jwt-methodology-card__body">
		<?php if ( '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ) : ?>
			<h3 class="jwt-methodology-card__title"><?php echo esc_html( $attributes['title'] ); ?></h3>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) ( $attributes['subtitle'] ?? '' ) ) ) : ?>
			<p class="jwt-methodology-card__sub"><?php echo esc_html( $attributes['subtitle'] ); ?></p>
		<?php endif; ?>
	</div>
</div>

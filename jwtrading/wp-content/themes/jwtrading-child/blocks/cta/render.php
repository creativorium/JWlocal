<?php
/**
 * Render: jwt/cta
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-cta' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-cta__box" data-jwt-reveal>
			<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>

			<?php if ( '' !== trim( $attributes['buttonText'] ) ) : ?>
				<div class="jwt-cta__actions">
					<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
				</div>
			<?php endif; ?>

			<?php if ( '' !== trim( $attributes['promoText'] ) ) : ?>
				<div class="jwt-cta__promo"><span class="jwt-pill"><?php echo esc_html( $attributes['promoText'] ); ?></span></div>
			<?php endif; ?>

			<?php if ( '' !== trim( $attributes['note'] ) ) : ?>
				<p class="jwt-cta__note"><?php echo esc_html( $attributes['note'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>

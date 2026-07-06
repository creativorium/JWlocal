<?php
/**
 * Render: jwt/cta-banner — horizontal dark card, text left + button right.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-cta-banner' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-cta-banner__card" data-jwt-reveal>
			<div class="jwt-cta-banner__text">
				<?php if ( '' !== trim( $attributes['title'] ) ) : ?>
					<h3 class="jwt-cta-banner__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h3>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['text'] ) ) : ?>
					<p class="jwt-cta-banner__sub"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== trim( $attributes['buttonText'] ) ) : ?>
				<a class="jwt-btn jwt-btn--primary jwt-cta-banner__btn" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>

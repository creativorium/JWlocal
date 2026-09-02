<?php
/**
 * Render: jwt/intro — centered eyebrow + heading + body paragraphs + CTA.
 * Plain (no box), sits on the dark canvas. Body may contain multiple <p>.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-intro' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container jwt-intro__inner" data-jwt-reveal>
		<?php if ( '' !== trim( (string) $attributes['eyebrow'] ) ) : ?>
			<span class="jwt-eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $attributes['title'] ) ) : ?>
			<h2 class="jwt-intro__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h2>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $attributes['body'] ) ) : ?>
			<div class="jwt-intro__body"><?php echo wp_kses_post( $attributes['body'] ); ?></div>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
			<div class="jwt-intro__cta">
				<?php // Ghost when this button points AWAY from the page's own goal (e.g. the
					// application page sending unready readers to Bootcamp) -- a filled
					// primary there would outrank the action the page exists for. ?>
					<a class="jwt-btn <?php echo empty( $attributes['buttonGhost'] ) ? 'jwt-btn--primary' : 'jwt-btn--ghost'; ?>" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
/**
 * Render: jwt/program — 2-col bootcamp panel; module rows are
 * jwt/curriculum-item inner blocks (with explicit `number`).
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-program' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-program__panel" data-jwt-reveal>
			<div class="jwt-program__pitch">
				<?php if ( '' !== trim( $attributes['eyebrow'] ) ) : ?>
					<span class="jwt-eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['title'] ) ) : ?>
					<h2 class="jwt-program__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['lead'] ) ) : ?>
					<p class="jwt-program__lead"><?php echo wp_kses_post( $attributes['lead'] ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['buttonText'] ) ) : ?>
					<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
				<?php endif; ?>
			</div>
			<div class="jwt-program__modules">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
				<?php if ( '' !== trim( $attributes['footnote'] ) ) : ?>
					<div class="jwt-program__footnote"><?php echo esc_html( $attributes['footnote'] ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

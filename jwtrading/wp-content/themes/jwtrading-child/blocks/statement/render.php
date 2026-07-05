<?php
/**
 * Render: jwt/statement — bordered gradient callout panel.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-statement' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-statement__box" data-jwt-reveal>
			<?php if ( '' !== trim( $attributes['eyebrow'] ) ) : ?>
				<span class="jwt-eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== trim( $attributes['title'] ) ) : ?>
				<h2 class="jwt-statement__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== trim( $attributes['lead'] ) ) : ?>
				<p class="jwt-statement__lead"><?php echo wp_kses_post( $attributes['lead'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>

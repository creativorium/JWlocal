<?php
/**
 * Render: jwt/curriculum-item
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="jwt-curriculum-item">
	<div>
		<h3 class="jwt-curriculum-item__title"><?php echo esc_html( $attributes['title'] ); ?></h3>
		<?php if ( '' !== trim( $attributes['text'] ) ) : ?>
			<p class="jwt-curriculum-item__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( '' !== trim( $attributes['tag'] ) ) : ?>
		<span class="jwt-pill"><?php echo esc_html( $attributes['tag'] ); ?></span>
	<?php endif; ?>
</div>

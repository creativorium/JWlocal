<?php
/**
 * Render: jwt/curriculum-item
 * Inside jwt/curriculum the number auto-counts via CSS; set `number`
 * for an explicit value (used by jwt/program module rows).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_number = trim( (string) ( $attributes['number'] ?? '' ) );
?>
<div class="jwt-curriculum-item<?php echo $jwt_number ? ' has-num' : ''; ?>">
	<?php if ( $jwt_number ) : ?>
		<span class="jwt-curriculum-item__num"><?php echo esc_html( $jwt_number ); ?></span>
	<?php endif; ?>
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

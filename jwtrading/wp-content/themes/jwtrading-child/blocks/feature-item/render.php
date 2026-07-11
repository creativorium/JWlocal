<?php
/**
 * Render: jwt/feature-item
 * Two looks: numbered pillar card (mono number + corner glow, JW Home.dc)
 * when `number` is set, otherwise the icon card.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_number = trim( (string) ( $attributes['number'] ?? '' ) );
?>
<article class="jwt-card jwt-feature<?php echo $jwt_number ? ' jwt-feature--numbered' : ''; ?>" data-jwt-reveal>
	<?php if ( $jwt_number ) : ?>
		<div class="jwt-feature__glow" aria-hidden="true"></div>
		<div class="jwt-feature__inner">
			<div class="jwt-feature__numrow">
				<span class="jwt-feature__num"><?php echo esc_html( $jwt_number ); ?></span>
				<?php if ( '' !== trim( (string) ( $attributes['tag'] ?? '' ) ) ) : ?>
					<span class="jwt-feature__tag"><?php echo esc_html( $attributes['tag'] ); ?></span>
				<?php endif; ?>
			</div>
			<h3 class="jwt-feature__title"><?php echo esc_html( $attributes['title'] ); ?></h3>
			<p class="jwt-feature__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
		</div>
	<?php elseif ( '' !== trim( (string) ( $attributes['tag'] ?? '' ) ) ) : ?>
		<span class="jwt-feature__tag jwt-feature__tag--solo"><?php echo esc_html( $attributes['tag'] ); ?></span>
		<h3 class="jwt-feature__title"><?php echo esc_html( $attributes['title'] ); ?></h3>
		<p class="jwt-feature__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
	<?php else : ?>
		<span class="jwt-feature__icon" aria-hidden="true"><?php echo jwt_icon( $attributes['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG map. ?></span>
		<h3 class="jwt-feature__title"><?php echo esc_html( $attributes['title'] ); ?></h3>
		<p class="jwt-feature__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
	<?php endif; ?>
</article>

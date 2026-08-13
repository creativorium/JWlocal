<?php
/**
 * Render: jwt/testimonials (wrapper — cards come from inner blocks).
 *
 * Two modes:
 *  - default: horizontal scroll-snap track, zero JS (the site's existing style).
 *  - marquee: auto-scrolling loop, same mechanism as jwt/proof — the inner
 *    blocks are rendered once into $content and output as TWO identical groups,
 *    and a CSS translateX(-50%) loop hands over seamlessly between them. Pure
 *    CSS, no JS; pauses on hover for real pointers only.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_marquee = ! empty( $attributes['marquee'] );
$jwt_speed   = max( 15, min( 160, (int) ( $attributes['speed'] ?? 60 ) ) );

$jwt_wrapper = get_block_wrapper_attributes(
	array( 'class' => 'jwt-testimonials' . ( $jwt_marquee ? ' jwt-testimonials--marquee' : '' ) )
);

// An empty marquee would render as a blank strip, so fall back to nothing.
$jwt_has_cards = '' !== trim( (string) $content );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
	</div>

	<?php if ( $jwt_marquee ) : ?>
		<?php if ( $jwt_has_cards ) : ?>
			<div class="jwt-testimonials__viewport">
				<div class="jwt-testimonials__mtrack" style="--jwt-tmarquee-speed:<?php echo esc_attr( $jwt_speed ); ?>s">
					<div class="jwt-testimonials__group">
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
					</div>
					<div class="jwt-testimonials__group" aria-hidden="true">
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- duplicate for the seamless loop. ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<div class="jwt-container">
			<div class="jwt-testimonials__track" data-jwt-reveal>
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
			</div>
		</div>
	<?php endif; ?>
</section>

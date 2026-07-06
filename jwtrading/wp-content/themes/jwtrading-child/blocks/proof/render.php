<?php
/**
 * Render: jwt/proof — auto-scrolling marquee of member-result cards.
 *
 * The inner blocks (jwt/proof-item) are rendered once into $content, then
 * output as TWO identical groups inside the track. A CSS translateX(-50%)
 * loop scrolls one group-width per cycle, so the second group seamlessly
 * takes over — pure CSS, no JS. Pauses on hover; static under
 * prefers-reduced-motion (see _home.scss).
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-proof' ) );
$jwt_speed   = max( 15, min( 120, (int) ( $attributes['speed'] ?? 45 ) ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
	</div>

	<div class="jwt-proof__viewport">
		<div class="jwt-proof__track" style="--jwt-proof-speed:<?php echo esc_attr( $jwt_speed ); ?>s">
			<div class="jwt-proof__group">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
			</div>
			<div class="jwt-proof__group" aria-hidden="true">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- duplicate for seamless loop. ?>
			</div>
		</div>
	</div>
</section>

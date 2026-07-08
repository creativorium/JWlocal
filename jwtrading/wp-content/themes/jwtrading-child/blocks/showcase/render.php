<?php
/**
 * Render: jwt/showcase — parent. Left media "stage" + right column of tab-cards
 * (the inner jwt/showcase-item blocks). The stage is filled by JS from the
 * active card's data (see [data-jwt-showcase] in src/main.js); with no JS the
 * cards still read fine as a plain list.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-showcase' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
		<div class="jwt-showcase__wrap" data-jwt-showcase>
			<div class="jwt-showcase__layout">
				<div class="jwt-showcase__stage" data-jwt-showcase-stage aria-live="polite"></div>
				<div class="jwt-showcase__cards">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
				</div>
			</div>
			<div class="jwt-showcase__nav">
				<button type="button" class="jwt-showcase__arrow" data-jwt-showcase-prev aria-label="<?php esc_attr_e( 'Sebelumnya', 'jwtrading' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
				</button>
				<div class="jwt-showcase__dots" data-jwt-showcase-dots></div>
				<button type="button" class="jwt-showcase__arrow" data-jwt-showcase-next aria-label="<?php esc_attr_e( 'Berikutnya', 'jwtrading' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
				</button>
			</div>
		</div>
	</div>
</section>

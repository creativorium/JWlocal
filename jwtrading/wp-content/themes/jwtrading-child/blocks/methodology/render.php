<?php
/**
 * Render: jwt/methodology — heading + 4-box market grid (inner blocks) +
 * a centered text CTA link below.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-methodology' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>

		<div class="jwt-methodology__grid" data-jwt-reveal>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
		</div>

		<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
			<div class="jwt-methodology__cta">
				<a class="jwt-textlink" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</section>

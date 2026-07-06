<?php
/**
 * Render: jwt/partners — logo strip. Heading + row of jwt/partner-item children.
 * Background is the brand purple at 8% over the dark canvas.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-partners' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php if ( '' !== trim( $attributes['heading'] ) ) : ?>
			<p class="jwt-partners__heading"><?php echo esc_html( $attributes['heading'] ); ?></p>
		<?php endif; ?>
		<div class="jwt-partners__row">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
		</div>
	</div>
</section>

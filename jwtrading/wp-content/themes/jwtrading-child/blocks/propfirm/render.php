<?php
/**
 * Render: jwt/propfirm — stacked list of affiliate prop-firm cards.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrap = get_block_wrapper_attributes( array( 'class' => 'jwt-propfirm' ) );
$jwt_disc = trim( (string) ( $attributes['disclosure'] ?? '' ) );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>

		<div class="jwt-propfirm__list">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
		</div>

		<?php if ( '' !== $jwt_disc ) : ?>
			<p class="jwt-propfirm__disclosure"><?php echo esc_html( $jwt_disc ); ?></p>
		<?php endif; ?>
	</div>
</section>

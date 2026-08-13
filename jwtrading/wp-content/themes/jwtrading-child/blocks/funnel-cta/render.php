<?php
/**
 * Render: jwt/funnel-cta — lone centred button that sends the visitor back to
 * the form higher up the page (per the layout PDF's closing CTA).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_text = trim( (string) ( $attributes['buttonText'] ?? '' ) );
if ( '' === $jwt_text ) {
	return;
}

$jwt_wrap = get_block_wrapper_attributes( array( 'class' => 'jwt-funnel-cta' ) );
$jwt_note = trim( (string) ( $attributes['note'] ?? '' ) );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<a class="jwt-btn jwt-btn--primary jwt-funnel-cta__btn" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $jwt_text ); ?> →</a>
		<?php if ( '' !== $jwt_note ) : ?>
			<p class="jwt-funnel-cta__note"><?php echo esc_html( $jwt_note ); ?></p>
		<?php endif; ?>
	</div>
</section>

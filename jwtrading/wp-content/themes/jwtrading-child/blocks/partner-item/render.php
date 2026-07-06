<?php
/**
 * Render: jwt/partner-item — one partner logo.
 * Shows the uploaded logo when set; otherwise a neutral placeholder mark +
 * the name (so the strip reads correctly before Jack supplies real logos).
 * Optionally wrapped in a link.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_name = trim( (string) ( $attributes['name'] ?? '' ) );
$jwt_url  = trim( (string) ( $attributes['url'] ?? '' ) );

ob_start();
if ( ! empty( $attributes['imageId'] ) ) {
	echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
		(int) $attributes['imageId'],
		'medium',
		false,
		array(
			'class'   => 'jwt-partner__logo',
			'loading' => 'lazy',
			'alt'     => $jwt_name,
		)
	);
} else {
	// Neutral placeholder mark (replaced by the real logo later).
	?>
	<span class="jwt-partner__placeholder" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 12h8"/></svg>
	</span>
	<?php
	if ( '' !== $jwt_name ) {
		echo '<span class="jwt-partner__name">' . esc_html( $jwt_name ) . '</span>';
	}
}
$jwt_inner = ob_get_clean();
?>
<?php if ( '' !== $jwt_url ) : ?>
	<a class="jwt-partner" href="<?php echo esc_url( $jwt_url ); ?>" target="_blank" rel="noopener noreferrer">
		<?php echo $jwt_inner; // phpcs:ignore WordPress.Security.EscapeOutput -- built above. ?>
	</a>
<?php else : ?>
	<div class="jwt-partner">
		<?php echo $jwt_inner; // phpcs:ignore WordPress.Security.EscapeOutput -- built above. ?>
	</div>
<?php endif; ?>

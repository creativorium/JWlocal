<?php
/**
 * Render: jwt/stat-item
 * `count` (numeric) + `suffix` are optional — when set, main.js animates the
 * number client-side; the real value is always in the markup for SEO/no-JS.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_count_attrs = '';
if ( '' !== trim( $attributes['count'] ) && is_numeric( $attributes['count'] ) ) {
	$jwt_count_attrs = sprintf(
		' data-jwt-count="%s" data-jwt-suffix="%s"',
		esc_attr( $attributes['count'] ),
		esc_attr( $attributes['suffix'] )
	);
}
?>
<div class="jwt-card jwt-stat" data-jwt-reveal>
	<span class="jwt-stat__number"<?php echo $jwt_count_attrs; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?>><?php echo esc_html( $attributes['value'] ); ?></span>
	<span class="jwt-stat__label"><?php echo esc_html( $attributes['label'] ); ?></span>
</div>

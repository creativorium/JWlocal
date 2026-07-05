<?php
/**
 * Render: jwt/connector — small mono pill between sections.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-connector' ) );
?>
<div <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container jwt-connector__wrap">
		<span class="jwt-connector__pill"><?php echo esc_html( $attributes['text'] ); ?></span>
	</div>
</div>

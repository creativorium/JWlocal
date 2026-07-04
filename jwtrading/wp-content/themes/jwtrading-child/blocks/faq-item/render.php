<?php
/**
 * Render: jwt/faq-item — native <details>, no JS needed.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;
?>
<details class="jwt-faq-item">
	<summary><?php echo esc_html( $attributes['question'] ); ?></summary>
	<div class="jwt-faq-item__answer"><?php echo wp_kses_post( wpautop( $attributes['answer'] ) ); ?></div>
</details>

<?php
/**
 * Render: jwt/faq-videos — stacked list of jwt/faq-video-item cards.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrap = get_block_wrapper_attributes( array( 'class' => 'jwt-faqvid' ) );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
		<div class="jwt-faqvid__list">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
		</div>
	</div>
</section>

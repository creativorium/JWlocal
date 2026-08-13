<?php
/**
 * Render: jwt/faq-video-item — question + YouTube facade answer.
 *
 * Facade via jwt_ytembed_html(): with eleven of these on the thank-you page,
 * eleven eager iframes would wreck it. Nothing loads until a card is clicked.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_question = trim( (string) ( $attributes['question'] ?? '' ) );
?>
<article class="jwt-faqvid__item">
	<?php if ( '' !== $jwt_question ) : ?>
		<h3 class="jwt-faqvid__q">
			<span class="jwt-faqvid__mark" aria-hidden="true">?</span>
			<?php echo esc_html( $jwt_question ); ?>
		</h3>
	<?php endif; ?>

	<?php
	echo jwt_ytembed_html( // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper.
		array(
			'video'       => $attributes['videoUrl'] ?? '',
			'posterId'    => $attributes['posterId'] ?? 0,
			'label'       => $jwt_question,
			'class'       => 'jwt-faqvid__video',
			'placeholder' => __( 'video menyusul', 'jwtrading' ),
		)
	);
	?>
</article>

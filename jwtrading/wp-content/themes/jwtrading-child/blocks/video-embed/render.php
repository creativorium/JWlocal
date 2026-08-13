<?php
/**
 * Render: jwt/video-embed — YouTube facade (markup from jwt_ytembed_html()).
 *
 * Nothing from YouTube is requested on page load; the iframe is only created on
 * click. Funnel/marketing VSLs must be hosted on YouTube, never self-hosted —
 * page speed is the reason.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_label = trim( (string) ( $attributes['label'] ?? '' ) );

$jwt_wrap = get_block_wrapper_attributes(
	array(
		'class' => 'jwt-ytembed-section' . ( ! empty( $attributes['narrow'] ) ? ' is-narrow' : '' ),
	)
);
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container" data-jwt-reveal>
		<?php
		echo jwt_ytembed_html( // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper.
			array(
				'video'       => $attributes['videoUrl'] ?? '',
				'posterId'    => $attributes['posterId'] ?? 0,
				'label'       => $jwt_label,
				'placeholder' => $jwt_label,
				'maxres'      => true,
			)
		);
		?>
	</div>
</section>

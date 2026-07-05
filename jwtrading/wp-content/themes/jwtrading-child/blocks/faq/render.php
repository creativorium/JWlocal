<?php
/**
 * Render: jwt/faq (wrapper — items come from inner blocks).
 * Optionally emits FAQPage JSON-LD built from the inner blocks' attributes.
 *
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-faq' ) );

$jwt_schema = '';
if ( ! empty( $attributes['schema'] ) && isset( $block->inner_blocks ) ) {
	$jwt_entities = array();

	foreach ( $block->inner_blocks as $jwt_inner ) {
		$jwt_q = trim( wp_strip_all_tags( (string) ( $jwt_inner->attributes['question'] ?? '' ) ) );
		$jwt_a = trim( wp_strip_all_tags( (string) ( $jwt_inner->attributes['answer'] ?? '' ) ) );

		if ( '' === $jwt_q || '' === $jwt_a ) {
			continue;
		}

		$jwt_entities[] = array(
			'@type'          => 'Question',
			'name'           => $jwt_q,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $jwt_a,
			),
		);
	}

	if ( $jwt_entities ) {
		$jwt_schema = wp_json_encode(
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $jwt_entities,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
	}
}
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
		<div class="jwt-faq__list" data-jwt-reveal>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
		</div>
		<?php if ( '' !== trim( (string) ( $attributes['buttonText'] ?? '' ) ) ) : ?>
			<div class="jwt-faq__cta">
				<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $jwt_schema ) : ?>
		<script type="application/ld+json"><?php echo $jwt_schema; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_json_encode output. ?></script>
	<?php endif; ?>
</section>

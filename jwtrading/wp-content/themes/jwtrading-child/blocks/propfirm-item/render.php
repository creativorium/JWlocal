<?php
/**
 * Render: jwt/propfirm-item — one prop firm row with its affiliate link.
 *
 * Affiliate links always carry rel="sponsored nofollow noopener" (Google's
 * requirement for paid/affiliate links — leaving it off risks the whole site's
 * ranking) and open in a new tab.
 *
 * `specs` is an optional pipe-separated "Label: value" list, so the same block
 * reads as a comparison row when the client supplies comparable attributes and
 * as a clean stacked card when they only send logo + blurb + link.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_name  = trim( (string) ( $attributes['name'] ?? '' ) );
$jwt_url   = trim( (string) ( $attributes['url'] ?? '' ) );
$jwt_blurb = trim( (string) ( $attributes['blurb'] ?? '' ) );
$jwt_badge = trim( (string) ( $attributes['badge'] ?? '' ) );
$jwt_specs = array_values( array_filter( array_map( 'trim', explode( '|', (string) ( $attributes['specs'] ?? '' ) ) ) ) );
?>
<article class="jwt-propfirm__card">
	<div class="jwt-propfirm__brand">
		<?php if ( ! empty( $attributes['imageId'] ) ) : ?>
			<?php
			echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
				(int) $attributes['imageId'],
				'medium',
				false,
				array(
					'class'    => 'jwt-propfirm__logo',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '180px',
					'alt'      => $jwt_name,
				)
			);
			?>
		<?php else : ?>
			<span class="jwt-propfirm__placeholder" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 12h8"/></svg>
			</span>
		<?php endif; ?>
	</div>

	<div class="jwt-propfirm__body">
		<h3 class="jwt-propfirm__name">
			<?php echo esc_html( $jwt_name ); ?>
			<?php if ( '' !== $jwt_badge ) : ?>
				<span class="jwt-propfirm__badge"><?php echo esc_html( $jwt_badge ); ?></span>
			<?php endif; ?>
		</h3>

		<?php if ( '' !== $jwt_blurb ) : ?>
			<p class="jwt-propfirm__blurb"><?php echo esc_html( $jwt_blurb ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $jwt_specs ) ) : ?>
			<dl class="jwt-propfirm__specs">
				<?php foreach ( $jwt_specs as $jwt_spec ) : ?>
					<?php
					$jwt_parts = array_map( 'trim', explode( ':', $jwt_spec, 2 ) );
					if ( count( $jwt_parts ) < 2 ) {
						continue;
					}
					?>
					<div class="jwt-propfirm__spec">
						<dt><?php echo esc_html( $jwt_parts[0] ); ?></dt>
						<dd><?php echo esc_html( $jwt_parts[1] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>

	<div class="jwt-propfirm__action">
		<?php if ( '' !== $jwt_url ) : ?>
			<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $jwt_url ); ?>" target="_blank" rel="sponsored nofollow noopener">
				<?php echo esc_html( (string) $attributes['buttonText'] ); ?> →
			</a>
		<?php else : ?>
			<span class="jwt-propfirm__soon"><?php esc_html_e( 'Link menyusul', 'jwtrading' ); ?></span>
		<?php endif; ?>
	</div>
</article>

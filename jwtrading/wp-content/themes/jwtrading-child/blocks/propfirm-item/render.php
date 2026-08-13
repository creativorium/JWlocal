<?php
/**
 * Render: jwt/propfirm-item — one partner card (prop firm / broker / tool).
 *
 * Anatomy per the client's layout PDF: tinted head with name + blurb, a
 * "Use Code" row whose pill copies the discount code to the clipboard, and a
 * guide sub-card holding that partner's walkthrough video.
 *
 * The name links out through the affiliate URL with rel="sponsored nofollow
 * noopener" — Google requires `sponsored` on affiliate links, and omitting it
 * puts the whole site's ranking at risk. A card with no URL renders as plain
 * text rather than a dead link, so it can ship before the links arrive.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_name    = trim( (string) ( $attributes['name'] ?? '' ) );
$jwt_url     = trim( (string) ( $attributes['url'] ?? '' ) );
$jwt_blurb   = trim( (string) ( $attributes['blurb'] ?? '' ) );
$jwt_code    = trim( (string) ( $attributes['code'] ?? '' ) );
$jwt_guide   = trim( (string) ( $attributes['guideLabel'] ?? '' ) );
$jwt_variant = in_array( $attributes['variant'] ?? 'default', array( 'default', 'green', 'blue' ), true )
	? $attributes['variant']
	: 'default';
?>
<article class="jwt-pfcard is-<?php echo esc_attr( $jwt_variant ); ?>">
	<div class="jwt-pfcard__head">
		<h3 class="jwt-pfcard__name">
			<?php if ( '' !== $jwt_url ) : ?>
				<a href="<?php echo esc_url( $jwt_url ); ?>" target="_blank" rel="sponsored nofollow noopener"><?php echo esc_html( $jwt_name ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $jwt_name ); ?>
			<?php endif; ?>
		</h3>
		<?php if ( '' !== $jwt_blurb ) : ?>
			<p class="jwt-pfcard__blurb"><?php echo esc_html( $jwt_blurb ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $jwt_code ) : ?>
		<div class="jwt-pfcard__code">
			<span class="jwt-pfcard__code-label"><?php echo esc_html( (string) $attributes['codeLabel'] ); ?></span>
			<button
				type="button"
				class="jwt-pfcard__code-pill"
				data-jwt-copy="<?php echo esc_attr( $jwt_code ); ?>"
				aria-label="<?php echo esc_attr( sprintf( /* translators: %s: discount code. */ __( 'Salin kode %s', 'jwtrading' ), $jwt_code ) ); ?>"
			><?php echo esc_html( $jwt_code ); ?></button>
		</div>
	<?php endif; ?>

	<div class="jwt-pfcard__guide">
		<?php if ( '' !== $jwt_guide ) : ?>
			<span class="jwt-pfcard__guide-label"><?php echo esc_html( $jwt_guide ); ?></span>
		<?php endif; ?>
		<?php
		echo jwt_ytembed_html( // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper.
			array(
				'video'       => $attributes['guideVideoUrl'] ?? '',
				'posterId'    => $attributes['guidePosterId'] ?? 0,
				'label'       => '' !== $jwt_guide ? $jwt_guide : $jwt_name,
				'class'       => 'jwt-pfcard__video',
				'placeholder' => __( 'video menyusul', 'jwtrading' ),
				'red'         => true,
			)
		);
		?>
	</div>
</article>

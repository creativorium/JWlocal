<?php
/**
 * Render: jwt/propfirm-item — one partner card (prop firm / broker / tool).
 *
 * Anatomy: tinted head with name + blurb, then a two-column action row --
 * click-to-copy discount code on the left, affiliate "Explore" link on the
 * right -- then a guide sub-card holding that partner's walkthrough video.
 *
 * Some partners have no unique code (the code lives behind the link instead).
 * Those set `codeNote` rather than `code`: the left column then shows that text
 * as a plain label, so the row keeps its two-column shape and the Explore
 * button does not jump the full width on just one card.
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
$jwt_note    = trim( (string) ( $attributes['codeNote'] ?? '' ) );
$jwt_explore = trim( (string) ( $attributes['exploreText'] ?? '' ) );
$jwt_label   = trim( (string) ( $attributes['codeLabel'] ?? '' ) );
$jwt_guide   = trim( (string) ( $attributes['guideLabel'] ?? '' ) );
$jwt_variant = in_array( $attributes['variant'] ?? 'default', array( 'default', 'blue' ), true )
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

	<?php if ( '' !== $jwt_code || '' !== $jwt_note || '' !== $jwt_url ) : ?>
		<div class="jwt-pfcard__actions">
			<?php if ( '' !== $jwt_code ) : ?>
				<?php // The copy handler swaps the text of an inner <code> only, so the
					// label and icon survive the "Tersalin!" flash. ?>
				<button
					type="button"
					class="jwt-pfcard__copy"
					data-jwt-copy="<?php echo esc_attr( $jwt_code ); ?>"
					data-jwt-copied="<?php esc_attr_e( 'Tersalin!', 'jwtrading' ); ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %s: discount code. */ __( 'Salin kode %s', 'jwtrading' ), $jwt_code ) ); ?>"
				>
					<span class="jwt-pfcard__copy-text">
						<?php if ( '' !== $jwt_label ) : ?>
							<span class="jwt-pfcard__copy-label"><?php echo esc_html( $jwt_label ); ?></span>
						<?php endif; ?>
						<code class="jwt-pfcard__copy-code"><?php echo esc_html( $jwt_code ); ?></code>
					</span>
					<svg class="jwt-pfcard__copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="9" y="9" width="13" height="13" rx="2"/>
						<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
					</svg>
				</button>
			<?php elseif ( '' !== $jwt_note ) : ?>
				<span class="jwt-pfcard__codenote">
					<?php if ( '' !== $jwt_label ) : ?>
						<span class="jwt-pfcard__copy-label"><?php echo esc_html( $jwt_label ); ?></span>
					<?php endif; ?>
					<span class="jwt-pfcard__codenote-text"><?php echo esc_html( $jwt_note ); ?></span>
				</span>
			<?php endif; ?>

			<?php if ( '' !== $jwt_url && '' !== $jwt_explore ) : ?>
				<a
					class="jwt-pfcard__explore"
					href="<?php echo esc_url( $jwt_url ); ?>"
					target="_blank"
					rel="sponsored nofollow noopener"
				><?php echo esc_html( $jwt_explore ); ?> <span aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
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
			)
		);
		?>
	</div>
</article>

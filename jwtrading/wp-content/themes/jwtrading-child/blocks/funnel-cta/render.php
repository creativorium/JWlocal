<?php
/**
 * Render: jwt/funnel-cta — lone centred button, optionally followed by a note
 * and a social-proof row (overlapping avatars + a member count).
 *
 * The whole block renders nothing without button text, so a page can ship with
 * the CTA's destination still undecided by leaving the text empty.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_text = trim( (string) ( $attributes['buttonText'] ?? '' ) );
$jwt_note = trim( (string) ( $attributes['note'] ?? '' ) );
$jwt_proof = trim( (string) ( $attributes['proofText'] ?? '' ) );

if ( '' === $jwt_text && '' === $jwt_proof ) {
	return;
}

$jwt_url = trim( (string) ( $attributes['buttonUrl'] ?? '' ) );

// Optional avatar cluster: comma-separated attachment IDs. Falls back to three
// neutral discs so the row reads correctly before real member photos exist.
$jwt_avatars = array_slice(
	array_filter( array_map( 'absint', explode( ',', (string) ( $attributes['proofAvatars'] ?? '' ) ) ) ),
	0,
	5
);

$jwt_wrap = get_block_wrapper_attributes( array( 'class' => 'jwt-funnel-cta' ) );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php if ( '' !== $jwt_text ) : ?>
			<?php if ( '' !== $jwt_url ) : ?>
				<a class="jwt-btn jwt-btn--primary jwt-funnel-cta__btn" href="<?php echo esc_url( $jwt_url ); ?>"><?php echo esc_html( $jwt_text ); ?> →</a>
			<?php else : ?>
				<?php /* No destination decided yet — render the button inert rather than pointing it at "#". */ ?>
				<span class="jwt-btn jwt-btn--primary jwt-funnel-cta__btn is-pending" aria-disabled="true"><?php echo esc_html( $jwt_text ); ?> →</span>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( '' !== $jwt_note ) : ?>
			<p class="jwt-funnel-cta__note"><?php echo esc_html( $jwt_note ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $jwt_proof ) : ?>
			<div class="jwt-funnel-cta__proof">
				<?php if ( 'discord' === ( $attributes['proofIcon'] ?? '' ) ) : ?>
					<?php /* Community lives on Discord and members have no site login, so
					         there are no real avatars to show — the platform mark says
					         "where" far better than three anonymous discs. */ ?>
					<span class="jwt-funnel-cta__discord">
						<?php echo jwt_discord_mark( 'jwt-funnel-cta__discord-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
					</span>
				<?php else : ?>
				<span class="jwt-funnel-cta__avatars" aria-hidden="true">
					<?php if ( ! empty( $jwt_avatars ) ) : ?>
						<?php foreach ( $jwt_avatars as $jwt_avatar_id ) : ?>
							<?php
							echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
								$jwt_avatar_id,
								'thumbnail',
								false,
								array(
									'class'    => 'jwt-funnel-cta__avatar',
									'loading'  => 'lazy',
									'decoding' => 'async',
									'sizes'    => '28px',
									'alt'      => '',
								)
							);
							?>
						<?php endforeach; ?>
					<?php else : ?>
						<span class="jwt-funnel-cta__avatar is-placeholder"></span>
						<span class="jwt-funnel-cta__avatar is-placeholder"></span>
						<span class="jwt-funnel-cta__avatar is-placeholder"></span>
					<?php endif; ?>
				</span>
				<?php endif; ?>
				<span class="jwt-funnel-cta__proof-text"><?php echo esc_html( $jwt_proof ); ?></span>
			</div>
		<?php endif; ?>
	</div>
</section>

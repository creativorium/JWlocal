<?php
/**
 * Render: jwt/contact — contact cards + message form.
 * The form has no backend; on submit JS composes a WhatsApp message (name +
 * email + message) and opens wa.me — see [data-jwt-contact] in main.js.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_wa      = preg_replace( '/[^0-9]/', '', (string) ( $attributes['waNumber'] ?? '' ) );
$jwt_wa_disp = trim( (string) ( $attributes['waDisplay'] ?? '' ) );
$jwt_email   = trim( (string) ( $attributes['email'] ?? '' ) );
$jwt_loc     = trim( (string) ( $attributes['location'] ?? '' ) );
$jwt_wrap    = get_block_wrapper_attributes( array( 'class' => 'jwt-contact' ) );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>

		<div class="jwt-contact__grid">
			<div class="jwt-contact__info">
				<?php if ( '' !== $jwt_wa ) : ?>
					<a class="jwt-contact__card jwt-contact__card--wa" href="https://wa.me/<?php echo esc_attr( $jwt_wa ); ?>" target="_blank" rel="noopener">
						<span class="jwt-contact__icon jwt-contact__icon--wa" aria-hidden="true">
							<svg viewBox="0 0 32 32" fill="currentColor"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.1.6 4.1 1.6 5.9L4 29l8.3-1.6c1.7.9 3.6 1.4 5.7 1.4 6.6 0 12-5.4 12-12S22.6 3 16 3zm0 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7.7.7-3.6-.2-.4c-1-1.6-1.5-3.4-1.5-5.3 0-5.4 4.4-9.8 9.9-9.8 5.4 0 9.8 4.4 9.8 9.8s-4.3 9.8-9.8 9.8zm5.4-7.3c-.3-.1-1.8-.9-2-1s-.5-.1-.7.1c-.2.3-.8 1-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5s-.7-1.7-1-2.3c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1.1 2.8 1.2 3c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.3z"/></svg>
						</span>
						<div>
							<span class="jwt-contact__label"><?php esc_html_e( 'Paling Cepat', 'jwtrading' ); ?></span>
							<strong class="jwt-contact__value">WhatsApp</strong>
							<?php if ( '' !== $jwt_wa_disp ) : ?><span class="jwt-contact__sub"><?php echo esc_html( $jwt_wa_disp ); ?></span><?php endif; ?>
							<span class="jwt-contact__link jwt-contact__link--wa"><?php esc_html_e( 'Mulai Chat →', 'jwtrading' ); ?></span>
						</div>
					</a>
				<?php endif; ?>

				<?php if ( '' !== $jwt_email ) : ?>
					<a class="jwt-contact__card" href="mailto:<?php echo esc_attr( $jwt_email ); ?>">
						<span class="jwt-contact__icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
						</span>
						<div>
							<span class="jwt-contact__label"><?php esc_html_e( 'Email', 'jwtrading' ); ?></span>
							<strong class="jwt-contact__value"><?php echo esc_html( $jwt_email ); ?></strong>
							<?php if ( '' !== trim( (string) ( $attributes['emailNote'] ?? '' ) ) ) : ?><span class="jwt-contact__sub"><?php echo esc_html( $attributes['emailNote'] ); ?></span><?php endif; ?>
						</div>
					</a>
				<?php endif; ?>

				<?php if ( '' !== $jwt_loc ) : ?>
					<div class="jwt-contact__card">
						<span class="jwt-contact__icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
						</span>
						<div>
							<span class="jwt-contact__label"><?php esc_html_e( 'Lokasi', 'jwtrading' ); ?></span>
							<strong class="jwt-contact__value"><?php echo esc_html( $jwt_loc ); ?></strong>
							<?php if ( '' !== trim( (string) ( $attributes['locationNote'] ?? '' ) ) ) : ?><span class="jwt-contact__sub"><?php echo esc_html( $attributes['locationNote'] ); ?></span><?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php
				$jwt_social = jwt_social_links_html( true );
				if ( '' !== $jwt_social ) :
					?>
					<div class="jwt-contact__follow">
						<span class="jwt-contact__label"><?php esc_html_e( 'Ikuti Kami', 'jwtrading' ); ?></span>
						<?php echo $jwt_social; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="jwt-contact__formwrap">
				<?php if ( '' !== trim( (string) ( $attributes['formTitle'] ?? '' ) ) ) : ?>
					<h3 class="jwt-contact__form-title"><?php echo esc_html( $attributes['formTitle'] ); ?></h3>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['formNote'] ?? '' ) ) ) : ?>
					<p class="jwt-contact__form-note"><?php echo esc_html( $attributes['formNote'] ); ?></p>
				<?php endif; ?>

				<form class="jwt-contact__form" data-jwt-contact data-wa="<?php echo esc_attr( $jwt_wa ); ?>">
					<div class="jwt-contact__fields">
						<label class="jwt-contact__field">
							<span><?php esc_html_e( 'Nama', 'jwtrading' ); ?></span>
							<input type="text" name="nama" required placeholder="<?php esc_attr_e( 'Nama kamu', 'jwtrading' ); ?>">
						</label>
						<label class="jwt-contact__field">
							<span><?php esc_html_e( 'Email', 'jwtrading' ); ?></span>
							<input type="email" name="email" required placeholder="<?php esc_attr_e( 'Email aktif', 'jwtrading' ); ?>">
						</label>
					</div>
					<label class="jwt-contact__field">
						<span><?php esc_html_e( 'Pesan', 'jwtrading' ); ?></span>
						<textarea name="pesan" rows="5" required placeholder="<?php esc_attr_e( 'Tulis pesanmu di sini...', 'jwtrading' ); ?>"></textarea>
					</label>
					<button type="submit" class="jwt-btn jwt-btn--primary jwt-contact__submit"><?php esc_html_e( 'Kirim Pesan →', 'jwtrading' ); ?></button>
					<?php if ( '' !== trim( (string) ( $attributes['formFootnote'] ?? '' ) ) && '' !== $jwt_wa ) : ?>
						<p class="jwt-contact__form-footnote"><?php echo esc_html( $attributes['formFootnote'] ); ?> <a href="https://wa.me/<?php echo esc_attr( $jwt_wa ); ?>" target="_blank" rel="noopener">WhatsApp</a></p>
					<?php endif; ?>
				</form>
			</div>
		</div>
	</div>
</section>

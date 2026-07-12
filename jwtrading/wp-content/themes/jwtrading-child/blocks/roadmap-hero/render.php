<?php
/**
 * Render: jwt/roadmap-hero — roadmap image + lead form.
 *
 * The form posts to the `jwt_roadmap_optin` AJAX action (JWT_Roadmap in
 * jwtrading-core): it tags the lead in Kit via `jw_kit_tag_subscriber`
 * (same first_name/last_name/email contract as the preview gate) and returns
 * the PDF URL, which main.js opens after showing the success message.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_img_id  = (int) ( $attributes['imageId'] ?? 0 );
$jwt_pdf     = trim( (string) ( $attributes['pdfUrl'] ?? '' ) );
$jwt_form_id = sanitize_key( $attributes['formId'] ?? 'trader_roadmap' );
$jwt_nonce   = wp_create_nonce( 'jwt_roadmap_optin' );
$jwt_wrap    = get_block_wrapper_attributes( array( 'class' => 'jwt-roadmap-hero' ) );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-roadmap-hero__grid">
			<div class="jwt-roadmap-hero__media">
				<?php
				if ( $jwt_img_id ) {
					echo wp_get_attachment_image( $jwt_img_id, 'large', false, array( 'class' => 'jwt-roadmap-hero__img', 'loading' => 'eager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>

			<div class="jwt-roadmap-hero__panel">
				<?php if ( '' !== trim( (string) $attributes['eyebrow'] ) ) : ?>
					<span class="jwt-roadmap-hero__eyebrow"><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
				<?php endif; ?>

				<?php if ( '' !== trim( (string) $attributes['title'] ) ) : ?>
					<h1 class="jwt-roadmap-hero__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h1>
				<?php endif; ?>

				<?php if ( '' !== trim( (string) $attributes['lead'] ) ) : ?>
					<p class="jwt-roadmap-hero__lead"><?php echo wp_kses_post( $attributes['lead'] ); ?></p>
				<?php endif; ?>

				<form class="jwt-roadmap-form" data-jwt-roadmap data-form-id="<?php echo esc_attr( $jwt_form_id ); ?>" data-pdf="<?php echo esc_url( $jwt_pdf ); ?>" data-redirect="<?php echo esc_url( (string) ( $attributes['redirectUrl'] ?? '' ) ); ?>">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( $jwt_nonce ); ?>">

					<label class="jwt-roadmap-form__field">
						<span><?php esc_html_e( 'Nama Lengkap', 'jwtrading' ); ?></span>
						<input type="text" name="first_name" required placeholder="<?php echo esc_attr( $attributes['namePlaceholder'] ); ?>">
					</label>
					<label class="jwt-roadmap-form__field">
						<span><?php esc_html_e( 'Email', 'jwtrading' ); ?></span>
						<input type="email" name="email" required placeholder="<?php echo esc_attr( $attributes['emailPlaceholder'] ); ?>">
					</label>

					<button type="submit" class="jwt-btn jwt-btn--primary jwt-roadmap-form__submit"><?php echo esc_html( $attributes['submitText'] ); ?> →</button>

					<?php if ( '' !== trim( (string) $attributes['footnote'] ) ) : ?>
						<p class="jwt-roadmap-form__footnote"><?php echo esc_html( $attributes['footnote'] ); ?></p>
					<?php endif; ?>

					<div class="jwt-roadmap-form__msg" role="status" aria-live="polite" data-success="<?php echo esc_attr( $attributes['successText'] ); ?>"></div>
				</form>
			</div>
		</div>
	</div>
</section>

<?php
/**
 * Render: jwt/optin-form — funnel step 1 (nama / email / WhatsApp).
 *
 * Posts to the `jwt_funnel_optin` AJAX action (JWT_Funnel in jwtrading-core),
 * which tags the lead in Kit and returns the application-page URL carrying the
 * lead token. All three fields are required: the team filters over WhatsApp, so
 * a lead without a number is unusable.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrap = get_block_wrapper_attributes( array( 'class' => 'jwt-optin' ) );
$jwt_sec  = class_exists( 'JWT_Funnel' ) ? JWT_Funnel::form_security_html() : '';
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<form class="jwt-optin__card" data-jwt-optin novalidate>
			<?php echo $jwt_sec; // phpcs:ignore WordPress.Security.EscapeOutput -- built in the plugin. ?>
			<input type="hidden" name="source" value="<?php echo esc_url( home_url( add_query_arg( array() ) ) ); ?>">

			<?php if ( '' !== trim( (string) $attributes['title'] ) ) : ?>
				<p class="jwt-optin__title"><?php echo wp_kses_post( $attributes['title'] ); ?></p>
			<?php endif; ?>

			<div class="jwt-optin__fields">
				<label class="jwt-optin__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Nama lengkap', 'jwtrading' ); ?></span>
					<input type="text" name="name" autocomplete="name" required placeholder="<?php echo esc_attr( $attributes['namePlaceholder'] ); ?>">
				</label>
				<label class="jwt-optin__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Email aktif', 'jwtrading' ); ?></span>
					<input type="email" name="email" autocomplete="email" required placeholder="<?php echo esc_attr( $attributes['emailPlaceholder'] ); ?>">
				</label>
				<label class="jwt-optin__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Nomor WhatsApp', 'jwtrading' ); ?></span>
					<input type="tel" name="phone" autocomplete="tel" inputmode="tel" required placeholder="<?php echo esc_attr( $attributes['phonePlaceholder'] ); ?>">
				</label>
			</div>

			<button type="submit" class="jwt-btn jwt-btn--primary jwt-optin__submit"><?php echo esc_html( $attributes['submitText'] ); ?> →</button>

			<div class="jwt-optin__msg" role="status" aria-live="polite"></div>

			<?php if ( '' !== trim( (string) $attributes['note'] ) ) : ?>
				<p class="jwt-optin__note">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
					<?php echo esc_html( $attributes['note'] ); ?>
				</p>
			<?php endif; ?>
		</form>
	</div>
</section>

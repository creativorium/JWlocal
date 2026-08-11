<?php
/**
 * Render: jwt/quiz — the mentorship application form.
 *
 * Card shell: progress bar + one visible jwt/quiz-question at a time + nav row.
 * Stepping, numbering and validation are handled by [data-jwt-quiz] in main.js;
 * on submit it POSTs the answers to `jwt_funnel_apply` (JWT_Funnel), pushes
 * `application_submitted` to the dataLayer (GTM owns the conversion trigger),
 * then follows the redirect to the token-gated thank-you page.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrap  = get_block_wrapper_attributes( array( 'class' => 'jwt-quiz-section' ) );
$jwt_sec   = class_exists( 'JWT_Funnel' ) ? JWT_Funnel::form_security_html() : '';
$jwt_token = isset( $_GET['lead'] ) ? sanitize_text_field( wp_unslash( $_GET['lead'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>

		<div class="jwt-quiz__cue" aria-hidden="true">↓</div>

		<form
			class="jwt-quiz"
			data-jwt-quiz
			data-step-label="<?php echo esc_attr( (string) $attributes['stepLabel'] ); ?>"
			data-next-text="<?php echo esc_attr( (string) $attributes['nextText'] ); ?>"
			data-submit-text="<?php echo esc_attr( (string) $attributes['submitText'] ); ?>"
			novalidate
		>
			<?php echo $jwt_sec; // phpcs:ignore WordPress.Security.EscapeOutput -- built in the plugin. ?>
			<input type="hidden" name="token" value="<?php echo esc_attr( $jwt_token ); ?>">

			<div class="jwt-quiz__progress"><span class="jwt-quiz__progress-bar" data-jwt-quiz-progress></span></div>

			<div class="jwt-quiz__body">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
			</div>

			<div class="jwt-quiz__msg" role="status" aria-live="polite"></div>

			<div class="jwt-quiz__nav">
				<button type="button" class="jwt-quiz__back" data-jwt-quiz-back hidden>← <?php echo esc_html( (string) $attributes['backText'] ); ?></button>
				<button type="submit" class="jwt-btn jwt-btn--primary jwt-quiz__next" data-jwt-quiz-next><?php echo esc_html( (string) $attributes['nextText'] ); ?> →</button>
			</div>
		</form>
	</div>
</section>

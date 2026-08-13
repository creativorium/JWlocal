<?php
/**
 * Render: jwt/quiz-question — one step of the application form.
 *
 * Steps are plain fieldsets rendered server-side; the parent card (jwt/quiz)
 * shows one at a time and drives the progress bar + nav from main.js. The
 * step number is NOT baked in here — an inner block has no way to know its own
 * index, so the parent's JS numbers them, which also keeps the numbering right
 * when the client adds or removes a question in the editor.
 *
 * Choices are real <input type="radio"> so the answer is keyboard-navigable and
 * survives with JS disabled; the pill look is pure CSS on the sibling <span>.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_q        = trim( (string) ( $attributes['question'] ?? '' ) );
$jwt_type     = in_array( $attributes['type'] ?? 'choice', array( 'choice', 'scale', 'text' ), true ) ? $attributes['type'] : 'choice';
$jwt_required = ! empty( $attributes['required'] );
$jwt_field    = wp_unique_id( 'jwt-q' );
?>
<fieldset class="jwt-quiz__step" data-jwt-quiz-step data-question="<?php echo esc_attr( $jwt_q ); ?>" data-type="<?php echo esc_attr( $jwt_type ); ?>"<?php echo $jwt_required ? ' data-required="1"' : ''; ?> hidden>
	<legend class="screen-reader-text"><?php echo esc_html( $jwt_q ); ?></legend>

	<p class="jwt-quiz__step-label" data-jwt-quiz-label></p>

	<?php if ( '' !== $jwt_q ) : ?>
		<p class="jwt-quiz__question"><?php echo wp_kses_post( $jwt_q ); ?></p>
	<?php endif; ?>

	<?php if ( 'text' === $jwt_type ) : ?>
		<div class="jwt-quiz__answer jwt-quiz__answer--text">
			<textarea
				class="jwt-quiz__textarea"
				name="<?php echo esc_attr( $jwt_field ); ?>"
				rows="4"
				placeholder="<?php echo esc_attr( (string) $attributes['placeholder'] ); ?>"
				<?php echo $jwt_required ? 'required' : ''; ?>
			></textarea>
		</div>

	<?php elseif ( 'scale' === $jwt_type ) : ?>
		<?php
		$jwt_min = max( 0, (int) $attributes['scaleMin'] );
		$jwt_max = max( $jwt_min + 1, (int) $attributes['scaleMax'] );
		?>
		<div class="jwt-quiz__answer jwt-quiz__answer--scale">
			<?php for ( $jwt_i = $jwt_min; $jwt_i <= $jwt_max; $jwt_i++ ) : ?>
				<label class="jwt-quiz__scale">
					<input type="radio" name="<?php echo esc_attr( $jwt_field ); ?>" value="<?php echo esc_attr( (string) $jwt_i ); ?>" <?php echo $jwt_required ? 'required' : ''; ?>>
					<span><?php echo esc_html( (string) $jwt_i ); ?></span>
				</label>
			<?php endfor; ?>
		</div>

	<?php else : ?>
		<?php $jwt_options = array_values( array_filter( array_map( 'trim', explode( '|', (string) $attributes['options'] ) ) ) ); ?>
		<div class="jwt-quiz__answer jwt-quiz__answer--choice">
			<?php foreach ( $jwt_options as $jwt_option ) : ?>
				<label class="jwt-quiz__option">
					<input type="radio" name="<?php echo esc_attr( $jwt_field ); ?>" value="<?php echo esc_attr( $jwt_option ); ?>" <?php echo $jwt_required ? 'required' : ''; ?>>
					<span><?php echo esc_html( $jwt_option ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</fieldset>

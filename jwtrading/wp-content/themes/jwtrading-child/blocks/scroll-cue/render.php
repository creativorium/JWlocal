<?php
/**
 * Render: jwt/scroll-cue — a fixed marker on the right edge telling people the
 * page continues, which retires itself once the thing it points at is on screen.
 *
 * The application page opens with a headline, a video and three paragraphs of
 * preamble before the form. On a phone that is several screens of reading with
 * no sign that a form exists at all, so the cue carries the page's purpose down
 * with the reader.
 *
 * It is a button, not decoration: clicking jumps to the target, which is faster
 * than scrolling past copy someone has decided not to read. Hidden from
 * assistive tech — the target is reachable by normal navigation, so announcing
 * it would only add noise.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_target = trim( (string) ( $attributes['target'] ?? '' ) );
$jwt_label  = trim( (string) ( $attributes['label'] ?? '' ) );

if ( '' === $jwt_target ) {
	return;
}
?>
<button
	type="button"
	class="jwt-scrollcue"
	data-jwt-scrollcue
	data-target="<?php echo esc_attr( $jwt_target ); ?>"
	aria-hidden="true"
	tabindex="-1"
>
	<?php if ( '' !== $jwt_label ) : ?>
		<span class="jwt-scrollcue__label"><?php echo esc_html( $jwt_label ); ?></span>
	<?php endif; ?>
	<span class="jwt-scrollcue__track"><span class="jwt-scrollcue__drop"></span></span>
</button>

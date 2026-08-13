<?php
/**
 * Render: jwt/propfirm — one partner section (heading + grid of cards).
 *
 * The page stacks several of these ("Prop Firm Terpercaya", "Broker yang Kami
 * Rekomendasikan", "Tools untuk Backtest & Journaling", "Trade Copier"), each
 * with its own SECTION nn eyebrow. The grid is two columns and centres itself
 * when a section only holds one card, matching the layout PDF.
 *
 * @var array  $attributes
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrap = get_block_wrapper_attributes( array( 'class' => 'jwt-propfirm' ) );
$jwt_disc = trim( (string) ( $attributes['disclosure'] ?? '' ) );

// One card in the section → narrow, centred column (Broker / Tools sections).
// Match the card's opening tag specifically: `jwt-pfcard` alone also matches
// every child element's class (__head, __code, __guide…) and would never be 1.
$jwt_count = substr_count( (string) $content, '<article class="jwt-pfcard' );
$jwt_grid  = 'jwt-propfirm__grid' . ( $jwt_count < 2 ? ' is-single' : '' );
?>
<section <?php echo $jwt_wrap; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>

		<div class="<?php echo esc_attr( $jwt_grid ); ?>">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-rendered inner blocks. ?>
		</div>

		<?php if ( '' !== $jwt_disc ) : ?>
			<p class="jwt-propfirm__disclosure"><?php echo esc_html( $jwt_disc ); ?></p>
		<?php endif; ?>
	</div>
</section>

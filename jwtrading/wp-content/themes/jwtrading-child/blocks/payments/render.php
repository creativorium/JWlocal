<?php
/**
 * Render: jwt/payments — payment-trust panel.
 * `methods` and `points` are pipe-separated strings (e.g. "VISA|QRIS|GoPay").
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_split = static function ( $val ) {
	$val = (string) $val;
	if ( '' === trim( $val ) ) {
		return array();
	}
	return array_filter( array_map( 'trim', explode( '|', $val ) ), static function ( $v ) {
		return '' !== $v;
	} );
};

$jwt_methods = $jwt_split( $attributes['methods'] ?? '' );
$jwt_points  = $jwt_split( $attributes['points'] ?? '' );
$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-payments' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-payments__box" data-jwt-reveal>
			<div class="jwt-payments__left">
				<?php if ( '' !== trim( (string) ( $attributes['title'] ?? '' ) ) ) : ?>
					<h2 class="jwt-payments__title"><?php echo esc_html( $attributes['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) ( $attributes['lead'] ?? '' ) ) ) : ?>
					<p class="jwt-payments__lead"><?php echo esc_html( $attributes['lead'] ); ?></p>
				<?php endif; ?>
				<?php if ( $jwt_methods ) : ?>
					<div class="jwt-payments__methods">
						<?php foreach ( $jwt_methods as $jwt_m ) : ?>
							<span class="jwt-payments__method"><?php echo esc_html( $jwt_m ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( $jwt_points ) : ?>
				<ul class="jwt-payments__points">
					<?php foreach ( $jwt_points as $jwt_p ) : ?>
						<li class="jwt-payments__point"><?php echo esc_html( $jwt_p ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>

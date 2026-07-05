<?php
/**
 * Render: jwt/hero
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_tag     = in_array( $attributes['titleTag'], array( 'h1', 'h2', 'p' ), true ) ? $attributes['titleTag'] : 'h1';
$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-hero' ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container" data-jwt-reveal>
		<?php if ( '' !== trim( $attributes['eyebrow'] ) ) : ?>
			<span class="jwt-badge jwt-hero__badge"><span class="jwt-eyebrow__dot"></span><?php echo esc_html( $attributes['eyebrow'] ); ?></span>
		<?php endif; ?>

		<?php if ( '' !== trim( $attributes['title'] ) ) : ?>
			<<?php echo $jwt_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="jwt-hero__title"><?php echo wp_kses_post( $attributes['title'] ); ?></<?php echo $jwt_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php endif; ?>

		<?php if ( '' !== trim( $attributes['lead'] ) ) : ?>
			<p class="jwt-hero__lead"><?php echo wp_kses_post( $attributes['lead'] ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== trim( $attributes['primaryText'] ) || '' !== trim( $attributes['secondaryText'] ) ) : ?>
			<div class="jwt-hero__actions">
				<?php if ( '' !== trim( $attributes['primaryText'] ) ) : ?>
					<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $attributes['primaryUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['primaryText'] ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['secondaryText'] ) ) : ?>
					<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( $attributes['secondaryUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['secondaryText'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== trim( $attributes['note'] ) ) : ?>
			<p class="jwt-hero__note"><?php echo esc_html( $attributes['note'] ); ?></p>
		<?php endif; ?>

		<?php
		if ( '' !== trim( $attributes['chips'] ) ) :
			$jwt_chips = array_filter( array_map( 'trim', preg_split( '/[•|]/u', $attributes['chips'] ) ) );
			?>
			<div class="jwt-hero__chips">
				<?php foreach ( $jwt_chips as $jwt_chip ) : ?>
					<span class="jwt-pill"><?php echo esc_html( $jwt_chip ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

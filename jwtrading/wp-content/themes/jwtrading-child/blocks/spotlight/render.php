<?php
/**
 * Render: jwt/spotlight — 2-col product panel with a tilted mock cover
 * (or a real image when imageId is set).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_classes = 'jwt-spotlight' . ( $attributes['reverse'] ? ' is-reverse' : '' );
$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => $jwt_classes ) );
$jwt_chips   = array_filter( array_map( 'trim', preg_split( '/[|•]/u', (string) $attributes['chips'] ) ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<div class="jwt-spotlight__panel" data-jwt-reveal>
			<div class="jwt-spotlight__media">
				<?php if ( $attributes['imageId'] ) : ?>
					<?php echo wp_get_attachment_image( (int) $attributes['imageId'], 'large', false, array( 'loading' => 'lazy', 'class' => 'jwt-spotlight__img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<div class="jwt-spotlight__cover">
						<div class="jwt-spotlight__cover-glow"></div>
						<div class="jwt-spotlight__cover-top">
							<?php if ( '' !== trim( $attributes['coverLabel'] ) ) : ?>
								<div class="jwt-spotlight__cover-label"><?php echo esc_html( $attributes['coverLabel'] ); ?></div>
							<?php endif; ?>
						</div>
						<div class="jwt-spotlight__cover-title"><?php echo esc_html( $attributes['coverTitle'] ); ?></div>
					</div>
				<?php endif; ?>
			</div>

			<div class="jwt-spotlight__body">
				<?php if ( '' !== trim( $attributes['badge'] ) ) : ?>
					<span class="jwt-badge"><?php echo esc_html( $attributes['badge'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['title'] ) ) : ?>
					<h3 class="jwt-spotlight__title"><?php echo wp_kses_post( $attributes['title'] ); ?></h3>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['text'] ) ) : ?>
					<p class="jwt-spotlight__text"><?php echo wp_kses_post( $attributes['text'] ); ?></p>
				<?php endif; ?>
				<?php if ( $jwt_chips ) : ?>
					<div class="jwt-spotlight__chips">
						<?php foreach ( $jwt_chips as $jwt_chip ) : ?>
							<span class="jwt-pill"><?php echo esc_html( $jwt_chip ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( '' !== trim( $attributes['buttonText'] ) ) : ?>
					<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( $attributes['buttonUrl'] ?: '#' ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

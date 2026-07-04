<?php
/**
 * Render: jwt/course-grid — server-side WooCommerce product grid.
 * Products are the source of truth (title, price, image, link); the client
 * only curates which/how many appear.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$jwt_wrapper = get_block_wrapper_attributes( array( 'class' => 'jwt-courses' ) );

if ( ! function_exists( 'wc_get_products' ) ) {
	?>
	<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<div class="jwt-container">
			<p class="jwt-courses__empty"><?php esc_html_e( 'WooCommerce belum aktif — grid kelas akan tampil di sini.', 'jwtrading' ); ?></p>
		</div>
	</section>
	<?php
	return;
}

$jwt_args = array(
	'status'  => 'publish',
	'limit'   => max( 1, min( 12, (int) $attributes['count'] ) ),
	'orderby' => 'menu_order',
	'order'   => 'ASC',
);

if ( '' !== trim( $attributes['category'] ) ) {
	$jwt_args['category'] = array( sanitize_title( $attributes['category'] ) );
}

$jwt_products = wc_get_products( $jwt_args );
$jwt_columns  = max( 1, min( 4, (int) $attributes['columns'] ) );
?>
<section <?php echo $jwt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="jwt-container">
		<?php echo jwt_section_header_html( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>

		<?php if ( empty( $jwt_products ) ) : ?>
			<p class="jwt-courses__empty"><?php esc_html_e( 'Belum ada produk yang cocok. Tambahkan produk WooCommerce atau ubah filter kategori.', 'jwtrading' ); ?></p>
		<?php else : ?>
			<div class="jwt-courses__grid" style="--jwt-cols:<?php echo (int) $jwt_columns; ?>">
				<?php foreach ( $jwt_products as $jwt_product ) : ?>
					<article class="jwt-card jwt-course" data-jwt-reveal>
						<?php if ( $jwt_product->get_image_id() ) : ?>
							<a class="jwt-course__media" href="<?php echo esc_url( $jwt_product->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
								<?php echo wp_get_attachment_image( $jwt_product->get_image_id(), 'woocommerce_thumbnail', false, array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
						<?php endif; ?>
						<div class="jwt-course__body">
							<h3 class="jwt-course__title">
								<a href="<?php echo esc_url( $jwt_product->get_permalink() ); ?>"><?php echo esc_html( $jwt_product->get_name() ); ?></a>
							</h3>
							<div class="jwt-course__price"><?php echo wp_kses_post( $jwt_product->get_price_html() ); ?></div>
							<?php if ( $jwt_product->get_short_description() ) : ?>
								<p class="jwt-course__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $jwt_product->get_short_description() ), 20 ) ); ?></p>
							<?php endif; ?>
							<a class="jwt-btn jwt-btn--primary jwt-course__btn" href="<?php echo esc_url( $jwt_product->get_permalink() ); ?>"><?php echo esc_html( $attributes['buttonText'] ?: __( 'Lihat Detail', 'jwtrading' ) ); ?></a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

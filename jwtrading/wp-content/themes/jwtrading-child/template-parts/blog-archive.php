<?php
/**
 * Blog archive body — hero header, category filter (JS instant + real archive
 * links), the card grid, and pagination. Shared by home.php + archive.php.
 *
 * @param array $args { title, lead, current (active category slug, '' on index) }
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

$jwt_title   = $args['title'] ?? esc_html__( 'Blog', 'jwtrading' );
$jwt_lead    = $args['lead'] ?? '';
$jwt_current = $args['current'] ?? '';

$jwt_cats = get_categories(
	array(
		'parent'     => 0,
		'hide_empty' => true,
	)
);
?>
<main class="jwt-blog">
	<div class="jwt-container">
		<header class="jwt-blog__head">
			<span class="jwt-badge jwt-blog__eyebrow"><span class="jwt-eyebrow__dot"></span><?php esc_html_e( 'Blog', 'jwtrading' ); ?></span>
			<h1 class="jwt-blog__title"><?php echo esc_html( $jwt_title ); ?></h1>
			<?php if ( '' !== $jwt_lead ) : ?>
				<p class="jwt-blog__lead"><?php echo esc_html( $jwt_lead ); ?></p>
			<?php endif; ?>
		</header>

		<div class="jwt-blog__toolbar">
			<?php if ( ! empty( $jwt_cats ) ) : ?>
				<nav class="jwt-filter" data-jwt-filter aria-label="<?php esc_attr_e( 'Filter kategori', 'jwtrading' ); ?>">
					<a class="jwt-filter__tab<?php echo '' === $jwt_current ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_permalink( (int) get_option( 'page_for_posts' ) ) ); ?>" data-filter="*"><?php esc_html_e( 'Semua', 'jwtrading' ); ?></a>
					<?php foreach ( $jwt_cats as $jwt_c ) : ?>
						<?php if ( 'uncategorized' === $jwt_c->slug ) { continue; } ?>
						<a class="jwt-filter__tab<?php echo $jwt_current === $jwt_c->slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $jwt_c ) ); ?>" data-filter="<?php echo esc_attr( $jwt_c->slug ); ?>"><?php echo esc_html( $jwt_c->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<div class="jwt-blog__search">
				<svg class="jwt-blog__search-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
				<input type="search" data-jwt-search placeholder="<?php esc_attr_e( 'Cari artikel…', 'jwtrading' ); ?>" aria-label="<?php esc_attr_e( 'Cari artikel', 'jwtrading' ); ?>">
			</div>
		</div>

		<?php if ( have_posts() ) : ?>
			<div class="jwt-blog__grid" data-jwt-filter-grid>
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/blog-card' );
				endwhile;
				?>
			</div>

			<p class="jwt-blog__empty" data-jwt-noresults hidden><?php esc_html_e( 'Tidak ada artikel yang cocok.', 'jwtrading' ); ?></p>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => esc_html__( 'Sebelumnya', 'jwtrading' ),
					'next_text' => esc_html__( 'Berikutnya', 'jwtrading' ),
					'class'     => 'jwt-pagination',
				)
			);
			?>
		<?php else : ?>
			<p class="jwt-blog__empty"><?php esc_html_e( 'Belum ada artikel di sini.', 'jwtrading' ); ?></p>
		<?php endif; ?>
	</div>
</main>

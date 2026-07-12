<?php
/**
 * Single blog article: hero header → featured image → body → related → CTA.
 *
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$jwt_cat        = jwt_post_primary_category();
	$jwt_posts_page = (int) get_option( 'page_for_posts' );
	?>
	<main class="jwt-article">
		<header class="jwt-article__head">
			<div class="jwt-container jwt-article__head-inner">
				<?php if ( $jwt_posts_page ) : ?>
					<a class="jwt-article__back" href="<?php echo esc_url( get_permalink( $jwt_posts_page ) ); ?>">&larr; <?php esc_html_e( 'Semua Artikel', 'jwtrading' ); ?></a>
				<?php endif; ?>

				<?php if ( $jwt_cat ) : ?>
					<a class="jwt-article__chip" href="<?php echo esc_url( get_category_link( $jwt_cat ) ); ?>"><?php echo esc_html( $jwt_cat->name ); ?></a>
				<?php endif; ?>

				<h1 class="jwt-article__title"><?php the_title(); ?></h1>

				<?php if ( has_excerpt() ) : ?>
					<p class="jwt-article__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<div class="jwt-article__meta">
					<?php echo get_avatar( get_the_author_meta( 'ID' ), 40, '', '', array( 'class' => 'jwt-article__avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="jwt-article__author"><?php the_author(); ?></span>
					<span class="jwt-article__dot" aria-hidden="true">·</span>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( jwt_blog_date() ); ?></time>
					<span class="jwt-article__dot" aria-hidden="true">·</span>
					<span><?php echo esc_html( jwt_read_time() ); ?></span>
				</div>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="jwt-article__figure">
				<div class="jwt-container">
					<?php the_post_thumbnail( 'large', array( 'class' => 'jwt-article__img', 'alt' => '' ) ); ?>
				</div>
			</figure>
		<?php endif; ?>

		<div class="jwt-container">
			<div class="jwt-article__body single-content">
				<?php the_content(); ?>
			</div>
		</div>

		<?php
		// Related posts — same primary category, newest first.
		if ( $jwt_cat ) :
			$jwt_related = new WP_Query(
				array(
					'post_type'           => 'post',
					'posts_per_page'      => 3,
					'post__not_in'        => array( get_the_ID() ),
					'cat'                 => $jwt_cat->term_id,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			);
			if ( $jwt_related->have_posts() ) :
				?>
				<section class="jwt-related">
					<div class="jwt-container">
						<h2 class="jwt-related__title"><?php esc_html_e( 'Artikel Lainnya', 'jwtrading' ); ?></h2>
						<div class="jwt-blog__grid">
							<?php
							while ( $jwt_related->have_posts() ) :
								$jwt_related->the_post();
								get_template_part( 'template-parts/blog-card' );
							endwhile;
							?>
						</div>
					</div>
				</section>
				<?php
			endif;
			wp_reset_postdata();
		endif;
		?>

		<section class="jwt-article-cta">
			<div class="jwt-container">
				<div class="jwt-article-cta__card">
					<h2><?php esc_html_e( 'Siap belajar ICT dengan serius?', 'jwtrading' ); ?></h2>
					<p><?php esc_html_e( 'Gabung JW Trading Bootcamp — metodologi ICT, prop firm, dan komunitas 17.000+ trader.', 'jwtrading' ); ?></p>
					<div class="jwt-article-cta__actions">
						<a class="jwt-btn jwt-btn--primary" href="<?php echo esc_url( home_url( '/bootcamp/' ) ); ?>"><?php esc_html_e( 'Akses Bootcamp', 'jwtrading' ); ?></a>
						<a class="jwt-btn jwt-btn--ghost" href="<?php echo esc_url( home_url( '/discord/' ) ); ?>"><?php esc_html_e( 'Gabung Discord', 'jwtrading' ); ?></a>
					</div>
				</div>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();

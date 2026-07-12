<?php
/**
 * Blog card — used by the archive grid and the "related posts" row.
 * Call inside the loop (expects the current $post).
 *
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

$jwt_cat  = jwt_post_primary_category();
$jwt_cats = jwt_post_category_slugs();
?>
<article class="jwt-card" data-cats="<?php echo esc_attr( $jwt_cats ); ?>" data-search="<?php echo esc_attr( strtolower( get_the_title() . ' ' . get_the_excerpt() ) ); ?>">
	<a class="jwt-card__media" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'large', array( 'class' => 'jwt-card__img', 'loading' => 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<span class="jwt-card__placeholder" aria-hidden="true">JW</span>
		<?php endif; ?>
		<?php if ( $jwt_cat ) : ?>
			<span class="jwt-card__chip"><?php echo esc_html( $jwt_cat->name ); ?></span>
		<?php endif; ?>
	</a>

	<div class="jwt-card__body">
		<h3 class="jwt-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<?php if ( has_excerpt() ) : ?>
			<p class="jwt-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
		<?php endif; ?>

		<div class="jwt-card__meta">
			<?php echo jwt_blog_author_avatar_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
			<span class="jwt-card__author"><?php echo esc_html( jwt_blog_author_name() ); ?></span>
			<span class="jwt-card__dot" aria-hidden="true">·</span>
			<time class="jwt-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( jwt_blog_date() ); ?></time>
		</div>
	</div>
</article>

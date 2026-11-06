<?php
/**
 * Blog post card — archive grid + related posts.
 *
 * Figma: 119:5785.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$category = get_the_category();
?>
<article class="tb-post-card">
	<a href="<?php the_permalink(); ?>" class="tb-post-card__thumb">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'large' ); ?>
		<?php else : ?>
			<span class="tb-post-card__thumb-placeholder" aria-hidden="true"></span>
		<?php endif; ?>
	</a>
	<div class="tb-post-card__body">
		<?php if ( $category ) : ?>
		<a href="<?php echo esc_url( get_category_link( $category[0] ) ); ?>" class="tb-post-card__tag">
			<?php echo esc_html( $category[0]->name ); ?>
		</a>
		<?php endif; ?>
		<h3 class="tb-post-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
		<p class="tb-post-card__meta">
			<?php echo esc_html( tb_date_label( (int) get_post_timestamp() ) ); ?>
		</p>
	</div>
</article>

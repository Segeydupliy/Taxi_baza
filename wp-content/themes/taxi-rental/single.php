<?php
/**
 * Single blog post.
 *
 * Figma: desktop 122:5894, mobile 246:7366.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$content = apply_filters( 'the_content', get_the_content() );

	// Inject ids into h2 headings and collect them for the TOC.
	$toc     = [];
	$content = preg_replace_callback(
		'/<h2([^>]*)>(.*?)<\/h2>/is',
		function ( $m ) use ( &$toc ) {
			$title = wp_strip_all_tags( $m[2] );
			$id    = sanitize_title( $title );
			$toc[] = [
				'id'    => $id,
				'title' => $title,
			];
			return '<h2' . $m[1] . ' id="' . esc_attr( $id ) . '">' . $m[2] . '</h2>';
		},
		$content
	);

	$category = get_the_category();
	?>

<main class="tb-post">
	<div class="tb-container">

		<nav class="tb-breadcrumbs" aria-label="<?php esc_attr_e( 'Хлібні крихти', 'taxi-rental' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Головна', 'taxi-rental' ); ?></a>
			<span aria-hidden="true">›</span>
			<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Блог', 'taxi-rental' ); ?></a>
			<?php if ( $category ) : ?>
			<span aria-hidden="true">›</span>
			<a href="<?php echo esc_url( get_category_link( $category[0] ) ); ?>"><?php echo esc_html( $category[0]->name ); ?></a>
			<?php endif; ?>
			<span aria-hidden="true">›</span>
			<span class="tb-breadcrumbs__current"><?php the_title(); ?></span>
		</nav>

		<header class="tb-post__header">
			<h1 class="tb-post__title"><?php the_title(); ?></h1>
			<div class="tb-post__meta">
				<?php if ( $category ) : ?>
				<a href="<?php echo esc_url( get_category_link( $category[0] ) ); ?>" class="tb-post-card__tag">
					<?php echo esc_html( $category[0]->name ); ?>
				</a>
				<?php endif; ?>
				<p class="tb-post-card__meta">
					<?php echo esc_html( get_the_date( 'j F Y' ) ); ?> · <?php echo esc_html( taxi_rental_reading_time( get_the_ID() ) ); ?>
				</p>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
		<div class="tb-post__hero">
			<?php the_post_thumbnail( 'full' ); ?>
		</div>
		<?php endif; ?>

		<div class="tb-post__layout">

			<?php if ( $toc ) : ?>
			<aside class="tb-post__toc">
				<div class="tb-post__toc-box">
					<p class="tb-post__toc-label"><?php esc_html_e( 'Obsah', 'taxi-rental' ); ?></p>
					<ul class="tb-post__toc-list">
						<?php foreach ( $toc as $item ) : ?>
						<li><a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</aside>
			<?php endif; ?>

			<div class="tb-post__content">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered post content. ?>
			</div>

		</div>

		<?php
		$related = new WP_Query(
			[
				'posts_per_page'      => 3,
				'post__not_in'        => [ get_the_ID() ],
				'cat'                 => $category ? $category[0]->term_id : 0,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			]
		);

		// Fall back to latest posts when the category has no other entries.
		if ( ! $related->have_posts() ) {
			$related = new WP_Query(
				[
					'posts_per_page'      => 3,
					'post__not_in'        => [ get_the_ID() ],
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				]
			);
		}

		if ( $related->have_posts() ) :
			?>
		<section class="tb-related">
			<div class="tb-related__head">
				<div>
					<p class="tb-related__label"><?php esc_html_e( 'Вас може зацікавити', 'taxi-rental' ); ?></p>
					<h2 class="tb-related__title"><?php esc_html_e( 'Схожі статті', 'taxi-rental' ); ?></h2>
				</div>
				<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' ) ); ?>" class="tb-btn tb-btn--outline-dark">
					<?php esc_html_e( 'Всі статті', 'taxi-rental' ); ?>
				</a>
			</div>
			<?php // #97 — this track becomes a swipeable carousel below 1024px. ?>
			<div class="tb-blog__grid tb-blog__grid--carousel">
				<?php
				while ( $related->have_posts() ) :
					$related->the_post();
					get_template_part( 'template-parts/blog/card-post' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php endif; ?>

	</div>

	<?php get_template_part( 'template-parts/cta-telegram' ); ?>
</main>

	<?php
endwhile;

get_footer();

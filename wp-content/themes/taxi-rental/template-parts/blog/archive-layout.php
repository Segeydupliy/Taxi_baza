<?php
/**
 * Blog archive layout — shared by home.php and category.php.
 *
 * Figma: desktop 119:5429, mobile 246:6979.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_cat = is_category() ? get_queried_object_id() : 0;
$categories  = get_categories( [ 'hide_empty' => true ] );

$blog_home = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' );
$blog_base = is_category() ? get_category_link( $current_cat ) : $blog_home;

// #95/#96 — search and sort. The term deliberately does NOT ride on "s":
// that is a WordPress-reserved var and would hand the request to the global
// search template instead of the blog archive. Queries::filter_blog_query()
// translates tb_bq / tb_bsort onto the main query.
$blog_q    = sanitize_text_field( wp_unslash( $_GET['tb_bq'] ?? '' ) );
$blog_sort = sanitize_key( wp_unslash( $_GET['tb_bsort'] ?? '' ) );
if ( ! in_array( $blog_sort, [ 'date_desc', 'date_asc', 'views_desc' ], true ) ) {
	$blog_sort = 'date_desc';
}

$blog_sort_labels = [
	'date_desc'  => tb_opt( 'tb_blog_sort_new', __( 'Najnovšie', 'taxi-rental' ) ),
	'date_asc'   => tb_opt( 'tb_blog_sort_old', __( 'Najstaršie', 'taxi-rental' ) ),
	'views_desc' => tb_opt( 'tb_blog_sort_views', __( 'Najpopulárnejšie', 'taxi-rental' ) ),
];

$lbl_blog_cats   = tb_opt( 'tb_blog_cats_label', __( 'Kategórie', 'taxi-rental' ) );
$lbl_blog_search = tb_opt( 'tb_blog_search_label', __( 'Hľadať články', 'taxi-rental' ) );
$lbl_blog_sortl  = tb_opt( 'tb_blog_sort_label', __( 'Zoradiť', 'taxi-rental' ) );

/** Keep the active search/sort when following a category or sort link. */
$blog_url = static function ( string $base, array $extra = [] ) use ( $blog_q, $blog_sort ): string {
	$params = $extra;
	if ( '' !== $blog_q ) {
		$params['tb_bq'] = $blog_q;
	}
	if ( 'date_desc' !== $blog_sort && ! isset( $params['tb_bsort'] ) ) {
		$params['tb_bsort'] = $blog_sort;
	}
	return esc_url( empty( $params ) ? $base : add_query_arg( $params, $base ) );
};
?>

<main class="tb-blog">
	<div class="tb-container">

		<h1 class="tb-blog__title">
			<?php echo esc_html( tb_opt( 'tb_blog_title', __( 'Корисні матеріали для водіїв та власників авто', 'taxi-rental' ) ) ); ?>
		</h1>

		<?php // Figma 246:7008 — category dropdown on the left, two 39x39 icons right. ?>
		<div class="tb-blog__toolbar" data-tb-toolbar>

			<?php if ( $categories ) : ?>
			<div class="tb-blog__filters">
				<a
					href="<?php echo $blog_url( $blog_home ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in closure. ?>"
					class="tb-blog__filter<?php echo 0 === $current_cat ? ' is-active' : ''; ?>"
				><?php echo esc_html( tb_opt( 'tb_blog_filter_all', __( 'Všetky', 'taxi-rental' ) ) ); ?></a>
				<?php foreach ( $categories as $cat ) : ?>
				<a
					href="<?php echo $blog_url( get_category_link( $cat ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in closure. ?>"
					class="tb-blog__filter<?php echo $cat->term_id === $current_cat ? ' is-active' : ''; ?>"
				><?php echo esc_html( $cat->name ); ?></a>
				<?php endforeach; ?>
			</div>

			<?php // #94 — same destinations as the pills, but as a select below 1024px. ?>
			<select class="tb-blog__cat-select" data-tb-nav-select aria-label="<?php echo esc_attr( $lbl_blog_cats ); ?>">
				<option value="<?php echo $blog_url( $blog_home ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" <?php selected( 0, $current_cat ); ?>>
					<?php echo esc_html( $lbl_blog_cats ); ?>
				</option>
				<?php foreach ( $categories as $cat ) : ?>
				<option value="<?php echo $blog_url( get_category_link( $cat ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" <?php selected( $cat->term_id, $current_cat ); ?>>
					<?php echo esc_html( $cat->name ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>

			<div class="tb-blog__tools">
				<?php // #95 — search. ?>
				<button type="button" class="tb-blog__icon-btn" data-tb-search-toggle aria-expanded="<?php echo '' !== $blog_q ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $lbl_blog_search ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21L15 15M17 11A6 6 0 115 11A6 6 0 0117 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</button>

				<?php // #96 — sort. ?>
				<div class="tb-blog__sort" data-tb-blog-sort>
					<button type="button" class="tb-blog__icon-btn<?php echo 'date_desc' !== $blog_sort ? ' is-active' : ''; ?>" data-tb-sort-toggle aria-expanded="false" aria-label="<?php echo esc_attr( $lbl_blog_sortl ); ?>">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					</button>
					<div class="tb-filter-panel tb-filter-panel--sort">
						<?php
						foreach ( $blog_sort_labels as $sort_val => $sort_lbl ) :
							// #96 — built directly, not via $blog_url(): that closure
							// re-adds tb_bsort whenever it isn't already in $extra, so the
							// "Najnovšie" link (which must OMIT the param) kept getting the
							// still-active sort put back onto it and could never be selected.
							$sort_params = [];
							if ( '' !== $blog_q ) {
								$sort_params['tb_bq'] = $blog_q;
							}
							if ( 'date_desc' !== $sort_val ) {
								$sort_params['tb_bsort'] = $sort_val;
							}
							$sort_href = empty( $sort_params ) ? $blog_base : add_query_arg( $sort_params, $blog_base );
							?>
						<a
							href="<?php echo esc_url( $sort_href ); ?>"
							class="tb-sort-option<?php echo $sort_val === $blog_sort ? ' is-selected' : ''; ?>"
							role="radio"
							aria-checked="<?php echo $sort_val === $blog_sort ? 'true' : 'false'; ?>"
						>
							<?php echo esc_html( $sort_lbl ); ?>
						</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<form class="tb-blog__search" method="get" action="<?php echo esc_url( $blog_base ); ?>">
				<?php if ( 'date_desc' !== $blog_sort ) : ?>
				<input type="hidden" name="tb_bsort" value="<?php echo esc_attr( $blog_sort ); ?>">
				<?php endif; ?>
				<label for="tb-blog-search" class="screen-reader-text"><?php echo esc_html( $lbl_blog_search ); ?></label>
				<input
					type="search"
					id="tb-blog-search"
					name="tb_bq"
					class="tb-blog__search-input"
					value="<?php echo esc_attr( $blog_q ); ?>"
					placeholder="<?php echo esc_attr( $lbl_blog_search ); ?>"
				>
			</form>
		</div>

		<?php if ( have_posts() ) : ?>

		<div class="tb-blog__grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/blog/card-post' );
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			[
				'mid_size'  => 2,
				'prev_text' => '‹',
				'next_text' => '›',
				'class'     => 'tb-blog__pagination',
			]
		);
		?>

		<?php else : ?>

		<p class="tb-blog__empty"><?php echo esc_html( tb_opt( 'tb_blog_empty', __( 'Články zatiaľ neboli publikované.', 'taxi-rental' ) ) ); ?></p>

		<?php endif; ?>

	</div>

	<?php get_template_part( 'template-parts/cta-telegram' ); ?>
</main>

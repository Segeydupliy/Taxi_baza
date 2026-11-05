<?php
/**
 * Archive template — car_listing CPT.
 * URL: /avto/
 */

$filters = \TaxiRental\Core\Queries::parse_filters();
$paged   = \max( 1, (int) ( $_GET['paged'] ?? get_query_var( 'paged', 1 ) ) );
$ctx     = tb_catalog_context( $filters, $paged );
$q       = $ctx['query'];

$base_url   = get_post_type_archive_link( 'car_listing' );
$query_args = \TaxiRental\Core\Queries::to_query_args( $filters );

get_header();
?>

<main class="tb-catalog" id="main-content">
<div class="tb-container">

	<?php
	// data-tb-catalog marks the region catalog.js swaps over AJAX; the base URL
	// and nonce ride along so the script needs no separate localisation.
	?>
	<div
		data-tb-catalog
		data-base="<?php echo esc_url( $base_url ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'tb_filter_listings' ) ); ?>"
	>

		<div class="tb-catalog__head">
			<h1 class="tb-catalog__title"><?php echo esc_html( tb_opt( 'tb_cat_archive_heading', __( 'Katalóg áut', 'taxi-rental' ) ) ); ?></h1>
			<p class="tb-catalog__count" data-tb-count><?php echo esc_html( tb_catalog_count_label( (int) $q->found_posts ) ); ?></p>
		</div>

		<div data-tb-filters>
			<?php get_template_part( 'template-parts/catalog/filters-bar', null, $ctx ); ?>
		</div>

		<div data-tb-results>
			<?php
			get_template_part(
				'template-parts/catalog/results',
				null,
				[
					'query'      => $q,
					'paged'      => $paged,
					'base_url'   => $base_url,
					'query_args' => $query_args,
				]
			);
			?>
		</div>

	</div>

</div><!-- .tb-container -->

<?php get_template_part( 'template-parts/cta-telegram' ); ?>

</main>

<?php get_footer(); ?>

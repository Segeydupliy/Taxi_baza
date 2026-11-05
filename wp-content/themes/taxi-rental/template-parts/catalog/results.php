<?php
/**
 * Catalog results — the grid plus load-more / empty state.
 *
 * Rendered identically by three callers: archive-car_listing.php,
 * taxonomy-city.php and the AJAX filter endpoint. That is the point — the
 * AJAX response has to be the same markup a full page load produces, or the
 * two drift apart the first time either is edited.
 *
 * Args:
 *   query           WP_Query  Result set.
 *   paged           int       Current page.
 *   base_url        string    URL the load-more link builds on.
 *   query_args      array     Public query args to preserve in that link.
 *   load_more_class string    Button modifier for the load-more link.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tb_q        = $args['query'] ?? null;
$tb_paged    = (int) ( $args['paged'] ?? 1 );
$tb_base     = (string) ( $args['base_url'] ?? get_post_type_archive_link( 'car_listing' ) );
$tb_qargs    = (array) ( $args['query_args'] ?? [] );
$tb_lm_class = (string) ( $args['load_more_class'] ?? 'tb-btn--outline' );

if ( ! $tb_q instanceof WP_Query ) {
	return;
}
?>

<?php if ( $tb_q->have_posts() ) : ?>

	<div class="tb-catalog__grid" id="tb-catalog-grid">
		<?php
		while ( $tb_q->have_posts() ) {
			$tb_q->the_post();
			get_template_part( 'template-parts/catalog/card-car' );
		}
		wp_reset_postdata();
		?>
	</div>

	<?php
	if ( $tb_q->max_num_pages > $tb_paged ) :
		$tb_next_url = add_query_arg(
			array_merge( $tb_qargs, [ 'paged' => $tb_paged + 1 ] ),
			$tb_base
		);
		?>
		<div class="tb-catalog__load-more">
			<a
				href="<?php echo esc_url( $tb_next_url ); ?>"
				class="tb-btn <?php echo esc_attr( $tb_lm_class ); ?>"
				data-tb-load-more
			>
				<?php echo esc_html( tb_opt( 'tb_cat_load_more', 'Načítať ďalšie' ) ); ?>
			</a>
		</div>
	<?php endif; ?>

<?php else : ?>

	<div class="tb-catalog__empty">
		<p class="tb-catalog__empty-title"><?php echo esc_html( tb_opt( 'tb_cat_empty_title', 'Nič sa nenašlo' ) ); ?></p>
		<p class="tb-catalog__empty-desc"><?php echo esc_html( tb_opt( 'tb_cat_empty_desc', 'Skúste zmeniť parametre filtra.' ) ); ?></p>
		<a href="<?php echo esc_url( $tb_base ); ?>" class="tb-btn tb-btn--outline">
			<?php echo esc_html( tb_opt( 'tb_cat_reset_filters', 'Zrušiť filtre' ) ); ?>
		</a>
	</div>

<?php endif; ?>

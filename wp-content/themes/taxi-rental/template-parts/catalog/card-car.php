<?php
/**
 * template-parts/catalog/card-car.php
 * Single car listing card for catalog grid.
 * Uses global $post (must be called inside The Loop).
 */

$post_id   = get_the_ID();
$permalink = get_permalink();
$title     = get_the_title();

$thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url( $post_id, 'large' ) : '';

$city_terms = get_the_terms( $post_id, 'city' );
$city_name  = ( $city_terms && ! is_wp_error( $city_terms ) ) ? $city_terms[0]->name : '';

$ride_cat_terms = get_the_terms( $post_id, 'ride_category' );
// Filter to leaf terms only (exclude parent Bolt/Uber nodes).
if ( $ride_cat_terms && ! is_wp_error( $ride_cat_terms ) ) {
	$ride_cat_terms = array_filter( $ride_cat_terms, fn( $t ) => $t->parent > 0 );
}

$price_week = (int) get_post_meta( $post_id, 'price_week', true );

$label_from = tb_opt( 'tb_cat_price_from', __( 'від', 'taxi-rental' ) );
$label_unit = tb_opt( 'tb_cat_price_unit', __( '/ тиждень', 'taxi-rental' ) );
$label_more = tb_opt( 'tb_cat_card_more', __( 'Детальніше', 'taxi-rental' ) );
?>
<article class="tb-car-card">

	<a href="<?php echo esc_url( $permalink ); ?>" class="tb-car-card__image-wrap" tabindex="-1" aria-hidden="true">
		<?php if ( $thumb_url ) : ?>
			<img
				src="<?php echo esc_url( $thumb_url ); ?>"
				alt="<?php echo esc_attr( $title ); ?>"
				class="tb-car-card__image"
				loading="lazy"
			>
		<?php else : ?>
			<div class="tb-car-card__image tb-car-card__image--placeholder" aria-hidden="true">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M5 11l1.5-4.5A2 2 0 018.4 5h7.2a2 2 0 011.9 1.5L19 11m-14 0h14m-14 0a2 2 0 00-2 2v3h2m12-5a2 2 0 012 2v3h-2m-12 0h12m-12 0v2a1 1 0 001 1h1a1 1 0 001-1v-2m8 0v2a1 1 0 001 1h1a1 1 0 001-1v-2M7 13.5h.01M17 13.5h.01" stroke="#c4c4c4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</div>
		<?php endif; ?>
	</a>

	<div class="tb-car-card__body">
		<div class="tb-car-card__info">

			<div class="tb-car-card__meta">
				<h2 class="tb-car-card__title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
				</h2>
				<?php if ( $city_name ) : ?>
					<p class="tb-car-card__city">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/>
						</svg>
						<?php echo esc_html( $city_name ); ?>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( $ride_cat_terms && ! is_wp_error( $ride_cat_terms ) ) : ?>
				<div class="tb-car-card__platforms">
					<?php foreach ( $ride_cat_terms as $cat ) :
						$bolt_parent = get_term_by( 'slug', 'bolt', 'ride_category' );
						$is_bolt     = $bolt_parent && ( $cat->parent === $bolt_parent->term_id );
						$badge_class = $is_bolt ? 'tb-car-card__badge--bolt' : 'tb-car-card__badge--uber';
					?>
						<span class="tb-car-card__badge <?php echo esc_attr( $badge_class ); ?>">
							<?php echo esc_html( $cat->name ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>

		<div class="tb-car-card__footer">
			<?php if ( $price_week > 0 ) : ?>
				<div class="tb-car-card__price">
					<span class="tb-car-card__price-from"><?php echo esc_html( $label_from ); ?></span>
					<div class="tb-car-card__price-row">
						<span class="tb-car-card__price-amount"><?php echo (int) $price_week; ?>€</span>
						<span class="tb-car-card__price-unit"><?php echo esc_html( $label_unit ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<a href="<?php echo esc_url( $permalink ); ?>" class="tb-car-card__link" aria-label="<?php echo esc_attr( sprintf( '%s: %s', $label_more, $title ) ); ?>">
				<?php echo esc_html( $label_more ); ?>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M9.29 6.71a.996.996 0 000 1.41L13.17 12l-3.88 3.88a.996.996 0 101.41 1.41l4.59-4.59a.996.996 0 000-1.41L10.7 6.7c-.38-.38-1.02-.38-1.41.01z" fill="currentColor"/>
				</svg>
			</a>
		</div>
	</div>

</article>

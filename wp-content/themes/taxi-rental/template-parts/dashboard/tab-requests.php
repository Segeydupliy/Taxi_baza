<?php
/**
 * Cabinet — "Moje žiadosti" tab (driver's rental requests).
 *
 * Figma: 634:1839 (list), 634:3264 (empty state).
 * Requests are stored by TaxiRental\Core\Requests when a driver submits the
 * "send message to owner" form on a listing.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$driver_id = get_current_user_id();
$requests  = \TaxiRental\Core\Requests::for_driver( $driver_id );

$catalog_url = get_post_type_archive_link( 'car_listing' ) ?: home_url( '/avto/' );

// ACF label lookups.
$lbl_write_btn      = tb_opt( 'tb_cab_req_write_btn',      __( 'Napísať vlastníkovi', 'taxi-rental' ) );
$lbl_delete_btn     = tb_opt( 'tb_cab_req_delete_btn',     __( 'Odstrániť', 'taxi-rental' ) );
$lbl_delete_confirm = tb_opt( 'tb_cab_req_delete_confirm', __( 'Odstrániť túto žiadosť?', 'taxi-rental' ) );
$lbl_empty_title    = tb_opt( 'tb_cab_req_empty_title',    __( 'Nič sa nenašlo', 'taxi-rental' ) );
$lbl_empty_desc     = tb_opt( 'tb_cab_req_empty_desc',     __( 'Zdá sa, že ste ešte nepodali žiadnu žiadosť o prenájom. Teraz je ideálny čas to zmeniť.', 'taxi-rental' ) );
$lbl_view_listings  = tb_opt( 'tb_cab_view_listings_btn',  __( 'Zobraziť inzeráty', 'taxi-rental' ) );
$lbl_soon           = tb_opt( 'tb_cab_req_soon',           __( 'Čoskoro', 'taxi-rental' ) );

if ( empty( $requests ) ) :
	?>
	<div class="tb-cabinet__empty">
		<div class="tb-cabinet__empty-icon tb-cabinet__empty-icon--chat" aria-hidden="true">
			<svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M4 4h16a1 1 0 011 1v11a1 1 0 01-1 1H8l-4 4V5a1 1 0 011-1z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="9" cy="10.5" r="1" fill="currentColor"/><circle cx="12" cy="10.5" r="1" fill="currentColor"/><circle cx="15" cy="10.5" r="1" fill="currentColor"/></svg>
		</div>
		<h2 class="tb-cabinet__empty-title"><?php echo esc_html( $lbl_empty_title ); ?></h2>
		<p class="tb-cabinet__empty-desc"><?php echo esc_html( $lbl_empty_desc ); ?></p>
		<a href="<?php echo esc_url( $catalog_url ); ?>" class="tb-btn tb-btn--primary">
			<?php echo esc_html( $lbl_view_listings ); ?>
		</a>
	</div>
	<?php
else :
	?>
	<div class="tb-listing-list" role="list">
		<?php
		foreach ( $requests as $request ) :
			$listing_id = (int) get_post_meta( $request->ID, 'tb_listing_id', true );
			$listing    = $listing_id ? get_post( $listing_id ) : null;
			if ( ! $listing ) {
				continue;
			}

			$title      = get_the_title( $listing_id );
			$price      = get_field( 'price_week', $listing_id );
			$city_terms = get_the_terms( $listing_id, 'city' );
			$city_name  = ( $city_terms && ! is_wp_error( $city_terms ) ) ? $city_terms[0]->name : '';
			$thumbnail  = get_the_post_thumbnail_url( $listing_id, 'thumbnail' );
			$views      = (int) get_post_meta( $listing_id, '_tb_listing_views', true );
			$is_live    = 'publish' === $listing->post_status;
			?>
			<div role="listitem">
				<div class="tb-listing-row" id="request-<?php echo esc_attr( $request->ID ); ?>">

					<div class="tb-listing-row__main">
						<div class="tb-listing-row__thumb">
							<?php if ( $thumbnail ) : ?>
								<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" width="140" height="100">
							<?php else : ?>
								<div class="tb-listing-row__thumb-placeholder" aria-hidden="true"></div>
							<?php endif; ?>
						</div>

						<div class="tb-listing-row__info">
							<div class="tb-listing-row__meta">
								<div class="tb-listing-row__title-row">
									<?php if ( $is_live ) : ?>
										<a href="<?php echo esc_url( get_permalink( $listing_id ) ); ?>" class="tb-listing-row__title">
											<?php echo esc_html( $title ); ?>
										</a>
									<?php else : ?>
										<span class="tb-listing-row__title"><?php echo esc_html( $title ); ?></span>
									<?php endif; ?>
								</div>
								<?php if ( $city_name ) : ?>
									<div class="tb-listing-row__location">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/></svg>
										<span><?php echo esc_html( $city_name ); ?></span>
									</div>
								<?php endif; ?>
							</div>

							<div class="tb-listing-row__stats">
								<span class="tb-listing-row__stat">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="currentColor"/></svg>
									<?php
									printf(
										/* translators: %d: view count */
										esc_html( tb_plural_opt( (int) $views, 'view', __( '%d zobrazenie', 'taxi-rental' ), __( '%d zobrazenia', 'taxi-rental' ), __( '%d zobrazení', 'taxi-rental' ) ) ),
										$views
									);
									?>
								</span>
							</div>
						</div>

						<?php if ( $price ) : ?>
							<div class="tb-listing-row__price">
								<?php
								printf(
									/* translators: %s: price amount */
									esc_html__( 'від %s€ / тиждень', 'taxi-rental' ),
									esc_html( number_format( (float) $price, 0, '.', ' ' ) )
								);
								?>
							</div>
						<?php endif; ?>
					</div><!-- .tb-listing-row__main -->

					<div class="tb-listing-row__footer">
						<span class="tb-listing-row__id">
							<?php printf( 'ID: %d', $listing_id ); ?>
						</span>

						<div class="tb-listing-row__actions">
							<!-- Chat with owner — disabled until messaging ships (phase 2). -->
							<button type="button" class="tb-btn tb-btn--outline tb-btn--sm" disabled aria-disabled="true" title="<?php echo esc_attr( $lbl_soon ); ?>">
								<?php echo esc_html( $lbl_write_btn ); ?>
							</button>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tb-request-delete-form" onsubmit="return confirm('<?php echo esc_attr( $lbl_delete_confirm ); ?>')">
								<input type="hidden" name="action" value="tb_delete_request">
								<input type="hidden" name="request_id" value="<?php echo esc_attr( $request->ID ); ?>">
								<?php wp_nonce_field( 'tb_delete_request_' . $request->ID ); ?>
								<button type="submit" class="tb-btn tb-btn--outline tb-btn--sm">
									<?php echo esc_html( $lbl_delete_btn ); ?>
								</button>
							</form>
						</div>
					</div><!-- .tb-listing-row__footer -->

				</div><!-- .tb-listing-row -->
			</div>
		<?php endforeach; ?>
	</div>
	<?php
endif;

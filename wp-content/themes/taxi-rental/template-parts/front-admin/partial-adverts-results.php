<?php
/**
 * template-parts/front-admin/partial-adverts-results.php
 * Renders the listings cards + load-more + empty state for the Adverts tab.
 * Included both by tab-adverts.php (normal page load) and by the
 * tb_fa_filter_listings AJAX handler (class-admin.php) — both callers must
 * define: $q (WP_Query), $badge_cfg, $edit_url, $sort_url, $sort_filter,
 * $paged, $search_q.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- ─── Listings (two-tier cards) ─────────────────────────────── -->
<?php if ( $q->have_posts() ) : ?>

<div class="tb-fa-cards" role="list">
	<?php
	while ( $q->have_posts() ) :
		$q->the_post();

		$post_id     = get_the_ID();
		$title       = get_the_title();
		$status      = get_post_status();
		$thumb_url   = get_the_post_thumbnail_url( $post_id, 'medium' );
		$price       = (float) get_field( 'price_week' );
		$city_terms  = get_the_terms( $post_id, 'city' );
		$city_name   = ( $city_terms && ! is_wp_error( $city_terms ) ) ? $city_terms[0]->name : '';
		$author_id   = (int) get_post_field( 'post_author', $post_id );
		$author_name = get_the_author_meta( 'display_name', $author_id );
		$views       = (int) get_post_meta( $post_id, '_tb_listing_views', true );
		$messages    = (int) get_post_meta( $post_id, '_tb_listing_messages', true );
		$calls       = (int) get_post_meta( $post_id, '_tb_listing_calls', true );
		$badge       = $badge_cfg[ $status ] ?? [ 'label' => $status, 'bg' => '#e5e5e5' ];
		?>
		<article class="tb-fa-mod-card" data-listing-id="<?php echo esc_attr( $post_id ); ?>" role="listitem">

			<!-- Top body -->
			<div class="tb-fa-mod-card__body">
				<div class="tb-fa-mod-card__thumb">
					<?php if ( $thumb_url ) : ?>
						<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" width="140" height="100">
					<?php else : ?>
						<div class="tb-fa-mod-card__thumb-placeholder" aria-hidden="true"></div>
					<?php endif; ?>
				</div>

				<div class="tb-fa-mod-card__info">
					<div class="tb-fa-mod-card__head">
						<span class="tb-fa-mod-card__title"><?php echo esc_html( $title ); ?></span>
						<span class="tb-fa-mod-card__badge" style="background:<?php echo esc_attr( $badge['bg'] ); ?>;">
							<?php echo esc_html( $badge['label'] ); ?>
						</span>
					</div>

					<div class="tb-fa-mod-card__meta">
						<?php if ( $city_name ) : ?>
							<span class="tb-fa-mod-card__meta-item">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<path d="M12 21s-7-5.686-7-11a7 7 0 1114 0c0 5.314-7 11-7 11z" stroke="currentColor" stroke-width="1.6"/>
									<circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/>
								</svg>
								<?php echo esc_html( $city_name ); ?>
							</span>
						<?php endif; ?>
						<?php if ( $author_name ) : ?>
							<span class="tb-fa-mod-card__meta-item">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/>
									<path d="M5 20a7 7 0 0114 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
								</svg>
								<?php echo esc_html( $author_name ); ?>
							</span>
						<?php endif; ?>
					</div>

					<div class="tb-fa-mod-card__metrics">
						<span class="tb-fa-mod-card__metric">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.6"/>
								<circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $views, 'view', '%d zobrazenie', '%d zobrazenia', '%d zobrazení' ) ), (int) $views ); ?>
						</span>
						<span class="tb-fa-mod-card__metric">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M21 11.5a8.38 8.38 0 01-9 8.4L3 21l1.1-3.3A8.38 8.38 0 1121 11.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $messages, 'message', '%d správa', '%d správy', '%d správ' ) ), (int) $messages ); ?>
						</span>
						<span class="tb-fa-mod-card__metric">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3 19.5 19.5 0 01-6-6 19.8 19.8 0 01-3-8.6A2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.6a2 2 0 01-.5 2.1L8 9.6a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.4c.8.3 1.7.5 2.6.6a2 2 0 011.7 2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $calls, 'call', '%d hovor', '%d hovory', '%d hovorov' ) ), (int) $calls ); ?>
						</span>
					</div>
				</div>

				<?php if ( $price > 0 ) : ?>
					<div class="tb-fa-mod-card__price">
						<?php printf( esc_html( tb_opt( 'tb_fa_adv_price', 'od %s€ / týždeň' ) ), esc_html( number_format( $price, 0, '.', ' ' ) ) ); ?>
					</div>
				<?php endif; ?>

				<!-- ID lives in the body, under the price (Figma 265:22941) -->
				<span class="tb-fa-mod-card__id">ID: <?php echo esc_html( $post_id ); ?></span>
			</div>

			<!-- Bottom bar — actions only -->
			<div class="tb-fa-mod-card__bar">
				<div class="tb-fa-mod-card__actions">
					<?php if ( 'pending' === $status ) : ?>
						<!-- Archive is part of the moderation card in Figma 265:22906 -->
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="archive"><?php echo esc_html( tb_opt( 'tb_fa_adv_archive_btn', 'Archivovať' ) ); ?></button>
						<button type="button" class="tb-fa-btn tb-fa-btn--dark" data-fa-action="reject"><?php echo esc_html( tb_opt( 'tb_fa_adv_reject_btn', 'Zamietnuť' ) ); ?></button>
						<a href="<?php echo $edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_adv_edit_btn', 'Upraviť' ) ); ?></a>
						<button type="button" class="tb-fa-btn tb-fa-btn--accent" data-fa-action="publish"><?php echo esc_html( tb_opt( 'tb_fa_adv_publish_btn', 'Zverejniť' ) ); ?></button>

					<?php elseif ( 'publish' === $status ) : ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="archive"><?php echo esc_html( tb_opt( 'tb_fa_adv_archive_btn', 'Archivovať' ) ); ?></button>
						<a href="<?php echo $edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_adv_edit_btn', 'Upraviť' ) ); ?></a>

					<?php elseif ( 'rejected' === $status ) : ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="pending"><?php echo esc_html( tb_opt( 'tb_fa_adv_review_btn', 'Na posúdenie' ) ); ?></button>
						<a href="<?php echo $edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_adv_edit_btn', 'Upraviť' ) ); ?></a>
						<button type="button" class="tb-fa-btn tb-fa-btn--danger" data-fa-action="delete"><?php echo esc_html( tb_opt( 'tb_fa_adv_delete_btn', 'Odstrániť' ) ); ?></button>

					<?php elseif ( 'archived' === $status ) : ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="pending"><?php echo esc_html( tb_opt( 'tb_fa_adv_restore_btn', 'Obnoviť' ) ); ?></button>
						<a href="<?php echo $edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_adv_edit_btn', 'Upraviť' ) ); ?></a>
						<button type="button" class="tb-fa-btn tb-fa-btn--danger" data-fa-action="delete"><?php echo esc_html( tb_opt( 'tb_fa_adv_delete_btn', 'Odstrániť' ) ); ?></button>

					<?php else : /* draft — same 3-button layout/order as "pending" (dark → outline → accent) */ ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--dark" data-fa-action="delete"><?php echo esc_html( tb_opt( 'tb_fa_adv_delete_btn', 'Odstrániť' ) ); ?></button>
						<a href="<?php echo $edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_adv_edit_btn', 'Upraviť' ) ); ?></a>
						<button type="button" class="tb-fa-btn tb-fa-btn--accent" data-fa-action="publish"><?php echo esc_html( tb_opt( 'tb_fa_adv_publish_btn', 'Zverejniť' ) ); ?></button>
					<?php endif; ?>
				</div>
			</div>

		</article>
		<?php
	endwhile;
	wp_reset_postdata();
	?>
</div>

<?php
// ── Load more ──────────────────────────────────────────────────
$total_pages = (int) $q->max_num_pages;
if ( $total_pages > $paged ) :
	$next_page  = $paged + 1;
	$next_url   = add_query_arg( 'paged', $next_page, $sort_url( $sort_filter ) );
	?>
	<div class="tb-fa-load-more">
		<a
			href="<?php echo esc_url( $next_url ); ?>"
			class="tb-fa-btn tb-fa-btn--dark"
			data-fa-page="<?php echo esc_attr( $next_page ); ?>"
		>
			<?php echo esc_html( tb_opt( 'tb_fa_adv_load_more', 'Načítať viac' ) ); ?>
		</a>
	</div>
<?php endif; ?>

<?php else : ?>
	<div class="tb-fa-empty">
		<svg width="48" height="48" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21L16.514 16.506M19 11C19 15.418 15.418 19 11 19C6.582 19 3 15.418 3 11C3 6.582 6.582 3 11 3C15.418 3 19 6.582 19 11Z" stroke="#ccc" stroke-width="1.5" stroke-linecap="round"/></svg>
		<p>
			<?php
			if ( $search_q ) {
				printf( esc_html( tb_opt( 'tb_fa_adv_empty_search', 'Pre hľadanie &#8222;%s&#8220; sa nič nenašlo.' ) ), esc_html( $search_q ) );
			} else {
				echo esc_html( tb_opt( 'tb_fa_adv_empty', 'Žiadne inzeráty.' ) );
			}
			?>
		</p>
	</div>
<?php endif; ?>

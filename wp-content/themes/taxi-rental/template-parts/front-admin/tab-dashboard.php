<?php
/**
 * template-parts/front-admin/tab-dashboard.php
 * Dashboard tab: site stats + pending moderation queue.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Stats ───────────────────────────────────────────────────────
// tb_listing_count() goes through WP_Query so these numbers match the lists
// (wp_count_posts() ignores WPML's language filter — see the helper's docblock).
$total_listings = tb_listing_count( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ] );
$pending_count  = tb_listing_count( [ 'pending' ] );

$user_counts  = count_users();
$total_users  = $user_counts['total_users'];

$cities_count = (int) wp_count_terms( [ 'taxonomy' => 'city', 'hide_empty' => false ] );

// ── Pending moderation query ────────────────────────────────────
$mod_query = new WP_Query( [
	'post_type'      => 'car_listing',
	'post_status'    => 'pending',
	'posts_per_page' => 30,
	'orderby'        => 'date',
	'order'          => 'ASC',
	'no_found_rows'  => true,
] );
?>

<!-- Stats heading -->
<h2 class="tb-fa-section-head__title tb-fa-stats-heading"><?php echo esc_html( tb_opt( 'tb_fa_dash_stats_title', 'Celková štatistika' ) ); ?></h2>

<!-- Stats row -->
<div class="tb-fa-stats" role="list">

	<div class="tb-fa-stat-card" role="listitem">
		<div class="tb-fa-stat-card__label"><?php echo esc_html( tb_opt( 'tb_fa_dash_total', 'Celkom inzerátov' ) ); ?></div>
		<div class="tb-fa-stat-card__number"><?php echo esc_html( number_format_i18n( $total_listings ) ); ?></div>
	</div>

	<div class="tb-fa-stat-card" role="listitem">
		<div class="tb-fa-stat-card__label"><?php echo esc_html( tb_opt( 'tb_fa_dash_pending', 'V moderácii' ) ); ?></div>
		<!-- data-fa-stat: front-admin.js decrements this after a moderation
		     action. It used to pick the 3rd card by index, which is "Users". -->
		<div class="tb-fa-stat-card__number" data-fa-stat="pending"><?php echo esc_html( number_format_i18n( $pending_count ) ); ?></div>
	</div>

	<div class="tb-fa-stat-card" role="listitem">
		<div class="tb-fa-stat-card__label"><?php echo esc_html( tb_opt( 'tb_fa_dash_users', 'Používateľov' ) ); ?></div>
		<div class="tb-fa-stat-card__number"><?php echo esc_html( number_format_i18n( $total_users ) ); ?></div>
	</div>

	<div class="tb-fa-stat-card" role="listitem">
		<div class="tb-fa-stat-card__label"><?php echo esc_html( tb_opt( 'tb_fa_dash_cities', 'Miest' ) ); ?></div>
		<div class="tb-fa-stat-card__number"><?php echo esc_html( number_format_i18n( $cities_count ) ); ?></div>
	</div>

</div>

<!-- Moderation queue heading -->
<div class="tb-fa-section-head">
	<h2 class="tb-fa-section-head__title"><?php echo esc_html( tb_opt( 'tb_fa_dash_mod_heading', 'Čakajú na moderáciu' ) ); ?></h2>
</div>

<!-- Moderation cards -->
<?php if ( $mod_query->have_posts() ) : ?>
<div class="tb-fa-cards" role="list">
	<?php
	while ( $mod_query->have_posts() ) :
		$mod_query->the_post();

		$post_id     = get_the_ID();
		$title       = get_the_title();
		$thumb_url   = get_the_post_thumbnail_url( $post_id, 'medium' );
		$price       = (float) get_field( 'price_week' );
		$city_terms  = get_the_terms( $post_id, 'city' );
		$city_name   = ( $city_terms && ! is_wp_error( $city_terms ) ) ? $city_terms[0]->name : '';
		$views       = (int) get_post_meta( $post_id, '_tb_listing_views', true );
		$messages    = (int) get_post_meta( $post_id, '_tb_listing_messages', true );
		$calls       = (int) get_post_meta( $post_id, '_tb_listing_calls', true );
		$author_id   = (int) get_post_field( 'post_author', $post_id );
		$author_name = get_the_author_meta( 'display_name', $author_id );
		$edit_url    = esc_url( add_query_arg( [ 'tab' => 'listings', 'edit' => $post_id ], $panel_url ) );
		?>
		<article
			class="tb-fa-mod-card"
			data-listing-id="<?php echo esc_attr( $post_id ); ?>"
			role="listitem"
		>
			<!-- Top body -->
			<div class="tb-fa-mod-card__body">

				<!-- Thumbnail -->
				<div class="tb-fa-mod-card__thumb">
					<?php if ( $thumb_url ) : ?>
						<img
							src="<?php echo esc_url( $thumb_url ); ?>"
							alt="<?php echo esc_attr( $title ); ?>"
							loading="lazy"
							width="140"
							height="100"
						>
					<?php else : ?>
						<div class="tb-fa-mod-card__thumb-placeholder" aria-hidden="true"></div>
					<?php endif; ?>
				</div>

				<!-- Info block -->
				<div class="tb-fa-mod-card__info">
					<div class="tb-fa-mod-card__head">
						<span class="tb-fa-mod-card__title"><?php echo esc_html( $title ); ?></span>
						<span class="tb-fa-mod-card__badge"><?php echo esc_html( tb_opt( 'tb_fa_dash_pending', 'V moderácii' ) ); ?></span>
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
							<?php
							printf(
								esc_html( tb_plural_opt( (int) $views, 'view', '%d zobrazenie', '%d zobrazenia', '%d zobrazení' ) ),
								(int) $views
							);
							?>
						</span>
						<span class="tb-fa-mod-card__metric">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M21 11.5a8.38 8.38 0 01-9 8.4L3 21l1.1-3.3A8.38 8.38 0 1121 11.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
							<?php
							printf(
								esc_html( tb_plural_opt( (int) $messages, 'message', '%d správa', '%d správy', '%d správ' ) ),
								(int) $messages
							);
							?>
						</span>
						<span class="tb-fa-mod-card__metric">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3 19.5 19.5 0 01-6-6 19.8 19.8 0 01-3-8.6A2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.6a2 2 0 01-.5 2.1L8 9.6a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.4c.8.3 1.7.5 2.6.6a2 2 0 011.7 2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
							<?php
							printf(
								esc_html( tb_plural_opt( (int) $calls, 'call', '%d hovor', '%d hovory', '%d hovorov' ) ),
								(int) $calls
							);
							?>
						</span>
					</div>
				</div>

				<!-- Price -->
				<?php if ( $price > 0 ) : ?>
					<div class="tb-fa-mod-card__price">
						<?php
						printf(
							/* translators: %s: price */
							tb_opt( 'tb_fa_dash_price', 'od %s€ / týždeň' ),
							esc_html( number_format( $price, 0, '.', ' ' ) )
						);
						?>
					</div>
				<?php endif; ?>

				<!-- ID lives in the body, under the price (Figma 265:22941) -->
				<span class="tb-fa-mod-card__id">ID: <?php echo esc_html( $post_id ); ?></span>

			</div>

			<!-- Bottom bar — actions only -->
			<div class="tb-fa-mod-card__bar">
				<!-- Actions (same set on every width — the mobile-only pair used to
				     drop "Upraviť", which read as a missing button on phones) -->
				<div class="tb-fa-mod-card__actions">
					<!-- Archive is part of the moderation card in Figma 265:22906 -->
					<button
						type="button"
						class="tb-fa-btn tb-fa-btn--outline"
						data-fa-action="archive"
						aria-label="<?php printf( esc_attr__( 'Archive: %s', 'taxi-rental' ), esc_attr( $title ) ); ?>"
					>
						<?php echo esc_html( tb_opt( 'tb_fa_dash_archive_btn', 'Archivovať' ) ); ?>
					</button>
					<button
						type="button"
						class="tb-fa-btn tb-fa-btn--dark"
						data-fa-action="reject"
						aria-label="<?php printf( esc_attr__( 'Reject: %s', 'taxi-rental' ), esc_attr( $title ) ); ?>"
					>
						<?php echo esc_html( tb_opt( 'tb_fa_dash_reject_btn', 'Zamietnuť' ) ); ?>
					</button>
					<a
						href="<?php echo esc_url( $edit_url ); ?>"
						class="tb-fa-btn tb-fa-btn--outline"
					>
						<?php echo esc_html( tb_opt( 'tb_fa_dash_edit_btn', 'Upraviť' ) ); ?>
					</a>
					<button
						type="button"
						class="tb-fa-btn tb-fa-btn--accent"
						data-fa-action="publish"
						aria-label="<?php printf( esc_attr__( 'Publish: %s', 'taxi-rental' ), esc_attr( $title ) ); ?>"
					>
						<?php echo esc_html( tb_opt( 'tb_fa_dash_publish_btn', 'Zverejniť' ) ); ?>
					</button>
				</div>
			</div>

		</article>
		<?php
	endwhile;
	wp_reset_postdata();
	?>
</div>
<?php else : ?>
	<div class="tb-fa-empty">
		<svg width="48" height="48" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#ccc" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<p><?php echo esc_html( tb_opt( 'tb_fa_dash_empty', 'Žiadne inzeráty čakajú na moderáciu.' ) ); ?></p>
	</div>
<?php endif; ?>

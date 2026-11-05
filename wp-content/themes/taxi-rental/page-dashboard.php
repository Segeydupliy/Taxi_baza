<?php
/**
 * Template Name: User Cabinet
 * Template Post Type: page
 *
 * Tabs: listings (default) | messages | profile | settings
 * Actions: archive, unarchive (GET + nonce)
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect(
		add_query_arg( 'redirect_to', rawurlencode( get_permalink() ), tb_url( '/login/' ) )
	);
	exit;
}

$current_user = wp_get_current_user();
$user_id      = (int) $current_user->ID;

// Drivers get a different cabinet: no listings, a "Moje žiadosti" (requests) tab,
// and a "view listings" CTA instead of "add listing".
$is_driver = in_array( 'driver', (array) $current_user->roles, true );

// -------------------------------------------------------------------------
// Handle quick actions (archive / unarchive)
// -------------------------------------------------------------------------
$tb_action = sanitize_key( wp_unslash( $_GET['tb_action'] ?? '' ) );
if ( in_array( $tb_action, [ 'archive', 'unarchive' ], true ) ) {
	$post_id = absint( wp_unslash( $_GET['post_id'] ?? 0 ) );
	$nonce   = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

	if ( $post_id
		&& wp_verify_nonce( $nonce, "tb_archive_{$post_id}" )
	) {
		$listing = get_post( $post_id );
		if ( $listing
			&& 'car_listing' === $listing->post_type
			&& (int) $listing->post_author === $user_id
		) {
			$new_status = ( 'archive' === $tb_action ) ? 'archived' : 'draft';
			wp_update_post( [ 'ID' => $post_id, 'post_status' => $new_status ] );
		}
	}

	wp_safe_redirect( get_permalink() );
	exit;
}

// -------------------------------------------------------------------------
// Read state
// -------------------------------------------------------------------------
$default_tab   = $is_driver ? 'requests' : 'listings';
$tab           = sanitize_key( wp_unslash( $_GET['tab'] ?? $default_tab ) );
$filter_status = sanitize_key( wp_unslash( $_GET['status'] ?? 'all' ) );
// Read the term from tb_q, NOT from s. "s" is a WordPress-reserved query var:
// hitting /cabinet/?s=... turns the request into a site-wide search, and
// redirect_canonical() then bounces off this page entirely — which is why
// searching in the cabinet used to land on a 404. It is still passed to
// WP_Query as 's' below; that is a secondary query and safe.
$search_q      = sanitize_text_field( wp_unslash( $_GET['tb_q'] ?? '' ) );
// 'paged' is a WordPress-reserved query var — like 's' (see note above),
// requesting it on a static page (?paged=2) makes WP 301-redirect to
// /cabinet/page/2/, after which $_GET['paged'] no longer exists (it's in
// the URL path), silently resetting pagination back to page 1 forever.
// Custom name avoids WP's own query-var handling entirely.
$paged         = max( 1, absint( wp_unslash( $_GET['tb_paged'] ?? 1 ) ) );
$sort          = sanitize_key( wp_unslash( $_GET['sort'] ?? 'date_desc' ) );

$allowed_tabs     = $is_driver
	? [ 'requests', 'profile', 'settings' ]
	: [ 'listings', 'profile', 'settings' ];
$allowed_statuses = [ 'all', 'publish', 'pending', 'draft', 'rejected', 'archived' ];
$allowed_sorts    = [ 'date_desc', 'date_asc', 'price_asc', 'price_desc', 'updated_desc' ];

if ( ! in_array( $tab, $allowed_tabs, true ) ) {
	$tab = $default_tab;
}
if ( ! in_array( $filter_status, $allowed_statuses, true ) ) {
	$filter_status = 'all';
}
if ( ! in_array( $sort, $allowed_sorts, true ) ) {
	$sort = 'date_desc';
}

// -------------------------------------------------------------------------
// Listing counts (single SQL)
// -------------------------------------------------------------------------
global $wpdb;
// WPML stores each language's translation of a car_listing as its own row in
// wp_posts, all sharing the same post_author — a plain COUNT(*) here counts
// every language duplicate, inflating the pills (1 real listing in 2
// languages showed as "2"). WP_Query below is unaffected because WPML's own
// pre_get_posts filter restricts it to the current language automatically;
// this raw SQL has to do that filtering itself via icl_translations.
$current_lang = (string) apply_filters( 'wpml_current_language', null );
$raw_counts   = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT p.post_status, COUNT(*) AS cnt
		 FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->prefix}icl_translations t
		   ON t.element_id = p.ID AND t.element_type = 'post_car_listing'
		 WHERE p.post_type = 'car_listing'
		   AND p.post_author = %d
		   AND p.post_status IN ('publish','pending','draft','rejected','archived')
		   AND t.language_code = %s
		 GROUP BY p.post_status",
		$user_id,
		$current_lang
	),
	ARRAY_A
);

$counts = array_fill_keys( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ], 0 );
foreach ( $raw_counts as $row ) {
	$counts[ $row['post_status'] ] = (int) $row['cnt'];
}
$counts['all'] = array_sum( $counts );

// Friendly stats for the welcome subtitle.
$stat_published = $counts['publish'];
$stat_pending   = $counts['pending'];
$stat_draft     = $counts['draft'];

// -------------------------------------------------------------------------
// Listings query (only for listings tab)
// -------------------------------------------------------------------------
$listings_query = null;
if ( 'listings' === $tab ) {
	$post_statuses = 'all' === $filter_status
		? [ 'publish', 'pending', 'draft', 'rejected', 'archived' ]
		: [ $filter_status ];

	$query_args = [
		'post_type'      => 'car_listing',
		'post_status'    => $post_statuses,
		'author'         => $user_id,
		'posts_per_page' => 10,
		'paged'          => $paged,
	];

	switch ( $sort ) {
		case 'date_asc':
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'ASC';
			break;
		case 'price_asc':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = 'price_week';
			$query_args['order']    = 'ASC';
			break;
		case 'price_desc':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = 'price_week';
			$query_args['order']    = 'DESC';
			break;
		case 'updated_desc':
			$query_args['orderby'] = 'modified';
			$query_args['order']   = 'DESC';
			break;
		default:
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
			break;
	}

	if ( $search_q ) {
		$query_args['s'] = $search_q;
	}

	$listings_query = new WP_Query( $query_args );
}

// -------------------------------------------------------------------------
// Tab URLs helper
// -------------------------------------------------------------------------
$cabinet_url = get_permalink();
$tab_url = static fn( string $t ): string => esc_url( add_query_arg( 'tab', $t, $cabinet_url ) );

// -------------------------------------------------------------------------
// ACF label lookups
// -------------------------------------------------------------------------
$lbl_toast_submitted = tb_opt( 'tb_cab_toast_submitted', __( 'Odoslané na moderáciu', 'taxi-rental' ) );
$lbl_toast_draft     = tb_opt( 'tb_cab_toast_draft',     __( 'Uložené ako koncept', 'taxi-rental' ) );
$lbl_toast_updated   = tb_opt( 'tb_cab_toast_updated',   __( 'Zmeny uložené', 'taxi-rental' ) );
$lbl_greeting        = tb_opt( 'tb_cab_greeting',        __( 'Вітаємо %s', 'taxi-rental' ) );
/* #102-style declension bug — "%1$d опублікованих оголошень" was one fixed
   genitive-plural form for every count, so 1 listing read "1 опублікованих
   оголошень" instead of "1 опубліковане оголошення". Went through
   tb_plural_opt()/tb_opt() first (same pattern as tb_catalog_count_label()),
   but this project has WPML Automatic Translation re-translating changed
   source strings in the background, and machine translation reliably mangles
   sprintf placeholders it wasn't trained on ("%1$s" became "%1$d" — a
   "valid"-looking spec that silently truncates the phrase to its leading
   number). Verified live: the DB value changed under us mid-session. A
   runtime validity check on the fetched string is one bad translation away
   from breaking again, so this whole sentence is built in PHP instead,
   switched on the two languages this project actually owns (sk/uk — see
   CLAUDE.md) — no WPML string registration involved, nothing to mistranslate. */
$phrase_published = 'uk' === $current_lang
	? sprintf( tb_plural( $stat_published, '%d опубліковане оголошення', '%d опубліковані оголошення', '%d опублікованих оголошень' ), $stat_published )
	: sprintf( tb_plural( $stat_published, '%d zverejnený inzerát', '%d zverejnené inzeráty', '%d zverejnených inzerátov' ), $stat_published );
$phrase_draft = 'uk' === $current_lang
	? sprintf( tb_plural( $stat_draft, '%d чернетка', '%d чернетки', '%d чернеток' ), $stat_draft )
	: sprintf( tb_plural( $stat_draft, '%d koncept', '%d koncepty', '%d konceptov' ), $stat_draft );
$lbl_stats = 'uk' === $current_lang
	? 'У вас %1$s, %2$d на модерації, %3$s.'
	: 'Máte %1$s, %2$d v moderácii, %3$s.';
$lbl_add_listing     = tb_opt( 'tb_cab_add_listing_btn', __( 'Додати оголошення', 'taxi-rental' ) );
$lbl_view_listings   = tb_opt( 'tb_cab_view_listings_btn', __( 'Zobraziť inzeráty', 'taxi-rental' ) );
$lbl_nav_listings    = tb_opt( 'tb_cab_nav_listings',    __( 'Мої оголошення', 'taxi-rental' ) );
$lbl_nav_requests    = tb_opt( 'tb_cab_nav_requests',    __( 'Moje žiadosti', 'taxi-rental' ) );
$lbl_nav_messages    = tb_opt( 'tb_cab_nav_messages',    __( 'Повідомлення', 'taxi-rental' ) );

$catalog_url = get_post_type_archive_link( 'car_listing' ) ?: home_url( '/avto/' );
$lbl_nav_profile     = tb_opt( 'tb_cab_nav_profile',     __( 'Мій профіль', 'taxi-rental' ) );
$lbl_nav_settings    = tb_opt( 'tb_cab_nav_settings',    __( 'Налаштування', 'taxi-rental' ) );
$lbl_filter_all      = tb_opt( 'tb_cab_filter_all',      __( 'Всі', 'taxi-rental' ) );
$lbl_filter_publish  = tb_opt( 'tb_cab_filter_publish',  __( 'Опубліковані', 'taxi-rental' ) );
$lbl_filter_draft    = tb_opt( 'tb_cab_filter_draft',    __( 'Чернетки', 'taxi-rental' ) );
$lbl_filter_pending  = tb_opt( 'tb_cab_filter_pending',  __( 'На модерації', 'taxi-rental' ) );
$lbl_filter_rejected = tb_opt( 'tb_cab_filter_rejected', __( 'Відхилені', 'taxi-rental' ) );
$lbl_filter_archived = tb_opt( 'tb_cab_filter_archived', __( 'Архівовані', 'taxi-rental' ) );
$lbl_search_label    = tb_opt( 'tb_cab_search_label',    __( 'Пошук оголошень', 'taxi-rental' ) );
$lbl_search_ph       = tb_opt( 'tb_cab_search_ph',       __( 'Пошук', 'taxi-rental' ) );
$lbl_load_more       = tb_opt( 'tb_cab_load_more',       __( 'Завантажити більше', 'taxi-rental' ) );
$lbl_empty_title     = tb_opt( 'tb_cab_empty_title',     __( 'Ваш список оголошень порожній', 'taxi-rental' ) );
$lbl_empty_desc      = tb_opt( 'tb_cab_empty_desc',      __( 'Ви ще не зробили жодного оголошення. Зараз ідеальний час щоб це змінити.', 'taxi-rental' ) );

$lbl_sort_label = tb_opt( 'tb_cab_sort_label',  __( 'Zoradiť', 'taxi-rental' ) );
$sort_labels = [
	'date_desc'    => tb_opt( 'tb_cab_sort_date_desc',    __( 'Najprv nové', 'taxi-rental' ) ),
	'date_asc'     => tb_opt( 'tb_cab_sort_date_asc',     __( 'Najprv staré', 'taxi-rental' ) ),
	'price_asc'    => tb_opt( 'tb_cab_sort_price_asc',    __( 'Od najlacnejších', 'taxi-rental' ) ),
	'price_desc'   => tb_opt( 'tb_cab_sort_price_desc',   __( 'Od najdrahších', 'taxi-rental' ) ),
	'updated_desc' => tb_opt( 'tb_cab_sort_updated_desc', __( 'Nedávno aktualizované', 'taxi-rental' ) ),
];

// -------------------------------------------------------------------------
// Template
// -------------------------------------------------------------------------
get_header();

$show_submitted = ! empty( $_GET['submitted'] );
$show_draft     = ! empty( $_GET['draft'] );
$show_updated   = ! empty( $_GET['updated'] );
?>

<?php if ( $show_submitted || $show_draft || $show_updated ) : ?>
<div class="tb-cabinet-toast" role="status" aria-live="polite">
	<div class="tb-cabinet-toast__icon" aria-hidden="true">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17L4 12" stroke="black" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</div>
	<span class="tb-cabinet-toast__text">
		<?php
		if ( $show_submitted ) {
			echo esc_html( $lbl_toast_submitted );
		} elseif ( $show_draft ) {
			echo esc_html( $lbl_toast_draft );
		} else {
			echo esc_html( $lbl_toast_updated );
		}
		?>
	</span>
</div>
<?php endif; ?>

<main class="tb-cabinet" id="main-content">
<div class="tb-container">

	<!-- ---------------------------------------------------------------
	     Welcome / Hero row
	     ------------------------------------------------------------- -->
	<?php
	// #113/#114/#115 — the greeting, the listing stats and the CTA belong to the
	// listings view only. They were rendered outside the tab switch, so Profile,
	// Profile edit and Settings all inherited a "Вітаємо" block the designs for
	// those screens do not have.
	$show_hero = in_array( $tab, [ 'listings', 'requests' ], true );
	?>
	<?php if ( $show_hero ) : ?>
	<div class="tb-cabinet__hero">
		<div class="tb-cabinet__hero-text">
			<h1 class="tb-cabinet__greeting">
				<?php
				printf(
					esc_html( $lbl_greeting ),
					esc_html( $current_user->display_name )
				);
				?>
			</h1>
			<?php if ( ! $is_driver ) : ?>
			<p class="tb-cabinet__stats">
				<?php
				printf(
					esc_html( $lbl_stats ),
					esc_html( $phrase_published ),
					$stat_pending,
					esc_html( $phrase_draft )
				);
				?>
			</p>
			<?php endif; ?>
		</div>
		<?php if ( $is_driver ) : ?>
		<a href="<?php echo esc_url( $catalog_url ); ?>" class="tb-btn tb-btn--primary tb-cabinet__add-btn">
			<?php echo esc_html( $lbl_view_listings ); ?>
		</a>
		<?php else : ?>
		<a href="<?php echo esc_url( tb_url( '/cabinet/add-listing/' ) ); ?>" class="tb-btn tb-btn--primary tb-cabinet__add-btn">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" fill="currentColor"/></svg>
			<?php echo esc_html( $lbl_add_listing ); ?>
		</a>
		<?php endif; ?>
	</div>
	<?php else : ?>
		<?php // Those tabs only have h2 card titles, so keep an h1 for the outline. ?>
		<h1 class="screen-reader-text">
			<?php echo esc_html( 'settings' === $tab ? $lbl_nav_settings : $lbl_nav_profile ); ?>
		</h1>
	<?php endif; ?>

	<!-- ---------------------------------------------------------------
	     Tabs
	     ------------------------------------------------------------- -->
	<nav class="tb-cabinet__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Кабінет', 'taxi-rental' ); ?>">
		<?php if ( $is_driver ) : ?>
		<a
			href="<?php echo $tab_url( 'requests' ); ?>"
			class="tb-cabinet__tab<?php echo ( 'requests' === $tab ) ? ' tb-cabinet__tab--active' : ''; ?>"
			role="tab"
			aria-selected="<?php echo ( 'requests' === $tab ) ? 'true' : 'false'; ?>"
		>
			<?php echo esc_html( $lbl_nav_requests ); ?>
		</a>
		<?php else : ?>
		<a
			href="<?php echo $tab_url( 'listings' ); ?>"
			class="tb-cabinet__tab<?php echo ( 'listings' === $tab ) ? ' tb-cabinet__tab--active' : ''; ?>"
			role="tab"
			aria-selected="<?php echo ( 'listings' === $tab ) ? 'true' : 'false'; ?>"
		>
			<?php echo esc_html( $lbl_nav_listings ); ?>
		</a>
		<?php endif; ?>
		<span class="tb-cabinet__tab tb-cabinet__tab--disabled" aria-disabled="true" title="<?php esc_attr_e( 'Незабаром', 'taxi-rental' ); ?>">
			<?php echo esc_html( $lbl_nav_messages ); ?>
		</span>
		<a
			href="<?php echo $tab_url( 'profile' ); ?>"
			class="tb-cabinet__tab<?php echo ( 'profile' === $tab ) ? ' tb-cabinet__tab--active' : ''; ?>"
			role="tab"
			aria-selected="<?php echo ( 'profile' === $tab ) ? 'true' : 'false'; ?>"
		>
			<?php echo esc_html( $lbl_nav_profile ); ?>
		</a>
		<a
			href="<?php echo $tab_url( 'settings' ); ?>"
			class="tb-cabinet__tab<?php echo ( 'settings' === $tab ) ? ' tb-cabinet__tab--active' : ''; ?>"
			role="tab"
			aria-selected="<?php echo ( 'settings' === $tab ) ? 'true' : 'false'; ?>"
		>
			<?php echo esc_html( $lbl_nav_settings ); ?>
		</a>
	</nav>

	<!-- ---------------------------------------------------------------
	     Tab content
	     ------------------------------------------------------------- -->

	<?php if ( 'requests' === $tab ) : ?>

		<div class="tb-cabinet__tab-body">
			<?php get_template_part( 'template-parts/dashboard/tab-requests' ); ?>
		</div>

	<?php elseif ( 'listings' === $tab ) : ?>

		<div class="tb-cabinet__toolbar" data-tb-toolbar role="search">
			<!-- Status filter pills -->
			<div class="tb-cabinet__filters" role="group" aria-label="<?php esc_attr_e( 'Фільтр за статусом', 'taxi-rental' ); ?>">
				<?php
				$filter_defs = [
					'all'      => $lbl_filter_all,
					'publish'  => $lbl_filter_publish,
					'draft'    => $lbl_filter_draft,
					'pending'  => $lbl_filter_pending,
					'rejected' => $lbl_filter_rejected,
					'archived' => $lbl_filter_archived,
				];
				foreach ( $filter_defs as $slug => $label ) :
					$is_active   = ( $filter_status === $slug );
					$filter_href = esc_url( add_query_arg( [ 'status' => $slug, 'tb_paged' => 1 ], $cabinet_url ) );
					?>
					<a
						href="<?php echo $filter_href; ?>"
						class="tb-filter-pill<?php echo $is_active ? ' tb-filter-pill--active' : ''; ?>"
						aria-current="<?php echo $is_active ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $label ); ?>
						<?php if ( 'all' !== $slug && $counts[ $slug ] > 0 ) : ?>
							<span class="tb-filter-pill__count"><?php echo (int) $counts[ $slug ]; ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>

			<?php
			// #105 — below 1024px the pill row was a horizontal scroller. Same set of
			// destinations, rendered as a native select so the value is always visible
			// and nothing has to scroll. The pills stay for desktop.
			?>
			<select class="tb-cabinet__status-select" data-tb-nav-select aria-label="<?php esc_attr_e( 'Фільтр за статусом', 'taxi-rental' ); ?>">
				<?php foreach ( $filter_defs as $slug => $label ) :
					$sel_href  = add_query_arg( [ 'status' => $slug, 'tb_paged' => 1 ], $cabinet_url );
					$sel_label = ( 'all' !== $slug && $counts[ $slug ] > 0 )
						? sprintf( '%s (%d)', $label, (int) $counts[ $slug ] )
						: $label;
					?>
					<option value="<?php echo esc_url( $sel_href ); ?>" <?php selected( $filter_status, $slug ); ?>>
						<?php echo esc_html( $sel_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<div class="tb-cabinet__toolbar-right">
				<?php // #106 — the field collapses to this magnifier below 1024px. ?>
				<button type="button" class="tb-cabinet__search-toggle" data-tb-search-toggle aria-expanded="false" aria-label="<?php echo esc_attr( $lbl_search_label ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21L15 15M17 11A6 6 0 115 11A6 6 0 0117 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</button>

				<!-- Search -->
				<form class="tb-cabinet__search" method="get" action="">
					<input type="hidden" name="tab" value="listings">
					<?php if ( 'all' !== $filter_status ) : ?>
						<input type="hidden" name="status" value="<?php echo esc_attr( $filter_status ); ?>">
					<?php endif; ?>
					<label for="tb-dashboard-search" class="screen-reader-text">
						<?php echo esc_html( $lbl_search_label ); ?>
					</label>
					<div class="tb-cabinet__search-inner">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21L15 15M17 11A6 6 0 111 11A6 6 0 0117 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
						<input
							type="search"
							id="tb-dashboard-search"
							name="tb_q"
							class="tb-cabinet__search-input"
							placeholder="<?php echo esc_attr( $lbl_search_ph ); ?>"
							value="<?php echo esc_attr( $search_q ); ?>"
						>
					</div>
				</form>

				<!-- Sort -->
				<div class="tb-filter-drop tb-filter-drop--sort" data-filter-drop>
					<button type="button" class="tb-filter-sort-btn" data-filter-toggle aria-expanded="false" title="<?php echo esc_attr( $lbl_sort_label ); ?>">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					</button>
					<div class="tb-filter-panel tb-filter-panel--sort">
						<?php foreach ( $sort_labels as $sort_val => $sort_lbl ) :
							$sort_params = [ 'tab' => 'listings' ];
							if ( 'all' !== $filter_status ) {
								$sort_params['status'] = $filter_status;
							}
							if ( $search_q ) {
								$sort_params['tb_q'] = $search_q;
							}
							if ( 'date_desc' !== $sort_val ) {
								$sort_params['sort'] = $sort_val;
							}
							$sort_url = esc_url( add_query_arg( $sort_params, $cabinet_url ) );
							$is_cur   = ( $sort_val === $sort );
						?>
							<a
								href="<?php echo $sort_url; ?>"
								class="tb-sort-option<?php echo $is_cur ? ' is-selected' : ''; ?>"
								role="radio"
								aria-checked="<?php echo $is_cur ? 'true' : 'false'; ?>"
							>
								<?php echo esc_html( $sort_lbl ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div><!-- .tb-cabinet__toolbar-right -->
		</div><!-- .tb-cabinet__toolbar -->

		<!-- Listings list -->
		<?php if ( $listings_query?->have_posts() ) : ?>

			<div class="tb-listing-list" role="list">
				<?php while ( $listings_query->have_posts() ) : $listings_query->the_post(); ?>
					<div role="listitem">
						<?php get_template_part( 'template-parts/dashboard/card-listing' ); ?>
					</div>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			</div>

			<!-- Pagination -->
			<?php if ( $listings_query->max_num_pages > $paged ) : ?>
				<div class="tb-cabinet__pagination">
					<a
						href="<?php echo esc_url( add_query_arg( 'tb_paged', $paged + 1, $cabinet_url ) ); ?>"
						class="tb-btn tb-btn--outline"
					>
						<?php echo esc_html( $lbl_load_more ); ?>
					</a>
				</div>
			<?php endif; ?>

		<?php else : ?>
			<!-- Empty state -->
			<div class="tb-cabinet__empty">
				<div class="tb-cabinet__empty-icon" aria-hidden="true">💬</div>
				<h2 class="tb-cabinet__empty-title">
					<?php echo esc_html( $lbl_empty_title ); ?>
				</h2>
				<p class="tb-cabinet__empty-desc">
					<?php echo esc_html( $lbl_empty_desc ); ?>
				</p>
				<a href="<?php echo esc_url( tb_url( '/cabinet/add-listing/' ) ); ?>" class="tb-btn tb-btn--primary">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" fill="currentColor"/></svg>
					<?php echo esc_html( $lbl_add_listing ); ?>
				</a>
			</div>
		<?php endif; ?>

	<?php elseif ( 'profile' === $tab ) : ?>

		<div class="tb-cabinet__tab-body">
			<?php get_template_part( 'template-parts/dashboard/tab-profile' ); ?>
		</div>

	<?php elseif ( 'settings' === $tab ) : ?>

		<div class="tb-cabinet__tab-body">
			<?php get_template_part( 'template-parts/dashboard/tab-settings' ); ?>
		</div>

	<?php endif; ?>

</div><!-- .tb-container -->
</main>

<?php get_footer(); ?>

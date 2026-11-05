<?php
/**
 * template-parts/front-admin/tab-adverts.php
 * All car_listings — filterable by status, searchable, paginated.
 * Two-tier rows (body + bottom bar) mirroring the dashboard moderation card (Figma).
 * Loaded via require from page-front-admin.php (shares its variable scope).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── URL params ─────────────────────────────────────────────────
$status_filter    = sanitize_key( wp_unslash( $_GET['status'] ?? 'all' ) );
// NOTE: "fa_s" (not "s") — "s" is WordPress's reserved core search query var and
// colliding with it on a custom page causes canonical-redirect/404 issues.
$search_q         = sanitize_text_field( wp_unslash( $_GET['fa_s'] ?? '' ) );
$sort_filter      = sanitize_key( wp_unslash( $_GET['sort'] ?? 'date_desc' ) );
$paged            = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) );
$per_page         = 20;

$allowed_statuses = [ 'all', 'publish', 'pending', 'rejected', 'archived', 'draft' ];
if ( ! in_array( $status_filter, $allowed_statuses, true ) ) {
	$status_filter = 'all';
}

$allowed_sorts = [ 'date_desc', 'date_asc', 'price_desc', 'price_asc' ];
if ( ! in_array( $sort_filter, $allowed_sorts, true ) ) {
	$sort_filter = 'date_desc';
}

// ── Stats (for heading subtitle) ───────────────────────────────
// Counted via WP_Query so the subtitle can't disagree with the list below it.
$cnt_all     = tb_listing_count( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ] );
$cnt_pub     = tb_listing_count( [ 'publish' ] );
$cnt_pend    = tb_listing_count( [ 'pending' ] );
$cnt_arch    = tb_listing_count( [ 'archived' ] );
$cnt_rej     = tb_listing_count( [ 'rejected' ] );

// ── Query ──────────────────────────────────────────────────────
$post_statuses = 'all' === $status_filter
	? [ 'publish', 'pending', 'draft', 'rejected', 'archived' ]
	: [ $status_filter ];

$query_args = [
	'post_type'      => 'car_listing',
	'post_status'    => $post_statuses,
	'posts_per_page' => $per_page,
	'paged'          => $paged,
];
switch ( $sort_filter ) {
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
	default: // date_desc
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
}
if ( $search_q ) {
	$query_args['s'] = $search_q;
}
$q = new WP_Query( $query_args );

// ── URL helpers ────────────────────────────────────────────────
// $panel_url is in scope from parent page-front-admin.php.
$list_base  = add_query_arg( 'tab', 'listings', remove_query_arg( [ 'edit', 'paged' ], $panel_url ) );
$pill_url   = static function ( string $st ) use ( $list_base, $sort_filter ): string {
	$url = add_query_arg( 'status', $st, remove_query_arg( [ 'paged', 'fa_s' ], $list_base ) );
	if ( 'date_desc' !== $sort_filter ) {
		$url = add_query_arg( 'sort', $sort_filter, $url );
	}
	return esc_url( $url );
};
$edit_url   = static fn( int $id ): string => esc_url( add_query_arg( 'edit', $id, $list_base ) );

$sort_base = $list_base;
if ( 'all' !== $status_filter ) {
	$sort_base = add_query_arg( 'status', $status_filter, $sort_base );
}
if ( $search_q ) {
	$sort_base = add_query_arg( 'fa_s', $search_q, $sort_base );
}
$sort_url = static fn( string $sk ): string => esc_url( add_query_arg( 'sort', $sk, remove_query_arg( 'paged', $sort_base ) ) );

// ── Status badge config ────────────────────────────────────────
$badge_cfg = [
	'publish'  => [ 'label' => tb_opt( 'tb_fa_ae_st_publish', 'Zverejnené' ),  'bg' => '#02f797' ],
	'pending'  => [ 'label' => tb_opt( 'tb_fa_ae_st_pending', 'V moderácii' ), 'bg' => '#f4d597' ],
	'rejected' => [ 'label' => tb_opt( 'tb_fa_ae_st_rejected', 'Zamietnuté' ), 'bg' => '#ffbdbd' ],
	'archived' => [ 'label' => tb_opt( 'tb_fa_ae_st_archived', 'Archivované' ),'bg' => '#e5e5e5' ],
	'draft'    => [ 'label' => tb_opt( 'tb_fa_ae_st_draft', 'Koncept' ),    'bg' => '#e5e5e5' ],
];
?>

<!-- ─── Page heading ──────────────────────────────────────────── -->
<div class="tb-fa-page-head">
	<h1 class="tb-fa-page-head__title"><?php echo esc_html( tb_opt( 'tb_fa_adv_heading', 'Inzeráty' ) ); ?></h1>
	<p class="tb-fa-page-head__stats">
		<?php
		printf(
			/* translators: %1$d total %2$d published %3$d pending %4$d archived %5$d rejected */
			esc_html( tb_opt( 'tb_fa_adv_stats', 'Celkom %1$d, zverejnených %2$d, v moderácii %3$d, archivovaných %4$d, zamietnutých %5$d' ) ),
			$cnt_all, $cnt_pub, $cnt_pend, $cnt_arch, $cnt_rej
		);
		?>
	</p>
</div>

<!-- ─── Toolbar: pills + search ──────────────────────────────── -->
<div class="tb-fa-toolbar">
	<?php
	$pill_items = [
		'all'      => tb_opt( 'tb_fa_adv_all', 'Všetky' ),
		'publish'  => tb_opt( 'tb_fa_adv_published', 'Zverejnené' ),
		'pending'  => tb_opt( 'tb_fa_adv_pending', 'V moderácii' ),
		'rejected' => tb_opt( 'tb_fa_adv_rejected', 'Zamietnuté' ),
		'archived' => tb_opt( 'tb_fa_adv_archived', 'Archivované' ),
	];
	?>
	<!-- Status pills (desktop) -->
	<div class="tb-fa-pills">
		<?php
		foreach ( $pill_items as $key => $label ) :
			$is_active = $status_filter === $key;
			?>
			<a
				href="<?php echo $pill_url( $key ); ?>"
				class="tb-fa-pill<?php echo $is_active ? ' is-active' : ''; ?>"
				<?php echo $is_active ? 'aria-current="true"' : ''; ?>
			>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<!-- Same filter as a dropdown for tablet/mobile (a pill carousel is
	     unusable at those widths). Option values are the pill URLs so both
	     controls stay driven by exactly one source of truth. The "all" entry
	     gets a fuller label because, unlike a pill in a row, the dropdown is
	     read on its own — Figma 265:23158 shows "Všetky inzeráty". -->
	<?php
	$select_labels          = $pill_items;
	$select_labels['all']   = tb_opt( 'tb_fa_adv_all_long', 'Všetky inzeráty' );
	?>
	<select
		class="tb-fa-pill-select tb-fa-status-select"
		data-fa-status-select
		aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_status_label', 'Stav inzerátu' ) ); ?>"
	>
		<?php foreach ( $select_labels as $key => $label ) : ?>
			<option value="<?php echo $pill_url( $key ); // already escaped ?>" <?php selected( $status_filter, $key ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>

	<!-- Search -->
	<div class="tb-fa-search-wrap">
		<!-- Collapsed search trigger — mobile only (Figma 265:23164) -->
		<button type="button" class="tb-fa-search-toggle" data-fa-search-toggle aria-expanded="false"
			aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_search_label', 'Hľadať' ) ); ?>">
			<svg class="tb-fa-search-toggle__open" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21L16.514 16.506M19 11C19 15.418 15.418 19 11 19C6.582 19 3 15.418 3 11C3 6.582 6.582 3 11 3C15.418 3 19 6.582 19 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			<svg class="tb-fa-search-toggle__close" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
		</button>

		<form
			method="get"
			action="<?php echo esc_url( $panel_url ); ?>"
			class="tb-fa-search-form"
			role="search"
		>
			<input type="hidden" name="tab" value="listings">
			<?php if ( 'all' !== $status_filter ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
			<?php endif; ?>
			<?php if ( 'date_desc' !== $sort_filter ) : ?>
				<input type="hidden" name="sort" value="<?php echo esc_attr( $sort_filter ); ?>">
			<?php endif; ?>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="tb-fa-search-icon"><path d="M21 21L16.514 16.506M19 11C19 15.418 15.418 19 11 19C6.582 19 3 15.418 3 11C3 6.582 6.582 3 11 3C15.418 3 19 6.582 19 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			<input
				type="search"
				name="fa_s"
				class="tb-fa-search-input"
				value="<?php echo esc_attr( $search_q ); ?>"
				placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_adv_search_ph', 'Hľadať inzeráty' ) ); ?>"
				aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_search_label', 'Hľadať' ) ); ?>"
			>
			<button type="submit" class="screen-reader-text"><?php echo esc_html( tb_opt( 'tb_fa_adv_search_btn', 'Hľadať' ) ); ?></button>
		</form>

		<?php if ( $search_q ) : ?>
			<a href="<?php echo esc_url( remove_query_arg( 'fa_s', add_query_arg( 'tab', 'listings', $panel_url ) ) ); ?>" class="tb-fa-search-clear" aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_search_reset', 'Vymazať hľadanie' ) ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</a>
		<?php endif; ?>

		<!-- Sort (Figma 214:5865) -->
		<div class="tb-fa-sort-drop">
			<button type="button" class="tb-fa-sort-btn" data-fa-sort-toggle aria-haspopup="true" aria-expanded="false" aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_sort_label', 'Zoradiť' ) ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
			<div class="tb-fa-sort-panel" role="menu">
				<?php
				$sort_items = [
					'date_desc'  => tb_opt( 'tb_fa_adv_sort_date_desc', 'Najnovšie' ),
					'date_asc'   => tb_opt( 'tb_fa_adv_sort_date_asc', 'Najstaršie' ),
					'price_asc'  => tb_opt( 'tb_fa_adv_sort_price_asc', 'Cena: od najnižšej' ),
					'price_desc' => tb_opt( 'tb_fa_adv_sort_price_desc', 'Cena: od najvyššej' ),
				];
				foreach ( $sort_items as $sk => $sl ) :
					$s_active = $sort_filter === $sk;
					?>
					<a
						href="<?php echo $sort_url( $sk ); ?>"
						class="tb-fa-sort-option<?php echo $s_active ? ' is-active' : ''; ?>"
						role="menuitemradio"
						aria-checked="<?php echo $s_active ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $sl ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<div id="tb-fa-listings-results" data-fa-list="listings">
	<?php require __DIR__ . '/partial-adverts-results.php'; ?>
</div>
